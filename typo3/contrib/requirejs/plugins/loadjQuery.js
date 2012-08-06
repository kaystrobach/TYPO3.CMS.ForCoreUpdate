/*
* requirejs plugin 
* module loads a jQuery version and appends it to the global var TYPO3s
*
* can be called like this: 
* require(['pathToRequirejsPlugins/loadjQuery!pathTojQueryFile'], function() {
*  //required jquery version can be used
* })
*/
define({
	load: function (name, req, load, config) {
		if(name.match(/latest/)) {
			var jQueryObjectPrefix = "latest";
			name = "jquery/jquery-latest";
		} else {
			 // generate version name from filename: jquery-1.5rc2 -> v15rc2
			var jQueryObjectPrefix = 'v' + name.split("/").pop().split('-').pop().replace(/\./, '');
		}
		jQueryObjectPrefix = 'jquery' + jQueryObjectPrefix;

		req([name], function (value) {
			
			// if global var TYPO3 doesn't exists, define it
			if (typeof TYPO3 === 'undefined') {
				TYPO3 = {};
			}

			// store jquery in globel var TYPO3
			// if (typeof TYPO3[jQueryObjectPrefix] === 'undefined') {
			TYPO3[jQueryObjectPrefix] = jQuery.noConflict(true);
			//}

			//return - source loaded
			load(value);
		});
	}
});