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

var RSAAuth = {
	encrypt: function() {
		var username = document.loginform.username;
		var password = document.loginform.p_field;
		var publicKey = document.loginform['tx_rsaauth_sv1[publicKey]'];
		var publicExponent = document.loginform['tx_rsaauth_sv1[publicExponent]'];

		var rsa = new RSAKey();
		rsa.setPublic(publicKey.value, publicExponent.value);

		var passwordEncrypted = rsa.encrypt(password.value);

			// Remove all plaintext-data
		password.value = '';
		publicKey.value = '';
		publicExponent.value = '';

		if(passwordEncrypted) {
			document.loginform.userident.value = linebrk(hex2b64(passwordEncrypted), 64);
		}
	}
};