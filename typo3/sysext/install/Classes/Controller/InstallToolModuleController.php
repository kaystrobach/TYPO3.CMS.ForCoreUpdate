<?php
/**
 * Backend module of the 'install' extension, which automatically enables the
 * Install Tool, if it's accessed by an authenticated Backend user.
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 * @package TYPO3
 * @subpackage install
 */
class tx_install_mod1 extends t3lib_SCbase {

	/**
	 * Entry point for the backend module
	 *
	 * @return void
	 */
	public function main() {
		/** @var $installToolService Tx_Install_Service_BasicService */
		$installToolService = t3lib_div::makeInstance('Tx_Install_Service_BasicService');
		if ($installToolService->checkInstallToolEnableFile()) {
			// Install Tool is already enabled
			t3lib_utility_Http::redirect('install/');
		} elseif ($this->isValidEnableRequest()) {
			// Install Tool should be enabled
			$installToolService->createInstallToolEnableFile();
			t3lib_utility_Http::redirect('install/');
		} else {
			// ask the user to enable the Install Tool
			$this->showInstallToolEnableRequest();
		}
	}

	/**
	 * Checks if enabling install tool is requested and form token is correct
	 *
	 * @return bool
	 */
	protected function isValidEnableRequest() {
		return t3lib_div::_POST('enableInstallTool') && t3lib_formprotection_Factory::get()->validateToken(t3lib_div::_POST('formToken'), 'installToolEnableToken');
	}

	/**
	 * Shows warning message about ENABLE_INSTALL_TOOL file and a button to create this file
	 *
	 * @return void
	 */
	protected function showInstallToolEnableRequest() {
		// Create instance of object for output of data
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('install') . 'mod/mod_template.html');
		$this->doc->form = '<form method="post" id="t3-install-form-unlock" action="">';
		$this->doc->addStyleSheet('install', 'stylesheets/install/install.css');
		$this->doc->addStyleSheet('mod-install', t3lib_extMgm::extRelPath('install') . 'mod/mod_styles.css');
		$markers = ($buttons = array());
		$markers['CONTENT'] = $this->renderMessage();
		$content = $this->doc->moduleBody('', $buttons, $markers);
		$this->doc->postCode = '<input type="hidden" name="enableInstallTool" value="1" />' . t3lib_TCEforms::getHiddenTokenField('installToolEnableToken');
		echo $this->doc->render('', $content);
	}

	/**
	 * Renders the message and the activation button
	 *
	 * @return string
	 */
	protected function renderMessage() {
		/** @var $message t3lib_message_ErrorpageMessage */
		$message = t3lib_div::makeInstance('t3lib_message_ErrorPageMessage');
		$message->setTitle($GLOBALS['LANG']->sL('LLL:EXT:install/mod/locallang_mod.xlf:confirmUnlockInstallToolTitle'));
		$message->setSeverity(t3lib_message_ErrorPageMessage::WARNING);
		$message->setHtmlTemplate('/typo3/templates/install.html');
		$content = (($GLOBALS['LANG']->sL('LLL:EXT:install/mod/locallang_mod.xlf:confirmUnlockInstallToolMessage') . '<button type="submit">') . $GLOBALS['LANG']->sL('LLL:EXT:install/mod/locallang_mod.xlf:confirmUnlockInstallToolButton')) . '<span class="t3-install-form-button-icon-positive">&nbsp;</span></button>';
		$messageMarkers = array();
		$messageMarkers['###CONTENT###'] = $content;
		$message->setMarkers($messageMarkers);
		return $message->render();
	}

}

?>