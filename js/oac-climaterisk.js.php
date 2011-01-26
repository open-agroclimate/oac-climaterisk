<?php
$handlerurl = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) );

$js =  "var chart = new Array();";
$js .= "jQuery(document).ready( function($) {\n";
$js .= "\tclimateriskjshandlerurl = \"".$handlerurl."/../oac-climaterisk-ajax.php\";\n";
$js .=<<< EOJS

	//Initially on load, we only need to draw the first tab (laaazy load baby)
	chart[0] = draw_graph_bar("avg-deviation-chart", [1,2,3,4,5,6,7,8,9,10,11,12], '#0000ff', ["Jan", "Feb", 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], 0);
	chart[1] = draw_graph_bar("prob-dist-chart", [1,3,5,7,9,11,2,4,6,8,10,12], '#ff0000', [], 1);
	chart[2] = draw_graph_bar("prob-exceed-chart", [12,11,10,9,8,7,6,5,4,3,2,1], '#0000ff', [], 2);
	chart[3] = draw_graph_bar("five-year-chart", [10,11,12,9,8,7,6,1,2,3,4,5], '#0f0f0f', [], 0);

	$("#tabs").tabs();
	$(".current-var").html( getCurrentVar() );
	$("#vartype").change( function() {
		$(".current-var").html( getCurrentVar() );
	});

	var userInput = $("#climaterisk-ui-container .oac-input");

	userInput.queue( "cb-stack", function( next ) {
		alert( "FIRE!" );
		next();
	});

	userInput.bind('change', function() {
		if ( $(this).data('hasRun') == undefined ) {
			$(this).data('hasRun', false );
			$(this).data('oac-climaterisk', false );
		}

		if ( ( $(this).data('hasRun') == false ) || ( ( $(this).data('oac-climaterisk') == true ) && ( $(this).data('hasRun') == true ) ) ) {
			alert( "Run from climaterisk" );
			var tmpQueue = $.extend(true, [], $(this).queue( "cb-stack" ) );
			$(this).dequeue( "cb-stack" );
			$(this).queue( "cb-stack", tmpQueue );
			$(this).data('hasRun', true);
			$(this).data('oac-climaterisk', true );
		} else {
			$(this).data('hasRun', false );
		}
	});
	
	$("#tabs").bind('tabshow', function( event, ui ) {
		chart[ui.index].redraw();
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
