$(function() {
	

	$.ajaxSetup({ dataType:'jsonp' });

	bus = {

		init: function() {

			_self = this;

			this.config = {
				rest: 'http://bus.seangirard.com/api/'
			}

			this.splashScreen();

			this.bindEvents();
			this.loadParams();
			
		},

		splashScreen: function() {
			var tmpl = Handlebars.compile( $('#bus-loading-routes-tmpl').html() );
	     $('#bus-app').html(tmpl( {} ));
		},

		throwError: function(error) {
			var tmpl = Handlebars.compile( $('#bus-error-tmpl').html() );
       $('#bus-app').html(tmpl( {error:error} ));
		},

		bindEvents: function() {
			$('body').on('click', '.bus-route', function(e) {
				//e.preventDefault();
				var hash = $(this).attr('href');
				_self.getRoute(hash.substring(1));
			});

			$('#stop-modal').on('show.bs.modal', function (e) {
				var $trigger = $(e.relatedTarget);
			  _self.getStopTimes($trigger.data('stop-id'));
			});

			$('body').on('change', 'input[name=bus-direction]:radio', function(e) {
				//console.log($(this).val());
				_self.did = $(this).val();
				_self.getRoute();
			});

			$('body').on('change', 'input[name=bus-service]:radio', function(e) {
				//console.log($(this).val());
				_self.sid = $(this).val();
				_self.getRoute();
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
      		switch ( hash ) {
      			case 'help':
      			case 'about':
      			case 'disclaimer':
      			case 'stop-modal':
      				break;
      			default:
      				_self.getRoute(hash.substring(1));
      				break;
      		}
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
      var tmpl = Handlebars.compile( $('#bus-tools-tmpl').html() );
      $('#bus-tools').html(tmpl( {api:api} ));
		},

		showRoutes: function() {
			var tmpl = Handlebars.compile( $('#bus-routes-tmpl').html() );
      $('#bus-app').html(tmpl( {api:_self.config.routes} ));
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
			if ( rid ) { 
				this.rid = rid;
			} else {
				rid = this.rid;
			}

			if ( !this.did ) {
				this.did = $('input[name=bus-direction]:radio').val();
			}
			if (!this.sid) {
				this.sid = $('input[name=bus-service]:radio').val();
			}

			if ( rid ) { 

				var api = { rid:rid }
				var tmpl = Handlebars.compile( $('#bus-loading-route-tmpl').html() );
	      $('#bus-app').html(tmpl( {api:api} ));

				$.ajax({ 
	        url: _self.config.rest+'timetable/'+rid+'/'+this.did+'/'+this.sid
	        ,data: {  }
	      })
	      .done(function(obj) {
	      	var api = obj;
	      	api.params = _self.params;

	        var tmpl = Handlebars.compile( $('#bus-route-tmpl').html() );
	        $('#bus-app').html(tmpl( {api:api} ));
	      })
	      .fail(function() {
	      	error = { msg: 'Could not load route.' }
	      	_self.throwError(error);
	      })
	      .always(function() {
	      });
			}

		},

		getStopTimes: function(stop) {
			var api = {  }
			var tmpl = Handlebars.compile( $('#bus-loading-stop-times-tmpl').html() );
	    $('#stop-times').html(tmpl( {api:api} ));
	    $('#stop-times-title').html('');

			$.ajax({ 
	        url: _self.config.rest+'stop/'+this.rid+'/'+this.did+'/'+this.sid+'/'+stop
	        ,data: {  }
	      })
	      .done(function(obj) {
	      	var api = obj;
	      	api.params = _self.params;
	        var tmpl = Handlebars.compile( $('#bus-stop-times-tmpl').html() );
	        $('#stop-times').html(tmpl( {api:api} ));
	        var tmpl = Handlebars.compile( $('#bus-stop-times-title-tmpl').html() );
	        $('#stop-times-title').html(tmpl( {api:api} ));
	      })
	      .fail(function() {
	      	error = { msg: 'Could not load stop.' }
	      	_self.throwError(error);
	      })
	      .always(function() {
	      });

		}


	}

	bus.init();

});