var jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Action to reload payments
	jq(document).on("invoice.payments.list.reload", function() {
		// Reload payments container
		jq("#paymentListOuterContainer").trigger("reload");
	});
});