<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Michael Stucki <michael@typo3.org>
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


require_once(t3lib_extMgm::extPath('rsaauth') . 'res/class.tx_rsaauth.php');


class tx_rsaauth_php extends tx_rsaauth {
	protected $privateKey;
	protected $opensslConfiguration = array();

	/**
	 * Destructur which cleans up unused keys.
	 */
	public function __destruct() {
		openssl_free_key($this->privateKey);
	}

	/**
	 * Generates new private key
	 *
	 * @return	void
	 */
	public function generatePrivateKey() {
		$this->privateKey = openssl_pkey_new($this->opensslConfiguration);
		$this->exportPrivateKey();
		$this->exportPublicKey();
	}

	/**
	 * Gets the private key as string.
	 *
	 * @return	string		The private key as string
	 */
	public function getPrivateKey() {
		return $this->privateKeyString;
	}

	/**
	 * Read existing private key (sent as string)
	 *
	 * @param	string		$key: String representation of private key (comes from local session)
	 * @return	void
	 */
	public function setPrivateKey($key)	{
		$this->privateKey = openssl_get_privatekey($key);
		$this->privateKeyString = $key;
		$this->exportPublicKey();
	}

	/**
	 * Gets the public exponent
	 *
	 * @return	integer		The public exponent
	 */
	public function getPublicExponent() {
		return $this->publicExponent;
	}

	/**
	 * Gets the public key as string.
	 *
	 * @return	string		Public key
	 */
	public function getPublicKey() {
		return $this->publicKeyString;
	}

	/**
	 * Decode incoming cipher string using private key
	 *
	 * @param	string		$data: Incoming data (encrypted string)
	 * @return	mixed		Decoded string or false if something went wrong
	 */
	public function decryptWithPrivateKey($data) {
		if (!openssl_private_decrypt($data, $decrypted, $this->privateKey)) {
			$decrypted = false;
		}
		return $decrypted;
	}

	/**
	 * Export the internal private key into a string so it can be stored in the database
	 *
	 * @return	void
	 */
	protected function exportPrivateKey() {
		openssl_pkey_export($this->privateKey, $this->privateKeyString);
	}

	/**
	 * Export the modulus using a tricky set of commands - this is much easier with the openssl command, so maybe anybody knows a better way for doing this?
	 *
	 * @return	void
	 */
	protected function exportPublicKey() {
		$csr = openssl_csr_new($dn=array(), $this->private_key_handler, $configargs=array());
		openssl_csr_export($csr, $str, false);	// Export CSR

			// Cut off everything but the Modulus data
		$str = preg_replace('/.*Modulus.*?\n(.*)Exponent:.*/ms', '$1', $str);
		$str = str_replace(' ','',$str);
		$str = str_replace(chr(10),'',$str);

			// Remove first element (TODO: what's the meaning of it?), remove all colons, and convert the rest into upper-case
		$modArr = explode(':',$str);
		unset($modArr[0]);
		$modulus = '';
		foreach ($modArr as $i) {
			$modulus.= strtoupper($i);
		}

		$this->publicKeyString = $modulus;
	}
}
?>