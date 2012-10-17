<?php
/**
 * Script Class for redirecting a backend user to the editing form when an "Edit wizard" link was clicked in TCEforms somewhere
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_edit {

	// Internal, static: GPvars
	// Wizard parameters, coming from TCEforms linking to the wizard.
	/**
	 * @todo Define visibility
	 */
	public $P;

	// Boolean; if set, the window will be closed by JavaScript
	/**
	 * @todo Define visibility
	 */
	public $doClose;

	/**
	 * Initialization of the script
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		$this->P = t3lib_div::_GP('P');
		// Used for the return URL to alt_doc.php so that we can close the window.
		$this->doClose = t3lib_div::_GP('doClose');
	}

	/**
	 * Main function
	 * Makes a header-location redirect to an edit form IF POSSIBLE from the passed data - otherwise the window will just close.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		if ($this->doClose) {
			$this->closeWindow();
		} else {
			// Initialize:
			$table = $this->P['table'];
			$field = $this->P['field'];
			t3lib_div::loadTCA($table);
			$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
			$fTable = $this->P['currentValue'] < 0 ? $config['neg_foreign_table'] : $config['foreign_table'];
			// Detecting the various allowed field type setups and acting accordingly.
			if ((((((is_array($config) && $config['type'] == 'select') && !$config['MM']) && $config['maxitems'] <= 1) && t3lib_utility_Math::canBeInterpretedAsInteger($this->P['currentValue'])) && $this->P['currentValue']) && $fTable) {
				// SINGLE value:
				$redirectUrl = ((((('alt_doc.php?returnUrl=' . rawurlencode('wizard_edit.php?doClose=1')) . '&edit[') . $fTable) . '][') . $this->P['currentValue']) . ']=edit';
				t3lib_utility_Http::redirect($redirectUrl);
			} elseif ((is_array($config) && $this->P['currentSelectedValues']) && ($config['type'] == 'select' && $config['foreign_table'] || $config['type'] == 'group' && $config['internal_type'] == 'db')) {
				// MULTIPLE VALUES:
				// Init settings:
				$allowedTables = $config['type'] == 'group' ? $config['allowed'] : ($config['foreign_table'] . ',') . $config['neg_foreign_table'];
				$prependName = 1;
				$params = '';
				// Selecting selected values into an array:
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->start($this->P['currentSelectedValues'], $allowedTables);
				$value = $dbAnalysis->getValueArray($prependName);
				// Traverse that array and make parameters for alt_doc.php:
				foreach ($value as $rec) {
					$recTableUidParts = t3lib_div::revExplode('_', $rec, 2);
					$params .= ((('&edit[' . $recTableUidParts[0]) . '][') . $recTableUidParts[1]) . ']=edit';
				}
				// Redirect to alt_doc.php:
				t3lib_utility_Http::redirect(('alt_doc.php?returnUrl=' . rawurlencode('wizard_edit.php?doClose=1')) . $params);
			} else {
				$this->closeWindow();
			}
		}
	}

	/**
	 * Printing a little JavaScript to close the open window.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function closeWindow() {
		echo '<script language="javascript" type="text/javascript">close();</script>';
		die;
	}

}

?>