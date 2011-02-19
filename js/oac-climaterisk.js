var OACClimateRisk = new Class({
	Implements: [Options],
	
	options: {
		handler: (''.toURI().get('directory'))+'handler.php',
		element: document.id('climaterisk-ui-container'),
		defaultSelect: []
	},
	data:   [],
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
						vartype: 'RAIN',
						location: this.scope.finalElement.get('value')
					}
				});
			}.bind(this)
		};
		// First bind our custom events to the scope
		this.scope.finalQueue.add(this.bound.req);
	},
	
	processData: function(data) {
		data.each(function(item, index) {
			var rowtable = ((index === 0) || (index === 3)),
			    table, enso;
			this.tables[index].getElement('tbody').empty();
			this.axis[index] = Object.filter(item, function( v, k ) { 
				return typeOf(v) === 'string';
			});
			this.data[index] = Array.clone(item.data);
			enso = Number.from(this.enso)-1;
			if(index === 3) enso = 0;
			table = new HtmlTable(this.tables[index], {rows: item.data[enso], selectable : rowtable});
			item.data.each( function( itm, idx ) {
				itm.each( function( it, id ) {
					this.data[index][idx][id] = it.slice(1, (((index == 0) || (index == 3)) ? it.length-1 : undefined));
				}, this);
				if((index == 1) || (index == 2)) {
					this.data[index][idx] = this.data[index][idx].transpose();
				}
			}, this);
		}, this);
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