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
class Tx_Extensionmanager_Utility_FileHandlingTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	public $fakedExtensions;


	public function tearDown() {
		foreach ($this->fakedExtensions as $extension => $_dummy) {
			t3lib_div::rmdir(PATH_site . 'typo3conf/ext/' . $extension, TRUE);
		}
	}
	/**
	 * Creates a fake extension inside typo3temp/. No configuration is created,
	 * just the folder
	 *
	 * @return string The extension key
	 */
	protected function createFakeExtension($extkeyOnly = FALSE) {
		$extKey = strtolower(uniqid('testing'));

		$absExtPath = PATH_site . "typo3conf/ext/$extKey/";
		$relPath = "typo3conf/ext/$extKey/";
		$this->fakedExtensions[$extKey] = array(
			'siteRelPath' => $relPath,
			'siteAbsPath' => $absExtPath
		);
		if($extkeyOnly === TRUE) {
			return $extKey;
		}
		t3lib_div::mkdir($absExtPath);
		return $extKey;
	}

	/**
	 * @test
	 */
	public function makeAndClearExtensionDirRemovesExtensionDirIfAlreadyExists() {
		$extKey = $this->createFakeExtension();
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('removeDirectory', 'addDirectory'));
		$fileHandlerMock->expects($this->once())->method('removeDirectory')->with(PATH_site . "typo3conf/ext/$extKey/");
		$fileHandlerMock->makeAndClearExtensionDir($extKey);
	}

	/**
	 * @test
	 */
	public function makeAndClearExtensionDirAddsDir() {
		$extKey = $this->createFakeExtension();
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('removeDirectory', 'addDirectory'));
		$fileHandlerMock->expects($this->once())->method('addDirectory')->with(PATH_site . "typo3conf/ext/$extKey/");
		$fileHandlerMock->makeAndClearExtensionDir($extKey);
	}

	/**
	 * @test
	 * @expectedException Tx_Extensionmanager_Exception_ExtensionManager
	 */
	public function makeAndClearExtensionDirThrowsExceptionOnInvalidPath() {
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('removeDirectory', 'addDirectory'));
		$fileHandlerMock->makeAndClearExtensionDir('testing123', 'fakepath');
	}

	/**
	 * @test
	 */
	public function addDirectoryAddsDirectory() {
		$extDirPath = $this->fakedExtensions[$this->createFakeExtension(TRUE)]['siteAbsPath'];
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('dummy'));
		$this->assertFalse(is_dir($extDirPath));
		$fileHandlerMock->addDirectory($extDirPath);
		$this->assertTrue(is_dir($extDirPath));
	}

	/**
	 * @test
	 * @expectedException Tx_Extensionmanager_Exception_ExtensionManager
	 */
	public function addDirectoryThrowsExceptionOnError(){
		$extDirPath = '/etc/test123/';
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('dummy'));
		$fileHandlerMock->addDirectory($extDirPath);
	}

	/**
	 * @test
	 */
	public function removeDirectoryRemovesDirectory() {
		$extDirPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath'];
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('dummy'));
		$this->assertTrue(is_dir($extDirPath));
		$fileHandlerMock->removeDirectory($extDirPath);
		$this->assertFalse(is_dir($extDirPath));
	}

	/**
	 * @test
	 * @expectedException Tx_Extensionmanager_Exception_ExtensionManager
	 */
	public function removeDirectoryThrowsExceptionOnError(){
		$extDirPath = '/etc/test123/';
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('dummy'));
		$fileHandlerMock->removeDirectory($extDirPath);
	}

	/**
	 * @test
	 */
	public function unpackExtensionFromExtensionDataArrayCreatesTheExtensionDirectory() {
		$extensionData = array(
			'extKey' => 'test'
		);
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
		$fileHandlerMock->expects($this->once())->method('makeAndClearExtensionDir')->with($extensionData['extKey']);
		$fileHandlerMock->unpackExtensionFromExtensionDataArray($extensionData);
	}

	/**
	 * @test
	 */
	public function extractFilesArrayFromExtensionDataReturnsFileArray() {
		$extensionData = array(
			'key' => 'test',
			'FILES' => array(
				'filename1' => 'dummycontent',
				'filename2' => 'dummycontent2'
			)
		);
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
		$extractedFiles = $fileHandlerMock->extractFilesArrayFromExtensionData($extensionData);
		$this->assertArrayHasKey('filename1', $extractedFiles);
		$this->assertArrayHasKey('filename2', $extractedFiles);
	}

	/**
	 * @test
	 */
	public function writeExtensionFilesWritesFiles() {
		$files = array(
			'ChangeLog' => array(
				'name' => 'ChangeLog',
				'size' => 4559,
				'mtime' => 1219448527,
				'is_executable' => FALSE,
				'content' => 'some content to write'
			),
			'README' => array(
				'name' => 'README',
				'size' => 4566,
				'mtime' => 1219448533,
				'is_executable' => FALSE,
				'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE'
			)
		);
		$rootPath = $extDirPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath'];
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
		$fileHandlerMock->writeExtensionFiles($files, $rootPath);
		$this->assertTrue(file_exists($rootPath . 'ChangeLog'));
	}

	/**
	 * @test
	 */
	public function extractDirectoriesFromExtensionDataExtractsDirectories() {
		$files = array(
			'doc/ChangeLog' => array(
				'name' => 'ChangeLog',
				'size' => 4559,
				'mtime' => 1219448527,
				'is_executable' => FALSE,
				'content' => 'some content to write'
			),
			'mod/doc/README' => array(
				'name' => 'README',
				'size' => 4566,
				'mtime' => 1219448533,
				'is_executable' => FALSE,
				'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE'
			)
		);
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
		$extractedDirectories = $fileHandlerMock->extractDirectoriesFromExtensionData($files);
		$this->assertContains('doc/', $extractedDirectories);
		$this->assertContains('mod/doc/', $extractedDirectories);
	}

	/**
	 * @test
	 */
	public function createDirectoriesForExtensionFilesCreatesDirectories() {
		$rootPath = $extDirPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath'];
		$directories = array(
			'doc/',
			'mod/doc/'
		);
		$fileHandlerMock = $this->getMock('Tx_Extensionmanager_Utility_FileHandling', array('makeAndClearExtensionDir'));
		$this->assertFalse(is_dir($rootPath . 'doc/'));
		$this->assertFalse(is_dir($rootPath . 'mod/doc/'));
		$fileHandlerMock->createDirectoriesForExtensionFiles($directories, $rootPath);
		$this->assertTrue(is_dir($rootPath . 'doc/'));
		$this->assertTrue(is_dir($rootPath . 'mod/doc/'));
	}
}