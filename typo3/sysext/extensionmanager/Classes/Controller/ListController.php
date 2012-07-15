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
class Tx_Extensionmanager_Controller_ListController extends Tx_Extensionmanager_Controller_AbstractController {


	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * Dependency injection of the Extension Repository
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
-	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * Shows list of extensions present in the system
	 *
	 * @return void
	 */
	public function indexAction() {
		/** @var $listUtility Tx_Extensionmanager_Utility_List */
		$listUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_List');
		$availableExtensions = $listUtility->getAvailableExtensions();
		$availableAndInstalledExtensions = $listUtility->getAvailableAndInstalledExtensions($availableExtensions);
		$availableAndInstalledExtensions = $listUtility->enrichExtensionsWithEmConfInformation($availableAndInstalledExtensions);
		$this->view->assign('extensions', $availableAndInstalledExtensions);
	}

	/**
	 * Shows extensions from TER
	 * Either all extensions or depending on a search param
	 *
	 * @todo handle / mark extensions already on the server
	 * @return void
	 */
	public function terAction() {
		if ($this->request->hasArgument('reset') && $this->request->getArgument('reset') == 1) {
			$this->resetStoredSearchParameters();
			$search = '';
		} else {
			$search = $this->getSearchParam();
		}

		if (is_string($search) && !empty($search)) {
			$extensions = $this->extensionRepository->findByTitleOrAuthorNameOrExtensionKey($search);
			$this->saveSearchParameters($search);
		} else {
			$extensions = $this->extensionRepository->findAll();
			$this->resetStoredSearchParameters();
		}
		$this->view
			->assign('extensions', $extensions)
			->assign('search', $search);
	}

	/**
	 * Shows all versions of a specific extension
	 *
	 * @todo higher priority for exact extensionKey result
	 * @return void
	 */
	public function showAllVersionsAction() {
		$extensions = array();
		$extensionKey = '';
		if (
			$this->request->hasArgument('allVersions') &&
			$this->request->getArgument('allVersions') == 1 &&
			$this->request->hasArgument('extensionKey') &&
			is_string($this->request->getArgument('extensionKey'))
		) {
			$extensionKey = $this->request->getArgument('extensionKey');
			$extensions = $this->extensionRepository->findByExtensionKeyOrderedByVersion($extensionKey);
		} else {
			$this->redirect('ter');
		}
		$this->view
			->assign('extensions', $extensions)
			->assign('extensionKey', $extensionKey);
	}

	/**
	 * Resets session search param
	 *
	 * @return void
	 */
	public function resetStoredSearchParameters() {
		$GLOBALS['BE_USER']->pushModuleData(
			get_class($this),
			json_encode(
				array('search' => '')
			)
		);
	}

	/**
	 * Saves current search parameter in the session
	 *
	 * @param $search
	 * @return void
	 */
	public function saveSearchParameters($search) {
		$GLOBALS['BE_USER']->pushModuleData(
			get_class($this),
			json_encode(
				array('search' => $search)
			)
		);
	}

	/**
	 * Gets the search parameter either from the url or out
	 * of the session if present
	 *
	 * @return string
	 */
	public function getSearchParam() {
		$search = '';
		if ($this->request->hasArgument('search') && is_string($this->request->getArgument('search'))) {
			$search = $this->request->getArgument('search');
		}

			// is a search param present in the session?
		if (empty($search)) {
			$moduleData = json_decode($GLOBALS['BE_USER']->getModuleData(get_class($this)));
			if (isset($moduleData->search)) {
				$search = $moduleData->search;
			}
		}
		return $search;
	}

	/**
	* Gets instance of template if exists or create a new one.
	* Saves instance in viewHelperVariableContainer
	*
	* @return template $doc
	*/
	public function getDocInstance() {
		if (!isset($GLOBALS['SOBE']->doc)) {
			$GLOBALS['SOBE']->doc = t3lib_div::makeInstance('template');
			$GLOBALS['SOBE']->doc->backPath = $GLOBALS['BACK_PATH'];
		}
		return $GLOBALS['SOBE']->doc;
	}
}
?>
