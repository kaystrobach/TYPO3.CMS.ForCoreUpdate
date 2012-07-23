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
class Tx_Extensionmanager_Controller_DownloadController extends Tx_Extensionmanager_Controller_AbstractController {

	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var Tx_Extensionmanager_Utility_FileHandling
	 */
	protected $fileHandlingUtility;


	/**
	 * @var Tx_Extensionmanager_Service_Management
	 */
	protected $managementService;

	/**
	 * @var Tx_Extensionmanager_Utility_Install
	 */
	protected $installUtility;

	/**
	 * @param Tx_Extensionmanager_Utility_Install $installUtility
	 * @return void
	 */
	public function injectInstallUtility(Tx_Extensionmanager_Utility_Install $installUtility) {
		$this->installUtility = $installUtility;
	}

	/**
	 * Dependency injection of the Extension Repository
	 *
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_FileHandling $fileHandlingUtility
	 * @return void
	 */
	public function injectFileHandlingUtility(Tx_Extensionmanager_Utility_FileHandling $fileHandlingUtility) {
		$this->fileHandlingUtility = $fileHandlingUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Service_Management $managementService
	 * @return void
	 */
	public function injectManagementService(Tx_Extensionmanager_Service_Management $managementService) {
		$this->managementService = $managementService;
	}

	/**
	 * Check extension dependencies
	 *
	 * @throws Exception
	 * @return void
	 */
	public function checkDependenciesAction() {
		if (!$this->request->hasArgument('extension')) {
			throw new Exception('Required argument extension not set.', 1334433342);
		}
		$extensionUid = $this->request->getArgument('extension');
		/** @var $extension Tx_Extensionmanager_Domain_Model_Extension */
		$extension = $this->extensionRepository->findByUid(intval($extensionUid));

		$dependencyTypes = $this->managementService->getDependencies($extension);
		$message = '';
		if (count($dependencyTypes) > 0) {
				// @todo translate and beautify
			$message = 'The following dependencies have to be resolved before installation:<br /><br />';
			foreach ($dependencyTypes as $dependencyType => $dependencies) {
				$message .= '<h3>Extensions marked for ' . $dependencyType . ':</h3>';
				foreach ($dependencies as $extensionKey => $dependency) {
					$message .= $extensionKey . '<br />';
				}
				$message .= 'Shall these dependencies be resolved automatically?';
			}
		}
		$this->view->assign('dependencies', $dependencyTypes)
			->assign('extension', $extension)
			->assign('message', $message);
	}

	/**
	 * Install an extension from TER
	 *
	 * @throws Exception
	 * @return void
	 */
	public function installFromTerAction() {
		if (!$this->request->hasArgument('extension')) {
			throw new Exception('Required argument extension not set.', 1334433342);
		}
		$extensionUid = $this->request->getArgument('extension');
		/** @var $extension Tx_Extensionmanager_Domain_Model_Extension */
		$extension = $this->extensionRepository->findByUid(intval($extensionUid));
		$result = $this->managementService->resolveDependenciesAndInstall($extension);
		$this->view->assign('result', $result)
			->assign('extension', $extension);
	}

	/**
	 * Update an extension. Makes no sanity check but directly searches highest
	 * available version from TER and updates. Update check is done by the list
	 * already. This method should only be called if we are sure that there is
	 * an update.
	 *
	 * @return void
	 */
	protected function updateExtensionAction() {
		$extensionKey = $this->request->getArgument('extension');
		/** @var $highestTerVersionExtension Tx_Extensionmanager_Domain_Model_Extension */
		$highestTerVersionExtension = $this->extensionRepository->findHighestAvailableVersion($extensionKey);
		$result = $this->managementService->resolveDependenciesAndInstall($highestTerVersionExtension);
		$this->view->assign('result', $result)
			->assign('extension', $highestTerVersionExtension);
	}

	/**
	 *
	 */
	protected function updateCommentForUpdatableVersionsAction() {
		$extensionKey = $this->request->getArgument('extension');
		$version = $this->request->getArgument('integerVersion');
		$updateComments = array();
		/** @var $updatableVersion Tx_Extensionmanager_Domain_Model_Extension */
		$updatableVersions = $this->extensionRepository->findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $version);
		foreach ($updatableVersions as $updatableVersion) {
			$updateComments[$updatableVersion->getVersion()] = $updatableVersion->getUpdateComment();
		}
		$this->view
			->assign('updateComments', $updateComments)
			->assign('extensionKey', $extensionKey);
	}



}