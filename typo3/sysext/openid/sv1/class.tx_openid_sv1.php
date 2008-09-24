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
 *
 *
 *   54: class tx_openid_sv1 extends t3lib_svbase
 *   89:     public function init()
 *  111:     public function initAuth($subType, array $loginData, array $authInfo, t3lib_userAuth &$pObj)
 *  131:     public function getUser()
 *  168:     public function authUser(array $userRecord)
 *  209:     protected function includePHPOpenIDLibrary()
 *  231:     protected function fetchUserRecord($openIDIdentifier)
 *  249:     protected function getOpenIDConsumer()
 *  272:     protected function sendOpenIDRequest()
 *  340:     protected function getReturnURL()
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib . 'class.t3lib_svbase.php');

/**
 * Service "OpenID Authentication" for the "openid" extension.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_openid
 */
class tx_openid_sv1 extends t3lib_svbase {
	/** Class name */
	public $prefixId = 'tx_openid_sv1';		// Same as class name

	/** Path to this script relative to the extension directory */
	public $scriptRelPath = 'sv1/class.tx_openid_sv1.php';

	/** The extension key */
	public $extKey = 'openid';

	/** Login data as passed to initAuth() */
	protected $loginData = array();

	/**
	 * Additional authentication information provided by t3lub_userAuth. We use
	 * it to decided what database table contains user records.
	 */
	protected $authInfo = array();

	/**
	 * OpenID response object. It is initialized when OpenID provider returns
	 * with success/failure response to us.
	 *
	 * @var	Auth_OpenID_ConsumerResponse
	 */
	protected $openIDResponse = null;

	/**
	 * Checks if service is available,. In case of this service we check that
	 * prerequesties for "PHP OpenID" libraries are fulfilled:
	 * - GMP or BCMATH PHP extensions are installed and functional
	 * - set_include_path() PHP function is available
	 *
	 * @return	boolean		true if service is available
	 */
	public function init() {
		$available = false;
		if (extension_loaded('gmp')) {
			$available = is_callable('gmp_init');
		}
		elseif (extension_loaded('bcmath')) {
			$available = is_callable('bcadd');
		}
		// We also need set_include_path() PHP function
		$available &= is_callable('set_include_path');
		return $available ? parent::init() : false;
	}

	/**
	 * Initializes authentication for this service.
	 *
	 * @param	string		$subType	Subtype for authentication (either "getUserFE" or "getUserBE")
	 * @param	array		$loginData	Login data submitted by user and preprocessed by t3lib/class.t3lib_userauth.php
	 * @param	array		$authInfo	Additional TYPO3 information for authentication services (unused here)
	 * @param	t3lib_userAuth		$pObj	Calling object
	 * @return	void
	 */
	public function initAuth($subType, array $loginData, array $authInfo, t3lib_userAuth &$pObj) {
		// Store login and authetication data
		$this->loginData = $loginData;
		$this->authInfo = $authInfo;
		// If we are here after authentication by the OpenID server, get its response.
		if (t3lib_div::GPvar('tx_openid_mode') == 'finish' && $this->openIDResponse == null) {
			$this->includePHPOpenIDLibrary();
			$openIDConsumer = $this->getOpenIDConsumer();
			$this->openIDResponse = $openIDConsumer->complete($this->getReturnURL());
		}
	}

	/**
	 * This function returns user record back to the t3lib_userAuth. it does not
	 * mean that user is authenticated, it means only that user is found. This
	 * function makes sure that user cannot be authenticated by any other service
	 * if user tries to use OpenID to authenticate.
	 *
	 * @return	mixed		User record (content of fe_users/be_users as appropriate for the current mode)
	 */
	public function getUser() {
		$userRecord = null;
		if ($this->loginData['status'] == 'login') {
			if ($this->openIDResponse instanceof Auth_OpenID_ConsumerResponse) {
				// We are running inside the OpenID return script
				// Note: we cannot use $this->openIDResponse->getDisplayIdentifier()
				// because it may return a different identifier. For example,
				// LiveJournal server converts all underscore characters in the
				// original identfier to dashes.
				if ($this->openIDResponse->status == Auth_OpenID_SUCCESS) {
					$claimedOpenIDIdentifier = t3lib_div::GPvar('tx_openid_claimed');
					if ($claimedOpenIDIdentifier) {
						$userRecord = $this->fetchUserRecord($claimedOpenIDIdentifier);
					}
				}
			} else {
				// Here if user just started authentication
				$userRecord = $this->fetchUserRecord($this->loginData['uname']);
			}
			// The above function will return user record from the OpenID. It means that
			// user actually tried to authenticate using his OpenID. In this case
			// we must change the password in the record to a long random string so
			// that this user cannot be authenticated with other service.
			if (is_array($userRecord)) {
				$userRecord[$this->authInfo['db_user']['userident_column']] = uniqid($this->prefixId . chr(10), true);
			}
		}
		return $userRecord;
	}

