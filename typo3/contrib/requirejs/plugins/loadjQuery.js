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
		var versionName = "v";
		if(name.match(/\latest/)) {
			versionName = "latest";
			name = "jQuery/jquery-latest";
		} else {
			//generate version name from filename: jquery-1.5 -> v15
			var h = name.split("/");
			h = h[h.length-1].match(/\d/g);
			for (i=0; i<h.length; i++) {
				versionName += h[i];
			}
		}

		req([name], function (value) {
			
			//if global var TYPO3 doesn't exists, define it
			if (typeof TYPO3 === 'undefined') {
				TYPO3 = {};
			}

			if (typeof TYPO3[versionName] === 'undefined') {
				//store jquery in globel var TYPO3
				TYPO3[versionName] = jQuery.noConflict(true);
			}

			//return - source loaded
			load(value);
		});
	}
});