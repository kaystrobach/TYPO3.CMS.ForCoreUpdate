<?php
/**
 * File edit hook for t3editor
 *
 * @author Tobias Liebig <mail_typo3@etobi.de>
 */
class tx_t3editor_hooks_fileedit {

	/**
	 * @var tx_t3editor
	 */
	protected $t3editor = NULL;

	/**
	 * @var string
	 */
	protected $ajaxSaveType = 'tx_tstemplateinfo';

	/**
	 * @return tx_t3editor
	 */
	protected function getT3editor() {
		if ($this->t3editor == NULL) {
			$this->t3editor = t3lib_div::makeInstance('tx_t3editor')->setAjaxSaveType($this->ajaxSaveType);
		}
		return $this->t3editor;
	}

	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in file_edit.php:SC_file_edit->main
	 *
	 * @param array $parameters
	 * @param SC_file_edit $pObj
	 */
	public function preOutputProcessingHook($parameters, $pObj) {
		$t3editor = $this->getT3editor();
		$t3editor->setModeByFile($parameters['target']);
		if (!$t3editor->isEnabled() || !$t3editor->getMode()) {
			return;
		}
		$parameters['content'] = str_replace('<!--###POSTJSMARKER###-->', '<!--###POSTJSMARKER###-->' . $t3editor->getModeSpecificJavascriptCode(), $parameters['content']);
	}

	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in typo3/template.php:startPage
	 *
	 * @param array $parameters
	 * @param template $pObj
	 */
	public function preStartPageHook($parameters, $pObj) {
		if (preg_match('/typo3\\/file_edit\\.php/', $_SERVER['SCRIPT_NAME'])) {
			$t3editor = $this->getT3editor();
			if (!$t3editor->isEnabled()) {
				return;
			}
			$pObj->JScode .= $t3editor->getJavascriptCode($pObj);
			$pObj->loadJavascriptLib(t3lib_extmgm::extRelPath('t3editor') . 'res/jslib/fileedit.js');
		}
	}

	/**
	 * Hook-function:
	 * called in file_edit.php:SC_file_edit->main
	 *
	 * @param array $parameters
	 * @param SC_file_edit $pObj
	 */
	public function postOutputProcessingHook($parameters, $pObj) {
		$t3editor = $this->getT3editor();
		if (!$t3editor->isEnabled() || !$t3editor->getMode()) {
			return;
		}
		$attributes = ('rows="30" ' . 'wrap="off" ') . $pObj->doc->formWidthText(48, 'width:98%;height:60%', 'off');
		$title = ($GLOBALS['LANG']->getLL('file') . ' ') . htmlspecialchars($pObj->target);
		$outCode = $t3editor->getCodeEditor('file[editfile][0][data]', 'fixed-font enable-tab', '$1', $attributes, $title, array(
			'target' => intval($pObj->target)
		));
		$parameters['pageContent'] = preg_replace('/\\<textarea .*name="file\\[editfile\\]\\[0\\]\\[data\\]".*\\>([^\\<]*)\\<\\/textarea\\>/mi', $outCode, $parameters['pageContent']);
	}

	/**
	 * @return boolean TRUE if successful
	 */
	public function save($parameters, $pObj) {
		$savingsuccess = FALSE;
		if ($parameters['type'] == $this->ajaxSaveType) {
			require_once 'init.php';
			$tceFile = t3lib_div::makeInstance('TYPO3_tcefile');
			$tceFile->processAjaxRequest(array(), $parameters['ajaxObj']);
			$result = $parameters['ajaxObj']->getContent('result');
			$savingsuccess = is_array($result) && $result['editfile'][0];
		}
		return $savingsuccess;
	}

}

?>