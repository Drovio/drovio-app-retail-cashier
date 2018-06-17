var jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Search customers
	jq(document).on("keyup", ".addCustomerDialog .searchContainer .searchInput", function(ev) {
		// Get input and search notes
		var search = jq(this).val();
		searchCustomers(search);
	});
	
	// Enable search
	jq(document).on("focusin", ".addCustomerDialog .searchContainer .searchInput", function(ev) {
		// Get input and search notes
		var search = jq(this).val();
		searchCustomers(search);
	});
	
	// Search all customers
	function searchCustomers(search) {
		// If search is empty, show all notes
		if (search == "")
			jq(".addCustomerDialog .custList .citem").show();
		
		// Create the regular expression
		var regEx = new RegExp(jq.map(search.trim().split(' '), function(v) {
			return '(?=.*?' + v + ')';
		}).join(''), 'i');
		
		// Select all note rows, hide and filter by the regex then show
		jq(".addCustomerDialog .custList .citem").hide().find(".cname").filter(function() {
			return regEx.exec(jq(this).text());
		}).each(function() {
			jq(this).closest(".citem").show();
		});
	}
	
	// Submit form on selection
	jq(document).on("click", ".addCustomerDialog .custList .citem", function() {
		// Set timeout to submit the form
		var jqForm = jq(this).closest("form");
		setTimeout(function() {
			jqForm.trigger("submit");
		}, 1);
	});
});