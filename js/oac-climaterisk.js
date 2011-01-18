function getCurrentVar( ) {
	return jQuery("#vartype").val();
}

jQuery(document).ready( function($) {
	$("#tabs").tabs();
	$(".current-var").html( getCurrentVar() );
	$("#vartype").change( function() {
		$(".current-var").html( getCurrentVar() );
	});
});

