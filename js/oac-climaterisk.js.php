<?php
$handlerurl = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) );

$js  = "jQuery(document).ready( function($) {\n";
$js .= "\tclimateriskAjaxUrl = \"".$handlerurl."/../oac-climaterisk-ajax.php\";\n";
$js .=<<< EOJS
	var columnLabels = [
	{ 'sTitle' : '' },
	{ 'sTitle' : 'Jan' },
	{ 'sTitle' : 'Feb' },
	{ 'sTitle' : 'Mar' },
	{ 'sTitle' : 'Apr' },
	{ 'sTitle' : 'May' },
	{ 'sTitle' : 'Jun' },
	{ 'sTitle' : 'Jul' },
	{ 'sTitle' : 'Aug' },
	{ 'sTitle' : 'Sep' },
	{ 'sTitle' : 'Oct' },
	{ 'sTitle' : 'Nov' },
	{ 'sTitle' : 'Dec' } ];

	var tabDef = new Array();
	tabDef[0] = new (function() {
		this.fnClick = rowHandler;
		this.tabId = "avg-deviation";
		this.tableDefaultSelect = 0;
		this.chartLabel = rowLabels( columnLabels );
		this.chartFun = draw_bar_graph;
		this.chart = null;
	})();
	tabDef[1] = new (function() {
		this.fnClick = colHandler;
		this.tabId = "prob-dist";
		this.tableDefaultSelect = null;
		this.chartFun = draw_bar_graph;
		this.chartLabel = null;
		this.chart = null;
	})();
	tabDef[2] = new (function() {
		this.fnClick = colHandler;
		this.tabId = "prob-exceed";
		this.tableDefaultSelect = null;
		this.chartFun = draw_graph_line;
		this.chartLabel = null;
		this.chart = null;
	})();
	tabDef[3] = new (function() {
		this.fnClick = rowHandler;
		this.tabId = "five-year";
		this.tableDefaultSelect = null;
		this.chartFun = draw_graph_bar_line;
		this.chartLabel = rowLabels( columnLabels );
		this.chart = null;
	})();

	var defaultOptions = { 
		"bFilter" : false,
	 	"bInfo" : false,
		"bJQueryUI": true,
		"bPaginate" : false,
		"sScrollX": "600px",
		"sScrollY": "125px",
		"bSort" : false,
		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
			$(nRow).attr( 'id', 'row_'+iDisplayIndexFull );
			$(nRow).children().each( function(i) {
				$(this).addClass('col_'+i);
			});
			return nRow;
		}
	};

	var empty = {};
	var tables = Array();
	var userInput = $("#climaterisk-ui-container .oac-input");
	
	function isNumber( o ) {
		return ! isNaN( o - 0 );
	}

	function colLabels( tab ) {
		var currentTable = tables[tab];
		return currentTable.fnGetColumnData(0, false, false, true );	
	}

	function colHandler( tab, el ) {
		var currentTable = tables[tab];
		var currentRow = null;
		var currentCol = null;
		if( isNumber( el ) ) {
			currentRow = currentTable.fnGetData( el );
			currentCol = el;
		} else {
			currentRow = currentTable.fnGetData( currentTable.fnGetPosition( el ).shift() );
			currentCol = $(el).attr('class').substr(4);  
		}
		$("#"+tabDef[tab].tabId+"-table td.ui-state-highlight").removeClass('ui-state-highlight');
		$('#'+tabDef[tab].tabId+'-table td.col_'+currentCol).addClass( 'ui-state-highlight' );
		return currentTable.fnGetColumnData( currentCol, false, false, true );
	}

	function rowLabels( l ) {
		var label = [];
		var temp = $.extend(true, [], l);
		temp.shift();
		for( var i = 0; i < temp.length; i++ ) {
			label.push(temp[i].sTitle);
		}
		return label;
	}

	function rowHandler( tab, el ) {
		var currentTable = tables[tab];
		var currentRow = null
		var targetRow = null
		if( isNumber( el ) ) {
			currentRow = currentTable.fnGetData( el );
			targetRow = currentTable.fnGetNodes( el );
		} else {
			currentRow = currentTable.fnGetData( currentTable.fnGetPosition( el ).shift() );
			targetRow = $(el).parent();
		}
		$("#"+tabDef[tab].tabId+"-table tr.ui-state-highlight").removeClass('ui-state-highlight');
		$(targetRow).addClass( 'ui-state-highlight' );
		return currentRow;
	}

	function initialize() {
		$("#tabs").tabs();
		loadTableData();
	}

	function loadTableData( ) {
		var scope = 'oac_scope_location';
		// Load all of the table data in bulk from json? Is this better than loading it when you need it.
		// Yes, because multiple XHR requests is more expensive than one large JSON pull.
		$.ajax({
			url: climateriskAjaxUrl,
			data: { 'route': 'allData', 'vartype' : $("#vartype").val() , 'enso': $('input[name="ensophase"]:checked').val(), 'location' : wpScoperGetFinal( scope ) },
			dataType: 'json',
			success: function( data ) {
				d = data.allData;
				for( var tabIndex = 0; tabIndex < d.length; tabIndex++ ) { // # of tabs returned (groups of data)
					var currentTableData = new Array();
					var currentTableIndex = 0;
					$.each( d[tabIndex].data, function( rowName, values ) {
						values.unshift( rowName );
						currentTableData[currentTableIndex] = values;
						currentTableIndex = currentTableIndex + 1;
					});
					if( tables[tabIndex] != undefined ) {
						var currentTab = $('#tabs').tabs('option', 'selected');
						var currentSelectedIndex = $("#"+tabDef[tabIndex].tabId+'-table .ui-state-highlight').index();
						tables[tabIndex].fnClearTable();
						tables[tabIndex].fnAddData( currentTableData );
						var data = tabDef[tabIndex].fnClick( tabIndex, currentSelectedIndex );
						if( tabIndex == currentTab ) {
							draw_graphs( tabIndex, data );
						}
					} else {
						tables[tabIndex] = $('#'+tabDef[tabIndex].tabId+'-table').dataTable( $.extend( {'aaData': currentTableData, 'aoColumns' : columnLabels}, defaultOptions ) );
					}
				}
			}
		});
	}

	function draw_graphs( currentTab, data ) {
		var name = data[0];
		var ymin, ymax, modifier;
		if( (currentTab != 1) && (currentTab != 2) ) {
			data = data.slice(1);
			modifier = 45;	
			
		} else {
			modifier = 10;
		}	
		ymin = ( Math.min.apply(null, data ) );
		ymin = ( ymin < 0 ) ? ymin - modifier : 0;
		ymax = Math.max.apply(null, data ) + modifier;
		if( Math.abs(ymin) > ymax ) {
			ymax = Math.abs(ymin);
		}
		ytolerance = ( 10 - (ymax % 10 ) );
		ymax = (ymax + ytolerance ); 
		if( modifier > 20 ) {
			if( ymax - Math.max.apply(null, data) < 50 ) {
				ymax = ymax + 20;
			}
		}
		if( ymin != 0)  {
			ymin = ymax * (-1);
		}
		// Lazy load any chart labels, because tables wasn't defined earlier
		if( tabDef[currentTab].chartLabel == null ) {
			tabDef[currentTab].chartLabel = colLabels( currentTab );
		}
		if( currentTab == 3 ) {
			var data2 = tables[0].fnGetData( 0 );
			data = [data,data2];	
		}
		tabDef[currentTab].chart = tabDef[currentTab].chartFun( tabDef[currentTab].tabId+'-chart', data, '#0f0f0f', tabDef[currentTab].chartLabel, ymin, ymax, name);
	}

	
	initialize();

	$('td').live('click', function( e ) {
		var currentTab = $("#tabs").tabs( 'option', 'selected' ); 
		if( ( tabDef[currentTab].fnClick == colHandler ) && ( $(this).hasClass('col_0'))){
			return;
		}
		var data = tabDef[currentTab].fnClick( currentTab, this );
		draw_graphs( currentTab, data );
	});
	
	// Climate Risk Chart generator function
	userInput.queue( "cb-stack", function( next ) {
		loadTableData();
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

	$("#tabs").bind( 'tabsshow', function( event, ui ) {
		tables[ui.index].fnAdjustColumnSizing();
		tabDef[ui.index].chart.replot();
	});

	var description = "This is a test";
	jQuery.jqplot.config.enablePlugins = true;

	function draw_bar_graph(myPlot,line1,graphcolor,Ticks, miny, maxy, xName2) {
		jQuery("#"+myPlot).empty();
		return jQuery.jqplot(myPlot, [line1], {
			title:description,
			grid: {
				drawGridLines: true,
				gridLineColor: '#cccccc',
				borderColor: '#000000',
				borderWidth: 1.0,
				shadow: false
			},	
			seriesDefaults:{
				fill:false,
				fillToZero:true,
				showMarker: false,
				pointLabels: {
					ypadding: 0,
					edgeTolerance: 0
				},
				renderer:jQuery.jqplot.BarRenderer,
				rendererOptions:{
					barPadding: 10,
					barMargin: 15,
					barWidth:25,
					useNegativeColors: false,
				}
			},
			series:[
				{
					label:'a',
					color:graphcolor
				}
			],
			axesDefaults: {
				min: miny,
				max: maxy,
				tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer ,
				tickOptions: {
					fontSize: '10pt',
					formatString:'%d'
				}
			},
			axes:{
				autoscale: true,
				xaxis:{
					label:xName2,
					renderer:jQuery.jqplot.CategoryAxisRenderer,
					rendererOptions:{tickRenderer:jQuery.jqplot.CanvasAxisTickRenderer},
					ticks:Ticks,
					tickOptions:{
						fontSize:'10pt',
						fontFamily:'Tahoma',
						angle:-30
					},
				},
				yaxis:{
					tickOptions:{
						formatString:'%d',
						fontSize:'10pt',
						fontFamily:'Tahoma'
					}
				}
			},
			highlighter: {
			   sizeAdjust: false,
			},
		});
	}



	function draw_graph_line(myPlot,line1,graphcolor,Ticks,miny, maxy, xName2) {
		$("#"+myPlot).empty();
		return $.jqplot(myPlot, [line1], {
			title:description,
			grid: {
				drawGridLines: true,
				gridLineColor: '#cccccc',
				borderColor: '#000000',
				borderWidth: 1.0,
				shadow: false
			},
			seriesDefaults: {
				fill:true, 
				fillToZero: true,
				showMarker: true,
				fillAndStroke: true,
				shadow: false,
				fillAlpha: 0.4
			},
			axesDefaults:{pad:1.3},
			series:[ {
				label:'a',
				color: graphcolor
			}],
			axes:{
				autoscale: true,
				xaxis:{
					label:xName2,
					renderer:$.jqplot.CategoryAxisRenderer,
					rendererOptions:{tickRenderer:$.jqplot.CanvasAxisTickRenderer},
					ticks:Ticks,
					tickOptions:{
						fontSize:'10pt',
						fontFamily:'Tahoma',
						angle:-30
					},
				},
				yaxis:{
					tickOptions:{formatString:'%d',
					fontSize:'10pt',
					fontFamily:'Tahoma'},			
					min: 0,
					max: 110
				}
			},
			highlighter: {sizeAdjust: 7.5},
			cursor: {
				show: true,
				tooltipLocation:'ne',
				showTooltip:true,
				followMouse: true
			}
		});
	}

	function draw_graph_bar_line(myPlot,line1,graphcolor,Ticks,miny,maxy,xName2) {
		$("#"+myPlot).empty();

		//line2 = [1, 3, 16, 13];
		//line3 = [8, 5, 7, 16];

		//line1=[line2,line3]
		return $.jqplot(myPlot, line1, {
			title:description,
			grid: {
				drawGridLines: true,
				gridLineColor: '#cccccc',
				borderColor: '#000000',
				borderWidth: 1.0,
				shadow: false
			},
			seriesDefaults: {
				fill:true, 
				fillToZero:true, 
				showMarker: true, 
				renderer:$.jqplot.BarRenderer,
				rendererOptions:{
					barPadding: 10,
					barMargin: 15,
					barWidth:25,
					useNegativeColors: false}
				},
				series:[
				{
					color:'#949494',


				},
				{
					renderer:$.jqplot.LineRenderer, 
					fill:false, 
					fillToZero:true,
					color:graphcolor

				}
				],    
				axes: {
					min: miny,
					max: maxy,
					xaxis:{
						label:xName2,
						renderer:$.jqplot.CategoryAxisRenderer,
						rendererOptions:{tickRenderer:$.jqplot.CanvasAxisTickRenderer},
						ticks:Ticks,
						tickOptions:{
							fontSize:'10pt',
							fontFamily:'Tahoma',
							angle:-30,
						},
					},
					yaxis:{
						tickOptions:{formatString:'%d',
						fontSize:'10pt',
						fontFamily:'Tahoma'},		
						min: 0

					}
				},
				highlighter: {sizeAdjust: 7.5},
				cursor: {
					show: true,
					tooltipLocation:'ne',
					showTooltip:true,
					followMouse: true
				}
			});
		}

});
EOJS;

header( "Content-Type: text/javascript" );
echo $js;
?>