	/**
	 * Authenticates user using OpenID.
	 *
	 * @param	array		$userRecord	User record
	 * @return	int		Code that shows if user is really authenticated.
	 * @see	t3lib_userAuth::checkAuthentication()
	 */
	public function authUser(array $userRecord) {
		$result = 0;	// 0 means authentication failure

		if ($userRecord['tx_openid_openid'] == '') {
			// If user does not have OpenID, let other services to try (code 100)
			$result = 100;
		} else {
			// Check if user is identified by the OpenID
			if ($this->openIDResponse instanceof Auth_OpenID_ConsumerResponse) {
				// If we have a response, it means OpenID server tried to authenticate
				// the user. Now we just look what is the status and provide
				// corresponding response to the caller
				if ($this->openIDResponse->status == Auth_OpenID_SUCCESS) {
					// Success (code 200)
					$result = 200;
				}
			} else {
				// We may need to send a request to the OpenID server.
				// Check if the user identifier looks like OpenID user identifier first.
				// Prevent PHP warning in case if identifiers is not an OpenID identifier
				// (not an URL).
				$urlParts = @parse_url($this->loginData['uname']);
				if (is_array($urlParts) && $urlParts['scheme'] != '' && $urlParts['host']) {
					// Yes, this looks like a good OpenID. Ask OpenID server (should not return)
					$this->sendOpenIDRequest();
				}
				// If we are here, it means we have a valid OpenID but failed to
				// contact the server. We stop authentication process.
				// Alternatively it may mean that OpenID format is not correct.
				// In both cases we return code 0 (complete failure)
			}
		}

		return $result;
	}

	/**
	 * Includes necessary files for the PHP OpenID library
	 *
	 * @return	void
	 */
	protected function includePHPOpenIDLibrary() {
		// PHP OpenID libraries requires adjustments of path settings. This
		$oldIncludePath = get_include_path();
		$phpOpenIDLibPath = t3lib_extMgm::extPath('openid') . 'lib/php-openid';
		@set_include_path($phpOpenIDLibPath . PATH_SEPARATOR .
						$phpOpenIDLibPath . '/Auth' . PATH_SEPARATOR .
						$oldIncludePath);

		// Include files
		require_once($phpOpenIDLibPath . '/Auth/OpenID/Consumer.php');
		require_once($phpOpenIDLibPath . '/Auth/OpenID/FileStore.php');

		// Restore path
		@set_include_path($oldIncludePath);

		if (!is_array($_SESSION)) {
			// Yadis requires session but session is not initialized when
			// processing Backend authentication
			@session_start();
		}
	}

