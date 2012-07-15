<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog <susanne.moog@typo3.org>
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
 * Service class for managing multiple step processes (dependencies for example)
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Service_Management implements t3lib_Singleton {

	public function markExtensionForInstallation($extensionKey) {
/*
 * add extensionKey to list of extensions to be installed
 */
	}

	public function markExtensionForDownload(Tx_Extensionmanager_Domain_Model_Extension $extension) {
/*
 * add extension to download queue
 */
	}


	public function markExtensionForUpdate(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		/*
		 * add extension to download queue and mark as update
		 */
	}
}

?>