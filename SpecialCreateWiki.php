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

/**
 * @author Kudu
 * @author Southparkfan
 * @author JohnFLewis
 * @author Addshore
 */
class SpecialCreateWiki extends SpecialPage {

	public function __construct() {
		parent::__construct( 'CreateWiki', 'createwiki' );
	}

	public function execute( $par ) {
		efCreateWikiExceptionOnBadConfig();

		$request = $this->getRequest();
		$this->setHeaders();
		$this->checkPermissions();

		$this->showInputForm();

		if ( $request->wasPosted() ) {
			$this->handleInput();
		}
	}

	public function showInputForm() {
		$localpage = $this->getPageTitle()->getLocalUrl();
		$request = $this->getRequest();
		$language = $request->getVal( 'cwLanguage' ) ? $request->getVal( 'cwLanguage' ) : 'en';
		$privateboxchecked = $request->getVal( 'cwPrivate' );

		$form = Xml::openElement( 'form', array( 'action' => $localpage, 'method' => 'post' ) );
		$form .= '<fieldset><legend>' . $this->msg( 'createwiki' )->escaped() . '</legend>';
		$form .= Xml::openElement( 'table' );
		$form .= '<tr><td>' . $this->msg( 'createwiki-label-dbname' )->escaped() . '</td>';
		$form .= '<td>' .
			Xml::input(
				'cwDBname',
				20,
				$request->getVal( 'cwDBname' ),
				array( 'required' => '' )
			) .
			'</td></tr>';
		$form .= '<tr><td>' . $this->msg( 'createwiki-label-founder' )->escaped() . '</td>';
		$form .= '<td>' .
			Xml::input(
				'cwFounder',
				20,
				$request->getVal( 'cwFounder' ),
				array( 'required' => '' )
			) .
			'</td></tr>';
		$form .= '<tr><td>' . $this->msg( 'createwiki-label-sitename' )->escaped() . '</td>';
		$form .= '<td>' .
			Xml::input(
				'cwSitename',
				20,
				$request->getVal( 'cwSitename' ),
				array( 'required' => '' )
			) .
			'</td></tr>';
		$form .= '<tr><td>' . $this->msg( 'createwiki-label-language' )->escaped() . '</td>';
		$form .= '<td>' .
			Xml::languageSelector( $language, true, null, array( 'name' => 'cwLanguage' ) )[1] .
			'</td></tr>';
		$form .= '<tr><td>' . $this->msg( 'createwiki-label-private' )->escaped() . '</td>';
		$form .= '<td>' .
			Xml::check( 'cwPrivate', $privateboxchecked, array( 'value' => 0 ) ) .
			'</td></tr>';
		$form .= '<tr><td>' . $this->msg( 'createwiki-label-reason' )->escaped() . '</td>';
		$form .= '<td>' .
			Xml::input(
				'cwReason',
				45,
				$request->getVal( 'cwReason' ),
				array( 'required' => '' )
			) .
			'</td></tr>';
		$form .= '<tr><td>' .
			Xml::submitButton( $this->msg( 'createwiki-label-submit' )->plain() ) .
			'</td></tr>';
		$form .= Xml::closeElement( 'table' );
		$form .= '</fieldset>';
		$form .= Html::hidden( 'cwToken', $this->getUser()->getEditToken() );
		$form .= Xml::closeElement( 'form' );

		$this->getOutput()->addHTML( $form );
	}