	/**
	 * Fetches user record for the user with the OpenID provided by the user
	 *
	 * @param	string		$openIDIdentifier	OpenID identifier to search for
	 * @return	array		Database fields from the table that corresponds to the current login mode (FE/BE)
	 */
	protected function fetchUserRecord($openIDIdentifier) {
		$record = null;
		if ($openIDIdentifier) {
			list($record) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
				$this->authInfo['db_user']['table'],
				'tx_openid_openid=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($openIDIdentifier, $this->authInfo['db_user']['table']) .
					$this->authInfo['db_user']['check_pid_clause'] .
					$this->authInfo['db_user']['enable_clause']);
		}
		return $record;
	}

	/**
	 * Creates OpenID Consumer object with a TYPO3-specific store. This function
	 * is almost identical to the example from the PHP OpenID library.
	 *
	 * @return	Auth_OpenID_Consumer		Consumer instance
	 */
	protected function getOpenIDConsumer() {
		// TODO Change this to a TYPO3-specific database-based store! Add a class
		// for it. File-based store is ineffective and insecure. After changing
		// get rid of the FileStore include in includePHPOpenIDLibrary()
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
	 * This function does not return on success. If it returns, it means something
	 * went totally wrong with OpenID.
	 *
	 * @return	void
	 */
	protected function sendOpenIDRequest() {
		$this->includePHPOpenIDLibrary();

		$openIDIdentifier = $this->loginData['uname'];

		// Initialize OpenID client system, get the consumer
		$openIDConsumer = $this->getOpenIDConsumer();

		// Begin the OpenID authentication process
		$authenticationRequest = $openIDConsumer->begin($openIDIdentifier);
		if (!$authenticationRequest) {
			// Not a valid OpenID. Since it can be some other ID, we just return
			// and let other service handle it.
			// TODO Log the problem
			return;
		}

		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.

		// For OpenID version 1, we *should* send a redirect. For OpenID version 2,
		// we should use a Javascript form to send a POST request to the server.
		$returnURL = $this->getReturnURL();
		$trustedRoot = t3lib_div::getIndpEnv('TYPO3_SITE_URL');

	    if ($authenticationRequest->shouldSendRedirect()) {
			$redirectURL = $authenticationRequest->redirectURL($trustedRoot, $returnURL);

			// If the redirect URL can't be built, return. We can only return.
			if (Auth_OpenID::isFailure($redirectURL)) {
				// TODO Log the problem
				return;
			}

			// Send redirect. We use 303 code because it allows to redirect POST
			// requests without resending the form. This is exactly what we need here.
			// See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.4
			@ob_end_clean();
			header(t3lib_div::HTTP_STATUS_303);
			header('Location: ' . $redirectURL);
		}
		else {
			$formHtml = $authenticationRequest->htmlMarkup($trustedRoot,
							$returnURL, false, array('id' => 'openid_message'));

			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if (Auth_OpenID::isFailure($formHtml)) {
				// Form markup cannot be generated
				// TODO Log problem
				return;
			} else {
				@ob_end_clean();
				echo $formHtml;
			}
		}
		// If we reached this point, we must not return!
		exit;
	}

	/**
	 * Creates return URL for the OpenID server. When a user is authenticated by
	 * the OpenID server, the user will be sent to this URL to complete
	 * authentication process with the current site. We send it to our script.
	 *
	 * @return	string		Return URL
	 */
	protected function getReturnURL() {
		if ($this->authInfo['loginType'] == 'FE') {
			// We will use eID to send user back, create session data and
			// return to the calling page.
			// Notice: 'pid' and 'logintype' parameter names cannot be changed!
			// They are essential for FE user authentication.
			$returnURL = 'index.php?eID=tx_openid&' .
						'pid=' . $this->authInfo['db_user']['checkPidList'] . '&' .
						'logintype=login&';
		}
		else {
			// In the Backend we will use dedicated script to create session.
			// It is much easier for the Backend to manage users.
			// Notice: 'login_status' parameter name cannot be changed!
			// It is essential for BE user authentication.
			$returnURL = '/typo3/sysext/' . $this->extKey . '/class.tx_openid_return.php?login_status=login&';
		}
		if (t3lib_div::GPvar('tx_openid_mode') == 'finish') {
			$requestURL = t3lib_div::GPvar('tx_openid_location');
			$claimedIdentifier = t3lib_div::GPvar('tx_openid_claimed');
		} else {
			$requestURL = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
			$claimedIdentifier = $this->loginData['uname'];
		}
		$returnURL .= 'tx_openid_location=' . rawurlencode($requestURL) . '&' .
						'tx_openid_mode=finish&' .
						'tx_openid_claimed=' . rawurlencode($claimedIdentifier);
		return t3lib_div::locationHeaderUrl($returnURL);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/openid/sv1/class.tx_openid_sv1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/openid/sv1/class.tx_openid_sv1.php']);
}

?>