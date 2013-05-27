<?php
/**
 * Created by JetBrains PhpStorm.
 * User: helmut
 * Date: 26.05.13
 * Time: 19:54
 * To change this template use File | Settings | File Templates.
 */

class t3lib_div extends \TYPO3\CMS\Core\Utility\GeneralUtility {


	static public function int_from_ver($verNumberStr) {
		return \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($verNumberStr);
	}
}