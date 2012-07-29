jQuery(document).ready(function() {
	jQuery('#terTable').dataTable({
		"bJQueryUI":true,
		"bLengthChange": false,
		'iDisplayLength': 15,
		"bStateSave": false,
		"bInfo": false,
		"bPaginate": false,
		"bFilter": false,
		"fnDrawCallback": bindDownload
	});

	jQuery('#terSearchTable').dataTable({
		"sPaginationType":"full_numbers",
		"bJQueryUI":true,
		"bLengthChange": false,
		'iDisplayLength': 15,
		"bStateSave": false,
		"oLanguage": {
			"sSearch": "Filter results:"
		},
		"aaSorting": [],
		"fnDrawCallback": bindDownload
	});
	bindDownload();
});

function bindDownload() {
	jQuery('.download').not('.transformed').each(
		function(){
			jQuery(this).data('href', jQuery(this).attr('href'));
			jQuery(this).attr('href', 'javascript:void(0);');
			jQuery(this).addClass('transformed');
			jQuery(this).click(function() {
				jQuery('#typo3-extension-manager').mask();
				jQuery.ajax({
					url: jQuery(this).data('href'),
					dataType: 'json',
					success: getDependencies
				});
			})
		}
	);
}

function getDependencies(data) {
	if (data.dependencies.length) {
		TYPO3.Dialog.QuestionDialog({
			title: 'Dependencies',
			msg: data.message,
			url: data.url,
			fn: getResolveDependenciesAndInstallResult
		});
	} else {
		var button = 'yes';
		var dialog = new Array();
		var dummy = '';
		dialog['url'] = data.url;
		getResolveDependenciesAndInstallResult(button, dummy, dialog)
	}
}

function getResolveDependenciesAndInstallResult(button, dummy, dialog) {
	if (button == 'yes') {
		var newUrl = dialog.url;
		jQuery.ajax({
			url: newUrl,
			dataType: 'json',
			success: function (data) {
				jQuery('#typo3-extension-manager').unmask();
				var successMessage = 'Your installation of ' + data.extension + ' was successfull. <br />';
				successMessage += '<br /><h3>Log:</h3>';
				jQuery.each(data.result, function(index, value) {
					successMessage += 'Extensions ' + index + ':<br /><ul>';
					jQuery.each(value, function(extkey, extdata) {
						successMessage += '<li>' + extkey + '</li>';
					});
					successMessage += '</ul>';
				});
				TYPO3.Flashmessage.display(TYPO3.Severity.information, data.extension + ' installed.', successMessage, 15);
			}
		});
	} else {
		jQuery('#typo3-extension-manager').unmask();
	}
}