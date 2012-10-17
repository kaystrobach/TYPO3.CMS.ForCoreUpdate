<?php
/**
 * Script Class for redirecting the user to the Web > List module if a wizard-link has been clicked in TCEforms
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_list {

	// Internal, static:
	// PID
	/**
	 * @todo Define visibility
	 */
	public $pid;

	// Internal, static: GPvars
	// Wizard parameters, coming from TCEforms linking to the wizard.
	/**
	 * @todo Define visibility
	 */
	public $P;

	// Table to show, if none, then all tables are listed in list module.
	/**
	 * @todo Define visibility
	 */
	public $table;

	// Page id to list.
	/**
	 * @todo Define visibility
	 */
	public $id;

	/**
	 * Initialization of the class, setting GPvars.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function init() {
		$this->P = t3lib_div::_GP('P');
		$this->table = t3lib_div::_GP('table');
		$this->id = t3lib_div::_GP('id');
	}

	/**
	 * Main function
	 * Will issue a location-header, redirecting either BACK or to a new alt_doc.php instance...
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Get this record
		$origRow = t3lib_BEfunc::getRecord($this->P['table'], $this->P['uid']);
		// Get TSconfig for it.
		$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($this->table, is_array($origRow) ? $origRow : array('pid' => $this->P['pid']));
		// Set [params][pid]
		if (substr($this->P['params']['pid'], 0, 3) == '###' && substr($this->P['params']['pid'], -3) == '###') {
			$this->pid = intval($TSconfig['_' . substr($this->P['params']['pid'], 3, -3)]);
		} else {
			$this->pid = intval($this->P['params']['pid']);
		}
		// Make redirect:
		// If pid is blank OR if id is set, then return...
		if (!strcmp($this->pid, '') || strcmp($this->id, '')) {
			$redirectUrl = t3lib_div::sanitizeLocalUrl($this->P['returnUrl']);
		} else {
			// Otherwise, show the list:
			$urlParameters = array();
			$urlParameters['id'] = $this->pid;
			$urlParameters['table'] = $this->P['params']['table'];
			$urlParameters['returnUrl'] = t3lib_div::getIndpEnv('REQUEST_URI');
			$redirectUrl = t3lib_BEfunc::getModuleUrl('web_list', $urlParameters);
		}
		t3lib_utility_Http::redirect($redirectUrl);
	}

}

?>