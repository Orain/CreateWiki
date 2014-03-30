<?php
/**
 * Copyright (C) 2013 Orain, Kudu and contributors
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
 * along with CreateWiki.  If not, see <http://www.gnu.org/licenses/>.
 */

class SpecialCreateWiki extends SpecialPage {
	function __construct() {
		parent::__construct( 'CreateWiki', 'createwiki' );
	}

	function execute( $par ) {
		$this->checkPermissions();
		$this->setHeaders();

		$form = new HTMLForm( array(
				'dbname' => array(
					'default' => $par, // e.g. Special:CreateWiki/enwiki
					'filter-callback' => array( 'SpecialCreateWiki', 'filterDBname' ),
					'label-message' => 'createwiki-label-dbname',
					'maxlength' => 30,
					'required' => true,
					'size' => 30,
					'type' => 'text',
					'validation-callback' => array( 'SpecialCreateWiki', 'validateDBname' ),
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
		$form->setTitle( $this->getTitle() );
		$form->setSubmitCallback( array( 'SpecialCreateWiki', 'processInput' ) );
		$form->show();
	}

	static function filterDBname( $DBname, $allData ) {
		return trim( $DBname );
	}

	static function validateDBname( $DBname, $allData ) {
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

		return true;
	}

	static function processInput( $formData, $form ) {
		global $wgCreateWikiSQLfiles;
		$DBname = $formData['dbname'];
		$dbw = wfGetDB( DB_MASTER );

		$dbTest = $dbw->query( 'SHOW DATABASES LIKE ' . $dbw->addQuotes( $DBname ) . ';' );
		$rows = $dbTest->numRows();
		$dbTest->free();
		if ( $rows !== 0 ) {
			return wfMessage( 'createwiki-error-dbexists' )->plain();
		}

		$logEntry = new ManualLogEntry( 'farmer', 'create' );
		$logEntry->setPerformer( $form->getUser() );
		$logEntry->setTarget( $form->getTitle() );
		$logEntry->setComment( $formData['comment'] );
		$logEntry->setParameters( array(
				'4::wiki' => $DBname,
			)
		);
		$logID = $logEntry->insert();
		$logEntry->publish( $logID );

		$dbw->query( 'SET storage_engine=InnoDB;' );
		$dbw->query( 'CREATE DATABASE ' . $dbw->addIdentifierQuotes( $DBname ) . ';' );
		$dbw->selectDB( $DBname );

		foreach ( $wgCreateWikiSQLfiles as $file ) {
			$dbw->sourceFile( $file );
		}

		$dbw->insert( 'site_stats', array( 'ss_row_id' => 1 ) );
		$dbw->close();

		$form->getOutput()->addWikiMsg( 'createwiki-success', $DBname );
		return true;
	}
}
