<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Helmut Hummel <helmut.hummel@typo3.org>
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
 * TYPO3 lock manager
 *
 * Use the manager to get lock instances of any kind.
 *
 * @package TYPO3
 * @subpackage t3lib_lock
 */
class t3lib_lock_Manager implements t3lib_Singleton {
	/**
	 * @var array
	 */
	protected $lockingInstances = array();

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * For now only injects the settings out of a global scope
	 */
	public function __construct() {
		$this->injectSettings($GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']);
	}

	/**
	 * @param $scope
	 * @param $id
	 * @return t3lib_lock_AbstractLock
	 */
	public function getLock($scope, $id) {
		$id = (string) $id;
		$scope = (string) $scope;

		if (isset($this->lockingInstances[$scope . $id])) {
			$lockingInstance = $this->lockingInstances[$scope . $id];
		} else {
			$this->lockingInstances[$scope . $id] = $lockingInstance = $this->createLockObject($scope, $id);
		}
		return $lockingInstance;
	}

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param $scope
	 * @param $id
	 * @return t3lib_lock_AbstractLock
	 */
	protected function createLockObject($scope, $id){
		$lockingInstance = t3lib_div::makeInstance($this->settings[$scope]['className'], $id);

		if (isset($this->settings[$scope]['options']) && is_array($this->settings[$scope]['options'])) {
			$this->applyOptions($lockingInstance, $this->settings[$scope]['options']);
		}

		$lockingInstance->setScope($scope);

		return $lockingInstance;
	}

	/**
	 * @param t3lib_lock_Interface $lockingInstance
	 * @param array $options
	 * @throws InvalidArgumentException
	 */
	protected function applyOptions(t3lib_lock_Interface $lockingInstance, array $options){
		foreach ($options as $optionKey => $optionValue) {
			$methodName = 'set' . ucfirst($optionKey);
			if (method_exists($lockingInstance, $methodName)) {
				$lockingInstance->$methodName($optionValue);
			} else {
				throw new InvalidArgumentException(
					'Invalid locking option "' . $optionKey . '" for lock of type "' . get_class($lockingInstance) . '"',
					1323181106
				);
			}
		}
	}
}
