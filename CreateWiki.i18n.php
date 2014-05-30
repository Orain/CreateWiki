<?php
/**
 * Internationalisation file for the CreateWiki extension
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Kudu
 */
$messages['en'] = array(
	'action-createwiki'	       => 'create a wiki', 	
	'createwiki'                   => 'Create a wiki',
	'createwiki-desc'              => 'Create a new wiki on a wiki farm',
	'createwiki-label-comment'     => 'Comment',
	'createwiki-label-create'      => 'Create',
	'createwiki-label-dbname'      => 'Database name',
	'createwiki-error-dbexists'    => 'The database already exists.',
	'createwiki-error-notsuffixed' => 'The database name must end in a configured suffix.',
	'createwiki-error-notalnum'    => 'The database name must be alphanumeric.',
	'createwiki-success'           => '$1 was successfully created.',
	'log-description-farmer'       => 'This is a log of changes made to the wiki farm.',
	'log-name-farmer'              => 'Wiki farm log',
	'logentry-farmer-create'       => '$1 created the wiki "$4"',
);

/** Korean
 * @author Revi
 */
$messages['ko'] = array(
	'createwiki'                   => '위키 만들기',
	'createwiki-desc'              => '위키 팜에 새로운 위키 만들기',
	'createwiki-label-comment'     => '덧글',
	'createwiki-label-create'      => '생성',
	'createwiki-label-dbname'      => '데이터베이스 이름',
	'createwiki-error-dbexists'    => '데이터베이스가 이미 존재합니다.',
	'createwiki-error-notsuffixed' => '데이터베이스 이름은 미리 설정된 접미사로 끝나야 합니다.',
	'createwiki-error-notalnum'    => '데이터베이스 이름은 알파벳 혹은 숫자여야 합니다.',
	'createwiki-success'           => '$1 위키를 성공적으로 생성하였습니다.',
	'log-description-farmer'       => '위키 팜에 생긴 변화에 대한 기록입니다.',
	'log-name-farmer'              => '위키 팜 기록',
	'logentry-farmer-create'       => '$1 사용자가 위키 "$4" 를 생성하였습니다.',
);
/** Dutch
 * @author Southparkfan
 */
$messages['nl'] = array(
	'action-createwiki'	       => 'maak een wiki aan',	
	'createwiki'                   => 'Maak een wiki aan',
	'createwiki-desc'              => 'Maak een nieuwe wiki aan in een wikigroep',
	'createwiki-label-comment'     => 'Opmerking',
	'createwiki-label-create'      => 'Maak wiki aan',
	'createwiki-label-dbname'      => 'Databasenaam',
	'createwiki-error-dbexists'    => 'De database bestaat al.',
	'createwiki-error-notsuffixed' => 'De databasenaam moet eindigen op een aangepaste suffix.',
	'createwiki-error-notalnum'    => 'De databasenaam moet alfanumeriek zijn.',
	'createwiki-success'           => '$1 is succesvol aangemaakt.',
	'log-description-farmer'       => 'Dit is een logboek dat veranderingen gemaakt aan de wikigroep.',
	'log-name-farmer'              => 'Logboek nieuwe wiki\'s',
	'logentry-farmer-create'       => '$1 maakte de wiki "$4" aan',
);

/** Message documentation (Message documentation)
 * @author Kudu and Southparkfan
 */
$messages['qqq'] = array(
	'action-createwiki'            => 'Text will be shown when trying to create a wiki without having the needed rights. Will be shown with MediaWiki:permissionserrorstext-withaction.',  
	'createwiki'                   => 'The title of Special:CreateWiki.',
	'createwiki-desc'              => 'The description of the extension.',
	'createwiki-label-comment'     => 'The label for the comment field.',
	'createwiki-label-create'      => 'The label for the Create button.',
	'createwiki-label-dbname'      => 'The label for the database name field.',
	'createwiki-error-dbexists'    => 'The error message displayed when the database already exists.',
	'createwiki-error-notsuffixed' => 'The error message displayed when the database name doesn\'t end in one of the configured suffixes.',
	'createwiki-error-notalnum'    => 'The error message displayed when the database name isn\'t alphanumeric.',
	'createwiki-success'           => 'The message displayed when the database was successfully created. Parameters:
* $1 = database name',
	'log-description-farmer'       => 'The description of the wiki farm log.',
	'log-name-farmer'              => 'The name of the wiki farm log.',
	'logentry-farmer-create'       => 'The format of the log entry for wiki creation. Parameters:
* $1 = user who created the wiki
* $4 = database name',
);
