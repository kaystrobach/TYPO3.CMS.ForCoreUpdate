/**
 * requirejs jquery plugin loader mechanism
 * this loader allows to specify a specific version of jQuery 
 * to be required BEFORE the plugin is included. 
 */
define({
	load: function (name, req, load, config) {
		var n = name.indexOf(":");

			// path / external URL to plugin that should be loaded
		var loadScript = name.substring(n+1, name.length-1).split('"')[1];


			// jQuery version that is needed
		var jQueryVersionName = name.substring(2, n-1);
		if(jQueryVersionName.match(/latest/)) {
			var jQueryObjectPrefix = "latest";
			jQueryVersionName = "jquery/jquery-latest";
		} else {
			 // generate version name from filename: jquery-1.5rc2 -> v15rc2
			var jQueryObjectPrefix = 'v' + jQueryVersionName.split("/").pop().split('-').pop().replace(/\./, '');
			jQueryVersionName = "jquery/" + jQueryVersionName;
		}
		jQueryObjectPrefix = 'jquery' + jQueryObjectPrefix;

		if (typeof TYPO3[jQueryObjectPrefix] === 'undefined') {
				// if jQuery version is undefined
			// console.log('loading jquery version ' + jQueryVersionName + ' because it is needed for plugin ' + loadScript);
			require(['requirejsPlugins/loadjQuery!' + jQueryVersionName], function() {
				jQuery = TYPO3[jQueryObjectPrefix];
			})
		} else {
				// store reference to requiered jQuery version to register jQuery Plugin
			jQuery = TYPO3[jQueryObjectPrefix];
		}

		req([loadScript], function (value) {

			// return - source loaded
			load(value);
		});

	}
});