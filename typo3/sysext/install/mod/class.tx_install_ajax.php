<?php
/*
 * @deprecated since 6.0, the classname tx_install_ajax and this file is obsolete
 * and will be removed by 7.0. The class was renamed and is now located at:
 * typo3/sysext/install/Classes/EidHandler.php
 */
require_once t3lib_extMgm::extPath('install') . 'Classes/EidHandler.php';
// Make instance:
$SOBE = t3lib_div::makeInstance('tx_install_ajax');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>