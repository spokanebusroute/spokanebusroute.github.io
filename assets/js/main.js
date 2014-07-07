$(function() {
	

	$.ajaxSetup({ dataType:'jsonp' });

	stand = {

		init: function() {

			_self = this;

			this.config = {
				rest: 'http://bus.seangirard.com/api/'
			}
		
			this.bindEvents();
			this.loadParams();
			
		},

		throwError: function(error) {
			var tmpl = Handlebars.compile( $('#stand-error-tmpl').html() );
       $('#stand-app').html(tmpl( {error:error} ));
		},

		bindEvents: function() {
			$('body').on('click', '.stand-route', function(e) {
				//e.preventDefault();
				var hash = $(this).attr('href');
				_self.getRoute(hash.substring(1));
			});

		},

		loadParams: function() {
			
			$.ajax({ 
        url: _self.config.rest+'params'
        ,data: {  }
      })
      .done(function(obj) {
      	_self.params = obj;

      	_self.loadTools();

      	var hash = window.location.hash;
      	if ( hash ) {
      		_self.getRoute(hash.substring(1));
      	} else {
      		_self.getRoutes();
      	}
      })
      .fail(function() {
      	error = { msg: 'Could not load app.' }
      	_self.throwError(error);
      })
      .always(function() {
      });

		},

		loadTools: function() {
			var api = {
      						params: _self.params
      					}				
      var tmpl = Handlebars.compile( $('#stand-tools-tmpl').html() );
      $('#stand-tools').html(tmpl( {api:api} ));
		},

		showRoutes: function() {
			var tmpl = Handlebars.compile( $('#stand-routes-tmpl').html() );
      $('#stand-app').html(tmpl( {api:_self.config.routes} ));
		},

		getRoutes: function() {
			if ( _self.config.routes ) {
				_self.showRoutes();
			} else {
				$.ajax({ 
	        url: _self.config.rest+'routes'
	        ,data: {  }
	      })
	      .done(function(obj) {
	      	_self.config.routes = obj;
	        _self.showRoutes();
	      })
	      .fail(function() {
	      	error = { msg: 'Could not load routes.' }
	      	_self.throwError(error);
	      })
	      .always(function() {
	      });
	    }

		},

		getRoute: function(rid) {
			var api = {}
			var tmpl = Handlebars.compile( $('#stand-loading-tmpl').html() );
      $('#stand-app').html(tmpl( {api:api} ));

			$.ajax({ 
        url: _self.config.rest+'timetable/'+rid+'/0'
        ,data: {  }
      })
      .done(function(obj) {
      	var api = obj;
      	api.params = _self.params;

        var tmpl = Handlebars.compile( $('#stand-route-tmpl').html() );
        $('#stand-app').html(tmpl( {api:api} ));
      })
      .fail(function() {
      	error = { msg: 'Could not load route.' }
      	_self.throwError(error);
      })
      .always(function() {
      });
			

		}


	}

	stand.init();


	/*
	$('.find-supper').typeahead('destroy');

	
	$('.find-supper').typeahead([
	{
		hint: false,
		name: 'find-supper',
		//prefetch: 'search.json',
		prefetch: {
			url: 'search.json',
    	//url: 'http://twitter.github.io/typeahead.js/data/countries.json',
    	ttl: 1 // in milliseconds
		},  
		template: [                                                                 
        '<a style="" href="{{href}}">{{title}}</a>'
    ].join(''),                                                                 
    engine: Hogan
	}
	]);

	$('.find-supper').on('typeahead:selected', function (e, datum) {
    //console.log(datum);
    window.location.href = datum.href;
	});
	*/

});