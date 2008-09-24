<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

// Register OpenID authentication service with TYPO3
t3lib_extMgm::addService($_EXTKEY, 'auth' /* sv type */,  'tx_openid_sv1' /* sv key */,
		array(
			'title' => 'OpenID Authentication',
			'description' => 'OpenID authentication service for Frontend and Backend',
			'subtype' => 'getUserFE,authUserFE,getUserBE,authUserBE',
			'available' => true,
			'priority' => 75, // Must be higher than for tx_sv_auth (50) or tx_sv_auth will deny request unconditionally
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_openid_sv1.php',
			'className' => 'tx_openid_sv1',
		)
	);

?>