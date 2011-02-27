var OACClimateRisk = new Class({
	Implements: [Options],
	
	options: {
		handler: (''.toURI().get('directory'))+'handler.php',
		element: document.id('climaterisk-ui-container'),
		defaultSelect: []
	},
	data:   [],
	axis: [],
	tabledata: [],
	linkedpaper: null,
	initialize: function(opts, scope) {
		var m, ensoel;
		this.setOptions(opts);
		if( this.options.element === null ) return;
		if( this.options.defaultSelect.length === 0 ) {
			m = (new Date().getMonth())+1;
			this.options.defaultSelect[0, m, m, 0];
		}
		this.scope = scope;
		ensoel  = this.options.element.getElement('input[name="ensophase"]:checked');
		this.enso    = ensoel.get('value');
		this.tables = this.options.element.getElements('.oac-table');
		this.graphs = this.options.element.getElements('.oac-chart');
		this.graphcolor = [ensoel.getParent().getStyle('background-color')];
		this.req = new Request.JSON({
			url: this.options.handler,
			link: 'cancel',
			onSuccess: function(res) { this.processData(res); }.bind(this),
			onFailure: function() { alert('There was an error fetching the data'); }
		});
		this.bound = {
			req: function() { 
				this.req.send({
					data: {
						route: 'allData',
						vartype: this.options.element.getElement('#vartype').get('value'),
						location: this.scope.finalElement.get('value')
					}
				});
			}.bind(this),
			clickRow: function(row) {
				var table = row.getParents('table')[0],
					tableindex = this.options.element.getElements('table').indexOf(table),
					rowindex = row.getParent().getChildren('tr').indexOf(row),
					enso = this.enso-1;
				if( tableindex === 3 ) enso = 0;
				this.drawGraph(rowindex, tableindex, enso);
			}.bind(this),
			clickCol: function(colindex, col) {
				var table = col[0].getParents('table')[0],
				    tableindex = this.options.element.getElements('table').indexOf(table),
					enso = this.enso-1;
				this.drawGraph(colindex-1, tableindex, enso);
			}.bind(this)
		};
		this.tables.each(function(table, index) {
			var rowtable = ((index === 0) || (index === 3)),
			    t = new HtmlTable(table, {
					selectable: rowtable,
					columnSelectable: !rowtable,
					classRowSelected: 'oac-data-selected',
					classColSelected: 'oac-data-selected',
					hasIndexColumn: true,
					onRowFocus: this.bound.clickRow,
					onColFocus: this.bound.clickCol
			    });
			this.tables[index] = t;
			this.tabledata[index] = {};
		}, this);
		// First bind our custom events to the scope
		this.scope.finalQueue.add(this.bound.req);
		this.bound.req();
	},
	
	processData: function(data) {
		data.each(function(item, index) {
			var tmpdata = [],
				alldata,
				l, i;
			this.axis[index] = Object.filter(item, function( v, k ) { 
				return typeOf(v) === 'string';
			});
			if(!this.data[index]) this.data[index] = {};
			this.data[index].data = Array.clone(item.data);
			this.tabledata[index] = Array.clone(item.data);
			item.data.each( function( itm, idx ) {
				itm.each( function( it, id ) {
					this.data[index].data[idx][id] = it.slice(1, (((index == 0) || (index == 3)) ? it.length-1 : undefined));
					if( index === 0 ) {
						if(!tmpdata[id]) tmpdata[id] = [];
						tmpdata[id].push(this.data[index].data[idx][id]);
					}
				}, this);
				if((index == 1) || (index == 2)) {
					this.data[index].data[idx] = this.data[index].data[idx].transpose();
				}
			}, this);
			alldata = this.data[index].data.flatten();
			alldata =  alldata.filter(function(itm, idx) {
			    return typeOf(Number.from(itm)) === 'number';
			});
			if(index === 0) {
				l = tmpdata.length;
				for( i = 0; i < l; i++ ) {
					if(!this.data[index].min) {
						this.data[index].min = [];
						this.data[index].max = [];
					}
					this.data[index].min[i] = tmpdata[i].flatten().min();
					this.data[index].max[i] = tmpdata[i].flatten().max();
				}
			} else {
				this.data[index].min = alldata.min();
				this.data[index].max = alldata.max();
			}
		}, this);
		this.genTables();
	},
	
	genTables: function() {
		// First get all the tables available (on the tabs)
		var enso = Number.from(this.enso)-1;
		this.tables.each(function( table, index ) {
			table.empty();
			if(index === 3) enso = 0;
			this.tabledata[index][enso].each(function(row, i) { table.push(row); });
			table.toElement().addClass('oac-enso-'+((index === 3) ? 4 : this.enso));
			if(instanceOf(this.graphs[index], OACGraph)) {
			     this.graphs[index].rescale = true;
		    }
		}, this);
	},
	
	drawGraph: function(dataindex, tabindex, enso) {
		var data   = this.data[tabindex].data[enso][dataindex],
		    min    = this.data[tabindex].min[dataindex] || this.data[tabindex].min,
			max    = this.data[tabindex].max[dataindex] || this.data[tabindex].max,
			labels = [],
			currentgraph, el, element;
		
		
		if( !instanceOf(this.graphs[tabindex], OACGraph)) {
		    if( tabindex === 0 || tabindex === 3) {
        		this.tables[tabindex].toElement().getChildren('thead th').each(function(label,i) {
        		   labels.push(label.get('text')); 
        		});
        		labels = labels.slice(1,labels.length-1);
        	} else {
        	    this.tables[tabindex].toElement().getElements('tbody tr td:nth-child(1)').each(function(label,i) {
        	       labels.push(label.get('text')); 
        	    });
        	}
			this.graphs[tabindex] = new OACGraph({
				linkpaper: (tabindex === 0),
				linkedpaper: (tabindex === 0) ? this.linkedpaper : undefined,
				element: this.graphs[tabindex],
				min: min,
				max: max,
				type: (tabindex == 2 ) ? 'linechart' : 'barchart',
				labels: labels,
				graphOptions: {
					title: 'Example '+tabindex,
					xlabel: 'Hecho',
					ylabel: 'Mexico'
				},
				chartOptions: {
					colors: (tabindex === 3) ? ['#808080'] : this.graphcolor,
					centeraxis: (tabindex === 0),
    				to: max,
    				from: min > 0 ? 0 : min,
    				shade: true,
    				symbol: 'o'
				}
			});
		}
		currentgraph = this.graphs[tabindex];
		if( tabindex !== 3)
			currentgraph.rescale = true;
		
		if( (tabindex === 1) || (tabindex === 2)  ) {
		    this.tables[tabindex].toElement().getElements('tbody tr td:nth-child(1)').each(function(label,i) {
    	       labels.push(label.get('text')); 
    	    });
		    left = data.intelfuzzyltrim(((tabindex === 1) ? 0 : 100),2);
		    right = left.data.intelfuzzyrtrim(0,2);
		    if( left.index !== null || right.index !== null ) {
    	        labels = labels.slice((left.index === null) ? 0 : left.index, (right.index ===null) ? labels.length : right.index+left.index);
    	    } else {
    	        labels = labels;
    	    }
    	    data = right.data;
		}
		if(labels.length === 0) labels = currentgraph.options.labels;
		// now we clean and do more label work;
		data = data.intelclean(0);
		data = data.data;
		if(currentgraph.rescale) {
		    currentgraph.options.labels = labels;
		    currentgraph.options.min = min;
		    currentgraph.options.max = max;
		    currentgraph.redraw(data, true);
		    currentgraph.rescale = false;
		} else {
    		currentgraph.draw(data);
    	}
	}
});


window.addEvent('domready', function() {
	// If you use an inline minifier like wp-minify, all bets are off and you should hardcode
	// the path to your ajax handler(s).
	var climateRiskHandler = (($$('script[src*="oac-climaterisk.js"]')[0].getProperty('src').toURI().get('directory'))+'../oac-climaterisk-ajax.php').toURI().toString(),
	    climateRiskElement = document.id('climaterisk-ui-container'),
	    climateRisk = new OACClimateRisk({
			handler: climateRiskHandler,
			element: climateRiskElement
		}, new OACScope({
			scope: 'location',
			element: climateRiskElement.getElementById('oac_scope_location')
		}));	
});