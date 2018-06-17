var jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Action to reload invoice info
	jq(document).on("invoice.info.reload", function() {
		// Reload payments container
		jq("#invoiceInfoOuterContainer").trigger("reload");
	});
	
	// Action to reload customer info
	jq(document).on("customer.info.reload", function() {
		// Reload customer info
		jq("#customerDetailsOuterContainer").trigger("reload");
	});
	
	// Action to reload payments status
	jq(document).on("invoice.payment_status.reload", function() {
		// Reload payments container
		jq("#paymentsOuterContainer").trigger("reload");
	});
	
	// Action to reload product list
	jq(document).on("product.list.reload", function() {
		// Reload products container
		jq("#invoiceProductListOuterContainer").trigger("reload");
	});
	
	// Action to close the invoice
	jq(document).on("invoice.close", function() {
		// Reload payments container
		jq(".retailCashierApplication .close_button").trigger("reload");
	});
});