<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for the Tx_Extensionmanager_Utility_List class in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage extensionmanager
 */
class Tx_Extensionmanager_Domain_Model_DownloadQueueTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueAddsExtensionToStorageArray() {
		$extensionModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Extension', array('dummy'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');

		$downloadQueueMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_DownloadQueue', array('dummy'));
		$downloadQueueMock->addExtensionToQueue($extensionModelMock);

		$this->assertArrayHasKey('foobar', $downloadQueueMock->_get('extensionStorage'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueThrowsExceptionIfExtensionWithSameKeyAndDifferentValuesAlreadyExists() {
		$extensionModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Extension', array('dummy'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');

		$extensionModelMock2 = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Extension', array('dummy'));
		$extensionModelMock2->_set('extensionKey', 'foobar');
		$extensionModelMock2->_set('version', '1.0.3');

		$downloadQueueMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_DownloadQueue', array('dummy'));
		$downloadQueueMock->_set('extensionStorage', array('foobar' => $extensionModelMock2));

		$this->setExpectedException('Tx_Extensionmanager_Exception_ExtensionManager', $this->any(), 1342432101);
		$downloadQueueMock->addExtensionToQueue($extensionModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function removeExtensionFromQueueRemovesExtension() {
		$extensionModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Extension', array('dummy'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');

		$extensionModelMock2 = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Extension', array('dummy'));
		$extensionModelMock2->_set('extensionKey', 'foobarbaz');
		$extensionModelMock2->_set('version', '1.0.3');

		$downloadQueueMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_DownloadQueue', array('dummy'));
		$downloadQueueMock->_set('extensionStorage', array('foobar' => $extensionModelMock, 'foobarbaz' => $extensionModelMock2));

		$this->assertTrue(array_key_exists('foobar', $downloadQueueMock->_get('extensionStorage')));
		$downloadQueueMock->removeExtensionFromQueue($extensionModelMock);
		$this->assertFalse(array_key_exists('foobar', $downloadQueueMock->_get('extensionStorage')));
	}
}
?>