jQuery(document).ready(function($) {

  var myUri = $('script[src*="oac-climaterisk"]').oacGetURI(),
      ajaxHandler = myUri+'oac-climaterisk-ajax.php',
      currentDate = new Date(),
      currentSelection = [0,currentDate.getMonth()-1, currentDate.getMonth()-1,0],
      phaseColors = ['#0F6', '#F66', '#0CF', '#A9A9A9'],
      redrawState = [true,true,true,true],
      displayAll = false,
      ajaxGetAll = function() {
        $.ajax({
          url: ajaxHandler,
          type: 'GET',
          data: {route: 'getData', loc: $(".wp-scope-final").val()},
          dataType: 'json',
          success: function( raw ) {
           currentData = raw.data;
           currentHtml = raw.html;
           getCurrentSettings();
           getHtml();
           runGraph();
          }
        });
      },
      tables = ['#avg-deviation-table', '#prob-dist-table', '#prob-exceed-table', '#five-year-table'],
      charts = ['#avg-deviation-chart', '#prob-dist-chart', '#prob-exceed-chart', '#five-year-chart'],
      tabs = $("#tabs").tabs(),
      graphs     = [],
      minigraphs = [],
      monthticks = [],
      labels, currentData, currentHtml, currentPhase, currentVar, currentVarName, currentTab;


  function fuzzyTrimData(d) {
    var l = d.length,
        started = false,
        ended = false,
        start, end;
    for(var i=0; i<l; i++) {
      if( d[i] === 100 || d[i] === 0 ) {
        if( started ) {
          // Check on a loop?
          if (d[i] === 0 && (d[i+1] === 0 || d[i+1] === undefined) && (d[i+2] === 0 || d[i+2] === undefined ) && !ended) {
            end = i;
            ended = true;
          }
        }
      } else {
        if( ! started ) {
          start = (( i > 0 && d[i-1] === 100 ) ? i-1 : i);
          started = true;
          ended = false;
        }
        end = i+1;
      }
    }
    return {start: start, end: end, data: d.slice(start,end)};
  }

  function getCurrentSettings() {
    currentPhase = ($("input[name=ensophase]:checked").val())-1;
    currentVar = $("#vartype").val();
    currentVarName = $("#vartype option").filter(":selected").text();
    currentVarName = currentVarName.substring(0,currentVarName.indexOf('(')-1);
    currentTab = tabs.tabs('option', 'selected');
  }

  function getHtml() {
    if( currentVar === 'RAIN' ) {
      $(".total").show();
    } else {
      $(".total").hide();
    }
    for(var i=0; i<4;i++) {
      $(tables[i]+' tbody').html(currentHtml[currentVar][i][(i==3 ? 3 : currentPhase)]);
      if( i===0 || i=== 3) {
        $(tables[i]+' tbody tr:eq('+currentSelection[i]+')').addClass('row_col_Active');
      } else {
        $(tables[i]+' tbody .col-'+currentSelection[i]).addClass('row_col_Active');
      }
    }
  }

  function runGraph() {
    redrawState = [true,true,true,true];
    drawGraph( currentTab );
  }

  function drawGraph( index ) {
    var graphText = {}, graphData = [], graphTicks = [], colors = [];
    if(redrawState[index]) {
      $(charts[index]).empty();
      switch( index ) {
        case 0:
          graphData = [currentData[currentVar][index][(index==3 ? 3 : currentPhase)][currentSelection[index]]];
          graphTicks = monthticks;
          break;
        case 1:
        case 2:
          graphData = fuzzyTrimData(currentData[currentVar][index][currentPhase][currentSelection[index]]);
          $(tables[index]+' .label').each(function(){ graphTicks.push( $(this).text() );});
          graphTicks = graphTicks.slice(graphData.start, graphData.end);
          graphData = [graphData.data];
          break;
        case 3:
          graphData = [currentData[currentVar][index][(index==3 ? 3 : currentPhase)][currentSelection[index]], currentData[currentVar][0][currentPhase][0]]
          graphTicks = monthticks;
          break;
      }
      if( index === 3 ) {
        colors = ['#949494', phaseColors[currentPhase]];
      } else {
        colors.push(phaseColors[currentPhase]);
      }
      switch( index ) {
        case 0:
        case 1:
          $(charts[index]).oacBarchart(0,0,600,350,graphData,graphTicks,{title: currentVarName, x: labels[currentVar][currentTab]['x'], y: labels[currentVar][currentTab].y},{colors: colors});
          break;
        case 2:
          $(charts[index]).oacLinechart(0,0,600,350,graphData,graphTicks,{title: currentVarName, x: labels[currentVar][currentTab]['x'], y: labels[currentVar][currentTab].y},{colors: colors, shade: true, symbol: 'circle'});
          break;
        case 3:
          $(charts[index]).oacHybridchart(0,0,600,350,graphData,graphTicks,{title: currentVarName, x: labels[currentVar][currentTab]['x'], y: labels[currentVar][currentTab].y},{colors: colors, symbol: 'circle'});
          break;
      }
      redrawState[index] = false;
    }
  }

  // Event control
  $("#tabs").bind('tabsshow', function(e, ui) {
    currentTab = ui.index;
    drawGraph(currentTab);
  });

  $(".wp-scope-final").change( function() {
     redrawState = [true, true, true, true];
     ajaxGetAll();
   });

   $(tables[0]+','+tables[3]).delegate('tr', 'click', function() {
     if( $(this).index() !== currentSelection[currentTab] ) {
       $(this).siblings().each(function(){ $(this).removeClass('row_col_Active'); });
       $(this).addClass('row_col_Active');
       currentSelection[currentTab] = $(this).index();
       redrawState[currentTab] = true;
       drawGraph( currentTab );
     }
   });

   $(tables[1]+','+tables[2]).delegate('.selectable', 'click', function(){
     var currCol = ($(this).index())-1,
         prevCol = currentSelection[currentTab];
     if( currCol !== prevCol ) {
       $(tables[1]+' .col-'+prevCol+','+tables[2]+' .col-'+prevCol).removeClass('row_col_Active');
       $(tables[1]+' .col-'+currCol+','+tables[2]+' .col-'+currCol).addClass('row_col_Active');
       currentSelection[1] = currentSelection[2] = currCol;
       redrawState[1] = redrawState[2] = true;
       drawGraph( currentTab );
     }
   });

   $("html").delegate(".oac-input:not(.wp-scope-final)", "change", function() {
     // This doesn't require firing the ajax call, keep everything in scope.
     getCurrentSettings();
     getHtml();
     runGraph();
   });


  // Actual "Main" Loop
  $.ajax({
    url: ajaxHandler,
    type: 'GET',
    data: {route: 'getLabels'},
    dataType: 'json',
    success: function( raw ) {
      labels = raw;
      $('#prob-dist-table thead th').each(function() { if( $(this).text() !== '' ) monthticks.push($(this).text()); });
      ajaxGetAll();
    }
  });
});
