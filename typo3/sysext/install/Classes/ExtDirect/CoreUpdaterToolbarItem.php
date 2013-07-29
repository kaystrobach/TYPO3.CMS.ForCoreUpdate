<?php
namespace TYPO3\CMS\Install\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Install tool controller, dispatcher class of the install tool.
 *
 * Handles install tool session, login and login form rendering,
 * calls actions that need authentication and handles form tokens.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
	require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('backend') . 'Classes/Toolbar/ToolbarItemHookInterface.php';
}

/**
 * class to render the workspace selector
 *
 * @author 	Ingo Renner <ingo@typo3.org>
 */
class CoreUpdaterToolbarItem implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemHookInterface {

	/**
	 * reference back to the backend object
	 *
	 * @var	TYPO3backend
	 */
	protected $backendReference;
	protected $checkAccess = NULL;
	protected $EXTKEY = 'install';
    /**
	 * constructor, loads the documents from the user control
	 *
	 * @param	TYPO3backend	TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$this->backendReference = $backendReference;
		$pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
	}


	/**
	 * checks whether the user has access to this toolbar item
	 *
	 * @return  boolean  true if user has access, false if not
	 */
	public function checkAccess() {
		return true;
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('install')) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	/**
	 * renders the toolbar item and the initial menu
	 *
	 * @return	string		the toolbar item including the initial menu content as HTML
	 */
	public function render() {
		//$this->initSettings();
		$this->addJavascriptToBackend();
		$this->addCssToBackend();
		$this->addLLToBackend();
		return $this->renderMenu();
	}

	function initSettings() {
		$this->settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->EXTKEY]);
		if(!array_key_exists('notificationSound', $this->settings)) {
			$this->settings['notificationSound'] = 'EXT:install/Resources/Public/Sounds/Fire_pager-jason-1283464858.mp3';
		}
	}

    /**
     * @return string
     */
	function renderMenu() {
		$path     = GeneralUtility::getFileAbsFileName($this->settings['notificationSound'],true);
		$path     = str_replace(PATH_site,'../',$path);

		$ogg      = str_replace('.mp3','.ogg',$path);
		$mp3      = str_replace('.ogg','.mp3',$path);

		$buffer = '<a href="#" class="toolbar-item"><img src="'.ExtensionManagementUtility::extRelPath($this->EXTKEY).'Resources/Public/Images/CoreUpdater/" class="t3-icon" style="background-image:none;"></a>';
		$buffer.= '<div class="toolbar-item-menu" style="display: none;">';
		$buffer.= '<audio id="coreupdateAudio">';
		$buffer.= '  <source src="'.$ogg.'" type="audio/ogg" />';
		$buffer.= '  <source src="'.$mp3.'" type="audio/mp3" />';
		$buffer.= '</audio>';
		$buffer.= '<div class="toolbar-item-menu-dynamic">';
		$buffer.= '</div>';
		$buffer.= '</div>';
		return $buffer;
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="tx-coreupdate-menu"';
	}
	/**
	 * adds the neccessary javascript to the backend
	 *
	 * @return	void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('install') . 'Resources/Public/Javascript/ToolbarItems/Updater.js');

		$pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
		//$pageRenderer->addInlineSettingArray($this->EXTKEY, $this->settings);
	}

	/**
	 * adds the neccessary CSS to the backend
	 *
	 * @return	void
	 */
	protected function addCssToBackend() {
		$this->backendReference->addCssFile('install', ExtensionManagementUtility::extRelPath($this->EXTKEY) . 'Resources/Public/Stylesheets/CoreUpdater/Updater.css');
	}

	function addLLToBackend() {
		// to be done @todo
	}

	//==========================================================================
	// AJAX
	//==========================================================================
	/**
	 * renders the menu so that it can be returned as response to an AJAX call
	 *
	 * @param	array		array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX	object of type TYPO3AJAX
	 * @return	void
	 */
	public function renderAjax($params = array(), \TYPO3AJAX &$ajaxObj = null) {
		//die('hard');
		$menuContent = $this->renderMenu();
		$ajaxObj->addContent($this->EXTKEY, $menuContent);
	}

}


if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$GLOBALS['TYPO3backend']->addToolbarItem('coreupdater', 'TYPO3\\CMS\\Install\\ExtDirect\\CoreUpdaterToolbarItem');
}
?>