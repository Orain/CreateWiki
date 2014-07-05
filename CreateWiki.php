<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
	'author' => 'Kudu',
	'descriptionmsg' => 'createwiki-desc',
	'name' => 'CreateWiki',
	'path' => __FILE__,
	'url' => '//github.com/Orain/CreateWiki'
);

$wgAutoloadClasses['SpecialCreateWiki'] = dirname( __FILE__ ) . '/SpecialCreateWiki.php';
$wgExtensionMessagesFiles['CreateWiki'] = dirname( __FILE__ ) . '/CreateWiki.i18n.php';
$wgMessagesDirs['CreateWiki'] = dirname( __FILE__ ) . '/i18n';
$wgExtensionMessagesFiles['CreateWikiAlias'] = dirname( __FILE__ ) . '/CreateWiki.alias.php';
$wgSpecialPages['CreateWiki'] = 'SpecialCreateWiki';

$wgAvailableRights[] = 'createwiki';
$wgLogTypes[] = 'farmer';
$wgLogActionsHandlers['farmer/*'] = 'LogFormatter';

/**
 * SQL files to be sourced into the created databases.
 */

$wgCreateWikiSQLfiles = array(
	"$IP/maintenance/tables.sql",
	"$IP/extensions/AbuseFilter/abusefilter.tables.sql",
	"$IP/extensions/AntiSpoof/sql/patch-antispoof.mysql.sql",
	"$IP/extensions/CentralNotice/CentralNotice.sql",
	"$IP/extensions/CheckUser/cu_changes.sql",
	"$IP/extensions/CheckUser/cu_log.sql",
	"$IP/extensions/CreateWiki/global_block_whitelist.sql",
	"$IP/extensions/TitleKey/titlekey.sql"
);
