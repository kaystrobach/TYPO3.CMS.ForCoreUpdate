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
class Tx_Extensionmanager_Utility_Dependency implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager){
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository){
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 */
	public function getExtensionDependencies($extension) {
		$dependencies = $extension->getDependencies();
		$dependencies->checkSpecialDependencies($dependencies);
	}

	/**
	 * @param string $dependencies
	 */
	public function convertDependenciesToObjects($dependencies) {
		$unserializedDependencies = unserialize($dependencies);
		$dependencies = new SplObjectStorage();
		foreach ($unserializedDependencies as $dependencyType => $dependencyValues) {
			foreach ($dependencyValues as $dependency => $versions) {
				list($highest, $lowest) = $this->convertVersionsStringToVersionNumbers($versions);
				/** @var $dependencyObject Tx_Extensionmanager_Domain_Model_Dependency */
				$dependencyObject = $this->objectManager->create('Tx_Extensionmanager_Domain_Model_Dependency');
				$dependencyObject->setType($dependencyType);
				$dependencyObject->setIdentifier($dependency);
				$dependencyObject->setLowestVersion($lowest);
				$dependencyObject->setHighestVersion($highest);
				$dependencies->attach($dependencyObject);
				unset($dependencyObject);
			}
		}
		return $dependencies;
	}

	/**
	 * '4.2.0-4.4.99',
	 */
	protected function convertVersionsStringToVersionNumbers($versionsString) {
		$versions = t3lib_div::trimExplode('-', $versionsString);
		for ($i = 0; $i < count($versions); $i++) {
			$cleanedVersion = t3lib_div::trimExplode('.', $versions[$i]);
			for ($j = 0; $j < count($cleanedVersion); $j++) {
				$cleanedVersion[$j] = t3lib_utility_Math::forceIntegerInRange($cleanedVersion[$j], 0, 99);
			}
			$cleanedVersionString = implode('.', $cleanedVersion);
			if (t3lib_utility_VersionNumber::convertVersionNumberToInteger($cleanedVersionString) === 0) {
				$cleanedVersionString = '';
			}
			$versions[$i] = $cleanedVersionString;
		}
		return $versions;
	}

	protected function checkSpecialDependencies() {

	}

	/**
	 * Returns true if current TYPO3 version fulfills extension requirements
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return boolean
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 */
	protected function checkTypo3Dependency(Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
		$lowerCaseIdentifier = strtolower($dependency->getIdentifier());
		if ($lowerCaseIdentifier === 'typo3') {
			if (!($dependency->getLowestVersion() === '') && version_compare(t3lib_utility_VersionNumber::getNumericTypo3Version(), $dependency->getLowestVersion()) === -1) {
				throw new Tx_Extensionmanager_Exception_ExtensionManager(
					'Your TYPO3 version is lower than necessary. You need at least TYPO3 version ' . $dependency->getLowestVersion()
				);
			}
			if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), t3lib_utility_VersionNumber::getNumericTypo3Version()) === -1) {
				throw new Tx_Extensionmanager_Exception_ExtensionManager(
					'Your TYPO3 version is higher than allowed. You can use TYPO3 versions ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion()
				);
			}
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				'checkTypo3Dependency can only check TYPO3 dependencies. Found dependency with identifier' . $dependency->getIdentifier()
			);
		}
		return true;
	}

}
