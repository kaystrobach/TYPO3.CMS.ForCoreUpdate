jQuery(document).ready(function() {
	jQuery('#terTable').dataTable({
		"bJQueryUI":true,
		"bLengthChange": false,
		'iDisplayLength': 50,
		"bStateSave": true,
		"bInfo": false,
		"bPaginate": false,
		"bFilter": false
	});

	jQuery('.download').each(
		function(){
			jQuery(this).data('href', jQuery(this).attr('href'));
			jQuery(this).attr('href', 'javascript:void(0);');
			jQuery(this).click(function() {
				jQuery.ajax({
					url: jQuery(this).data('href'),
					dataType: 'json',
					success: function(data) {
						var message = 'The following dependencies have to be resolved before installation:<br /><br />';
						jQuery.each(data.dependencies, function(index, value) {
							message += 'Extensions to ' + index + '<br />';
							jQuery.each(value, function(extkey) {
								message += extkey + '<br />';
							})
						});
						TYPO3.Dialog.QuestionDialog({
							title: 'Dependencies',
							msg: message + '<br />Shall these dependencies be resolved automatically before installing?<br />',
							url: data.url,
							fn: function (button, dummy, dialog) {
								if (button == 'yes') {
									var newUrl = dialog.url;
									jQuery.ajax({
										url: newUrl,
										dataType: 'json',
										success: function (data) {
											console.log(data);
											var successMessage = 'Your installation of ' + data.extension + ' was successfull. <br />';
											successMessage += '<br /><h3>Log:</h3>';
											jQuery.each(data.result, function(index, value) {
												successMessage += 'Extensions ' + index + ':<br /><ul>';
												jQuery.each(value, function(extkey, extdata) {
													successMessage += '<li>' + extkey + '</li>';
												})
												successMessage += '</ul>';
											});
											TYPO3.Dialog.InformationDialog({
												title: data.extension + ' installed.',
												msg: successMessage
											})
										}
									});
								}
							}
						});
					}
				});
			})
		}
	);
});