	public function handleInput() {
		global $IP, $wgCreateWikiSQLfiles;

		$request = $this->getRequest();
		$out = $this->getOutput();

		$DBname = trim( $request->getVal( 'cwDBname' ) );
		$founder = trim( $request->getVal( 'cwFounder' ) );
		$sitename = trim( $request->getVal( 'cwSitename' ) );
		$reason = $request->getVal( 'cwReason' );
		$language = $request->getVal( 'cwLanguage' );
		$private = is_null( $request->getVal( 'cwPrivate' ) ) ? 0 : 1;

		$dbw = wfGetDB( DB_MASTER );

		if ( $dbw->getType() === 'sqlite' ) {
			throw new MWException( 'CreateWiki can not work with sqlite' );
		}

		if ( !$this->getUser()->matchEditToken( $request->getVal( 'cwToken' ) ) ) {
			$out->addWikiMsg( 'createwiki-error-csrf' );

			return false;
		}

		$validationStatus = $this->validateInput( $DBname, $founder );

		if ( !$validationStatus->isGood() ) {
			$this->addErrorBox( $validationStatus->getHTML() );
			return false;
		}

		$farmerLogEntry = new ManualLogEntry( 'farmer', 'createwiki' );
		$farmerLogEntry->setPerformer( $this->getUser() );
		$farmerLogEntry->setTarget( $this->getTitle() );
		$farmerLogEntry->setComment( $reason );
		$farmerLogEntry->setParameters(
			array(
				'4::wiki' => $DBname,
			)
		);
		$farmerLogID = $farmerLogEntry->insert();
		$farmerLogEntry->publish( $farmerLogID );

		try{
			$dbw->query( 'SET storage_engine=InnoDB;' );
			$dbw->query( 'CREATE DATABASE ' . $dbw->addIdentifierQuotes( $DBname ) . ';' );
		} catch( MWException $ex ) {
			//TODO i18n
			$this->addErrorBox( "Failed to create database: " . $DBname );
			return false;
		}

		$postCreationStatus = new Status();

		$dbw->selectDB( $DBname );

		foreach ( $wgCreateWikiSQLfiles as $key => $sqlfile ) {
			try {
				//TODO make sure the file exists first? Perhaps not be hard coded?
				$dbw->sourceFile( $sqlfile );
			} catch ( Exception $ex ) {
				//TODO i18n
				$postCreationStatus->merge(
					Status::newFatal( 'Failed to run SQL files on new db: ' . $key )
				);
			}
		}

		// Update a local dblist if one is set
		global $wgCreateWikiPublicDbListLocation;
		global $wgCreateWikiPrivateDbListLocation;
		if(
			is_string( $wgCreateWikiPublicDbListLocation ) ||
			is_string( $wgCreateWikiPrivateDbListLocation )
		) {
			$localDbListStatus = $this->writeToDBlist( $DBname, $sitename, $language, $private );
			$postCreationStatus->merge( $localDbListStatus );
		}

		// TODO Update onwiki dblist if one is set

		global $wgCreateWikiUseCloudFlare;
		if ( $wgCreateWikiUseCloudFlare ) {
			$cloudFlareStatus = $this->addCloudFlareRecord( $DBname );
			$postCreationStatus->merge( $cloudFlareStatus );
		}

		$shcreateaccount =
			exec(
				"/usr/bin/php $IP/extensions/CentralAuth/maintenance/createLocalAccount.php " .
				wfEscapeShellArg( $founder ) .
				' --wiki ' .
				wfEscapeShellArg( $DBname )
			);
		if ( !strpos( $shcreateaccount, 'created' ) ) {
			$postCreationStatus->merge(
				Status::newFatal( $this->msg( 'createwiki-error-usernotcreated' ) )
			);
		}

		$shpromoteaccount =
			exec(
				"/usr/bin/php $IP/maintenance/createAndPromote.php " .
				wfEscapeShellArg( $founder ) .
				' --bureaucrat --sysop --force --wiki ' .
				wfEscapeShellArg( $DBname )
			);
		if ( !strpos( $shpromoteaccount, 'done.' ) ) {
			$postCreationStatus->merge(
				Status::newFatal( $this->msg( 'createwiki-error-usernotpromoted' ) )
			);
		}

		$createMainPageStatus = $this->createMainPage( $language );
		$postCreationStatus->merge( $createMainPageStatus );

		// Grant founder sysop and bureaucrat rights
		try{
			$founderUser = UserRightsProxy::newFromName(
				$DBname,
				User::newFromName( $founder )->getName()
			);
			foreach( array( 'sysop', 'bureaucrat' ) as $group ) {
				$founderUser->addGroup( $group );
			}
		} catch( Exception $ex ) {
			$postCreationStatus->merge(
				//TODO i18n
				Status::newFatal( 'Failed to add groups to founding user' )
			);
		}

		if ( !$validationStatus->isGood() ) {
			$this->addErrorBox( $validationStatus->getHTML() );
			return false;
		}

		$out->addHTML(
			'<div class="successbox">' . $this->msg( 'createwiki-success' )->escaped() . '</div>'
		);

		//TODO i18n
		$manualDbLine = "<p>If you need to add the dbline manually to a wikipage please use the following</p>";
		$manualDbLine .= "<p>" . $this->getDbLine( $DBname, $sitename, $language, $private ) . "</p>";
		$out->addHTML(
			'<div class="successbox">' . $manualDbLine . '</div>'
		);

		return true;
	}

	/**
	 * @param string $DBname
	 * @param string $founder
	 *
	 * @return Status
	 */
	public function validateInput( $DBname, $founder ) {
		$status = new Status();

		if ( !User::newFromName( $founder )->getId() ) {
			$status->merge( Status::newFatal( $this->msg( 'createwiki-error-foundernonexistent' ) ) );
		}

		$status->merge( $this->validateDBname( $DBname ) );

		return $status;
	}

