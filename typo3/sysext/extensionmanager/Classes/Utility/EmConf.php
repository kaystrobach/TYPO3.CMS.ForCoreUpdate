<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog <susanne.moog@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Utility for dealing with ext_emconf
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Utility_EmConf implements t3lib_Singleton {

	/**
	 * Returns the $EM_CONF array from an extensions ext_emconf.php file
	 *
	 * @param array $extension Extension information array
	 * @return array EMconf array values.
	 */
	public function includeEmConf(array $extension) {
		$_EXTKEY = $extension['key'];
		$path = PATH_site . $extension['siteRelPath'] . '/ext_emconf.php';
		$EM_CONF = NULL;

		if(file_exists($path)) {
			include($path);
			if (is_array($EM_CONF[$_EXTKEY])) {
				return $EM_CONF[$_EXTKEY];
			}
		}
		return FALSE;
	}


	/**
	 * Generates the content for the ext_emconf.php file
	 *
	 * @internal
	 * @param array $extensionData
	 * @return string
	 */
	public function constructEmConf(array $extensionData) {
		$emConf = var_export($extensionData['EM_CONF'], TRUE);
		$code = '<?php

/***************************************************************
* Extension Manager/Repository config file for ext "' . $extensionData['extKey'] . '".
*
* Auto generated ' . date('d-m-Y H:i') . '
*
* Manual updates:
* Only the data in the array - everything else is removed by next
* writing. "version" and "dependencies" must not be touched!
***************************************************************/

$EM_CONF[$_EXTKEY] = ' . $emConf. ';

?>';
		return str_replace('  ', TAB, $code);
	}

}
?>
