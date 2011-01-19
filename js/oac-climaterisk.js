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
    axesDefaults: {
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
    },
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