	/**
	 * @param string $DBname
	 *
	 * @return Status
	 */
	public function validateDBname( $DBname ) {
		global $wgConf;

		$status = new Status();

		$suffixed = false;
		foreach ( $wgConf->suffixes as $suffix ) {
			if ( substr( $DBname, -strlen( $suffix ) ) === $suffix ) {
				$suffixed = true;
				break;
			}
		}

		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->query( 'SHOW DATABASES LIKE ' . $dbw->addQuotes( $DBname ) . ';' );

		if ( $res->numRows() !== 0 ) {
			$status->merge( Status::newFatal( $this->msg( 'createwiki-error-dbexists' ) ) );
		}

		if ( !$suffixed ) {
			$status->merge( Status::newFatal( $this->msg( 'createwiki-error-notsuffixed' ) ) );
		}

		if ( !ctype_alnum( $DBname ) ) {
			$status->merge( Status::newFatal( $this->msg( 'createwiki-error-notalnum' ) ) );
		}

		if ( strtolower( $DBname ) !== $DBname ) {
			$status->merge( Status::newFatal( $this->msg( 'createwiki-error-notlowercase' ) ) );
		}

		return $status;
	}

	/**
	 * @param string $content
	 */
	private function addErrorBox( $content ) {
		$this->getOutput()->addHTML( '<div class="errorbox">' . $content . '</div>');
	}

	/**
	 * @param $DBname
	 * @param $sitename
	 * @param $language
	 * @param $private
	 *
	 * @return Status
	 */
	public function writeToDBlist( $DBname, $sitename, $language, $private ) {
		global $wgCreateWikiPublicDbListLocation, $wgCreateWikiPrivateDbListLocation;

		$status = new Status();

		$dbline = $this->getDbLine( $DBname, $sitename, $language, $private ) . "\n";
		$writeSuccessPublic = file_put_contents(
				$wgCreateWikiPublicDbListLocation,
				$dbline,
				FILE_APPEND | LOCK_EX
			);

		if( $writeSuccessPublic === false ) {
			$status->merge( Status::newFatal( 'Failed to write to local public dblist' ) );
		}

		if ( $private !== 0 ) {
			$writeSuccessPrivate = file_put_contents(
				$wgCreateWikiPrivateDbListLocation,
				"$DBname\n",
				FILE_APPEND | LOCK_EX
			);

			if( $writeSuccessPrivate === false ) {
				$status->merge( Status::newFatal( 'Failed to write to local private dblist' ) );
			}
		}

		return $status;
	}

	/**
	 * @param string $DBname
	 * @param string $sitename
	 * @param string $language
	 * @param bool $private
	 *
	 * @return string
	 */
	private function getDbLine( $DBname, $sitename, $language, $private ) {
		$dbline = "$DBname|$sitename|$language|";
		if( $private ) {
			$dbline .= 'private|';
		}
		return $dbline;
	}

	/**
	 * @param string $lang
	 *
	 * @return Status
	 */
	public function createMainPage( $lang ) {
		try{
			// Don't use Meta's mainpage message!
			if ( $lang !== 'en' ) {
				$page = wfMessage( 'mainpage' )->inLanguage( $lang )->plain();
			} else {
				$page = 'Main_Page';
			}

			$title = Title::newFromText( $page );
			$article = WikiPage::factory( $title );

			$article->doEditContent(
				new WikitextContent(
					wfMessage( 'createwiki-defaultmainpage' )->inLanguage( $lang )->plain()
				),
				'Create main page',
				EDIT_NEW
			);
		} catch ( Exception $ex ) {
			//TODO i18n
			return Status::newFatal( 'Failed to create main page' );
		}
		return new Status();

	}

	/**
	 * @param string $DBname
	 *
	 * @return Status
	 */
	public function addCloudFlareRecord( $DBname ) {
		global $wgCloudFlareUser, $wgCloudFlareKey, $wgCreateWikiBaseDomain;

		$domainPrefix = substr( $DBname, 0, -4 );
		$cloudFlare = new cloudflare_api( $wgCloudFlareUser, $wgCloudFlareKey );
		$cloudFlareResult = $cloudFlare->rec_new(
			$wgCreateWikiBaseDomain,
			'CNAME',
			$domainPrefix,
			'lb.' . $wgCreateWikiBaseDomain
		);
		if( !is_object( $cloudFlareResult ) || $cloudFlareResult->result !== 'success' ) {
			//TODO i18n
			return Status::newFatal( 'CloudFlare FAILED to add CNAME for ' . $domainPrefix );
		} else {
			return new Status();
		}
	}
}
