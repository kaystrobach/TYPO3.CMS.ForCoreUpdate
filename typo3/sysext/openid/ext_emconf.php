<?php

########################################################################
# Extension Manager/Repository config file for ext: "openid"
#
# Auto generated 24-09-2008 11:40
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'OpenID authentication',
	'description' => 'Adds OpenID authentication to TYPO3',
	'category' => 'services',
	'author' => 'Dmitry Dulepov',
	'author_email' => 'dmitry@typo3.org',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/tx_openid',
	'modify_tables' => 'fe_users,be_users',
	'clearCacheOnLoad' => 0,
	'lockType' => 'system',
	'author_company' => 'TYPO3 core team',
	'version' => '0.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.3-',
			'php' => '5.2.0-'
		),
		'conflicts' => array(
			'naw_openid' => '',
			'naw_openid_be' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:9:{s:9:"ChangeLog";s:4:"84d5";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"3612";s:14:"ext_tables.php";s:4:"14d7";s:14:"ext_tables.sql";s:4:"20df";s:16:"locallang_db.xml";s:4:"3e23";s:19:"doc/wizard_form.dat";s:4:"8108";s:20:"doc/wizard_form.html";s:4:"5b2a";s:31:"sv1/class.tx_openid_sv1.php";s:4:"4d40";}',
);

?>