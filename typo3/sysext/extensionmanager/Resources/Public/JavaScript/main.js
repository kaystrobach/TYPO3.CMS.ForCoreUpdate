jQuery(document).ready(function() {
	var datatable = jQuery('#typo3-extension-list').dataTable({
		"sPaginationType":"full_numbers",
		"bJQueryUI":true,
		"bLengthChange": false,
		'iDisplayLength': 50,
		"bStateSave": true
	});

	var getVars = getUrlVars();

		// restore filter
	if(datatable.length && getVars['search']) {
		datatable.fnFilter(getVars['search']);
	}

	jQuery("#typo3-extension-configuration-forms ul").tabs("div.category");

});

function getUrlVars() {
	var vars = [], hash;
	var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	for(var i = 0; i < hashes.length; i++) {
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
	}
	return vars;
}