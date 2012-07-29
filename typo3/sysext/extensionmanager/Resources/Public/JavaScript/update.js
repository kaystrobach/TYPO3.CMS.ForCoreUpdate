jQuery(document).ready(function() {
	jQuery('.updateFromTer a').each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).click(function() {
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
	jQuery.ajax({
		url: url,
		dataType: 'json',
		success: function(data) {
			jQuery('.updateFromTer .spinner').hide();
			jQuery('.updateFromTer .text').html(
				data.message
			);
		}
	});
}