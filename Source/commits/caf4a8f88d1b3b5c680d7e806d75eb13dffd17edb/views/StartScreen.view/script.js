var jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Get date clicked
	jq(document).on("click", ".dtContainer .dtc", function() {
		// Set selected
		jq(".dtc").removeClass("selected");
		jq(this).addClass("selected");
		
		if (jq(".rinp:checked").hasClass("auto"))
			jq(".bginp.dt").addClass("auto");
		else
			jq(".bginp.dt").removeClass("auto");
	});
});