var jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Set price when select changes
	jq(document).on("change", ".productInfoContainer select.bginp[name='price_type']", function(ev) {
		// Get price and set to input
		var price = jq(this).val();
		jq(".productInfoContainer .bginp[name='price']").val(price);
	});
});