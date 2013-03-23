<?php
namespace TYPO3\CMS\Core\DataHandling;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Benjamin Mack (benni@typo3.org)
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * main class for asking or modifying $TCA tables, each table gets its
 * own instance.
 *
 * Is used to become a full and final API for any TCA-related logic
 * this way, TYPO3 will be able to cache in a clean way, load only certain
 * parts of the TCA for FE, and extract the logic from ExtensionManagement to
 * here.
 * Additionally, in order to change certain options but also still have
 * backwards-compatibility
 *
 * step 1 is to include all of the checks and logic in this class, so it
 * is used anywhere in the TYPO3 core (target version: 6.2)
 * step 2 is to deprecate/drop the calls to the $GLOBALS[TCA] array
 *
 * @author Benjamin Mack <benni@typo3.org>
 */
class DataStructure {

	/*************************************
	 * METHODS RELATED TO INSTANTIATION
	 *************************************/

	/**
	 * holds all instances for each table
	 * @var array
	 */
	private static $_instances = array();

	/**
	 * allows to fetch the singleton for a certain table
	 *
	 * @param $tableName the name of the table, e.g. "pages"
	 */
	public static function getInstance($tableName) {
		if (!isset(self::$_instances[$tableName])) {
			self::$_instances[$tableName] = new Datastructure($tableName);
		}
		return self::$_instances[$tableName];
	}

	/**
	 * can only be called by getInstance()
	 *
	 * @param $tableName
	 */
	protected function __construct($tableName) {
		if (isset($GLOBALS['TCA'][$tableName])) {
			$this->tableName = $tableName;
		} else {
			throw new \RuntimeException('No structure in $TCA found with name "' . $tableName . '".', 1364044246);
		}
	}

	/*************************************
	 * generic functionality for the table
	 *************************************/

	/**
	 * contains the table name, used for $TCA[$tableName]
	 *
	 * @var string
	 */
	protected $tableName = NULL;

	/**
	 * returns only parts of the array of a specific section, e.g. the "ctrl" section
	 *
	 * @param $sectionName can be "ctrl", "columns", "types" or "palettes"
	 * @return array
	 * @throws \RuntimeException if the section cannot be found
	 */
	protected function getStructureInformation($sectionName) {
		if (isset($GLOBALS['TCA'][$this->tableName][$sectionName])) {
			return $GLOBALS['TCA'][$this->tableName][$sectionName];
		} else {
			throw new \RuntimeException('No key found in $TCA["' . $this->tableName . '"] found with name "' . $sectionName. '".', 1364044249);
		}
	}

	/**************************************
	 * ctrl-related functionality
	 **************************************/

	/**
	 * check if the TCA table supports workspaces
	 *
	 * @return bool
	 */
	public function supportsWorkspaces() {
		$ctrl = $this->getTableInformation('ctrl');
		return ($ctrl['versioningWS']) ? TRUE : FALSE;
	}




	/**************************************
	 * columns-related functionality
	 **************************************/

	/**
	 * adds a field/column to the structure
	 *
	 * @param $fieldName the name of the field, will be placed inside $GLOBALS['TCA'][$tableName]['columns'][$fieldName]
	 * @param $configuration the configuration to add for this column/field
	 * @return void
	 */
	public function addField($fieldName, $configuration) {
		$GLOBALS['TCA'][$this->tableName]['columns'][$fieldName] = $configuration;
	}

	/**
	 * adds the field to the frontend interface (feInterface|fe_admin_fieldList)
	 *
	 * @param $fieldName the name of the field
	 * @return void
	 */
	public function addFieldToFrontendInterface($fieldName) {
		$GLOBALS['TCA'][$this->tableName]['feInterface']['fe_admin_fieldList'] .= ',' . $fieldName;
	}

	/**
	 * fetches the configuration of a specific column, e.g. $GLOBALS['TCA']['tt_content']['columns']['bodytext']
	 * but optionally merged via a specific type
	 *
	 * @param $fieldName the name of the field, e.g. "bodytext"
	 * @param string $typeOverride if set to a specific type (e.g. "textpic" for tt_content CType)
	 * @return array
	 */
	public function getFieldConfiguration($fieldName, $typeOverride = NULL) {
		$allColumns = $this->getTableInformation('columns');

		// check if the field is a valid field in the current table
		if (isset($allColumns[$fieldName])) {
			$finalConfiguration = $allColumns[$fieldName];

			// check if the configuration that is returned, should be overloaded to be type-specific
			if ($typeOverride !== NULL) {
				$typeSpecificConfiguration = $finalConfiguration['types'][$typeOverride];
				if (is_array($typeSpecificConfiguration)) {
					unset($finalConfiguration['types']);
					$finalConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($finalConfiguration, $typeSpecificConfiguration);
				}
			}
			return $finalConfiguration;
		} else {
			throw new \RuntimeException('No valid column named "' . $fieldName . '" found in $TCA["' . $this->tableName . '"]["columns"].', 1364044272);
		}
	}
}