var jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Search customers
	jq(document).on("keyup", ".addProductDialog .searchContainer .searchInput", function(ev) {
		// Get input and search notes
		var search = jq(this).val();
		searchProducts(search);
	});
	
	// Enable search
	jq(document).on("focusin", ".addProductDialog .searchContainer .searchInput", function(ev) {
		// Get input and search notes
		var search = jq(this).val();
		searchProducts(search);
	});
	
	// Search all customers
	function searchProducts(search) {
		// If search is empty, show all notes
		if (search == "")
			jq(".addProductDialog .prdList .pitem").show();
		
		// Create the regular expression
		var regEx = new RegExp(jq.map(search.trim().split(' '), function(v) {
			return '(?=.*?' + v + ')';
		}).join(''), 'i');
		
		// Select all note rows, hide and filter by the regex then show
		jq(".addProductDialog .prdList .pitem").hide().find(".ptitle").filter(function() {
			return regEx.exec(jq(this).text());
		}).each(function() {
			jq(this).closest(".pitem").show();
		});
	}
});