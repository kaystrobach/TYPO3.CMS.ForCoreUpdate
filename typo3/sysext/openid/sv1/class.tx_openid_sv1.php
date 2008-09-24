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

require_once(PATH_t3lib . 'class.t3lib_svbase.php');
$tx_openid_libPath = t3lib_extMgm::extPath('openid') . 'lib/php-openid/Auth/';
require_once($tx_openid_libPath . 'Consumer.php');
require_once($tx_openid_libPath . 'FileStore.php');
require_once($tx_openid_libPath . 'SReg.php');
require_once($tx_openid_libPath . 'PAPE.php');

/**
 * Service "OpenID Authentication" for the "openid" extension.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_openid
 */
class tx_openid_sv1 extends t3lib_svbase {
	public $prefixId = 'tx_openid_sv1';		// Same as class name
	public $scriptRelPath = 'sv1/class.tx_openid_sv1.php';	// Path to this script relative to the extension dir.
	public $extKey = 'openid';	// The extension key.

	/**
	 * Checks if service is available,. In case of this service we check that
	 * prerequesties for "PHP OpenID" libraries are fulfilled:
	 * - GMP or BCMATH PHP extensions are installed and functional
	 *
	 * @return	boolean	true if service is available
	 */
	public function init() {
		$available = false;
		if (extension_loaded('gmp')) {
			$available = is_callable('gmp_init');
		}
		elseif (extension_loaded('bcmath')) {
			$available = is_callable('bcadd');
		}
		return $available ? parent::init() : false;
	}

	/**
	 * Initializes authentication for this service.
	 *
	 * @param	string	$subType	Subtype for authentication (either "getUserFE" or "getUserBE")
	 * @param	array	$loginData	Login data submitted by user and preprocessed by t3lib/class.t3lib_userauth.php
	 * @param	array	$authInfo	Additional TYPO3 information for authentication services (unused here)
	 * @param	t3lib_userAuth	$pObj	Calling object
	 * @return	void
	 */
	public function initAuth($subType, array $loginData, array $authInfo, t3lib_userAuth &$pObj) {
		return;
	}

	/**
	 * This function returns authenticated user record back to the t3lib_userAuth.
	 * If user is not authenticated, it returns null.
	 *
	 * @return	mixed	User record (content of fe_users/be_users as appropriate for the current mode)
	 */
	public function getUser() {
		return null;
	}

	/**
	 * Creates OpenID Consumer object with a TYPO3-specific store. This function
	 * is almost identical to the example from the PHP OpenID library.
	 *
	 * @return	Auth_OpenID_Consumer	Consumer instance
	 */
	protected function getOpenIDConsumer() {
		// TODO Change this to a database-based store!
		$openIDStorePath = PATH_site . 'typo3temp/tx_openid';
		$openIDStore = new Auth_OpenID_FileStore($openIDStorePath);
		return new Auth_OpenID_Consumer($openIDStore);
	}

	/**
	 * Sends request to the OpenID server to authenticate the user with the
	 * given ID. This function is almost identical to the example from the PHP
	 * OpenID library. Due to the OpenID specification we cannot do a slient login.
	 * Sometimes we have to redirect to the OpenID provider web site so that
	 * user can enter his password there. In this case we will redirect and provide
	 * a return adress to the special script inside this directory, which will
	 * handle the result appropriately.
	 *
	 * @param	string	$openIDIdentifier	OpenID identifier provided by the user
	 * @return	boolean	false if there are problems setting up OpenID framework or connecting to the OpenID provider
	 */
	protected function sendOpenIDRequest($openIDIdentifier) {
		// Initialize OpenID client system, get the consumer
		$openIDConsumer = $this->getOpenIDConsumer();

		// Begin the OpenID authentication process
		$authenticationRequest = $openIDConsumer->begin($openIDIdentifier);
		 if (!$authenticationRequest) {
			// Not a valid OpenID. Since it can be some other ID, we just return
			// and let other service handle it.
			// TODO Log the problem
			return false;
		}
/*
		// We do not need to get user's mickname yet!
		$sregRequest = Auth_OpenID_SRegRequest::build(array('nickname'));
		if ($sregRequest) {
			$authenticationRequest->addExtension($sregRequest);
		}
*/
/*
		// We do not need any policies yet!
		 $policy_uris = $_GET['policies'];

		 $pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
		 if ($pape_request) {
			 $auth_request->addExtension($pape_request);
		 }
*/
		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.

		// For OpenID 1, we *should* send a redirect. For OpenID 2, use a Javascript
		// form to send a POST request to the server. Due to TYPO3 specifics, we
		// always send a redirect. This is allowed by OpenID specification.
		$redirectURL = $authenticationRequest->redirectURL(getTrustRoot(),
														getReturnTo());

		// If the redirect URL can't be built, return. We can only return.
		if (Auth_OpenID::isFailure($redirectURL)) {
			// TODO Log the problem
			return false;
		}

		// Send redirect.
		@ob_end_clean();
		header(t3lib_div::HTTP_STATUS_307);
		header('Location: ' . $redirectURL);
		// According to the HTTP specification we *should* produce a short note
		// saying what we do if we use HTTP status 307.
		// See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.8
		// TODO Should we localize this message? Does it make sense during authentication?
		echo 'Redirecting to the OpenID server for authentication. If your browser ' .
			'does not redirect, click <a href="' . $redirectURL . '">here</a> ' .
			'to go to the OpenID server manually.';
		exit;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/openid/sv1/class.tx_openid_sv1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/openid/sv1/class.tx_openid_sv1.php']);
}

?>