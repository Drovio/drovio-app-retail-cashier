var jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Edit product item
	jq(document).on("click", ".productListOuterContainer .productList .prow .fld.pedit", function(ev) {
		// Toggle edit
		jq(this).closest(".prow").addClass("edit");
	});
	
	// Cancel editing product item
	jq(document).on("click", ".productListOuterContainer .productList .prow .fld.pcancel", function(ev) {
		// Toggle edit
		jq(this).closest(".prow").removeClass("edit");
	});
	
	// Update labels
	jq(document).on("change keyup", ".productListOuterContainer .productList .prow input.fld", function(ev) {
		// Check if key code = escape
		if (ev.keyCode == 27)
			return jq(this).closest(".pform").find(".fld.pcancel").trigger("click");
			
		// Get price
		var jqProw = jq(this).closest(".prow");
		var price = parseFloat(jqProw.find(".pform input.fld[name='price']").val());
		var amount = parseFloat(jqProw.find(".pform input.fld[name='amount']").val());
		var tax_rate = parseFloat(jqProw.find(".pform input.fld[name='tax_rate']").val());
		var discount = parseFloat(jqProw.find(".pform input.fld[name='discount']").val());
		
		// Calculate tax
		var tax = price * tax_rate * amount;
		jqProw.find(".pform label.fld.ptax").html(tax.toFixed(2));
		
		// Calculate total price
		var total_price = price * (1 + tax_rate) * amount;
		total_price = total_price * (1 - discount/100);
		jqProw.find(".pform label.fld.ptotal_price").html(total_price.toFixed(2));
	});
});