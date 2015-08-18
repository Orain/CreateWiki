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
 */
class SpecialCreateWiki extends SpecialPage {

	public function __construct() {
		parent::__construct( 'CreateWiki', 'createwiki' );
	}

	public function execute( $par ) {
		efCreateWikiExceptionOnBadConfig();

		$request = $this->getRequest();
		$out = $this->getOutput();
		$this->setHeaders();

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

		if ( !$this->getUser()->matchEditToken( $request->getVal( 'cwToken' ) ) ) {
			$out->addWikiMsg( 'createwiki-error-csrf' );

			return false;
		}

		$validation = $this->validateInput( $DBname, $founder );

		if ( !$validation ) {
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

		$dbw->query( 'SET storage_engine=InnoDB;' );
		$dbw->query( 'CREATE DATABASE ' . $dbw->addIdentifierQuotes( $DBname ) . ';' );
		$dbw->selectDB( $DBname );

		foreach ( $wgCreateWikiSQLfiles as $sqlfile ) {
			$dbw->sourceFile( $sqlfile );
		}

		$this->writeToDBlist( $DBname, $sitename, $language, $private );

		$this->addCloudFlareRecordIfEnabled( $DBname );

		$shcreateaccount =
			exec(
				"/usr/bin/php $IP/extensions/CentralAuth/maintenance/createLocalAccount.php " .
				wfEscapeShellArg( $founder ) .
				' --wiki ' .
				wfEscapeShellArg( $DBname )
			);
		if ( !strpos( $shcreateaccount, 'created' ) ) {
			wfDebugLog(
				'CreateWiki',
				'Failed to create local account for founder. - error: ' . $shcreateaccount
			);

			$out->addHTML(
				'<div class="errorbox">' .
				$this->msg( 'createwiki-error-usernotcreated' )->escaped() .
				'</div>'
			);

			return false;
		}

		$shpromoteaccount =
			exec(
				"/usr/bin/php $IP/maintenance/createAndPromote.php " .
				wfEscapeShellArg( $founder ) .
				' --bureaucrat --sysop --force --wiki ' .
				wfEscapeShellArg( $DBname )
			);
		if ( !strpos( $shpromoteaccount, 'done.' ) ) {
			wfDebugLog(
				'CreateWiki',
				'Failed to promote local account for founder. - error: ' . $shpromoteaccount
			);

			$out->addHTML(
				'<div class="errorbox">' .
				$this->msg( 'createwiki-error-usernotpromoted' )->escaped() .
				'</div>'
			);

			return false;
		}

		$this->createMainPage( $language );

		// Grant founder sysop and bureaucrat rights
		$founderUser =
			UserRightsProxy::newFromName( $DBname, User::newFromName( $founder )->getName() );
		$newGroups = array( 'sysop', 'bureaucrat' );
		array_map( array( $founderUser, 'addGroup' ), $newGroups );

		$out->addHTML(
			'<div class="successbox">' . $this->msg( 'createwiki-success' )->escaped() . '</div>'
		);

		return true;
	}

	public function validateInput( $DBname, $founder ) {
		$out = $this->getOutput();

		$user = User::newFromName( $founder );
		if ( !$user->getId() ) {
			$out->addHTML(
				'<div class="errorbox">' .
				$this->msg( 'createwiki-error-foundernonexistent' )->escaped() .
				'</div>'
			);

			return false;
		}

		if ( !$this->validateDBname( $DBname ) ) {
			return false;
		}

		return true;
	}

	public function validateDBname( $DBname ) {
		global $wgConf;
		$out = $this->getOutput();

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
			$out->addHTML(
				'<div class="errorbox">' .
				$this->msg( 'createwiki-error-dbexists' )->escaped() .
				'</div>'
			);

			return false;
		}

		if ( !$suffixed ) {
			$out->addHTML(
				'<div class="errorbox">' .
				$this->msg( 'createwiki-error-notsuffixed' )->escaped() .
				'</div>'
			);

			return false;
		}

		if ( !ctype_alnum( $DBname ) ) {
			$out->addHTML(
				'<div class="errorbox">' .
				$this->msg( 'createwiki-error-notalnum' )->escaped() .
				'</div>'
			);

			return false;
		}

		if ( strtolower( $DBname ) !== $DBname ) {
			$out->addHTML(
				'<div class="errorbox">' .
				$this->msg( 'createwiki-error-notlowercase' )->escaped() .
				'</div>'
			);

			return false;
		}

		return true;
	}

	public function writeToDBlist( $DBname, $sitename, $language, $private ) {
		global $IP;
		global $wgCreateWikiPublicDbListLocation, $wgCreateWikiPrivateDbListLocation;

		$dbline = "$DBname|$sitename|$language|\n";
		file_put_contents( $wgCreateWikiPublicDbListLocation, $dbline, FILE_APPEND | LOCK_EX );

		if ( $private !== 0 ) {
			file_put_contents(
				$wgCreateWikiPrivateDbListLocation,
				"$DBname\n",
				FILE_APPEND | LOCK_EX
			);
		}

		return true;
	}

	public function createMainPage( $lang ) {
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
	}

	/**
	 * @param string $DBname
	 */
	public function addCloudFlareRecordIfEnabled( $DBname ) {
		global $wgCreateWikiUseCloudFlare, $wgCloudFlareUser, $wgCloudFlareKey, $wgCreateWikiBaseDomain;

		if ( $wgCreateWikiUseCloudFlare ) {
			$domainPrefix = substr( $DBname, 0, -4 );
			$cloudFlare = new cloudflare_api( $wgCloudFlareUser, $wgCloudFlareKey );
			$cloudFlareResult = $cloudFlare->rec_new(
				$wgCreateWikiBaseDomain,
				'CNAME',
				$domainPrefix,
				'lb.' . $wgCreateWikiBaseDomain
			);
			if( !is_object( $cloudFlareResult ) || $cloudFlareResult->result !== 'success' ) {
				wfDebugLog( 'CreateWiki', 'CloudFlare FAILED to add CNAME for ' . $domainPrefix );
			} else {
				wfDebugLog( 'CreateWiki', 'CloudFlare CNAME added for ' . $domainPrefix );
			}
		} else {
			wfDebugLog( 'CreateWiki', 'CloudFlare is not enabled.' );
		}
	}
}
