<?php
$handlerurl = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) );

$js  = "jQuery(document).ready( function($) {\n";
$js .= "\tclimateriskAjaxUrl = \"".$handlerurl."/../oac-climaterisk-ajax.php\";\n";
$js .=<<< EOJS

	// Remove input[name="ensophase"] from oac-input (not needed here)
	$("#climaterisk-ui-container input[name=\\"ensophase\\"]").removeClass('oac-input');
	var climateriskUserInput = $("#climaterisk-ui-container .oac-input");
	var climateriskDataSet;
	var currentMonthIndex = new Date();
	currentMonthIndex = currentMonthIndex.getMonth();
	var climateriskHandlers = [],
		canvases = [];
	climateriskHandlers[0] = { 'tableCallback': oac().highlightTableRow, 'defaultSelection': 0, 'graphCallback': [oac().barchart, oac().deviationbarchart] };
	climateriskHandlers[1] = { 'tableCallback': oac().highlightTableCol, 'defaultSelection': currentMonthIndex+1, 'graphCallback':oac().barchart };
	climateriskHandlers[2] = { 'tableCallback': oac().highlightTableCol, 'defaultSelection': currentMonthIndex+1, 'graphCallback':oac().linechart };
	climateriskHandlers[3] = { 'tableCallback': oac().highlightTableRow, 'defaultSelection': 0, 'graphCallback':oac().barchart };
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
				if( status === 'success') {
					dataSet =  $.parseJSON(response.responseText);
				} else {
					alert('There was an error processing the data. Please try again.');
				}
			},
		});
		return dataSet;
	}
		
	function climateriskIndexSelect( tableBody, index, tableIndex  ) {
			if (climateriskHandlers[tableIndex].tableCallback === oac().highlightTableRow ) {
				$(tableBody).find("tr:eq("+index+") td:eq(1)").click();
			} else if ( climateriskHandlers[tableIndex].tableCallback === oac().highlightTableCol ) {
				$(tableBody).find('tr:eq(0) td:eq('+index+')').click();
			}
	}

	// This function draws all the tables on all tabs
	function climateriskDrawTables( enso, selectedIndices ) {
		var tables = $("#tabs").find(".oac-table tbody");
		for( var tab = 0; tab < 4; tab++ ) {
			if( tab == 3 ) {
				enso = 4;
			}
			oac().drawTable( tables[tab], climateriskDataSet[tab][enso] );
			// load from the defaults above
			if( selectedIndices === undefined ) {
				climateriskIndexSelect( tables[tab], climateriskHandlers[tab].defaultSelection, tab );
			} else {
				climateriskIndexSelect( tables[tab], selectedIndices[tab], tab)
			}
		}
	}

	function climateriskDrawGraph( tab, enso, opts ) {
		if( tab == 3 ) {
			enso = 4;
		}
		
		var isCol = ( tab == 1 || tab == 2 ) ? true : false,
			panel = $("#tabs").children("div:eq("+tab+")"),
			table = $(panel).find(".oac-table tbody"),
			index = oac().tableHighlightedIndices([$(table)]),
			colIndex = isCol ? index-1 : $(table).find("tr:eq("+index+") .index-col").text(),
			label = isCol ? (function() { var x = []; $(table).find(".index-col").each( function() { x.push($(this).text()); }); return x; })() : (function() { var x=[]; $(table).parent().find("thead th").each( function() { x.push($(this).text()); }); x.shift(); return x; })(),
			data  = isCol ? oac().getHighlightedData( table ) : oac().getHighlightedData( table ).slice(1),
			graph = $(panel).find(".oac-chart"),
			title = $("#climaterisk-ui-container").find("#vartype :selected").text(),
			fin = function() {
				var point = this.bar || this || undefined;
				if ( point === undefined ) return;
				this.flag = canvases[tab].canvas.g.popup( point.x, point.y, (point.value || "0")+" "+units).insertBefore(this);
				graphobj.labels[this.index].attr({"fill-opacity": 1, "font-weight" : "bold"});
				graphobj.labels[this.index].toFront();
			},
			fout = function() {
				var point = this.bar || this || undefined;
				if( point === undefined ) return;
				this.flag.animate({opacity:0}, 300, function() {this.remove(); });
				graphobj.labels[this.index].animate({"fill-opacity": 0.25, "font-weight" : "normal"}, 300, function(){});
				graphobj.labels[this.index].toBack();
			},
			cleaned,
			units,
			graphobj;
		
		// Cleanup Aisle One
		cleaned = oac().cleanData( data, label );
		data = cleaned.data;
		label = cleaned.labels;
		title = title.substring(0, title.indexOf('('));
		if( tab == 0 || tab == 3 ) {
			xlabel = "Months"; // needs translations
			title += "("+colIndex+")";
			units = "mm";
		} else {
			xlabel = "Millimeters"; // needs translation
			title += "("+$(table).parent().find("thead th:eq("+index+")").text()+")";
			units = "%";
		}
		
		//if( tab == 1 || tab == 2) { return; }
		
		if( climateriskHandlers[tab].tableCallback === oac().highlightTableRow ) {
			if (data.length > 12 ) {
				data.pop();
				label.pop();
			}
		}
		if( canvases[tab] === undefined ) {
			canvases[tab] = {canvas : Raphael($(graph).attr("id"), 600, 300 ), redraw: false };
		}
		canvases[tab].canvas.clear();
		if( data.length === 0 ) {
			canvases[tab].canvas.text(300,150, "Data unavailable");
		} else {
			graphobj = oac().chartWithAxis(($.isArray(climateriskHandlers[tab].graphCallback)) ? climateriskHandlers[tab].graphCallback[index] : climateriskHandlers[tab].graphCallback, canvases[tab].canvas, 0, 0, 600, 300, data, label, { title: title, xlabel: xlabel, ylabel: "", yunits: units }, opts, {"fill-opacity" : .25, "font-weight" : "normal" } );
			graphobj.graph.hover(fin, fout);
		}
	}
	
	$('#enso-select input[name="ensophase"]').bind( 'change', function() {
		//oac().drawTables( climateriskDataSet, $("#climaterisk-ui-container #enso-select input:checked").val(), $("#climaterisk-ui-container #tabs div table tbody"), oac().tableHighlightedIndices( $("#climaterisk-ui-container #tabs div table tbody") ), climateriskIndexSelect );
		climateriskDrawTables( $("#climaterisk-ui-container #enso-select input[name=\"ensophase\"]:checked").val(), oac().tableHighlightedIndices( $("#climaterisk-ui-container #tabs").find(".oac-table tbody") ) );
		
	});
	
	climateriskUserInput.queue( "cb-stack", function( next ) {
		var tab = $("#tabs").tabs('option', 'selected'),
		    ensoColor = $("#climaterisk-ui-container #tabs table:eq("+tab+") .highlight").css('background-color');
		
		climateriskDataSet = loadClimateRiskData( $("#vartype").val(), wpScoperGetFinal( 'oac_scope_location' ) );
		climateriskDrawTables( $("#climaterisk-ui-container #enso-select input[name=\"ensophase\"]:checked").val(), oac().tableHighlightedIndices( $("#climaterisk-ui-container #tabs").find(".oac-table tbody") ) );
		climateriskDrawGraph( tab, $("#climaterisk-ui-container #enso-select input[name=\"ensophase\"]:checked").val(), { type: "soft", colors: ensoColor } );

		next();
	});

	climateriskUserInput.bind('change', function() {
		if ( $(this).data('hasRun') === undefined ) {
			$(this).data('hasRun', false );
			$(this).data('oac-climaterisk', false );
		}

		if ( ( $(this).data('hasRun') === false ) || ( ( $(this).data('oac-climaterisk') === true ) && ( $(this).data('hasRun') === true ) ) ) {
			var tmpQueue = $.extend(true, [], $(this).queue( "cb-stack" ) );
			$(this).dequeue( "cb-stack" );
			$(this).queue( "cb-stack", tmpQueue );
			$(this).data('hasRun', true);
			$(this).data('oac-climaterisk', true );
		} else {
			$(this).data('hasRun', false );
		}
	});
	
	$("table").delegate('td', 'click', function() {
			if( $(this).hasClass('index-col') ) {
				return;
			}
			var tableIndex = $("table").index( $(this).parents('table') ),
				enso = $("#climaterisk-ui-container #enso-select input:checked").val();			
			if( tableIndex == 3 ) {
				enso = 4;
			}
			climateriskHandlers[tableIndex].tableCallback( this, enso );
			climateriskDrawGraph( tableIndex, enso, { type: "soft", colors: $(this).parents('table').find('.highlight').css('background-color') } );
	});

	$("#tabs").bind( 'tabsshow', function( event, ui ) {
		// Don't do anything on initial load, that is taken care of below
		if( climateriskDataSet === undefined ) return;
		var ensoColor = $("#climaterisk-ui-container #tabs table:eq("+ui.index+") .highlight").css('background-color');
		climateriskDrawGraph( ui.index, $("#climaterisk-ui-container #enso-select input[name=\"ensophase\"]:checked").val(), { type: "soft", colors: ensoColor } );
	});

	$("#tabs").tabs();
	climateriskDataSet = loadClimateRiskData( $("#vartype").val(), wpScoperGetFinal( 'oac_scope_location' ) );
	climateriskDrawTables( $("#climaterisk-ui-container #enso-select input[name=\"ensophase\"]:checked").val() );
	climateriskDrawGraph(  $("#climaterisk-ui-container #tabs").tabs('option', 'selected'), $("#climaterisk-ui-container #enso-select input[name=\"ensophase\"]:checked").val(), { type: "soft", colors: $("#climaterisk-ui-container #tabs table:eq("+$("#climaterisk-ui-container #tabs").tabs('option', 'selected')+") .highlight").css('background-color') } );
});
EOJS;
header( 'Content-type: text/javascript');
echo $js;