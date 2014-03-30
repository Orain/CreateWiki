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
	'createwiki'                          => 'Create a wiki',
	'createwiki-desc'                     => 'Create a new wiki on a wiki farm',
	'createwiki-label-comment'            => 'Comment',
	'createwiki-label-create'             => 'Create',
	'createwiki-label-dbname'             => 'Database name',
	'createwiki-label-founder'            => 'Founder',
	'createwiki-error-dbexists'           => 'The database already exists.',
	'createwiki-error-nonexistentfounder' => 'The founder does not exist on Orain Meta.',
	'createwiki-error-notsuffixed'        => 'The database name must end in a configured suffix.',
	'createwiki-error-notalnum'           => 'The database name must be alphanumeric.',
	'createwiki-error-usernotcreated'     => 'There was an error while creating the founder\'s user.',
	'createwiki-success'                  => '$1 was successfully created.',
	'log-description-farmer'              => 'This is a log of changes made to the wiki farm.',
	'log-name-farmer'                     => 'Wiki farm log',
	'logentry-farmer-create'              => '$1 created the wiki "$4"',
	'logentry-farmer-createandpromote'    => '$1 created the wiki "$4" with "$5" as founder',
);

/** Message documentation (Message documentation)
 * @author Kudu
 */
$messages['qqq'] = array(
	'createwiki'                          => 'The title of Special:CreateWiki.',
	'createwiki-desc'                     => 'The description of the extension.',
	'createwiki-label-comment'            => 'The label for the comment field.',
	'createwiki-label-create'             => 'The label for the Create button.',
	'createwiki-label-dbname'             => 'The label for the database name field.',
	'createwiki-label-founder'            => 'The label for the Founder field.',
	'createwiki-error-dbexists'           => 'The error message displayed when the database already exists.',
	'createwiki-error-nonexistentfounder' => 'The error message displayed when the founder does not exist on Orain Meta.',
	'createwiki-error-notsuffixed'        => 'The error message displayed when the database name doesn\'t end in one of the configured suffixes.',
	'createwiki-error-notalnum'           => 'The error message displayed when the database name isn\'t alphanumeric.',
	'createwiki-error-usernotcreated'     => 'The error message displayed when there was an error while creating the founder\'s user.',
	'createwiki-success'                  => 'The message displayed when the database was successfully created. Parameters:
* $1 = database name',
	'log-description-farmer'              => 'The description of the wiki farm log.',
	'log-name-farmer'                     => 'The name of the wiki farm log.',
	'logentry-farmer-create'              => 'The format of the log entry for wiki creation. Parameters:
* $1 = user who created the wiki
* $4 = database name',
	'logentry-farmer-createandpromote'    => 'The format of the log entry for wiki creation and founder promotion. Parameters:
* $1 = user who created the wiki
* $4 = database name
* $5 = founder',
);

