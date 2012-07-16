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
 * Service class for managing multiple step processes (dependencies for example)
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Service_Management implements t3lib_Singleton {

	/**
	 * @var Tx_Extensionmanager_Domain_Model_DownloadQueue
	 */
	protected $downloadQueue;

	/**
	 * @var Tx_Extensionmanager_Utility_Dependency
	 */
	protected $dependencyUtility;

	/**
	 * @param Tx_Extensionmanager_Domain_Model_DownloadQueue $downloadQueue
	 * @return void
	 */
	public function injectDownloadQueue(Tx_Extensionmanager_Domain_Model_DownloadQueue $downloadQueue) {
		$this->downloadQueue = $downloadQueue;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_Dependency $dependencyUtility
	 * @return void
	 */
	public function injectDependencyUtility(Tx_Extensionmanager_Utility_Dependency $dependencyUtility) {
		$this->dependencyUtility = $dependencyUtility;
	}

	/**
	 * @var Tx_Extensionmanager_Utility_FileHandling
	 */
	protected $downloadUtility;

	/**
	 * @param Tx_Extensionmanager_Utility_Download $downloadUtility
	 * @return void
	 */
	public function injectDownloadUtility(Tx_Extensionmanager_Utility_Download $downloadUtility) {
		$this->downloadUtility = $downloadUtility;
	}


	public function markExtensionForInstallation($extensionKey) {
		var_dump($extensionKey);
		die();
/*
 * add extensionKey to list of extensions to be installed
 */
	}

	/**
	 * Mark an extension for download
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return void
	 */
	public function markExtensionForDownload(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		$this->downloadQueue->addExtensionToQueue($extension);
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
/*
 * add extension to download queue
 */
	}


	public function markExtensionForUpdate(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		/*
		 * add extension to download queue and mark as update
		 */
		$this->downloadQueue->addExtensionToQueue($extension);
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
	}

	public function resolveDependencies(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
		$downloads = $this->downloadQueue->getExtensionQueue();
		foreach ($downloads as $extensionToDownload) {
			$this->downloadUtility->download($extensionToDownload);
			$this->downloadQueue->removeExtensionFromQueue($extensionToDownload);
		}
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
	}
}

?>