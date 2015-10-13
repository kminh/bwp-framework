jQuery(document).ready(function($){
	/* Paypal form */
	$('.paypal-form select[name="amount"]').change(function() {
		if ($(this).val() == '100.00') {
			$(this).hide();

			$('.paypal-alternate-input')
				.append('<input type="text" style="padding: 3px; width: 70px; text-align: right; line-height: 1;" name="amount" value="15.00" /> <code>$</code>')
				.show();
		}
	});
});
