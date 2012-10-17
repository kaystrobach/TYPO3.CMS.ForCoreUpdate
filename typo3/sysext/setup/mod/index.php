<?php
/*
 * @deprecated since 6.0, the classname SC_mod_user_setup_index and this file is obsolete
 * and will be removed by 7.0. The class was renamed and is now located at:
 * typo3/sysext/setup/Classes/Controller/SetupModuleController.php
 */
require_once t3lib_extMgm::extPath('setup') . 'Classes/Controller/SetupModuleController.php';
// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_user_setup_index');
$SOBE->simulateUser();
$SOBE->storeIncomingData();
// These includes MUST be afterwards the settings are saved...!
$LANG->includeLLFile('EXT:setup/mod/locallang.xml');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>