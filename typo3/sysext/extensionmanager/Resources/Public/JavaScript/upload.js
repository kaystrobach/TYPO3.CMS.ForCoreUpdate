
jQuery(document).ready(function() {
	jQuery('.uploadExtension').not('.transformed').each(function(){
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).addClass('transformed');
		jQuery(this).click(function() {
			jQuery('.uploadForm').show();
			jQuery(this).hide();
			jQuery.ajax({
				url: jQuery(this).data('href'),
				dataType: 'html',
				success: function(data) {
					jQuery('.uploadForm').html(
						data
					);
					handleUploadForm();
				}
			});
		});
	});
});

function handleUploadForm() {
	jQuery('#typo3-extensionmanager-upload-target').on('load', function() {
		var ret = frames['typo3-extensionmanager-upload-target'].document.getElementsByTagName("body")[0].innerHTML;
		var data = eval("("+ret+")");
		if (data.success) {
			jQuery('.uploadForm').html(
				'<div class="success">Successfully uploaded ' + data.extension + '</div>'
			);
			window.setTimeout(restoreUploadStartView, 3000);
		}
	})
}

function restoreUploadStartView() {
	console.log('restore');
	jQuery('.uploadForm').fadeOut(1000);
	window.setTimeout(restoreUploadFormContent, 1000);
}

function restoreUploadFormContent() {
	jQuery('.uploadForm').html('Loading form...<span class="spinner"></span>')
	jQuery('.uploadExtension').fadeIn(1000);
}