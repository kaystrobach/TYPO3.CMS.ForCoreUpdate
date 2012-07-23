jQuery(document).ready(function() {
	manageExtensionListing();

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

function manageExtensionListing() {
	datatable = jQuery('#typo3-extension-list').dataTable({
		"sPaginationType":"full_numbers",
		"bJQueryUI":true,
		"bLengthChange":false,
		'iDisplayLength':50,
		"bStateSave":true
	});

	var getVars = getUrlVars();

	// restore filter
	if(datatable.length && getVars['search']) {
		datatable.fnFilter(getVars['search']);
	}

	jQuery('.removeExtension').each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).addClass('transformed');
		jQuery(this).click(function() {
			if (jQuery(this).hasClass('isLoadedWarning')) {
				TYPO3.Dialog.QuestionDialog({
					title: 'Extension Removal',
					msg: 'The extension is currently installed. Uninstall extension?',
					url: jQuery(this).data('href'),
					fn: function(button, dummy, dialog) {
						if (button == 'yes') {
							confirmDeletionAndDelete(dialog.url)
						}
					}
				});
			} else {
				confirmDeletionAndDelete(jQuery(this).data('href'));
			}
		})
	});

	jQuery('.updateAvailable').each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).addClass('transformed');
		jQuery(this).click(function() {
			jQuery.ajax({
				url: jQuery(this).data('href'),
				dataType: 'json',
				success: function(data) {
					var message = '<h1>Update?</h1>';
					message += '<h2>Update Comments:</h2>';
					jQuery.each(data.updateComments, function(version, comment) {
						message += '<h3>' + version + '</h3>';
						message += '<div>' + comment + '</div>';
					});

					TYPO3.Dialog.QuestionDialog({
						title: 'Version Comments',
						msg: message,
						width: 600,
						url: data.url,
						fn: function(button, dummy, dialog) {
							if (button == 'yes') {
								jQuery.ajax({
									url: dialog.url,
									dataType: 'json',
									success: function(data) {
										TYPO3.Flashmessage.display(TYPO3.Severity.information, 'Extension Update', data.extension + ' updated!', 15);
									}
								});
							}
						}
					});
				}
			});
		});
	})
}


function confirmDeletionAndDelete(url) {
	TYPO3.Dialog.QuestionDialog({
		title: 'Extension Removal',
		msg: 'Are you sure you want to remove the extension?',
		url: url,
		fn: function(button, dummy, dialog) {
			if (button == 'yes') {
				jQuery.ajax({
					url: dialog.url,
					dataType: 'json',
					success: function(data) {
						if (data.success) {
							datatable.fnDeleteRow(datatable.fnGetPosition(document.getElementById(data.extension)));
						} else {
							TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Extension Removal', data.message, 15);
						}
					}
				});
			}
		}
	});
}