<?php
$handlerurl = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) );

$js  = "jQuery(document).ready( function($) {\n";
$js .= "\tclimateriskAjaxUrl = \"".$handlerurl."/../oac-climaterisk-ajax.php\";\n";
$js .=<<< EOJS

	// Remove the oac-input class from all ENSO switches ( we dont' need it in this plugin )
	$("#climaterisk-ui-container #enso-select input").removeClass('oac-input');
	var userInput = $("#climaterisk-ui-container .oac-input");
	var climateriskDataSet = null;
	
	function loadClimateRiskData( varType, location ) {
		var dataSet = { 'data': null };
		$.ajax({
			url: climateriskAjaxUrl,
			async: false,
			cache: true,
			dataType: 'json',
			data: {
				'route'   : 'allData',
				'vartype' : varType,
				'location': location
			},
			complete: function( response, status ) {
				if( status == 'success') {
					dataSet =  $.parseJSON(response.responseText);
				} else {
					alert('There was an error processing the data. Please try again.');
				}
			},
		});
		return dataSet;
	}
	
	function drawTables( dataSet, enso, tableBodies ) {
		for( var i = 0; i < tableBodies.length; i++ ) {
			$(tableBodies[i]).empty();
			if( i == 3 ) {
				enso = 4;
			}
			$(tableBodies[i]).append(dataSet[i].html[enso]);
		}
	}
	
	$('#enso-select input[name="ensophase"]').bind( 'change', function() {
		drawTables( climateriskDataSet, $("#enso-select input:checked").val(), $("#climaterisk-ui-container #tabs div table tbody")  );	
	});
	
	userInput.queue( "cb-stack", function( next ) {
		climateriskDataSet = loadClimateRiskData( $("#vartype").val(), wpScoperGetFinal( 'oac_scope_location' ) );
		drawTables( climateriskDataSet, $("#enso-select input:checked").val(), $("#climaterisk-ui-container #tabs div table tbody") );
		next();
	});

	userInput.bind('change', function() {
		if ( $(this).data('hasRun') == undefined ) {
			$(this).data('hasRun', false );
			$(this).data('oac-climaterisk', false );
		}

		if ( ( $(this).data('hasRun') == false ) || ( ( $(this).data('oac-climaterisk') == true ) && ( $(this).data('hasRun') == true ) ) ) {
			var tmpQueue = $.extend(true, [], $(this).queue( "cb-stack" ) );
			$(this).dequeue( "cb-stack" );
			$(this).queue( "cb-stack", tmpQueue );
			$(this).data('hasRun', true);
			$(this).data('oac-climaterisk', true );
		} else {
			$(this).data('hasRun', false );
		}
	});
	
	$("#tabs").tabs();
	climateriskDataSet = loadClimateRiskData( $("#vartype").val(), wpScoperGetFinal( 'oac_scope_location' ) );
	drawTables( climateriskDataSet, $("#climaterisk-ui-container #enso-select input:checked").val(), $("#climaterisk-ui-container #tabs div table tbody") );
});
EOJS;
header( 'Content-type: text/javascript');
echo $js;