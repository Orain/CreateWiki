<?php
/**
 * Copyright (C) 2013 Orain, Kudu, Southparkfan and contributors
 *
 * This file is part of CreateWiki.
 *
 * CreateWiki is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * CreateWiki is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with CreateWiki. If not, see <http://www.gnu.org/licenses/>.
 */

class SpecialCreateWiki extends SpecialPage {

	public function __construct() {
		parent::__construct( 'CreateWiki', 'createwiki' );
	}

	public function execute( $par ) {
		$this->checkPermissions();
		$this->setHeaders();

		$form = new HTMLForm( array(
				'dbname' => array(
					'default' => $par, // e.g. Special:CreateWiki/enwiki
					'filter-callback' => array( 'SpecialCreateWiki', 'filter' ),
					'label-message' => 'createwiki-label-dbname',
					'maxlength' => 30,
					'required' => true,
					'size' => 30,
					'type' => 'text',
					'validation-callback' => array( 'SpecialCreateWiki', 'validateDBname' ),
				),
				'founder' => array(
					'filter-callback' => array( 'SpecialCreateWiki', 'filter' ),
					'label-message' => 'createwiki-label-founder',
					'required' => true,
					'size' => 30,
					'type' => 'text',
					'validation-callback' => array( 'SpecialCreateWiki', 'validateFounder' ),
				),
				'comment' => array(
					'label-message' => 'createwiki-label-comment',
					'maxlength' => 79,
					'size' => 79,
					'type' => 'text',
			 	),
			)
		);
		$form->setSubmitTextMsg( 'createwiki-label-create' );
		$form->setTitle( $this->getPageTitle() );
		$form->setSubmitCallback( array( 'SpecialCreateWiki', 'processInput' ) );
		$form->show();
	}

	public static function filter( $string, $allData ) {
		return trim( $string );
	}

	public static function validateDBname( $DBname, $allData ) {
		global $wgConf;

		$suffixed = false;
		foreach ( $wgConf->suffixes as $suffix ) {
			if ( substr( $DBname, -strlen( $suffix ) ) === $suffix ) {
				$suffixed = true;
				break;
			}
		}

		if ( !$suffixed ) {
			return wfMessage( 'createwiki-error-notsuffixed' )->plain();
		}

		if ( !ctype_alnum( $DBname ) ) {
			return wfMessage( 'createwiki-error-notalnum' )->plain();
		}

		if ( strtolower( $DBname ) != $DBname ) {
			return wfMessage( 'createwiki-label-dbnamecontainsuppercase' )->plain();
		}

		return true;
	}

	public static function validateFounder( $founderName, $allData ) {
		$user = User::newFromName( $founderName );
		if ( !$user->getId() ) {
			return wfMessage( 'createwiki-error-nonexistentfounder' )->plain();
		}
		return true;
	}

	/**
	 * @param array $formData
	 * @param HtmlForm $form
	 *
	 * @return bool|string
	 * @throws DBUnexpectedError
	 * @throws Exception
	 * @throws MWException
	 */
	public static function processInput( array $formData, HtmlForm $form ) {
		error_reporting( 0 );
		global $wgCreateWikiSQLfiles, $IP;
		$DBname = $formData['dbname'];
		$founderName = $formData['founder'];
		$dbw = wfGetDB( DB_MASTER );

		$dbTest = $dbw->query( 'SHOW DATABASES LIKE ' . $dbw->addQuotes( $DBname ) . ';' );
		$rows = $dbTest->numRows();
		$dbTest->free();
		if ( $rows !== 0 ) {
			return wfMessage( 'createwiki-error-dbexists' )->plain();
		}

		$farmerLogEntry = new ManualLogEntry( 'farmer', 'createandpromote' );
		$farmerLogEntry->setPerformer( $form->getUser() );
		$farmerLogEntry->setTarget( $form->getTitle() );
		$farmerLogEntry->setComment( $formData['comment'] );
		$farmerLogEntry->setParameters( array(
				'4::wiki' => $DBname,
				'5::founder' => $founderName,
			)
		);
		$farmerLogID = $farmerLogEntry->insert();
		$farmerLogEntry->publish( $farmerLogID );

		$dbw->query( 'SET storage_engine=InnoDB;' );
		$dbw->query( 'CREATE DATABASE ' . $dbw->addIdentifierQuotes( $DBname ) . ';' );
		$dbw->selectDB( $DBname );

		foreach ( $wgCreateWikiSQLfiles as $file ) {
			$dbw->sourceFile( $file );
		}

		$dbw->insert( 'site_stats', array( 'ss_row_id' => 1 ) );
		$dbw->close();

		// Add DNS record to cloudflare
		global $wgCreateWikiUseCloudFlare, $wgCloudFlareUser,$wgCloudFlareKey;
		if( $wgCreateWikiUseCloudFlare ) {
			$cloudFlare = new cloudflare_api( $wgCloudFlareUser, $wgCloudFlareKey );
			$cloudFlare->rec_new(
				'orain.org',
				'CNAME',
				substr( $DBname, 0, -4 ),
				'lb.orain.org'
			);
		}

		// Create local account for founder (hack)
		$out = exec( "php5 $IP/extensions/CentralAuth/maintenance/createLocalAccount.php " . escapeshellarg( $founderName ) . ' --wiki ' . escapeshellarg( $DBname ) );
		if ( !strpos( $out, 'created' ) ) {
			return wfMessage( 'createwiki-error-usernotcreated' )->plain();
		}

		require_once( "$IP/includes/UserRightsProxy.php" );
		// Grant founder sysop and bureaucrat rights
		$founderUser = UserRightsProxy::newFromName( $DBname, $founderName );
		$newGroups = array( 'sysop', 'bureaucrat' );
		array_map( array( $founderUser, 'addGroup' ), $newGroups );
		$founderUser->invalidateCache();

		$form->getOutput()->addWikiMsg( 'createwiki-success', $DBname );
		return true;
	}

}
