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
 * TYPO3 locking class
 *
 * It is intended to blocks requests until some data has been generated.
 * This is especially useful if two clients are requesting the same website short after each other.
 * While the request of client 1 triggers building and caching of the website, client 2 will be waiting at this lock.
 *
 * @package TYPO3
 * @subpackage t3lib_lock
 * @see	class.t3lib_tstemplate.php, class.tslib_fe.php
 */
class t3lib_lock {
	/**
	 * @var t3lib_lock_AbstractLock
	 */
	protected $lockInstance;

	/**
	 * @var string Logging facility
	 */
	protected $syslogFacility = 'cms';

	/**
	 * @var string|resource
	 */
	protected $resource;

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * Initializes locking, check input parameters and set variables accordingly.
	 *
	 * @param string $id ID to identify this lock in the system
	 * @param string $method Define which locking method to use. Defaults to "simple".
	 * @param integer $loops Number of times a locked resource is tried to be acquired. Only used in manual locks method "simple".
	 * @param integer $step Milliseconds after lock acquire is retried. $loops * $step results in the maximum delay of a lock. Only used in manual lock method "simple".
	 * @deprecated
	 */
	public function __construct($id, $method = 'simple', $loops = NULL, $step = NULL) {
		t3lib_div::logDeprecatedFunction();

		$this->lockInstance = t3lib_div::makeInstance('t3lib_lock_Manager')->getLock($method, $id);
		if ($method === 'simple') {
			if (!is_null($step)) {
				$this->lockInstance->setStep($step);
			}
			if (!is_null($loops)) {
				$this->lockInstance->setLoops($loops);
			}
		}
		if ($method === 'simple' || $method === 'flock') {
			$this->resource = $this->lockInstance->getAbsoluteFilename();
		} else {
			$this->resource = $this->lockInstance->getResource();
		}
		$this->method = $method;
	}

	/**
	 * Acquire a lock and return when successful.
	 *
	 * It is important to know that the lock will be acquired in any case, even if the request was blocked first. Therefore, the lock needs to be released in every situation.
	 *
	 * @return boolean Returns TRUE if lock could be acquired without waiting, FALSE otherwise.
	 */
	public function acquire() {
		return $this->lockInstance->acquire();
	}

	/**
	 * Release the lock
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function release() {
		return $this->lockInstance->release();
	}

	/**
	 * Return the locking method which is currently used
	 *
	 * @return	string		Locking method
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Return the ID which is currently used
	 *
	 * @return	string		Locking ID
	 */
	public function getId() {
		return $this->lockInstance->getId();
	}

	/**
	 * Return the resource which is currently used.
	 * Depending on the locking method this can be a filename or a semaphore resource.
	 *
	 * @return mixed Locking resource (filename as string or semaphore as resource)
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Return the status of a lock
	 *
	 * @return string Returns TRUE if lock is acquired, FALSE otherwise
	 */
	public function getLockStatus() {
		return $this->lockInstance->getLockStatus();
	}

	/**
	 * Sets the facility (extension name) for the syslog entry.
	 *
	 * @param string $syslogFacility
	 */
	public function setSyslogFacility($syslogFacility) {
		$this->syslogFacility = $syslogFacility;
	}

	/**
	 * Adds a common log entry for this locking API using t3lib_div::sysLog().
	 * Example: 25-02-08 17:58 - cms: Locking [simple::0aeafd2a67a6bb8b9543fb9ea25ecbe2]: Acquired
	 *
	 * @param string $message: The message to be logged
	 * @param integer $severity: Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is error, 4 is fatal error
	 */
	public function sysLog($message, $severity = 0) {
		if ($this->lockInstance->getIsLoggingEnabled()) {
			t3lib_div::sysLog('Locking [' . $this->method . '::' . $this->id . ']: ' . trim($message), $this->syslogFacility, $severity);
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_lock.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_lock.php']);
}
?>
