<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Dmitry Dulepov <dmitry@typo3.org>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

if (!$TYPO3_CONF_VARS['BE']['compressionLevel']) {
	// We need to buffer output because we may need to redirect later.
	// If we do not buffer, redirection may fail because output could be
	// started in init.php
	ob_start();
}

// Fix _GET/_POST values for authentication
if (isset($_GET['login_status'])) {
	$_POST['login_status'] = $_GET['login_status'];
}

define('TYPO3_MOD_PATH', 'sysext/openid/');
require_once('../../init.php');

class tx_openid_return {
	/**
	 * Processed Backend session creation and redirect to backend.php
	 *
	 * @return	void
	 */
	public function main() {
		if ($GLOBALS['BE_USER']->user['uid']) {
			@ob_end_clean();
			header(t3lib_div::HTTP_STATUS_303);
			header('Location: ' . t3lib_div::locationHeaderUrl('/typo3/backend.php'));
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/openid/class.tx_openid_return.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/openid/class.tx_openid_return.php']);
}

$module = t3lib_div::makeInstance('tx_openid_return');
/* @var tx_openid_return $module */
$module->main();

?>