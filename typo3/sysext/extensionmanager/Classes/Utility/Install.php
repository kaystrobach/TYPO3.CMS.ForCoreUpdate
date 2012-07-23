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
 * Extension Manager Install Utility
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
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

	/**
	 * @var Tx_Extensionmanager_Utility_Dependency
	 */
	protected $dependencyUtility;

	/**
	 * @var Tx_Extensionmanager_Utility_FileHandling
	 */
	protected $filehandlingUtility;

	/**
	 * @var Tx_Extensionmanager_Utility_List
	 */
	protected $listUtility;

	/**
	 * @param Tx_Extensionmanager_Utility_List $listUtility
	 * @return void
	 */
	public function injectListUtility(Tx_Extensionmanager_Utility_List $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_FileHandling $filehandlingUtility
	 * @return void
	 */
	public function injectFilehandlingUtility(Tx_Extensionmanager_Utility_FileHandling $filehandlingUtility) {
		$this->filehandlingUtility = $filehandlingUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_Dependency $dependencyUtility
	 * @todo does not work...
	 * @return void
	 */
	public function injectDependencyUtility(Tx_Extensionmanager_Utility_Dependency $dependencyUtility) {
		$this->dependencyUtility = $dependencyUtility;
	}

	/**
	 * __construct
	 */
	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		/** @var $installToolSqlParser t3lib_install_Sql */
		$this->installToolSqlParser = $this->objectManager->get('t3lib_install_Sql');
		$this->dependencyUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_Dependency');
	}

	/**
	 * Helper function to uninstall an extension
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	public function uninstall($extensionKey) {
		$dependentExtensions = $this->dependencyUtility->findInstalledExtensionsThatDependOnMe($extensionKey);
		if (is_array($dependentExtensions) && count($dependentExtensions) > 0) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				'Cannot deactivate extension ' . $extensionKey . ' - The extension(s) ' . implode(',', $dependentExtensions) . ' depend on it',
				1342554622
			);
		} else {
			$installedExtensions = t3lib_extMgm::getInstalledAndLoadedExtensions();
			unset($installedExtensions[$extensionKey]);
			$newInstalledExtensionList = implode(',', array_keys($installedExtensions));
			$this->writeNewExtensionList($newInstalledExtensionList);
		}
	}

	/**
	 * Helper function to install an extension
	 * also processes db updates and clears the cache if the extension asks for it
	 *
	 * @param string $extensionKey
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function install($extensionKey) {
		$extension = $this->enrichExtensionWithDetails($extensionKey);
		$installedExtensions = t3lib_extMgm::getInstalledAndLoadedExtensions();
		$installedExtensions = array_merge($installedExtensions, array($extension['key'] => $extension['key']));
		$this->processDatabaseUpdates($extension);
		if ($extension['clearcacheonload']) {
			$GLOBALS['typo3CacheManager']->flushCaches();
		}
		$newInstalledExtensionList = implode(',', array_keys($installedExtensions));
		$this->writeNewExtensionList($newInstalledExtensionList);

		$this->reloadCaches();
		$this->saveDefaultConfiguration($extension['key']);
	}

	/**
	 * Fetch additional information for an extension key
	 *
	 * @todo unit tests
	 * @param string $extensionKey
	 * @internal
	 * @return mixed
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 */
	public function enrichExtensionWithDetails($extensionKey) {
		$availableExtensions = $this->listUtility->getAvailableExtensions();
		if (isset($availableExtensions[$extensionKey])) {
			$extension = $availableExtensions[$extensionKey];
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager('Extension ' . $extensionKey . ' is not available', 1342864081);
		}
		$availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfInformation(array($extensionKey => $extension));
		return $availableAndInstalledExtensions[$extensionKey];
	}

	/**
	 * Gets the content of the ext_tables.sql and ext_tables_static+adt.sql files
	 * Additionally adds the table definitions for the cache tables
	 *
	 * @param string $extension
	 * @return void
	 */
	public function processDatabaseUpdates($extension) {
		$extTablesSqlFile = PATH_site . $extension['siteRelPath'] . '/ext_tables.sql';
		if (file_exists($extTablesSqlFile)) {
			$extTablesSqlContent = t3lib_div::getUrl($extTablesSqlFile);
			$extTablesSqlContent .= t3lib_cache::getDatabaseTableDefinitions();
			$this->updateDbWithExtTablesSql($extTablesSqlContent);
		}
		$extTablesStaticSqlFile = PATH_site . $extension['siteRelPath'] .  '/ext_tables_static+adt.sql';
		if (file_exists($extTablesStaticSqlFile)) {
			$extTablesStaticSqlContent = t3lib_div::getUrl($extTablesStaticSqlFile);
			$this->importStaticSql($extTablesStaticSqlContent);
		}
	}

	/**
	 * Writes the extension list to "localconf.php" file
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param string $newExtList
	 * @throws Exception
	 * @return void
	 */
	public function writeNewExtensionList($newExtList) {
		if (!t3lib_extMgm::isLocalconfWritable()) {
			throw new Exception('localconf not writable');
		}

			// Instance of install tool
		$instObj = $this->getT3libInstallInstance();
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extList\']', $newExtList);
		$instObj->writeToLocalconf_control($lines);

		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = $newExtList;
		t3lib_extMgm::removeCacheFiles();
		$GLOBALS['typo3CacheManager']->getCache('cache_phpcode')->flushByTag('t3lib_autoloader');
	}

	/**
	 * Wrapper for make instance to make
	 * mocking possible
	 *
	 * @return t3lib_install
	 */
	protected function getT3libInstallInstance() {
		return t3lib_div::makeInstance('t3lib_install');
	}

	/**
	 * Reload Cache files and Typo3LoadedExtensions
	 *
	 * @return void
	 */
	protected function reloadCaches() {
		t3lib_extMgm::removeCacheFiles();
		$bootstrap = Typo3_Bootstrap::getInstance();
		$bootstrap->populateTypo3LoadedExtGlobal();
	}

	/**
	 * Save default configuration of an extension
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	protected function saveDefaultConfiguration($extensionKey) {
		/** @var $configUtility Tx_Extensionmanager_Utility_Configuration */
		$configUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_Configuration');
		$configUtility->saveDefaultConfiguration($extensionKey);
	}

	/**
	 * Update database / process db updates from ext_tables
	 *
	 * @param string $rawDefinitions The raw SQL statements from ext_tables.sql
	 * @return void
	 */
	public function updateDbWithExtTablesSql($rawDefinitions) {
		$fieldDefinitionsFromFile = $this->installToolSqlParser->getFieldDefinitions_fileContent($rawDefinitions);

		if (count($fieldDefinitionsFromFile)) {
			$fieldDefinitionsFromCurrentDatabase = $this->installToolSqlParser->getFieldDefinitions_database(TYPO3_db);
			$diff = $this->installToolSqlParser->getDatabaseExtra($fieldDefinitionsFromFile, $fieldDefinitionsFromCurrentDatabase);
			$updateStatements = $this->installToolSqlParser->getUpdateSuggestions($diff);

			foreach ((array)$updateStatements['add'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
			foreach ((array)$updateStatements['change'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
			foreach ((array)$updateStatements['create_table'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
		}
	}


	/**
	 * Import static SQL data (normally used for ext_tables_static+adt.sql)
	 *
	 * @param string $rawDefinitions
	 * @return void
	 */
	public function importStaticSql($rawDefinitions) {
		$statements = $this->installToolSqlParser->getStatementarray($rawDefinitions, 1);
		list($statementsPerTable, $insertCount) = $this->installToolSqlParser->getCreateTables($statements, 1);

			// Traverse the tables
		foreach ($statementsPerTable as $table => $query) {
			$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS ' . $table);
			$GLOBALS['TYPO3_DB']->admin_query($query);

			if ($insertCount[$table]) {
				$insertStatements = $this->installToolSqlParser->getTableInsertStatements($statements, $table);

				foreach ($insertStatements as $statement) {
					$GLOBALS['TYPO3_DB']->admin_query($statement);
				}
			}
		}
	}

	/**
	 * Writes the TSstyleconf values to "localconf.php"
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param string $extensionKey Extension key
	 * @param array $newConfiguration Configuration array to write back
	 * @return void
	 */
	public function writeExtensionTypoScriptStyleConfigurationToLocalconf($extensionKey, $newConfiguration) {
			// Instance of install tool
		$instObj = new t3lib_install;
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
			// This will be saved only if there are no linebreaks in it !
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\'' . $extensionKey . '\']', serialize($newConfiguration));
		$instObj->writeToLocalconf_control($lines);

		t3lib_extMgm::removeCacheFiles();
	}

	/**
	 * Remove an extension (delete the directory)
	 *
	 * @param string $extension
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function removeExtension($extension) {
		$absolutePath = $this->filehandlingUtility->getAbsoluteExtensionPath($extension);
		if ($this->filehandlingUtility->isValidExtensionPath($absolutePath)) {
			$this->filehandlingUtility->removeDirectory($absolutePath);
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager('No valid extension path given.', 1342875724);
		}
	}
}

?>
