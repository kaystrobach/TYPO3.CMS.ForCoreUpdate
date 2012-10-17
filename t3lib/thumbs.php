<?php
/*
 * @deprecated since 6.0, the classname SC_t3lib_thumbs and this file is obsolete
 * and will be removed by 7.0. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/View/ThumbnailView.php
 */
require_once t3lib_extMgm::extPath('backend') . 'Classes/View/ThumbnailView.php';
// Make instance:
$SOBE = t3lib_div::makeInstance('SC_t3lib_thumbs');
$SOBE->init();
$SOBE->main();
?>