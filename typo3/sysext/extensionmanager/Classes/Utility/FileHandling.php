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
 * Utility for dealing with files and folders
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Utility_FileHandling implements t3lib_Singleton {

	/**
	 * @var Tx_Extensionmanager_Utility_EmConf
	 */
	protected $emConfUtility;

	/**
	 * Injector for Tx_Extensionmanager_Utility_EmConf
	 *
	 * @param Tx_Extensionmanager_Utility_EmConf $emConfUtility
	 * @return void
	 */
	public function injectEmConfUtility(Tx_Extensionmanager_Utility_EmConf $emConfUtility) {
		$this->emConfUtility = $emConfUtility;
	}

	/**
	 * @todo allow installation in different paths
	 * @param $extensionData
	 */
	public function unpackExtensionFromExtensionDataArray($extensionData, Tx_Extensionmanager_Domain_Model_Extension $extension = NULL) {
		$extensionDir = $this->makeAndClearExtensionDir($extensionData['extKey']);
		$files = $this->extractFilesArrayFromExtensionData($extensionData);
		$directories = $this->extractDirectoriesFromExtensionData($files);
		$this->createDirectoriesForExtensionFiles($directories, $extensionDir);
		$this->writeExtensionFiles($files, $extensionDir);
		$this->writeEmConfToFile($extensionData, $extensionDir, $extension);
	}

	protected function extractDirectoriesFromExtensionData($files) {
		$directories = array();
		foreach ($files as $filePath => $file) {
			preg_match('/(.*)\//', $filePath, $matches);
			$directories[] = $matches[0];
		}
		return $directories;
	}

	protected function extractFilesArrayFromExtensionData($extensionData) {
		return $extensionData['FILES'];
	}

	protected function createDirectoriesForExtensionFiles($directories, $rootPath) {
		foreach ($directories as $directory) {
			t3lib_div::mkdir_deep($rootPath . $directory);
		}
	}

	protected function writeExtensionFiles($files, $rootPath) {
		foreach($files as $file) {
			t3lib_div::writeFile($rootPath . $file['name'], $file['content']);
		}
	}
	/**
	 * Removes the current extension of $type and creates the base folder for
	 * the new one (which is going to be imported)
	 *
	 * @param string $extensionkey
	 * @param string $pathType Extension installation scope (Local,Global,System)
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return string
	 */
	protected function makeAndClearExtensionDir($extensionkey, $pathType = 'Local') {
		$paths = Tx_Extensionmanager_Domain_Model_Extension::returnInstallPaths();
		$path = $paths[$pathType];
		if (!$path || !is_dir($path) || !$extensionkey) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_no_dir'), $path), 1337280417);
		} else {
			$extDirPath = $path . $extensionkey . '/';
			if (is_dir($extDirPath)) {
				$this->removeDirectory($extDirPath);
			}
			$this->addDirectory($extDirPath);
		}
		return $extDirPath;
	}

	/**
	 * Add specified directory
	 *
	 * @param string $extDirPath
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	protected function addDirectory($extDirPath) {
		t3lib_div::mkdir($extDirPath);
		if(!is_dir($extDirPath)) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_could_not_create_dir'), $extDirPath), 1337280416);
		}
	}

	/**
	 * Remove specified directory
	 *
	 * @param string $extDirPath
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	protected function removeDirectory($extDirPath) {
		$res = t3lib_div::rmdir($extDirPath, TRUE);
		if($res === FALSE) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_could_not_remove_dir'), $extDirPath), 1337280415);
		}
	}

	protected function writeEmConfToFile(array $extensionData, $rootPath, Tx_Extensionmanager_Domain_Model_Extension $extension = NULL) {
		$emConfContent = $this->emConfUtility->constructEmConf($extensionData, $extension);
		t3lib_div::writeFile($rootPath . 'ext_emconf.php', $emConfContent);
	}

}