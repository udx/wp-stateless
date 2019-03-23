/**
 * View Model
 *
 * @required jQuery, knockout, knockout.mapping, knockout.ud
 * @author peshkov@UD
 */

Application.define( 'core.view_model', function( args ) {

  /* Set arguments */
  args = jQuery.extend( true, {
    '_required': {},
    'scope': false, // Application's object
    'model': {}, //
    'view': false, // HTML data or template's url
    'container': false, // HTML container
    'args': {
      // Localization strings
      'l10n': {
        'remove_confirmation': 'Are you sure you want to remove it?'
      }
    },
    // Set of actions listeners which are fired on view model 'triggers'
    'actions': {
      // Called on view_model update. view_model is updated on every selection ( see app.js -> Sammy implementation )
      'update': function() {
        return null;
      },
      // Called before ko.applyBindings() function
      'pre_apply': function( self, callback ) {
        callback( null, true );
      },
      // Child Constructor. Called after ko.applyBindings() function
      'init': function( self, callback ) {
        callback( null, true );
      },
      // Called after data adding
      'add_data': function() {
        return null;
      },
      // Called after data removing
      'remove_data': function() {
        return null;
      },
      // Additional callback
      'callback': false
    },
    // Callback should not be overwritten! If you want to add your callback use actions.callback
    'callback': function( error, data ) {
      var self = this;
      if( typeof self.actions === 'object' && typeof self.actions.callback === 'function' ) {
        return self.actions.callback( error, data );
      } else {

        if( error && error instanceof Error ) {
          console.error( error.message, data );
        }

        return data;

      }
    }
  }, typeof args === 'object' ? args : {} );

  /* Check container argument */
  var container = ( args.container && typeof args.container !== 'object' ) ? jQuery( args.container ) : args.container;

  if( !container || typeof container.length === 'undefined' || !container.length > 0 ) {
    return args.callback( new Error( 'ko.view_model. Container is missing, or incorrect.' ), false );
  }

  /* Appends View if it exists */
  if( args.view ) {

    /* Determine if view is link we try to get template using ajax. */
    if( /^((https?|ftp):)?\/\/([\-A-Z0-9.]+)(\/[\-A-Z0-9+&@#\/%=~_|!:,.;]*)?(\?[A-Z0-9+&@#\/%=~_|!:,.;]*)?/i.test( args.view ) ) {
      jQuery.ajax( {
        url: args.view,
        async: false,
        dataType: 'html',
        complete: function( r, status ) {
          args.view = r.responseText;
        }
      } );
    }
    container.html( args.view );
  }

  var html = container.html();

  container.html( '' ).addClass( 'ud_view_model ud_ui_loading' ).append( '<div class="ud_ui_spinner"></div>' ).append( '<div class="ud_ui_prepared_interface"></div>' ).find( '.ud_ui_prepared_interface' ).html( html );

  /**
   * Creates View_Model
   */
  var vm = function( args, container ) {

    var self = this;

    /* Determines if view_model already applied Bindings ( ko.applyBinding )  */
    self._applied = false;

    /* Arguments */
    self._args = args;

    /* Application's object */
    self.scope = args.scope;

    /* Application core functions */
    self.core = args.scope.core;

    /* Socket connection */
    self.socket = typeof args.scope.socket === 'object' ? args.scope.socket : false;

    /* DOM */
    self.container = container;

    /**
     * Pushes new element to array.
     *
     * Example of usage:
     * data-bind="click: $root.add_data.bind( $data, $root.some_array, $root.vhandler )"
     * where $root.vhandler is a function, which creates data.
     *
     * $root.vhandler example:
     * self.handler = function() {
     *   var self = this;
     *   self.arg1 = ko.observable( 'value1' );
     *   self.arg2 = 'value2';
     * }
     *
     * @param observableArray item. Where we want to add new data
     * @param mixed vhandler. Name of function or function which inits new data
     * @param object view_model. The current view_model object
     * @param object event.
     * @author peshkov@UD
     */
    self.add_data = function( item, vhanlder, view_model, event ) {
      if( typeof vhanlder == 'function' ) {
        item.push( new vhanlder );
      } else if( typeof view_model[ vhanlder ] === 'function' ) {
        item.push( new view_model[ vhanlder ]() );
      }
      try { self._args.actions.add_data( self, event, item, vhanlder ) }
      catch( e ) { self._args.callback( e, view_model ); }
    };

    /**
     * Adds message (success/warning/error)
     *
     * @author peshkov@UD
     */
    self.alert = function( message, type ) {
      var c = 'alert-success';
      if( typeof type !== 'undefined' ) {
        switch( type ) {
          case 'error':
            c = 'alert-error';
            break;
          case 'warning':
            c = '';
            break;
        }
      }
      var html = '<div class="container alert fade in ' + c + '"><button type="button" class="close" data-dismiss="alert">&times;</button>' + message + '</div>';
      self.container.prepend( html );
    };

    /**
     * Removes data from array.
     *
     * Example of usage:
     * data-bind="click: $root.remove_data.bind( $data, $root.some_array )"
     *
     * @param observableArray item. Where we want to remove data
     * @param mixed data. Data which should be removed from array.
     * @param object event.
     * @author peshkov@UD
     */
    self.remove_data = function( item, data, event ) {

      if( confirm( self.l10n.remove_confirmation ) ) {
        item.remove( data );
      }

      try {
        self._args.actions.remove_data( self, event, item, data )
      } catch( e ) { self._args.callback( e, self ); }
    };

    /**
     * Wrapper for ko.applyBindings()
     *
     * Calls before ko.applyBindings() - self.pre_apply()
     * Calls after ko.applyBindings()  - init()
     *
     * @TODO: NEED TO IMPLEMENT ASYNC HERE TO HAVE ABILITY TO LOAD ALL DATA FROM SOCKET BEFORE APPLYBINDING. peshkov@UD
     * @param array args. Optional
     * @author peshkov@UD
     */
    self.apply = function( args ) {
      var self = this;

      var element = self.container.get( 0 );

      if( self._applied ) {
        return self._args.callback( null, self, element );
      }

      async.series({
        'pre_apply': function( callback ){
          /* Special Handlers can be added here */
          try {
            self._args.actions.pre_apply( self, callback );
          } catch( error ) {
            callback( 'Error occured on VM pre_apply event' );
          }
        },
        'apply_bindings': function( callback ){
          ko.applyBindings( self, self.container.get( 0 ) );
          self._applied = true;
          callback( null, true );
        },
        'init': function ( callback ) {
          self.container.removeClass( 'ud_ui_loading' ).addClass( 'ud_ui_applied' );
          /* Special Handlers can be added here */
          try {
            self._args.actions.init( self, callback );
          } catch( error ) {
            callback( 'Error occured on VM init event' );
          }
        }
      },
      function(err, results) {
          self.update( typeof args !== 'undefined' ? args : [] );
          return self._args.callback( null, self );
      });

      return null;

    };

    /**
     * It's just a wrapper.
     * It should be called if view_model should be updated
     *
     * @param array args. Optional
     * @author peshkov@UD
     */
    self.update = function( args ) {
      var self = this;
      /* Special Handlers can be added here */
      try { self._args.actions.update( self, typeof args !== 'undefined' ? args : [] ); } catch( error ) { self._args.callback( error, self ); }
    };

    /* Add view_model data which is not related to args from model. */
    var m = {};
    var args = {};

    if( typeof self._args.model === 'object' ? self._args.model : {} ) {
      jQuery.each( self._args.model, function( i, e ) {
        if( typeof self._args[ i ] === 'undefined' ) { m[ i ] = e; } else { args[ i ] = e; }
      });
    }

    /* Combine arguments */
    self._args = jQuery.extend( true, self._args, args );

    /* All additional methods and elements for the current model are added here */
    self = jQuery.extend( true, self, typeof self._args.args === 'object' ? self._args.args : args, m );

  };

  /* Bind Knockout */
  return new vm( args, container );

});

