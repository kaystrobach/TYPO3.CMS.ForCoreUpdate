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
 * Main extension model
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Model
 */


class Tx_Extensionmanager_Domain_Model_Extension extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var string
	 */
	protected $extensionKey = '';

	/**
	 * @var string
	 */
	protected $version = '';

	/**
	 * @var string
	 */
	protected $title = '';

	/**
	 * @var string
	 */
	protected $description = '';

	/**
	 * @var int
	 */
	protected $state = 0;

	/**
	 * @var int
	 */
	protected $category = 0;

	/**
	 * @var DateTime
	 */
	protected $lastUpdated;

	/**
	 * @var string
	 */
	protected $updateComment = '';

	/**
	 * @var string
	 */
	protected $authorName = '';

	/**
	 * @var string
	 */
	protected $authorEmail = '';

	/**
	 * @param string $authorEmail
	 */
	public function setAuthorEmail($authorEmail) {
		$this->authorEmail = $authorEmail;
	}

	/**
	 * @return string
	 */
	public function getAuthorEmail() {
		return $this->authorEmail;
	}

	/**
	 * @param string $authorName
	 */
	public function setAuthorName($authorName) {
		$this->authorName = $authorName;
	}

	/**
	 * @return string
	 */
	public function getAuthorName() {
		return $this->authorName;
	}

	/**
	 * @param int $category
	 */
	public function setCategory($category) {
		$this->category = $category;
	}

	/**
	 * @return int
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $extensionKey
	 */
	public function setExtensionKey($extensionKey) {
		$this->extensionKey = $extensionKey;
	}

	/**
	 * @return string
	 */
	public function getExtensionKey() {
		return $this->extensionKey;
	}

	/**
	 * @param DateTime $lastUpdated
	 */
	public function setLastUpdated(DateTime $lastUpdated) {
		$this->lastUpdated = $lastUpdated;
	}

	/**
	 * @return DateTime
	 */
	public function getLastUpdated() {
		return $this->lastUpdated;
	}

	/**
	 * @param int $state
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 * @return int
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $updateComment
	 */
	public function setUpdateComment($updateComment) {
		$this->updateComment = $updateComment;
	}

	/**
	 * @return string
	 */
	public function getUpdateComment() {
		return $this->updateComment;
	}

	/**
	 * @param string $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}
}
