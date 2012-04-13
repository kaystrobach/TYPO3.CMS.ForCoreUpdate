<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012
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
 * action controller.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Controller
 */
class Tx_Extensionmanager_Utility_EmConf implements t3lib_Singleton {

	/**
	 * Fixes an old style ext_emconf.php array by adding constraints if needed and removing deprecated keys
	 *
	 * @param	array		$emConf
	 * @return	array
	 */
	protected function fixEmConf($emConf) {
		if (!isset($emConf['constraints']) || !isset($emConf['constraints']['depends']) || !isset($emConf['constraints']['conflicts']) || !isset($emConf['constraints']['suggests'])) {
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['depends'])) {
				$emConf['constraints']['depends'] = $this->stringToDependency($emConf['dependencies']);
				if (strlen($emConf['PHP_version'])) {
					$versionRange = t3lib_utility_VersionNumber::splitVersionRange($emConf['PHP_version']);
					if (version_compare($versionRange[0], '3.0.0', '<')) {
						$versionRange[0] = '3.0.0';
					}
					if (version_compare($versionRange[1], '3.0.0', '<')) {
						$versionRange[1] = '0.0.0';
					}
					$emConf['constraints']['depends']['php'] = implode('-', $versionRange);
				}
				if (strlen($emConf['TYPO3_version'])) {
					$versionRange = t3lib_utility_VersionNumber::splitVersionRange($emConf['TYPO3_version']);
					if (version_compare($versionRange[0], '3.5.0', '<')) {
						$versionRange[0] = '3.5.0';
					}
					if (version_compare($versionRange[1], '3.5.0', '<')) {
						$versionRange[1] = '0.0.0';
					}
					$emConf['constraints']['depends']['typo3'] = implode('-', $versionRange);
				}
			}
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['conflicts'])) {
				$emConf['constraints']['conflicts'] = $this->stringToDependency($emConf['conflicts']);
			}
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['suggests'])) {
				$emConf['constraints']['suggests'] = array();
			}
		} elseif (isset($emConf['constraints']) && isset($emConf['dependencies'])) {
			$emConf['suggests'] = isset($emConf['suggests']) ? $emConf['suggests'] : array();
			$emConf['dependencies'] = $this->dependencyToString($emConf['constraints']);
			$emConf['conflicts'] = $this->dependencyToString($emConf['constraints'], 'conflicts');
		}

			// sanity check for version numbers, intentionally only checks php and typo3
		if (isset($emConf['constraints']['depends']) && isset($emConf['constraints']['depends']['php'])) {
			$versionRange = t3lib_utility_VersionNumber::splitVersionRange($emConf['constraints']['depends']['php']);
			if (version_compare($versionRange[0], '3.0.0', '<')) {
				$versionRange[0] = '3.0.0';
			}
			if (version_compare($versionRange[1], '3.0.0', '<')) {
				$versionRange[1] = '0.0.0';
			}
			$emConf['constraints']['depends']['php'] = implode('-', $versionRange);
		}
		if (isset($emConf['constraints']['depends']) && isset($emConf['constraints']['depends']['typo3'])) {
			$versionRange = t3lib_utility_VersionNumber::splitVersionRange($emConf['constraints']['depends']['typo3']);
			if (version_compare($versionRange[0], '3.5.0', '<')) {
				$versionRange[0] = '3.5.0';
			}
			if (version_compare($versionRange[1], '3.5.0', '<')) {
				$versionRange[1] = '0.0.0';
			}
			$emConf['constraints']['depends']['typo3'] = implode('-', $versionRange);
		}

		unset($emConf['private']);
		unset($emConf['download_password']);
		unset($emConf['TYPO3_version']);
		unset($emConf['PHP_version']);

		return $emConf;
	}

	/**
	 * Checks whether the passed dependency is TER2-style (array) and returns a single string for displaying the dependencies.
	 *
	 * It leaves out all version numbers and the "php" and "typo3" dependencies, as they are implicit and of no interest without the version number.
	 *
	 * @param	mixed		$dep Either a string or an array listing dependencies.
	 * @param	string		$type The dependency type to list if $dep is an array
	 * @return	string		A simple dependency list for display
	 */
	protected function dependencyToString($dep, $type = 'depends') {
		if (is_array($dep)) {
			unset($dep[$type]['php']);
			unset($dep[$type]['typo3']);
			$s = (count($dep[$type])) ? implode(',', array_keys($dep[$type])) : '';
			return $s;
		}
		return '';
	}

	/**
	 * Checks whether the passed dependency is TER-style (string) or TER2-style (array) and returns a single string for displaying the dependencies.
	 *
	 * It leaves out all version numbers and the "php" and "typo3" dependencies, as they are implicit and of no interest without the version number.
	 *
	 * @param	mixed		$dep Either a string or an array listing dependencies.
	 * @return	string		A simple dependency list for display
	 */
	protected function stringToDependency($dep) {
		$constraint = array();
		if (is_string($dep) && strlen($dep)) {
			$dep = explode(',', $dep);
			foreach ($dep as $v) {
				$constraint[$v] = '';
			}
		}
		return $constraint;
	}


	/**
	 * Returns the $EM_CONF array from an extensions ext_emconf.php file
	 *
	 * @param array $extension Extension information array
	 * @return array EMconf array values.
	 */
	public function includeEmConf($extension) {
		$_EXTKEY = $extension['key'];
		$path = PATH_site . $extension['siteRelPath'] . '/ext_emconf.php';
		$EM_CONF = NULL;
		if(file_exists($path)) {
		include_once($path);
			if (is_array($EM_CONF[$_EXTKEY])) {
				return $this->fixEmConf($EM_CONF[$_EXTKEY]);
			}
		}
		return FALSE;
	}
}
?>
