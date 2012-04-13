jQuery(document).ready(function() {
	if(jQuery('.ajax').length) {
		jQuery('#terTable').dataTable({
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": jQuery('.ajax').attr('href')
		})
	}
});
