<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

/**
 * Adds the definition of new fields to the pallete for the given field. If
 * palette does not exist for the field, it is created.
 *
 * @param	string	$tableName	Table name where to add/modify new fields
 * @param	string	$fieldName	Field name to add/modify pallete for
 * @param	string	$newFields	Definition for new fields
 * @return	void
 */
function tx_openid_addToPalette($tableName, $fieldName, $newFields) {
	t3lib_div::loadTCA($tableName);
	if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
		die('No field named \'' . $fieldName . '\' found in $TCA for the table named \'' . $tableName . '\'');
	} else {
		// Process all definitions
		foreach ($GLOBALS['TCA'][$tableName]['types'] as $typeCode => $typeDefinition) {
			$fieldDefinitionModified = false;
			// Expand comma-separated list of values
			$fields = t3lib_div::trimExplode(',', $typeDefinition['showitem'], true);
			foreach ($fields as &$field) {
				// $code1 to $code4 are subcodes separated by a semicolon.
				// We are not interested here in their values
				list($currentFieldName, $code1, $palleteCode, $code3, $code4) = explode(';', $field, 5);
				if (trim($currentFieldName) === $fieldName) {
					// Found the field, now look if it already has palettes.
					// If there is no palette, create a new one and reassemble
					// field definition
					if (!$palleteCode) {
						// If palettes do not exist, create them
						if (!isset($GLOBALS['TCA'][$tableName]['palettes'])) {
							$GLOBALS['TCA'][$tableName]['palettes'] = array();
						}
						// Now find the next free palette number
						// This loop does not have limitation because number of palletes is
						// limited anyway. It will stop eventially
						for ($palleteCode = 1; isset($GLOBALS['TCA'][$tableName]['palettes'][$palleteCode]); $palleteCode++) {
							// Empty loop. We simply need to increase counter until we found a free number
						}
						// Put field definition back. Note that it will create the full field definition
						// (with all four semicolons) even if original had only some semicolons
						$field = $currentFieldName . ';' . $code1 . ';' . $palleteCode . ';' . $code3 . ';' . $code4;
						$fieldDefinitionModified = true;
					}
					// Now we check if palette already contains anything
					if ($GLOBALS['TCA'][$tableName]['palettes'][$palleteCode]['showitem']) {
						$GLOBALS['TCA'][$tableName]['palettes'][$palleteCode]['showitem'] .= ',';
					}
					$GLOBALS['TCA'][$tableName]['palettes'][$palleteCode]['showitem'] .= $newFields;
					// We processed the field, stop the loop
					break;
				}
			}
			// If we added a new palette, we need to put modified field definition back
			if ($fieldDefinitionModified) {
				$GLOBALS['TCA'][$tableName]['types'][$typeCode]['showitem'] = implode(',', $fields);
			}
		}
	}
}

// Prepare new columns for be_users table
$tempColumns = array (
	'tx_openid_openid' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:openid/locallang_db.xml:be_users.tx_openid_openid',
		'config' => array (
			'type' => 'input',
			'size' => '30',
			// Requirement: unique (BE users are unique in the whole system)
			'eval' => 'trim,nospace,unique',
		)
	),
);

// Add new columns to be_users table
t3lib_div::loadTCA('be_users');
t3lib_extMgm::addTCAcolumns('be_users', $tempColumns, false);
t3lib_extMgm::addToAllTCAtypes('be_users','tx_openid_openid;;;;1-1-1', '', 'after:username');
t3lib_extMgm::addLLrefForTCAdescr('be_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');

// Prepare new columns for fe_users table
$tempColumns = array (
	'tx_openid_openid' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:openid/locallang_db.xml:fe_users.tx_openid_openid',
		'config' => array (
			'type' => 'input',
			'size' => '30',
			// Requirement: uniqueInPid (FE users are pid-specific)
			'eval' => 'trim,nospace,uniqueInPid',
		)
	),
);

// Add new columns to fe_users table
t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users', $tempColumns, false);
//t3lib_extMgm::addToAllTCAtypes('fe_users', 'tx_openid_openid;;;;1-1-1');
tx_openid_addToPalette('fe_users', 'username', 'tx_openid_openid');
t3lib_extMgm::addLLrefForTCAdescr('fe_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');

?>