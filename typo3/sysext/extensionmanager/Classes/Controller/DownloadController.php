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
	 * Dependency injection of the Extension Repository
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
	-	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	public function terExtensionDownloadAction() {
		if(!$this->request->hasArgument('extension')) {
			throw new Exception('Required argument extension not set.', 1334433342);
		}
		$extensionUid = $this->request->getArgument('extension');
		$extension = $this->extensionRepository->findByUid(intval($extensionUid));
		/** @var $repositoryHelper Tx_Extensionmanager_Utility_Repository_Helper */
		$repositoryHelper = $this->objectManager->get('Tx_Extensionmanager_Utility_Repository_Helper');
		/** @var $terConnection Tx_Extensionmanager_Utility_Connection_Ter */
		$terConnection = $this->objectManager->get('Tx_Extensionmanager_Utility_Connection_Ter');
		$mirrorUrl = $repositoryHelper->getMirrors()->getMirrorUrl();
		$fetchedExtensions = $terConnection->fetchExtension($extension->getExtensionKey(), $extension->getVersion(), $extension->getMd5hash(), $mirrorUrl);
		foreach($fetchedExtensions as $fetchedExtension) {

		}
	}

	/**
	 * Removes the current extension of $type and creates the base folder for the new one (which is going to be imported)
	 *
	 * @param $extensionkey
	 * @param string $type Extension installation scope (L,G,S)
	 * @internal param array $importedData Data for imported extension
	 * @return mixed Returns array on success (with extension directory), otherwise an error string.
	 */
	protected function makeAndClearExtensionDir($extensionkey, $type) {
		// Setting install path (L, G, S or fileadmin/_temp_/)
		$path = '';
		$paths = Tx_Extensionmanager_Domain_Model_Extension::returnInstallPaths();

		// If the install path is OK...
		if ($path && @is_dir($path)) {

			// Set extension directory:
			$extDirPath = $path . $importedData['extKey'] . $suffix . '/';

			// Install dir was found, remove it then:
			if (@is_dir($extDirPath)) {
				if ($dontDelete) {
					return array($extDirPath);
				}
				$res = $this->removeExtDirectory($extDirPath);
				if ($res) {
					if (!$this->silentMode) {
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							nl2br($res),
							sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_could_not_remove_dir'), $extDirPath),
							t3lib_FlashMessage::ERROR
						);
						return $flashMessage->render();
					}
					return '';
				}
			}

			// We go create...
			t3lib_div::mkdir($extDirPath);
			if (!is_dir($extDirPath)) {
				return sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_could_not_create_dir'),
					$extDirPath);
			}
			return array($extDirPath);
		} else {
			return sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_no_dir'),
				$path);
		}
	}

}