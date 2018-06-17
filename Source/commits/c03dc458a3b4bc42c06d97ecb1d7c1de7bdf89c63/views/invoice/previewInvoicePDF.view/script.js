jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Close dialog
	jq(document).on("click", ".previewInvoiceDialog .close_button", function(ev) {
		jq(this).trigger("dispose");
	});
});