jQuery(document).ready(function() {
	jQuery('.updateFromTer a').each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).click(function() {
			jQuery('.updateFromTer .spinner').show();
			jQuery.ajax({
				url: jQuery(this).data('href'),
				dataType: 'json',
				success: function(data) {
					jQuery('.updateFromTer .spinner').hide();
					jQuery('.updateFromTer .text').html(
						data.message
					);
				}
			});
		});
	});
	jQuery('.updateFromTer a').trigger('click');
});
