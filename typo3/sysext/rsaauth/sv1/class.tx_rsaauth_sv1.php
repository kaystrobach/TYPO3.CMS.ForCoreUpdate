<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Oliver Hader <oliver@typo3.org>
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

require_once(PATH_t3lib.'class.t3lib_svbase.php');
require_once(t3lib_extMgm::extPath('sv') . 'class.tx_sv_auth.php');
require_once(t3lib_extMgm::extPath('sv') . 'class.tx_sv_authbase.php');
require_once(t3lib_extMgm::extPath('rsaauth') . 'res/class.tx_rsaauth_php.php');


/**
 * Service "RSA Authentication" for the "rsaauth" extension.
 *
 * @author		Oliver Hader <oliver@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_sv1 extends tx_sv_auth {
	public $prefixId = 'tx_rsaauth_sv1';		// Same as class name
	public $scriptRelPath = 'sv1/class.tx_rsaauth_sv1.php';	// Path to this script relative to the extension dir.
	public $extKey = 'rsaauth';	// The extension key.

	public $pObj;

	protected $rsa;

	/**
	 * Intializes this authentication service and returns whether the service can be used.
	 *
	 * @return	boolean		Whether this service can be used
	 */
	public function init()	{
		$available = false;

		// RSA authentication is currently only possible in the TYPO3 back-end
		if (TYPO3_MODE == 'BE') {
			$available = parent::init();
			$this->rsa = new tx_rsaauth_php();
		}

		return $available;
	}

	/**
	 * Enter description here...
	 *
	 * @param	string			$subType
	 * @param	array			$loginData
	 * @param	array			$authInfo
	 * @param	t3lib_userAuth	$parentObject
	 */
	public function initAuth($subType, array $loginData, array $authInfo, t3lib_userAuth &$parentObject) {
		session_start();

		$this->pObj = $parentObject;

		$this->mode = $mode;
		$this->login = $loginData;
		$this->authInfo = $authInfo;

		$this->db_user = $this->getServiceOption('db_user', $authInfo['db_user'], false);
		$this->db_groups = $this->getServiceOption('db_groups', $authInfo['db_groups'], false);

		$this->writeAttemptLog = $parentObject->writeAttemptLog;
		$this->writeDevLog	 = $parentObject->writeDevLog;
	}

	/**
	 * Find a user (eg. look up the user record in database when a login is sent)
	 *
	 * @return	mixed		User information (array) or false if something went wrong
	 */
	public function getUser() {
		return parent::getUser();
	}

	/**
	 * Enter description here...
	 *
	 * @param	array		$user
	 */
	public function authUser($user) {
		$authenticated = false;

		if ($this->login['uident'] && $this->login['uname'] && $this->isSessionDataAvailable()) {
			$this->rsa->setPrivateKey($this->getPrivateKeyFromSession());
			$uIdent = $this->rsa->decryptWithPrivateKey( 
				base64_decode($this->login['uident'])
			);

			if (md5($uIdent) === $user[$this->userident_column]) {
				$authenticated = true;
			}
		}

		return $authenticated;
	}

	/**
	 * Extends a docuement by specific information like JavaScript or form fields.
	 *
	 * @param	template	$template
	 * @param	object		$parentObject
	 * @return	void
	 */
	public function extendDocument(template &$template, &$parentObject) {
		$this->rsa->generatePrivateKey();
		$this->setSessionData();

		$this->includeJavaScript($template);
		$this->includeHiddenFormFields($template);
		
		if ($parentObject instanceof SC_index) {
			$parentObject->setJavaScriptLoginSecurityLevelCall('RSAAuth.encrypt();');
		}
	}

	/**
	 * Includes JavaScript classes that are required on client-side to use RSA.
	 *
	 * @param	template	$template
	 * @return	void
	 */
	protected function includeJavaScript(template &$template) {
		$javaScriptFiles = array(
			'contrib/jsbn/jsbn.js',
			'contrib/jsbn/prng4.js',
			'contrib/jsbn/rng.js',
			'contrib/jsbn/rsa.js',
			'contrib/jsbn/base64.js',
			'js/rsaauth.js'
		);

		foreach ($javaScriptFiles as $file) {
			$template->loadJavascriptLib('sysext/rsaauth/' . $file);
		}
	}

	protected function includeHiddenFormFields(template &$template) {
		$template->form.= '
			<input type="hidden" name="' . $this->prefixId . '[publicExponent]" value="' . $this->rsa->getPublicExponent() . '" />
			<input type="hidden" name="' . $this->prefixId . '[publicKey]" value="' . $this->rsa->getPublicKey() . '" />
		';
	}

	protected function setSessionData() {
		$_SESSION[$this->prefixId] = array(
			'publicExponent'	=> $this->rsa->getPublicExponent(),
			'privateKey'		=> $this->rsa->getPrivateKey(),
		);
	}

	/**
	 * Determines whether public exponent and public key are available in session data.
	 *
	 * @return	boolean
	 */
	protected function isSessionDataAvailable() {
		return (isset($_SESSION[$this->prefixId]['publicExponent']) && isset($_SESSION[$this->prefixId]['privateKey']));
	}

	/**
	 * Gets the private key string that was stored on the session
	 *
	 * @return	string		Private key
	 */
	protected function getPrivateKeyFromSession() {
		return $_SESSION[$this->prefixId]['privateKey'];
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/class.tx_rsaauth_sv1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/class.tx_rsaauth_sv1.php']);
}

?>