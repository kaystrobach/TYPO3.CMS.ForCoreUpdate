jQuery(document).ready(function() {
	jQuery('.updateFromTer a').each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).click(function() {
				// force update on click
			updateFromTer(jQuery(this).data('href'), 1);
		});
		updateFromTer(jQuery(this).data('href'), 0);
	});
});

function updateFromTer(url, forceUpdate) {
	var url = url;
	if (forceUpdate == 1) {
		url = url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1'
	}
	jQuery('.updateFromTer .spinner').show();
	jQuery('.f3-widget-paginator').hide();
	jQuery('#terTable').mask();
	jQuery.ajax({
		url: url,
		dataType: 'json',
		success: function(data) {
			jQuery('.updateFromTer .spinner').hide();
			jQuery('.f3-widget-paginator').show();
			jQuery('#terTable').unmask();
			if (data.errorMessage.length) {
				TYPO3.Flashmessage.display(TYPO3.Severity.warning, 'Update Extension List', data.errorMessage, 10);
			}
			jQuery('.updateFromTer .text').html(
				data.message
			);
		}
	});
}