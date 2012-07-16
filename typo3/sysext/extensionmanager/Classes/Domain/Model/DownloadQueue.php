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
 * Download Queue - storage for extensions to be downloaded
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Model
 */
class Tx_Extensionmanager_Domain_Model_DownloadQueue implements t3lib_Singleton {

	/**
	 * Storage for extensions to be installed
	 *
	 * @var array<Tx_Extensionmanager_Domain_Model_Extension>
	 */
	protected $extensionStorage = array();

	/**
	 * Adds an extension to the download queue.
	 * If the extension was already requested in a different version
	 * an exception is thrown.
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function addExtensionToQueue(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		if (array_key_exists($extension->getExtensionKey(), $this->extensionStorage)) {
			if (!($this->extensionStorage[$extension->getExtensionKey()] === $extension)) {
				throw new Tx_Extensionmanager_Exception_ExtensionManager(
					$extension->getExtensionKey() . ' was requested to be downloaded in different versions.',
					1342432101
				);
			}
		}
		$this->extensionStorage[$extension->getExtensionKey()] = $extension;
	}

	/**
	 * @return array
	 */
	public function getExtensionQueue() {
		return $this->extensionStorage;
	}

	/**
	 * Remove an extension from download queue
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return void
	 */
	public function removeExtensionFromQueue(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		if (array_key_exists($extension->getExtensionKey(), $this->extensionStorage)) {
			unset($this->extensionStorage[$extension->getExtensionKey()]);
		}
	}
}

?>