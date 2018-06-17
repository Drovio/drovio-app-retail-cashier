var jq = jQuery.noConflict();
jq(document).one("ready", function() {
	// Get date clicked
	jq(document).on("click", ".dtContainer .dtc", function() {
		// Set selected
		var dtContainer = jq(this).closest(".dtContainer");
		dtContainer.find(".dtc").removeClass("selected");
		jq(this).addClass("selected");
		
		if (dtContainer.find(".rinp:checked").hasClass("auto"))
			dtContainer.find(".bginp.dt").addClass("auto");
		else
			dtContainer.find(".bginp.dt").removeClass("auto");
	});
});