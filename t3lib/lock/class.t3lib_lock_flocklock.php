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
 * TYPO3 locking class
 * This class provides a locking using flock.
 *
 * @package TYPO3
 * @subpackage t3lib_lock
 */
class t3lib_lock_FlockLock extends t3lib_lock_AbstractLock {

	/**
	 * Lock file path relative to the TYPO3 main directory (PATH_site)
	 *
	 * @var string
	 */
	protected $lockFileDirectory = 'typo3temp/locks/';

	/**
	 * Absolute path to the lock file
	 *
	 * @var string
	 */
	protected $absoluteFilename = '';

	/**
	 * Constructor:
	 * initializes locking, check input parameters and set variables accordingly.
	 *
	 * @param	string		ID to identify this lock in the system
	 */
	public function __construct($id) {
		parent::__construct($id);
		$path = t3lib_div::getFileAbsFileName($this->lockFileDirectory);
		if (!is_dir($path)) {
			t3lib_div::mkdir($path);
		}
		$this->id = md5($id);
		$this->absoluteFilename = $path . $this->id;
	}

	/**
	 * Acquire a lock and return when successful. If the lock is already open, the client will be
	 *
	 * It is important to know that the lock will be acquired in any case, even if the request was blocked first. Therefore, the lock needs to be released in every situation.
	 *
	 * @return boolean TRUE if lock could be acquired without waiting, FALSE otherwise.
	 */
	public function acquire() {
		$isAcquired = TRUE;
		if (($this->resource = fopen($this->absoluteFilename, 'w+')) === FALSE) {
			throw new RuntimeException('Lock file could not be opened', 1324559214);
		}

		if (flock($this->resource, LOCK_EX | LOCK_NB) === TRUE) { // Lock without blocking
			$noWait = TRUE;
		} elseif (flock($this->resource, LOCK_EX) === TRUE) { // Lock with blocking (waiting for similar locks to become released)
			$noWait = FALSE;
		} else {
			throw new RuntimeException('Could not lock file "' . $this->absoluteFilename . '"', 1324559254);
		}
				
		$this->isAcquired = $isAcquired;
		return $noWait;
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
		$success = flock($this->resource, LOCK_UN);
		fclose($this->resource);
		unlink($this->absoluteFilename);
		$this->log('Released Lock');
		return $success;
	}
}
	


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/lock/class.t3lib_lock_flockbackend.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/lock/class.t3lib_lock_flockbackend.php']);
}
?>