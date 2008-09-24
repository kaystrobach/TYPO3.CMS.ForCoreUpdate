<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Prepare new columns for be_users table
$tempColumns = array (
	'tx_openidauth_openid' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:openidauth/locallang_db.xml:be_users.tx_openidauth_openid',
		'config' => array (
			'type' => 'input',
			'size' => '30',
			'eval' => 'trim,nospace',
		)
	),
);

// Add new columns to be_users table
t3lib_div::loadTCA('be_users');
t3lib_extMgm::addTCAcolumns('be_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('be_users','tx_openidauth_openid;;;;1-1-1');

// Prepare new columns for fe_users table
$tempColumns = array (
	'tx_openidauth_openid' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:openidauth/locallang_db.xml:fe_users.tx_openidauth_openid',
		'config' => array (
			'type' => 'input',
			'size' => '30',
			'eval' => 'trim,nospace',
		)
	),
);

// Add new columns to fe_users table
t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users', $tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_users', 'tx_openidauth_openid;;;;1-1-1');

?>