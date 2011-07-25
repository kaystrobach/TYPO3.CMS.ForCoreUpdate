<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains a session management class
 *
 * @package TYPO3
 * @author Popy <popy.dev@gmail.com>
 */

/*
CREATE TABLE sessions (
  ses_id varchar(32) DEFAULT '' NOT NULL,
  ses_hashlock int(11) DEFAULT '0' NOT NULL,
  ses_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  ses_data longtext,
  PRIMARY KEY (ses_id,ses_hashlock)
);
*/

class t3lib_session {
	/**
	 * Session cookie name
	 * @access protected
	 * @var string
	 */
	protected $cookieName = '';

	/**
	 * Session cookie lifetime (seconds, 0 means "until browser is closed")
	 * @access protected
	 * @var int
	 */
	protected $cookieLifeTime = 0;

	/**
	 * Will prevent the setting of the session cookie
	 * @access protected
	 * @var bool
	 */
	protected $dontSetCookie = false;

	/**
	 * Session lifetime
	 * @access protected
	 * @var int
	 */
	protected $sessionLifeTime = 0;

	/**
	 * Current session's id
	 * @access protected
	 * @var string
	 */
	protected $sessionId = '';

	/**
	 * Session storage table
	 * @access protected
	 * @var string
	 */
	protected $sessionTable = 'sessions';

	/**
	 * Session data
	 * @access public
	 * @var array
	 */
	public $sessionData = array();

	/**
	 * Is set to true if a session was found during script init
	 * @access protected
	 * @var bool
	 */
	protected $sessionDidExist = false;


	/**
	 * getTypo3CookieDomain method's cache
	 * @access protected
	 * @var string
	 */
	protected static $typo3CookieDomain = null;

	/**
	 * Session init
	 * Read cookie & fetch session
	 *
	 * @access public
	 * @return void 
	 */
	public function initialize() {
		$this->readSessionCookie();

		if ($this->sessionId) {
			$this->fetchSession();
		}
	}

	/**
	 * Session saving : cookie is sent to browser and session is saved to database
	 *
	 * Note : this method should be called just after the content rendering (to save every session
	 *    update without having to be called multiple times), and just before the content outputting
	 *    (in order to be able to send the cookie)
	 *
	 * @access public
	 * @return void 
	 */
	public function save() {
		if (empty($this->sessionData)) {
			// Session is empty
			if ($this->sessionDidExist) {
				// We had a session record : delete it
				$this->deleteSession();
			}

			// Ensure the browser will not send the cookie again
			//   (which would lead to an useless SELECT query)
			$this->sessionId = null;
			$this->writeSessionCookie();
		} else {
			$this->refreshSessionCookie();

			if ($this->sessionDidExist) {
				$this->updateSession();
			} else {
				$this->createSession();
			}
		}
	}

	/**
	 * Destroy session
	 *
	 * Session data is emptied. The session record will be deleted if nothing is stored in the 
	 *    session, or will store the new session data
	 *
	 * @access public
	 * @return void 
	 */
	public function destroy() {
		$this->sessionData = array();
	}

	/**
	 * Fetch user's session and check it's lifetime
	 *
	 * @access protected
	 * @return void 
	 */
	protected function fetchSession() {
		$session = reset($GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->sessionTable,
			$this->getSessionWhereClause()
		));

		if ($session) {
			$this->sessionDidExist = true;

			// Check session lifetime
			if ($this->sessionLifeTime
				&& time() > $this->sessionLifeTime + intval($session['ses_tstamp'])
			) {
				// Expired session : the session data is dropped, but the current session record will
				//    be re-used (or deleted), so it won't need an additionnal delete query
				$session = false;
			}
		}

