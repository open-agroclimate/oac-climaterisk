<?php
$handlerurl = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) );

$js  = "jQuery(document).ready( function($) {\n";
$js .= "\tclimateriskjshandlerurl = \"".$handlerurl."/../oac-climaterisk-ajax.php\";\n";
$js .=<<< EOJS
	// Setup our charts	
	var charts = new Array();
	charts[0] = new (function () {
		this.containerId = 'avg-deviation-chart';
		this.chartFun    = draw_graph_bar;
		this.chart       = null;
	})();
	charts[1] = new (function() {
		this.containerId = 'prob-dist-chart';
		this.chartFun    = draw_graph_bar;
		this.chart       = null;
	})();
	charts[2] = new (function() {
		this.containerId = 'prob-exceed-chart';
		this.chartFun    = draw_graph_bar;
		this.chart       = null;
	})();
	charts[3] = new (function() {
		this.containerId = 'five-year-chart';
		this.chartFun    = draw_graph_bar;
		this.chart       = null;
	})();

	// Initialize the tabs
	$("#tabs").tabs();
	
	var userInput = $("#climaterisk-ui-container .oac-input");

	// Climate Risk Chart generator function
	userInput.queue( "cb-stack", function( next ) {
		var scope = 'oac_scope_location';
		var currentTab = $("#tabs").tabs( 'option', 'selected' );
		$.ajax( {
			url : climateriskjshandlerurl,
			cache: false,	
			dataType: 'json',
			data: { route: 'avg',
				vartype: $("#vartype").val(),
				enso: $('input[name="ensophase"]:checked').val(),
				tab: currentTab,
				option: 'Average',
				location: wpScoperGetFinal( scope )
			},
			success: function( json ) {
				var graph = charts[currentTab];
				graph.chart = graph.chartFun( graph.containerId, json.data, '#0f0f0f', ["J", "F", "M", "A", "M", "J", "J", "A", "S", "O", "N", "D"], 1 );
			}
		});
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
	
	$("#tabs").bind('tabsshow', function( event, ui ) {
		$.ajax( {
			url : climateriskjshandlerurl,
			cache: false,	
			dataType: 'json',
			data: { route: 'avg',
				vartype: $("#vartype").val(),
				enso: $('input[name="ensophase"]:checked').val(),
				tab: ui.index,
				location: wpScoperGetFinal( 'oac_scope_location' )
			},
			success: function( json ) {
				var graph = charts[ui.index];
				console.log( graph.containerId );
				graph.chart = graph.chartFun( graph.containerId, json.data, '#0f0f0f', ["D"], 1 );
			}
		});
	});
});

var description = "This is a test";
var xName2 = "Something";
jQuery.jqplot.config.enablePlugins = true;
function getCurrentVar( ) {
	return jQuery("#vartype").val();
}

function draw_graph_bar(myPlot,line1,graphcolor,Ticks,startYaxis) {
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
		renderer:jQuery.jqplot.BarRenderer,
        rendererOptions:{
		barPadding: 10,
		barMargin: 15,
		barWidth:25,
		useNegativeColors: false
		}
    },
	series:[
        {label:'a',
		color:graphcolor
		}
    ],
    /*axesDefaults: {
		min: startYaxis,
		tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer ,
		tickOptions: {
        fontSize: '10pt',
		formatString:'%d'
      }
	},
	axes:{
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
		tickOptions:{formatString:'%d',
		fontSize:'10pt',
        fontFamily:'Tahoma'}
		}
    },*/
	highlighter: {
           sizeAdjust: 20,
		   show: false
           //tooltipLocation: 'n',
           //tooltipAxes: 'y',
         //  tooltipFormatString: '<b><i><span style="color:red;">hello</span></i></b> %.2f',
          // useAxesFormatters: false
       },
       cursor: {
           show: true,
		   //tooltipLocation:'ne',
			followMouse: true
		   
       }
	//cursor: {tooltipLocation:'sw', zoom:true, clickReset:true},
	//cursor:{zoom:true, showTooltip:true}

});

}

function draw_graph_line(myPlot,line1,graphcolor,Ticks,startYaxis) {
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
	seriesDefaults: {
		fill:true, 
		fillToZero: true,
		showMarker: true,
		fillAndStroke: true,
		shadow: false,
		//fillColor:'#cccccc',
		fillAlpha: 0.4
		
	},
	axesDefaults:{pad:1.3},
	series:[
        {label:'a',
		color:graphcolor

		}
    ],
	
    axes:{
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
		tickOptions:{formatString:'%d',
		fontSize:'10pt',
        fontFamily:'Tahoma'},			
		min: startYaxis,
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

function draw_graph_bar_line(myPlot,line1,graphcolor,Ticks,startYaxis) {
	jQuery("#"+myPlot).empty();

	//line2 = [1, 3, 16, 13];
	//line3 = [8, 5, 7, 16];

	//line1=[line2,line3]
	return jQuery.jqplot(myPlot, line1, {
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
    renderer:jQuery.jqplot.BarRenderer,
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
	renderer:jQuery.jqplot.LineRenderer, 
	fill:false, 
	fillToZero:true,
	color:graphcolor
	
	}
  ],    
  axes: {
        xaxis:{
			label:xName2,
            renderer:jQuery.jqplot.CategoryAxisRenderer,
			rendererOptions:{tickRenderer:jQuery.jqplot.CanvasAxisTickRenderer},
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
			min: startYaxis
		
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
EOJS;

header( "Content-Type: text/javascript" );
echo $js;
?>
