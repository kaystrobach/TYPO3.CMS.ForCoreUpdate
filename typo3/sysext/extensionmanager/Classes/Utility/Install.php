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
 * action controller.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Controller
 */
class Tx_Extensionmanager_Utility_Install implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	public $objectManager;

	/**
	 * @var t3lib_install_Sql
	 */
	public $installToolSqlParser;

	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		/** @var $installToolSqlParser t3lib_install_Sql */
		$this->installToolSqlParser = $this->objectManager->get('t3lib_install_Sql');
	}

	public function toggleExtensionInstallationState($extension) {
		$installedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];
		if(array_key_exists($extension['key'], $installedExtensions)) {
			// uninstall
			unset($installedExtensions[$extension['key']]);
		} else {
			// install
			$installedExtensions = array_merge($installedExtensions, array($extension['key'] => $extension['key']));
			$this->processDatabaseUpdates($extension);
			if($extension['clearcacheonload']) {
				/** @var $cacheUtility Tx_Extensionmanager_Utility_Cache */
				$cacheUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_Cache');
				$cacheUtility->clearCacheOnLoad();
			}
		}
		$newInstalledExtensionList = implode(',', array_keys($installedExtensions));
		$this->writeNewExtensionList($newInstalledExtensionList);
	}

	public function processDatabaseUpdates($extension) {
		$extTablesSqlFile = PATH_site . $extension['siteRelPath'] . '/ext_tables.sql';
		if(file_exists($extTablesSqlFile)) {
			$extTablesSqlContent = t3lib_div::getUrl($extTablesSqlFile);
			/** Christian fragen!! */
			$extTablesSqlContent .= t3lib_cache::getDatabaseTableDefinitions();
			$this->updateDbWithExtTablesSql($extTablesSqlContent);
		}
		$extTablesStaticSqlFile = PATH_site . $extension['siteRelPath'] .  '/ext_tables_static+adt.sql';
		if(file_exists($extTablesStaticSqlFile)) {
			$extTablesStaticSqlContent = t3lib_div::getUrl($extTablesStaticSqlFile);
			$this->importStaticSql($extTablesStaticSqlContent);
		}
	}

	/**
	 * Writes the extension list to "localconf.php" file
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param string $newExtList List of extensions
	 * @return void
	 */
	public function writeNewExtensionList($newExtList) {
		if(!t3lib_extMgm::isLocalconfWritable()) {
			throw new Exception('localconf not writable');
		}
		$strippedExtensionList = $this->stripNonFrontendExtensionsFromExtensionList($newExtList);

		// Instance of install tool
		$instObj = new t3lib_install;
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';

		// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extList\']', $newExtList);
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extList_FE\']', $strippedExtensionList);
		$instObj->writeToLocalconf_control($lines);

		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = $newExtList;
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList_FE'] = $strippedExtensionList;
		t3lib_extMgm::removeCacheFiles();
		$GLOBALS['typo3CacheManager']->getCache('cache_phpcode')->flushByTag('t3lib_autoloader');
	}

	/**
	 * Removes unneeded extensions from the frontend based on
	 * EMCONF doNotLoadInFE = 1
	 *
	 * @param string $extList
	 * @return string
	 */
	public function stripNonFrontendExtensionsFromExtensionList($extList) {
		/** @var $listUtility Tx_Extensionmanager_Utility_List */
		$listUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_List');
		$fullExtList = $listUtility->enrichExtensionsWithEmConfInformation($listUtility->getAvailableExtensions());
		$extListArray = t3lib_div::trimExplode(',', $extList);
		foreach($extListArray as $arrayKey => $extKey) {
			if($fullExtList[$extKey]['doNotLoadInFE'] == 1) {
				unset($extListArray[$arrayKey]);
			}
		}
		$nonFEList = implode(',', $extListArray);
		return $nonFEList;
	}

	public function updateDbWithExtTablesSql($rawDefinitions) {
		$fieldDefinitionsFromFile = $this->installToolSqlParser->getFieldDefinitions_fileContent($rawDefinitions);

		if(count($fieldDefinitionsFromFile)) {
			$fieldDefinitionsFromCurrentDatabase = $this->installToolSqlParser->getFieldDefinitions_database(TYPO3_db);
			$diff = $this->installToolSqlParser->getDatabaseExtra($fieldDefinitionsFromFile, $fieldDefinitionsFromCurrentDatabase);
			$updateStatements = $this->installToolSqlParser->getUpdateSuggestions($diff);

			foreach((array)$updateStatements['add'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
			foreach((array)$updateStatements['change'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
			foreach((array)$updateStatements['create_table'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
		}
	}

	public function importStaticSql($rawDefinitions) {
		$statements = $this->installToolSqlParser->getStatementarray($rawDefinitions, 1);
		list($statementsPerTable, $insertCount) = $this->installToolSqlParser->getCreateTables($statements, 1);

		// Traverse the tables
		foreach($statementsPerTable as $table => $query) {
			$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS ' . $table);
			$GLOBALS['TYPO3_DB']->admin_query($query);

			if($insertCount[$table]) {
				$insertStatements = $this->installToolSqlParser->getTableInsertStatements($statements, $table);

				foreach($insertStatements as $v) {
					$GLOBALS['TYPO3_DB']->admin_query($v);
				}
			}
		}
	}

	/**
	 * Writes the TSstyleconf values to "localconf.php"
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param	string		Extension key
	 * @param	array		Configuration array to write back
	 * @return	void
	 */
	function writeExtensionTypoScriptStyleConfigurationToLocalconf($extensionKey, $newConfiguration) {
			// Instance of install tool
		$instObj = new t3lib_install;
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\'' . $extensionKey . '\']', serialize($newConfiguration)); // This will be saved only if there are no linebreaks in it !
		$instObj->writeToLocalconf_control($lines);

		t3lib_extMgm::removeCacheFiles();
	}
}

?>