		if ($session) {
			$this->sessionData = unserialize($session['ses_data']);
		}
	}

	/**
	 * Save session data in a new session record
	 *
	 * @access protected
	 * @return void
	 */
	protected function createSession() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->sessionTable, array(
			'ses_id' => $this->sessionId,
			'ses_hashlock' => $this->getUserHashLock(),
			'ses_tstamp' => time(),
			'ses_data' => serialize($this->sessionData),
		));
	}

	/**
	 * Save session data in currently used session record
	 *
	 * @access public
	 * @return void 
	 */
	protected function updateSession() {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$this->sessionTable,
			$this->getSessionWhereClause(),
			array(
				'ses_tstamp' => time(),
				'ses_data' => serialize($this->sessionData),
			)
		);
	}

	/**
	 * Delete current session record
	 *
	 * @access protected
	 * @return void 
	 */
	protected function deleteSession() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->sessionTable,
			$this->getSessionWhereClause()
		);
	}

	/**
	 * Build session where clause
	 *
	 * @access protected
	 * @return string 
	 */
	protected function getSessionWhereClause() {
		return 'ses_id = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->sessionId, $this->sessionTable)
			. ' AND ses_hashlock = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->getUserHashLock(), $this->sessionTable);
	}

	/**
	 * Creates hash integer to lock user to. Depends on configured keywords
	 *
	 * @return int
	 */
	protected function getUserHashLock() {
		$hashStr = t3lib_div::getIndpEnv('HTTP_USER_AGENT');

		return t3lib_div::md5int($hashStr);
	}

	/*************************
	 *
	 * SESSION FUNCTIONS
	 *
	 *************************/

	/**
	 * Create or refresh session cookie
	 *
	 * @access protected
	 * @return void 
	 */
	protected function refreshSessionCookie() {
		if (!$this->sessionId) {
			$this->createNewSessionId();
			$this->writeSessionCookie();

			if ($this->writeDevLog) {
				t3lib_div::devLog('Set new Cookie: '.$this->sessionId, 't3lib_session');
			}
		} elseif ($this->isRefreshTimeBasedCookie()) {
			$this->writeSessionCookie();

			if ($this->writeDevLog) {
				t3lib_div::devLog('Update Cookie: '.$this->sessionId, 't3lib_session');
			}
		}
	}

	/**
	 * Get the value of the session cookie
	 *
	 * Note : imported from t3lib_userAuth. Maybe the original getCookie method should be moved in
	 *    t3lib_div/utility and used here ?
	 *
	 * Uses HTTP_COOKIE, if available, to avoid a IE8 bug where multiple
	 * cookies with the same name might be returned if the user accessed
	 * the site without "www." first and switched to "www." later:
	 *   Cookie: fe_typo_user=AAA; fe_typo_user=BBB
	 * In this case PHP will set _COOKIE as the first cookie, when we
	 * would need the last one (which is what this function then returns).
	 *
	 * @return void
	 */
	protected function readSessionCookie() {
		$this->sessionId = null;

		if (isset($_SERVER['HTTP_COOKIE'])) {
			$cookies = t3lib_div::trimExplode(';', $_SERVER['HTTP_COOKIE']);
			foreach ($cookies as $cookie) {
				list ($name, $value) = t3lib_div::trimExplode('=', $cookie);
				if ($name == $this->cookieName) {
					// Use the last one
					$this->sessionId = stripslashes($value);
				}
			}
		} elseif (isset($_COOKIE[$this->cookieName])) {
			// Fallback if there is no HTTP_COOKIE, use original method:
			$this->sessionId = stripslashes($_COOKIE[$this->cookieName]);
		}
	}

	/**
	 * Send the cookie to user's browser
	 *
	 * @access protected
	 * @return void 
	 */
	protected function writeSessionCookie() {
		$cookieDomain = $this->getTypo3CookieDomain();
		$lifeTime = $this->cookieLifeTime;
		$path = '/';

		if ($lifeTime) {
			$lifeTime = time() + $lifeTime;
		}
		if (!$cookieDomain) {
			$path = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		}

		if (!$this->dontSetCookie) {
			SetCookie($this->cookieName, $this->sessionId, $lifeTime, $path, $cookieDomain);
		}
	}

	/**
	 * Generate a new session id
	 *
	 * @access protected
	 * @return string
	 */
	protected function createNewSessionId() {
		$this->sessionId = t3lib_div::getRandomHexString(32);
	}

	/**
	 * Get cookieDomain from typo3 configuration
	 *
	 * Note : raw copy from t3lib_userauth
	 *
	 * @access public
	 * @return string / null
	 */
	public static function getTypo3CookieDomain() {
		global $TYPO3_CONF_VARS;

		if (is_null(self::$typo3CookieDomain)
			&& isset($TYPO3_CONF_VARS['SYS']['cookieDomain'])
			&& $TYPO3_CONF_VARS['SYS']['cookieDomain']
		) {
			self::$typo3CookieDomain = false;

			if ($TYPO3_CONF_VARS['SYS']['cookieDomain']{0} == '/') {
				$matchCnt = @preg_match(
					$TYPO3_CONF_VARS['SYS']['cookieDomain'],
					t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'),
					$match
				);
				if ($matchCnt === false) {
					t3lib_div::sysLog('The regular expression of $TYPO3_CONF_VARS[SYS][cookieDomain] contains errors. The session is not shared across sub-domains.', 'Core', 3);
				} elseif ($matchCnt) {
					self::$typo3CookieDomain = $match[0];
				}
			} else {
				self::$typo3CookieDomain = $TYPO3_CONF_VARS['SYS']['cookieDomain'];
			}
		}

		return self::$typo3CookieDomain;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_session.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_session.php']);
}
?>