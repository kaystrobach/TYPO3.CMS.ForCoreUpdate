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
class Tx_Extensionmanager_Utility_DependencyTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function convertDependenciesToObjectsCreatesObjectStorage() {
		$serializedDependencies = serialize(
			array(
				'depends' => array(
					'php' => '5.1.0-0.0.0',
					'typo3' => '4.2.0-4.4.99',
					'fn_lib' =>''
				)
			)
		);
		/** @var $dependencyUtility Tx_Extensionmanager_Utility_Dependency */
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('dummy'));
		$objectManagerMock = $this->getAccessibleMock('Tx_Extbase_Object_ObjectManager', array('create'));
		$dependencyModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Dependency', array('dummy'));
		$objectManagerMock->expects($this->any())->method('create')->will($this->returnValue($dependencyModelMock));
		$dependencyUtility->_set('objectManager', $objectManagerMock);
		$objectStorage = $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
		$this->assertTrue($objectStorage instanceof SplObjectStorage);
	}

	/**
	 * @test
	 */
	public function convertDependenciesToObjectsSetsIdentifier() {
		$serializedDependencies = serialize(
			array(
				'depends' => array(
					'php' => '5.1.0-0.0.0',
					'typo3' => '4.2.0-4.4.99',
					'fn_lib' =>''
				)
			)
		);
		/** @var $dependencyUtility Tx_Extensionmanager_Utility_Dependency */
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('dummy'));
		$objectManagerMock = $this->getAccessibleMock('Tx_Extbase_Object_ObjectManager', array('create'));
		$dependencyModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Dependency', array('setIdentifier'));
		$objectManagerMock->expects($this->any())->method('create')->will($this->returnValue($dependencyModelMock));
		$dependencyUtility->_set('objectManager', $objectManagerMock);
		$dependencyModelMock->expects($this->at(0))->method('setIdentifier')->with('php');
		$dependencyModelMock->expects($this->at(1))->method('setIdentifier')->with('typo3');
		$dependencyModelMock->expects($this->at(2))->method('setIdentifier')->with('fn_lib');
		$dependencyUtility->convertDependenciesToObjects($serializedDependencies);
	}

	public function convertDependenciesToObjectSetsVersionDataProvider() {
		return array(
			'everything ok' => array(
				array(
					'depends' => array(
						'typo3' => '4.2.0-4.4.99',
					)
				),
				array(
					'4.2.0',
					'4.4.99'
				)
			),
			'empty high value' => array(
				array(
					'depends' => array(
						'typo3' => '4.2.0-0.0.0',
					),
				),
				array(
					'4.2.0',
					''
				)
			),
			'empty low value' => array(
				array(
					'depends' => array(
						'typo3' => '0.0.0-4.4.99',
					),
				),
				array(
					'',
					'4.4.99'
				)
			)
		);
	}


	/**
	 * @test
	 * @dataProvider convertDependenciesToObjectSetsVersionDataProvider
	 */
	public function convertDependenciesToObjectSetsVersion($dependencyString, $returnValue) {
		$serializedDependencies = serialize($dependencyString);
		/** @var $dependencyUtility Tx_Extensionmanager_Utility_Dependency */
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('convertVersionsStringToVersionNumbers'));
		$objectManagerMock = $this->getAccessibleMock('Tx_Extbase_Object_ObjectManager', array('create'));
		$dependencyModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Dependency', array('setHighestVersion', 'setLowestVersion'));
		$objectManagerMock->expects($this->any())->method('create')->will($this->returnValue($dependencyModelMock));
		$dependencyUtility->_set('objectManager', $objectManagerMock);
		$dependencyUtility->expects($this->atLeastOnce())
			->method('convertVersionsStringToVersionNumbers')
			->with($dependencyString['depends']['typo3'])
			->will($this->returnValue($returnValue));
		$dependencyModelMock->expects($this->atLeastOnce())->method('setHighestVersion')->with($returnValue[0]);
		$dependencyModelMock->expects($this->atLeastOnce())->method('setLowestVersion')->with($returnValue[1]);
		$dependencyUtility->convertDependenciesToObjects($serializedDependencies);
	}

	/**
	 * data provider for convertVersionsStringToVersionNumbersForcesVersionNumberInRange
	 * @return array
	 */
	public function convertVersionsStringToVersionNumbersForcesVersionNumberInRangeDataProvider() {
		return array(
			'everything ok' => array(
				'4.2.0-4.4.99',
				array(
					'4.2.0',
					'4.4.99'
				)
			),
			'too high value' => array(
				'4.2.0-4.4.299',
				array(
					'4.2.0',
					'4.4.99'
				)
			),
			'empty high value' => array(
				'4.2.0-0.0.0',
				array(
					'4.2.0',
					''
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider convertVersionsStringToVersionNumbersForcesVersionNumberInRangeDataProvider
	 */
	public function convertVersionsStringToVersionNumbersForcesVersionNumberInRange($versionString, $expectedResult) {
		/** @var $dependencyUtility Tx_Extensionmanager_Utility_Dependency */
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('dummy'));
		$versions = $dependencyUtility->_call('convertVersionsStringToVersionNumbers', $versionString);
		$this->assertEquals($expectedResult, $versions);
	}

	/**
	 * @test
	 */
	public function checkTypo3DependencyThrowsExceptionIfVersionNumberIsTooLow() {
		$dependencyModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('dummy'));

		$this->setExpectedException('Tx_Extensionmanager_Exception_ExtensionManager', 'Your TYPO3 version is lower than necessary. You need at least TYPO3 version 15.0.0');
		$dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock);
	}

	/**
	 * @test
	 */
	public function checkTypo3DependencyThrowsExceptionIfVersionNumberIsTooHigh() {
		$dependencyModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('3.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('dummy'));

		$this->setExpectedException('Tx_Extensionmanager_Exception_ExtensionManager',
			'Your TYPO3 version is higher than allowed. You can use TYPO3 versions 1.0.0 - 3.0.0'
		);
		$dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock);
	}

	/**
	 * @test
	 */
	public function checkTypo3DependencyReturnsTrueIfVersionNumberIsInRange() {
		$dependencyModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('dummy'));

		$this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock));
	}

	/**
	 * @test
	 */
	public function checkTypo3DependencyCanHandleEmptyVersionHighestVersion() {
		$dependencyModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue(''));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('dummy'));

		$this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock));
	}
	/**
	 * @test
	 */
	public function checkTypo3DependencyCanHandleEmptyVersionLowestVersion() {
		$dependencyModelMock = $this->getAccessibleMock('Tx_Extensionmanager_Domain_Model_Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue(''));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('Tx_Extensionmanager_Utility_Dependency', array('dummy'));

		$this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock));
	}
}
?>