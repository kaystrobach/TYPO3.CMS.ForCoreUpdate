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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A repository for extensions
 */
class Tx_Extensionmanager_Domain_Repository_ExtensionRepository extends Tx_Extbase_Persistence_Repository {

	public function countAll() {
		$query = $this->createQuery();
		$query = $this->addDefaultConstraints($query);
		return $query->execute()->count();
	}

	public function findAll() {
		$query = $this->createQuery();
		$query = $this->addDefaultConstraints($query);
		return $query->execute();
	}

	/**
	 * @param string $extensionKey
	 * @return Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findByExtensionKeyOrderedByVersion($extensionKey) {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('extensionKey', $extensionKey),
				$query->greaterThanOrEqual('reviewState', 0)
			)
		);
		$query->setOrderings(array('version' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING));
		return $query->execute();
	}

	/**
	 * @param string $extensionKey
	 * @param string $version (example: 4.3.10)
	 * @return array|Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findOneByExtensionKeyAndVersion($extensionKey, $version) {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('extensionKey', $extensionKey),
				$query->equals('version', $version)
			)
		);
		return $query->setLimit(1)
			->execute()
			->getFirst();
	}

	/**
	 * @param $searchString
	 * @return mixed
	 */
	public function findByTitleOrAuthorNameOrExtensionKey($searchString) {
		$searchStringForLike = '%' . $searchString . '%';
		$exactMatch = NULL;
		$query = $this->createQuery();
		$query->matching(
			$query->logicalOr(
				$query->like('extensionKey', $searchStringForLike),
				$query->like('title', $searchStringForLike),
				$query->like('authorName', $searchStringForLike)
			)
		);
		$query = $this->addDefaultConstraints($query);
		$result = $query->execute();
		$result = $result->toArray();
		foreach ($result as $key => $extension) {
			if ($searchString === $extension->getExtensionKey()) {
				$exactMatch = $extension;
				unset($result[$key]);
			}
		}
		if (is_object($exactMatch)) {
			$result = array_merge(array($exactMatch), $result);
		}
		return $result;
	}

	/**
	 * @param string $extensionKey
	 * @param integer $lowestVersion
	 * @param integer $highestVersion
	 * @return Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion = 0, $highestVersion = 0) {
		$query = $this->createQuery();
		$constraint = NULL;

		if ($lowestVersion !== 0 && $highestVersion !== 0) {
			$constraint = $query->logicalAnd(
				$query->lessThan('integerVersion', $highestVersion),
				$query->greaterThan('integerVersion', $lowestVersion),
				$query->equals('extensionKey', $extensionKey)
			);
		} elseif ($lowestVersion === 0 && $highestVersion !== 0) {
			$constraint = $query->logicalAnd(
				$query->lessThan('integerVersion', $highestVersion),
				$query->equals('extensionKey', $extensionKey)
			);
		} elseif ($lowestVersion !== 0 && $highestVersion === 0) {
			$constraint = $query->logicalAnd(
				$query->greaterThan('integerVersion', $lowestVersion),
				$query->equals('extensionKey', $extensionKey)
			);
		} elseif ($lowestVersion === 0 && $highestVersion === 0) {
			$constraint = $query->equals('extensionKey', $extensionKey);
		}
		if ($constraint) {
			$query->matching(
				$query->logicalAnd(
					$constraint,
					$query->greaterThanOrEqual('reviewState', 0)
				)
			);
		}
		$query->setOrderings(
			array(
				'integerVersion' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
			)
		);
		return $query->execute();
	}

	/**
	 * @param string $extensionKey
	 * @param integer $lowestVersion
	 * @param integer $highestVersion
	 * @return integer
	 */
	public function countByVersionRangeAndExtensionKey($extensionKey, $lowestVersion = 0, $highestVersion = 0) {
		return $this->findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion, $highestVersion)->count();
	}

	/**
	 * Find highest version available of an extension
	 *
	 * @param string $extensionKey
	 * @return object
	 */
	public function findHighestAvailableVersion($extensionKey) {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('extensionKey', $extensionKey),
				$query->greaterThanOrEqual('reviewState', 0)
			)
		);
		$query->setOrderings(
			array(
				'integerVersion' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
			)
		);
		return $query->setLimit(1)
			->execute()
			->getFirst();
	}

	/**
	 * Update the lastversion field after update
	 * For performance reason "native" TYPO3_DB is
	 * used here directly.
	 *
	 * @param int $repositoryUid
	 * @return integer
	 */
	public function insertLastVersion($repositoryUid = 1) {
		$groupedRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'extkey, version, max(intversion) maxintversion',
			'cache_extensions',
			'repository=' . intval($repositoryUid),
			'extkey'
		);
		$extensions = count($groupedRows);

		if ($extensions > 0) {
				// set all to 0
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'cache_extensions',
				'lastversion=1 AND repository=' . intval($repositoryUid),
				array('lastversion' => 0)
			);

				// Find latest version of extensions and set lastversion to 1 for these
			foreach ($groupedRows as $row) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'cache_extensions',
					'extkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($row['extkey'], 'cache_extensions') . ' AND intversion=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($row['maxintversion'], 'cache_extensions') . ' AND repository=' . intval($repositoryUid),
					array('lastversion' => 1)
				);
			}
		}

		return $extensions;
	}


	protected function addDefaultConstraints(Tx_Extbase_Persistence_Query $query) {
		if($query->getConstraint()) {
			$query->matching(
				$query->logicalAnd(
					$query->getConstraint(),
					$query->equals('lastversion', TRUE)
				)
			);
		} else {
			$query->matching(
				$query->equals('lastversion', TRUE)
			);
		}
		return $query;
	}
}
?>
