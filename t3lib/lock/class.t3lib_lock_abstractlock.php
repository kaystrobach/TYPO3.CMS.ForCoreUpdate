<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Michael Stucki (michael@typo3.org)
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
 * This class provides an abstract layer to various locking features
 *
 * @package TYPO3
 * @subpackage t3lib_lock
 */
abstract class t3lib_lock_AbstractLock implements t3lib_lock_Interface {
	/**
	 * @var mixed Identifier used for this lock
	 */
	protected $id;

	/**
	 * @var boolean TRUE if lock is acquired
	 */
	protected $isAcquired = FALSE;

	/**
	 * @var null|resource
	 */
	protected $resource;

	/**
	 * @var string
	 */
	protected $scope;

	/**
	 * @var boolean True if locking should be logged
	 */
	protected $isLoggingEnabled = TRUE;

	/**
	 * Deny unserializing
	 */
	private function __wakeup() {}

	/**
	 * Deny serializing
	 */
	private function __sleep() {}

	/**
	 * @param $id
	 */
	public function __construct($id) {
		$this->id = (string) $id;
	}

	/**
	 * Releases lock automatically when instance is destroyed.
	 */
	function __destruct() {
		$this->release();
	}

	/**
	 * Return the ID which is currently used
	 *
	 * @return string Locking ID
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Return the status of a lock
	 *
	 * @return string TRUE if lock is acquired, FALSE otherwise
	 */
	public function getLockStatus() {
		return $this->isAcquired;
	}

	/**
	 * @param string $scope
	 */
	public function setScope($scope)
	{
		$this->scope = $scope;
	}

	/**
	 * @return string
	 */
	public function getScope()
	{
		return $this->scope;
	}


	/**
	 * @return null|resource
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * Enable/ disable logging
	 *
	 * @param boolean $isLoggingEnabled
	 */
	public function setEnableLogging($isLoggingEnabled) {
		$this->isLoggingEnabled = $isLoggingEnabled;
	}

	/**
	 * Returns TRUE if logging is enabled
	 *
	 * @return boolean
	 */
	public function getIsLoggingEnabled() {
		return $this->isLoggingEnabled;
	}

	/**
	 * Adds a common log entry for this locking API using t3lib_div::sysLog().
	 * Example: 25-02-08 17:58 - cms: Locking [simple::0aeafd2a67a6bb8b9543fb9ea25ecbe2]: Acquired
	 *
	 * @param string $message The message to be logged
	 * @param integer $severity
	 */
	protected function log($message, $severity = t3lib_div::SYSLOG_SEVERITY_INFO) {
		if (!$this->isLoggingEnabled) {
			t3lib_div::sysLog('Locking [' . get_class($this) . '::' . $this->id . ']: ' . trim($message), $this->scope, $severity);
		}
	}
}
	

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/lock/class.t3lib_lock_abstractlock.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/lock/class.t3lib_lock_abstractlock.php']);
}
?>