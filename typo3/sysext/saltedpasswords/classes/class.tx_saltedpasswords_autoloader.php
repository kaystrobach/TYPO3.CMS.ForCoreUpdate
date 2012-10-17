<?php
/*
 * @deprecated since 6.0, the classname tx_saltedpasswords_autoloader and this file is obsolete
 * and will be removed by 7.0. The class was renamed and is now located at:
 * typo3/sysext/saltedpasswords/Classes/Autoloader.php
 */
require_once t3lib_extMgm::extPath('saltedpasswords') . 'Classes/Autoloader.php';
/**
 * @var $SOBE tx_saltedpasswords_autoloader
 */
$SOBE = t3lib_div::makeInstance('tx_saltedpasswords_autoloader');
$SOBE->execute($this);
?>