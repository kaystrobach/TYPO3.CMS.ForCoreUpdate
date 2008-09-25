<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addService($_EXTKEY,  'auth' /* sv type */,  'tx_rsaauth_sv1' /* sv key */,
		array(

			'title' => 'RSA Authentication',
			'description' => 'RSA Authentication for front-end and back-end',

			'subtype' => 'getUserBE,authUserBE,extendDocumentBE',

			'available' => TRUE,
			'priority' => 80,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_rsaauth_sv1.php',
			'className' => 'tx_rsaauth_sv1',
		)
	);
?>