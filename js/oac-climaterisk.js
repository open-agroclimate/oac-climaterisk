var OACClimateRisk = new Class({
	Implements: [Options],
	
	options: {
		handler: (''.toURI().get('directory'))+'handler.php',
		element: document.id('climaterisk-ui-container'),
		defaultSelect: []
	},
	data:   [],
	tabledata: [],
	axis:   [],
	labels: [],
	initialize: function(opts, scope) {
		this.setOptions(opts);
		if( this.options.element === null ) return;
		if( this.options.defaultSelect.length === 0 ) {
			var m = (new Date().getMonth())+1;
			this.options.defaultSelect[0, m, m, 0];
		}
		this.scope = scope;
		this.enso  = this.options.element.getElement('input[name="ensophase"]:checked').get('value');
		this.tables = this.options.element.getElements('.oac-table');
		this.req = new Request.JSON({
			url: this.options.handler,
			link: 'cancel',
			onSuccess: function(res) { this.processData(res); }.bind(this),
			onFailure: function() { alert('There was an error fetching the data'); }
		});
		this.bound = {
			feCheck: function(){ console.log(this.scope.finalElement.get('value')); }.bind(this),
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
				this.drawGraph(this.data[tableindex][enso][rowindex], tableindex);
			}.bind(this),
			clickCol: function(colindex, col) {
				var table = col[0].getParents('table')[0],
				    tableindex = this.options.element.getElements('table').indexOf(table),
					enso = this.enso-1;
				this.drawGraph(this.data[tableindex][enso][colindex-1]);
			}.bind(this)
		};
		this.tables.each(function(table, index) {
			var rowtable = ((index === 0) || (index === 3)),
			    t = new HtmlTable(table, {
					selectable: rowtable,
					columnSelectable: !rowtable,
					hasIndexColumn: true,
					onRowFocus: this.bound.clickRow,
					onColFocus: this.bound.clickCol
			    });
			this.tables[index] = t;
		}, this);
		// First bind our custom events to the scope
		this.scope.finalQueue.add(this.bound.req);
		this.bound.req();
	},
	
	processData: function(data) {
		data.each(function(item, index) {
			this.axis[index] = Object.filter(item, function( v, k ) { 
				return typeOf(v) === 'string';
			});
			this.data[index] = Array.clone(item.data);
			this.tabledata[index] = Array.clone(item.data);
			item.data.each( function( itm, idx ) {
				itm.each( function( it, id ) {
					this.data[index][idx][id] = it.slice(1, (((index == 0) || (index == 3)) ? it.length-1 : undefined));
				}, this);
				if((index == 1) || (index == 2)) {
					this.data[index][idx] = this.data[index][idx].transpose();
				}
			}, this);
		}, this);
		this.genTables();
	},
	
	genTables: function() {
		// First get all the tables available (on the tabs)
		var enso = Number.from(this.enso)-1;
		this.tables.each(function( table, index ) {
			if(index === 3) enso = 0;
			this.tabledata[index][enso].each(function(row, i) { table.push(row); });
			table.toElement().addClass('oac-enso-'+this.enso);
		}, this);
	},
	
	drawGraph: function(data, tabindex) {
		// First we have to get the proper labels and then merge the two (label,data)
		// tabindex determines the type of graph we are drawing
		console.log(data);
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