/***************************************************************
*
*  javascript functions regarding the TYPO3 wrapper
*  for the javascript library "prototype".
*
*  Copyright notice
*
*  (c) 2008-2011 Benjamin Mack <benni@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 backend provided by
*  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in /typo3/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
*
***************************************************************/

var T3AJAX = {};
T3AJAX.showError = function(xhr, json) {
	if (typeof xhr.responseText !== undefined && xhr.responseText) {
		if (typeof Ext.MessageBox !== undefined) {
			Ext.MessageBox.alert('TYPO3', xhr.responseText);
		}
		else {
			alert(xhr.responseText);
		}
	}
};

// common storage and global object, could later hold more information about the current user etc.
var TYPO3 = TYPO3 || {};
TYPO3 = Ext.apply(TYPO3, {
	// store instances that only should be running once
	_instances: {},
	getInstance: function(className) {
		return TYPO3._instances[className] || false;
	},
	addInstance: function(className, instance) {
		TYPO3._instances[className] = instance;
		return instance;
	},

	helpers: {
		// creates an array by splitting a string into parts, taking a delimiter
		split: function(str, delim) {
			var res = [];
			while (str.indexOf(delim) > 0) {
				res.push(str.substr(0, str.indexOf(delim)));
				str = str.substr(str.indexOf(delim) + delim.length);
			}
			return res;
		}
	}
});
