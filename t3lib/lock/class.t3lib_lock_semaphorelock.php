<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2010 Michael Stucki (michael@typo3.org)
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
 * This class provides an SYSV semaphore lock.
 *
 * @package TYPO3
 * @subpackage t3lib_lock
 */
class t3lib_lock_SemaphoreLock extends t3lib_lock_AbstractLock {

	/**
	 * Initializes semaphore locking.
	 *
	 * @param string ID to identify this lock in the system
	 */
	public function __construct($id) {
		parent::__construct($id);
		$this->id = abs(crc32($id));
		$this->resource = sem_get($this->id, 1);
	}

	/**
	 * Acquire a lock and return when successful. If the lock is already open, the client will be
	 *
	 * It is important to know that the lock will be acquired in any case, even if the request was blocked first. Therefore, the lock needs to be released in every situation.
	 *
	 * @return	boolean		Returns true if lock could be acquired without waiting, false otherwise.
	 */
	public function acquire() {
		sem_acquire($this->resource);
		$this->isAcquired = TRUE;
			// Unfortunately it seems not possible to find out if the request was blocked, so we return FALSE in any case to make sure the operation is tried again.
		return FALSE;
	}

	/**
	 * Release the lock
	 *
	 * @return	boolean		Returns TRUE on success or FALSE on failure
	 */
	public function release() {
		if (!$this->isAcquired) {
			return TRUE;
		}

		$success = TRUE;
		if (@sem_release($this->resource)) {
			sem_remove($this->resource);
		} else {
			$success = FALSE;
		}
		$this->log('Released Lock');
		return $success;
	}

}
	

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/lock/class.t3lib_lock_semaphorelock.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/lock/class.t3lib_lock_semaphorelock.php']);
}
?>