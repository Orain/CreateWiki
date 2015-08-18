<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
	'author' => 'Southparkfan & Kudu',
	'descriptionmsg' => 'createwiki-desc',
	'name' => 'CreateWiki',
	'path' => __FILE__,
	'url' => '//github.com/Orain/CreateWiki'
);

$wgAutoloadClasses['SpecialCreateWiki'] = __DIR__ . '/SpecialCreateWiki.php';
$wgAutoloadClasses['CreateWikiHooks'] = __DIR__ . '/CreateWiki.hooks.php';
$wgAutoloadClasses['RequestWikiQueuePager'] = __DIR__ . '/RequestWikiQueuePager.php';
$wgAutoloadClasses['SpecialRequestWiki'] = __DIR__ . '/SpecialRequestWiki.php';
$wgAutoloadClasses['SpecialRequestWikiQueue'] = __DIR__ . '/SpecialRequestWikiQueue.php';

$wgExtensionMessagesFiles['CreateWiki'] = dirname( __FILE__ ) . '/CreateWiki.i18n.php';
$wgMessagesDirs['CreateWiki'] = __DIR__ . '/i18n';

$wgExtensionMessagesFiles['CreateWikiAlias'] = __DIR__ . '/CreateWiki.alias.php';

$wgSpecialPages['CreateWiki'] = 'SpecialCreateWiki';
$wgSpecialPages['RequestWiki'] = 'SpecialRequestWiki';
$wgSpecialPages['RequestWikiQueue'] = 'SpecialRequestWikiQueue';

$wgHooks['LoadExtensionSchemaUpdates'][] = 'CreateWikiHooks::fnCreateWikiSchemaUpdates';

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
	"$IP/extensions/CheckUser/cu_log.sql",
	"$IP/extensions/CheckUser/cu_changes.sql",
);
