function bwp_enhance_form_fields($, $t) {
	var asterisk = ' <span class="text-danger">*</span>';

	// apply the enhancement globally or for a specific target
	$t = $t || $('body');

	$t.find('.bwp-label-required').each(function() {
		$(this).html($(this).text() + asterisk);
	});
}
