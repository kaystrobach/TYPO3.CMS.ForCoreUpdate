<?php
/**
 * Model class for the 'recycler' extension.
 *
 * @author 	Julian Kleinhans <typo3@kj187.de>
 * @package 	TYPO3
 * @subpackage 	tx_recycler
 */
class tx_recycler_model_tables {

	/**
	 * Get tables for menu example
	 *
	 * @param 	string		$format: Return format (example: json)
	 * @param 	boolean		$withAllOption: 0 no, 1 return tables with a "all" option
	 * @param 	integer		$id: UID from selected page
	 * @param 	integer		$depth: How many levels recursive
	 * @return 	string		The tables to be displayed
	 */
	public function getTables($format, $withAllOption = 0, $startUid, $depth = 0) {
		$deletedRecordsTotal = 0;
		$tables = array();
		foreach (array_keys($GLOBALS['TCA']) as $tableName) {
			$deletedField = tx_recycler_helper::getDeletedField($tableName);
			if ($deletedField) {
				// Determine whether the table has deleted records:
				$deletedCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $tableName, $deletedField . '<>0');
				if ($deletedCount) {
					$deletedDataObject = t3lib_div::makeInstance('tx_recycler_model_deletedRecords');
					$deletedData = $deletedDataObject->loadData($startUid, $tableName, $depth)->getDeletedRows();
					if (isset($deletedData[$tableName])) {
						if ($deletedRecordsInTable = count($deletedData[$tableName])) {
							$deletedRecordsTotal += $deletedRecordsInTable;
							$tables[] = array(
								$tableName,
								$deletedRecordsInTable,
								$tableName,
								tx_recycler_helper::getUtf8String($GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']))
							);
						}
					}
				}
			}
		}
		$jsonArray = $tables;
		if ($withAllOption) {
			array_unshift($jsonArray, array(
				'',
				$deletedRecordsTotal,
				'',
				$GLOBALS['LANG']->sL('LLL:EXT:recycler/mod1/locallang.xml:label_alltables')
			));
		}
		$output = json_encode($jsonArray);
		return $output;
	}

}

?>