/** =========================================================
 * jquery.ud.elastic_filter.js v0.5
 * http://usabilitydynamics.com
 * =========================================================
 *
 * Commercial use requires one-time license fee
 * http://usabilitydynamics.com/licenses
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"..
 * http://usabilitydynamics.com/warranty
 *
 * Copyright 2012-2013 Usability Dynamics, Inc. (usabilitydynamics.com)
 *                               *
 * Console Log Commands
 * ====================
 * Last Query: elastic_filter.computed_query();
 *
 * ========================================================= */

new (function( args ) {

  // Merge Custom Args into Default Args and then into Self
  var app = window.ef_app = jQuery.extend( true, this, {

    // Local Settings.
    'settings': {

      'account_id' : undefined,

      'access_key' : undefined,

      'per_page': 10,

      'debug': false,

      'visual_debug': false,

      'url': 'https://cloud.usabilitydynamics.com',

      'index' : ''

    },

    // Result Items.
    'documents'   : [],

    // Facet Objects.
    'facets'      : [],

    // Available Sorting Types
    'sort_options': [],

    // Defaults from CloudAPI
    'defaults'    : null,

    // Overall App State
    'state'        : 'loading',

    // Query Object
    'query'        : {

      // Full Text Search
      'full_text': null,

      // Single Field Search(es)
      'field'    : [],

      // Multiple Term Search(es)
      'terms'    : [],

      // Range Search(es)
      'range'    : [],

      // Fuzzy Term / Date / Number Search(es)
      'fuzzy'    : [],

      // Start Result
      'from'     : 0

    },

    'size' : 0,

    // Sort Order
    'sort'     : null,

    'bindings_initialized': [],

    // Total available results. Integer
    'total'        : null,

    // General Status Message. String.
    'message'      : '',

    // Set to true once fully loaded
    'elastic_ready': false,

    // Socket Session ID
    'session_id'   : null,

    // Global Variable for Data Storage
    'global'       : window.__elastic_filter = {},

    // Initialization Complete
    'ready'        : function() {},

    // Required Assets
    '_required'    : {
      'io'         : '//ud-cdn.com/js/ud.socket/1.0.0/ud.socket.js',
      'ko.mapping' : '//ud-cdn.com/js/knockout.mapping/2.3.2/knockout.mapping.js',
      'async'      : '//ud-cdn.com/js/async/1.0/async.js',
      'ud.ko'      : '//ud-cdn.com/js/knockout.ud/1.0/knockout.ud.js',
      'ud.select'  : '//ud-cdn.com/js/ud.select/3.2/ud.select.js',
      'jq-lazyload': '//ud-cdn.com/js/jquery.lazyload/1.8.2/jquery.lazyload.js'
    },

    // Application Event Log
    '_log'         : {

      // Active Socket Subscriptions
      'subscriptions': {},

      // Search History
      'search'       : [],

      // Debug History
      'debug'        : [],

      // Active Profilers
      'profilers'    : {}

    }

  }, args );

  /**
   * Document constructor. Use to normalize incoming object.
   */
  app._document = function( item ) {return item;};

  /**
   * Facet constructor. Use to normalize incoming object.
   */
  app._facet = function( item, title ) {

    var options = [];
    for( var i in item.terms ) {
      options.push( {
        'text' : item.terms[i].term,
        'id': item.terms[i].term
      });
    }
    item._label = title;

    // Objervable dropdown options
    item.options = ko.observableArray( options );

    // Observable value
    item.value = ko.observable();

    // Observable multiple options
    item.select_multiple = ko.observableArray();

    // Build Terms Query based on Facet Selections
    item.select_multiple.subscribe( function( value ) {

      // Organize terms inside query
      app.view_model.query.terms.remove(
        ko.utils.arrayFirst( app.view_model.query.terms(), function( term ) {
          return typeof term[title] != 'undefined';
        } )
      );

      if( value.length ) {
        var object = {};
        object[title] = value;
        app.view_model.query.terms.push( object );
      }

      // Set default size
      app.view_model.size( parseInt( app.view_model.settings.per_page() ) );

      // Do request
      app.search_request();

    } );

    // Add CSS class for wrapper of facet to be able to manage its appearence in different states
    item.css_class = ko.computed(function(){
      var len = typeof item.options() !== 'undefined' ? item.options().length : 0;
      var len_class = len == 1 ? 'eff_single_option' : (!len ? 'eff_no_options': 'eff_options_'+len);
      return len_class+' ef_facet_'+title.replace(' ','_');
    });

    return item;
  };

  /**
   * Profiler. A note is required to output.
   *
   * @version 0.1.1
   * @updated 0.1.5
   */
  app.profile = function( name, note, args ) {

    // If note is set, output timer.
    if( app._log.profilers[ name ] && note ) {

      var output = [ 'Profiler', name, note, ( new Date().getTime() - app._log.profilers[ name ] ) / 1000 ];

      if( args ) {
        output.push( args );
      }

      return app.log.apply( this, output );
    }

    // Mark app.profiler as active
    app._log.profilers[ name ] = new Date().getTime();

    return this;
  }

  /**
   * Internal logging function
   *
   * @updated 0.4
   * @since 0.1
   */
  app.log = function() {

    if ( typeof console === 'undefined' ) {
      return arguments ? arguments : null;
    }

    if( arguments[0] instanceof Error ) {
      console.error( 'ElasticFilter Fatal Error:', arguments[0].message );
      return arguments;
    }

    console.log( arguments );

    return arguments ? arguments : null;

  };

  /**
   * Debug Logging. Requires debug to be set to true, unless an Error object is passed.
   *
   * @author potanin@UD
   */
  app.debug = function() {

    app._log.debug.push( {'time': new Date().getTime(), 'data': arguments} );

    return ( ko.utils.unwrapObservable( app.settings.debug ) || arguments[0] instanceof Error ? app.log.apply( this, arguments ) : arguments );

  }

  /**
   * Initial Asynchronous Initialization
   *
   * @todo Add Meta Mapping and have them return labels
   * @author potanin@UD
   */
  app.init = function() {

    utils.back_support();

    app.debug( 'init' );

    // Make Things Happen.
    async.auto( {

      /**
       * Verify Dependancies and Create Knockout Bindings for Elastic Filter Elements
       */
      'binding_handlers': [ function( next, report ) {
        app.debug( 'init', 'auto', 'binding_handlers' );

        if( typeof ko !== 'object' ) {
          return next( new Error( 'Missing Knockout.' ) );
        }

        if( typeof io !== 'object' ) {
          return next( app.debug( new Error( 'Missing Socket.io.' ) ) );
        }

        // Main Configurations. Merges DOM Settings into app.settings
        ko.bindingHandlers[ 'elastic-filter' ] = {

          'init': function( element, valueAccessor ) {
            app.log( 'binding_handlers', 'elastic-filter', 'init' );
            ko.mapping.fromJS( {'settings': jQuery.extend( true, {}, app.settings, ko.utils.unwrapObservable( valueAccessor() ) )}, app.view_model );
            app.bindings_initialized.push('elastic-filter');
          }

        }

        /**
         * Handler for fulltext search function. Similar to "select"
         *
         */
        ko.bindingHandlers[ 'fulltext-search' ] = {

          'init': function( element, valueAccessor, allBindingsAccessor, viewModel, bindingContext ) {
            app.debug( 'binding_handlers', 'fulltext-search', 'init' );

            if( typeof jQuery().select2 == 'undefined' ) return new Error( 'Select2 library is required for Fulltext Search feature' );

            jQuery( element ).select2(valueAccessor());

            if ( typeof allBindingsAccessor().elastic_settings != 'undefined' )
              ko.mapping.fromJS( {'settings': allBindingsAccessor().elastic_settings}, app.view_model );

            ko.utils.domNodeDisposal.addDisposeCallback( element, function() {
              jQuery( element ).select2( 'destroy' );
            });

            app.bindings_initialized.push('fulltext-search');
          }

        };

        return next( null, [ ko.bindingHandlers ] );

      }],

      /**
       * Create Observables and Computed Functions
       *
       */
      'observable': [ 'binding_handlers', function( next, report ) {
        app.debug( 'init', 'auto', 'observable' );

        // Create Initial JSON Object from App, and Extend Template Functions into Root
        app.view_model = ko.mapping.fromJS( app, {

          'ignore': utils.get_methods( app ).concat( '_log', '_required', 'model_functions', 'facet_functions', 'document_functions', 'utils', 'success', 'global' )

        });

        /*app.view_model.query.full_text.subscribe(function(value){
          app.search_request();
        });*/

        // Execute Binding Magic
        ko.applyBindings( app.view_model );

        // Expand Additional Template Functions into View Model
        jQuery.extend( true, app.view_model, app.facet_functions, app.document_functions );

        // Always a success.
        return next( null, app );

      }],

      /**
       * Initialize Socket Connection
       *
       */
      'socket': [ 'observable', function( next ) {
        app.debug( 'init', 'auto', 'socket' );

        if ( !app.bindings_initialized.length ) return;

        if ( typeof app.view_model.settings[ 'account_id' ] == 'undefined' ||
             typeof app.view_model.settings[ 'access_key' ] == 'undefined' ) {
          return next( new Error('Empty credentials.') );
        }

        ud.socket.connect( ko.utils.unwrapObservable( app.view_model.settings[ 'url' ] ), {
          'resource': 'websocket.api/1.5',
          'account-id': ko.utils.unwrapObservable( app.view_model.settings[ 'account_id' ] ),
          'access-key': ko.utils.unwrapObservable( app.view_model.settings[ 'access_key' ] )
        }, function( error, socket ) {

          app.socket = socket;

          // Break initializtion chain since a WebSocket connection is required
          if( error ) {
            return next( error );
          }

          app.socket.once( 'reconnect', function() {
            app.debug( new Error( 'Reconnecting, re-initializing ElasticFilter.' ). arguments );
            app.init();
          });

          app.view_model.session_id( app.socket.sessionid );

          return next( null, app.socket );

        });

      }],

      /**
       * Load Settings and setup Defaults. Settings request could also be filtered via { 'path': 'defaults' }
       *
       */
      'settings': [ 'socket', function( next, report ) {
        app.debug( 'init', 'auto', 'settings' );

        app.socket.request( 'get', 'api/v1/settings', function( error, response ) {

          app.log('settings', response);

          if( error || !response ) {
            return next( app.log( error || new Error( 'Request for index settings returned no results.' ) ) );
          }

          ko.mapping.fromJS({'settings': response.settings}, app.view_model );

          app.ready();

          return next( null, app.settings );

        });

      }],

      /**
       * Fully Loaded, make initial request
       *
       */
      'ready': [ 'settings', function( next, report ) {
        app.debug( 'init', 'auto', 'ready', report );

        // Make Default Search Requests
        app.view_model.sort( ko.mapping.toJS( app.view_model.settings.defaults.sort ) );
        app.view_model.size( parseInt( app.view_model.settings.per_page() ) );

        // Do this only if elastic-filter binding exists on the page
        if( app.bindings_initialized.indexOf( 'elastic-filter' ) !== -1 ) {
          app.search_request();
        }

        // Fully loaded.
        app.view_model.elastic_ready( true );

        next( null, app.view_model.elastic_ready() );

      }]

    }, app.initialized );

    return this;

  }

  /**
   * Execute Search Request. Gets value from computed_query observable
   *
   * @since 0.1.5
   */
  app.search_request = function( callback ) {

    app.profile( 'search_request' );

    var request = {
      index  : app.view_model.settings.index(),
      query  : jQuery.extend( true, {'match_all': {}}, ko.mapping.toJS( app.view_model.query ) ),
      facets : ko.mapping.toJS( app.view_model.settings.facets ),
      size   : app.view_model.size(),
      sort   : app.view_model.sort()
    }

    request = utils.clean_object( request );

    /** Temp logger */
    app.computed_query = function() {
      return request;
    }

    app.log( 'search_request_data', 'Data Before Send', request );

    app.view_model.state( 'loading' );

    app.socket.request( 'post', 'api/v1/search', request, function( error, response ) {

      /** Temp logger */
      app.last_response = function() {
        return response;
      }

      //** Profiling */
      app.profile( 'search_request', 'Have Cloud Response.', response );
      app.profile( 'search_request', 'Request Mapping Start.' );

      var documents = [];
      jQuery.each(typeof response.documents !== 'undefined' ? response.documents : [], function(){
        documents.push(arguments[1]);
      });

      app.view_model.documents( ko.utils.arrayMap( documents, function( item ) {
        return new app._document( item );
      }));

      for( var i in typeof response.meta.facets != 'undefined' ? response.meta.facets : {} ) {
        var found = false;

        ko.utils.arrayForEach( app.view_model.facets(), function( existing_facet ) {
          if( i == existing_facet._label ) {
            //existing_facet.options( item.options );
            found = true;
          }
        });

        if( !found ) {
          app.view_model.facets.push( new app._facet( response.meta.facets[i], i ) );
        }
      }

      app.view_model.total( typeof response.meta.total != 'undefined' ? response.meta.total : 0 );
      app.view_model.state( 'ready' );

      //** Profiling */
      app.profile( 'search_request', 'Request Mapping Complete.' );

      return typeof callback === 'function' ? callback( error, response ) : response;

    });

  };

  /**
   * Custom search requester.
   */
  app.custom_search = function( callback ) {
    app.profile( 'custom_search_start' );

    var request = {
      index  : app.view_model.settings.index(),
      query  : { "query_string": { "query": app.view_model.query.full_text() } },
      size   : app.view_model.size(),
      sort   : app.view_model.sort()
    }

    app.socket.request( 'post', 'api/v1/search', request, callback );

    app.profile( 'custom_search_end' );

    return true;
  }

  /**
   * Get JSON Output of full View Model
   *
   * @since 0.1.5
   */
  app.get_json = function() {
    return JSON.parse( ko.mapping.toJSON( app.view_model ) );
  };

  /**
   * Post Initialization
   *
   */
  app.initialized = function( error, report ) {
    app.debug( 'initialized', arguments );

    app.initialization = app.log( error ? 'Initializaiton Failed.' : 'Initializaiton Done.', ( report ? report : error ) );

    if( typeof app.ready === 'function' ) {
      app.ready( app, error, report );
    }

    return app.initialization;

  };

  /**
   * Facet Functions
   *
   */
  app.facet_functions = {

    /**
     * Single Facet Rendered in DOM
     *
     * @event afterRender
     */
    'facet_after_render': function( data ) {
    },

    /**
     * Before Single Facet is Removed from DOM
     *
     * @event beforeRemove
     */
    'facet_before_remove': function( element ) { // app.debug( 'facet_functions', 'facet_before_remove' );
      ko.removeNode( element );
    },

    /**
     * After a Single Facet is Added to DOM
     *
     * @event afterAdd
     */
    'facet_after_add': function( element, something, object ) {
    },

    /**
     * Get Best Template for Facet
     *
     * @todo Use .notifySubscribers() to trigger request()
     */
    'facet_template': function( facet ) { // app.debug( 'facet_functions', 'facet_template' );

      var templates = [];

      // Add Possible Templates
      switch( facet && facet._type ) {

        case 'terms':
          templates.push( 'template-facet-terms' );
          break;

      }

      // Default Facet Template
      templates.push( 'template-default-facet' );

      return app.model_functions._get_template( templates );

    },

    /**
     * Submit Search
     */
    'submit_facets': function( data, event ) {
      app.debug( 'facet_functions', 'submit_facets' );
      app.view_model.size( parseInt( app.view_model.settings.per_page() ) );
      app.search_request();
      return;
    }

  };

  /**
   * Document Functions
   *
   */
  app.document_functions = {

    /**
     * Single Document Rendered in DOM
     *
     * @event afterRender
     */
    'document_after_render': function( data, object ) {
    },

    /**
     * Before Single Document is Removed from DOM
     *
     * @event beforeRemove
     */
    'document_before_remove': function( element, something, object ) { // app.debug( 'document_functions', 'document_before_remove' );
      ko.removeNode( element );
    },

    /**
     * After a Single Document is Added to DOM
     *
     * @event afterAdd
     */
    'document_after_add': function( element, something, object ) {
    },

    /**
     * Get Best Template for Document
     *
     */
    'document_template': function( document ) { // app.debug( 'document_functions', 'document_template' );
      return app.model_functions._get_template( ['template-default-document' ] );
    },

    /**
     * Toggle Sort Option
     */
    'sort_by': function( data, event ) {
      app.debug( 'document_functions', 'sort' );

      jQuery( event.target ).trigger( 'sort', [data] );

      var field = jQuery( event.target ).data( 'field' );

      var sort = ko.utils.arrayFirst( data.sort_options, function( option ) {
        return typeof option[field] != 'undefined';
      } );

      if( app.view_model.sort() ) {
        var existing_sort = typeof app.view_model.sort()[field] != 'undefined' ? app.view_model.sort()[field] : false;
        if( existing_sort )
          sort[field].order = app.view_model.sort()[field].order == 'desc' ? 'asc' : 'desc';
      }

      app.view_model.sort( sort );
      app.view_model.size( parseInt( app.view_model.settings.per_page() ) );
      app.search_request();
    },

    /**
     * Determine if sort type is active by sort
     */
    'is_active_sort': function( data ) {
      if( !app.view_model.sort() ) return 'disabled';
      return typeof app.view_model.sort()[data] != 'undefined' ? 'active' : 'disabled';
    },

    /**
     * If there are more results available
     */
    'have_more': function( data, event ) {
      app.debug( 'document_functions', 'have_more()' );

      // Create Obervable if it has not been created yet.
      app.have_more = ko.computed( {
        'owner': this,
        'read' : function() {
          return ( app.view_model.total() > app.view_model.documents().length ) ? true : false;
        }
      } );

      return app.have_more();

    },

    /**
     * Load More
     *
     */
    'load_more': function( data, event ) {
      app.debug( 'document_functions', 'load_more()' );
      app.view_model.size( parseInt( app.view_model.size() ) + parseInt( app.view_model.settings.per_page() ) );
      app.search_request();
    }

  };

  /**
   * Template Functions, expanded into _view_model
   *
   */
  app.model_functions = {

    /**
     * Accepts an array of Knockout template IDs, and returns first one that exists in document
     *
     */
    '_get_template': function( templates ) { // app.debug( 'model_functions', '_get_template' );

      for( i in templates ? templates : [] ) {
        if( document.getElementById( templates[i] ) ) {
          return templates[i];
        }
      }

      return templates[0];

    },

    /**
     * Delete a Document by ID (Could be triggered by subscribed event)
     *
     * elastic_filter.observable._documents.remove( function( item ) { item.id = 6552 } );
     *
     * @todo Does not work. Perhaps some issues with computation..
     */
    '_remove_item': function( index, id ) { // app.debug( 'model_functions', '_remove_item' );

      var items = this[ index ];

      ko.utils.arrayFirst( items, function( item ) {

        if( item && parseInt( item.id ) === parseInt( id ) ) {
          items.remove( document );
          ko.utils.arrayRemoveItem( item );
        }

      } );

    }

  };

  /**
   * Utility Functions
   *
   * @author potanin@UD
   */
  var utils = app.utilis = {

    /**
     * Adds Compatibility with Crappy Browsers
     *
     */
    'back_support': function() {

      //** IE fix for unsupported methods */
      Object.keys = Object.keys || (function() {
        var hasOwnProperty = Object.prototype.hasOwnProperty, hasDontEnumBug = !{toString: null}.propertyIsEnumerable( "toString" ), DontEnums =
          [
            'toString', 'toLocaleString', 'valueOf', 'hasOwnProperty', 'isPrototypeOf', 'propertyIsEnumerable',
            'constructor'
          ], DontEnumsLength = DontEnums.length;

        return function( o ) {
          if( typeof o != "object" && typeof o != "function" || o === null ) {
            throw new TypeError( "Object.keys called on a non-object" );
          }

          var result = [];
          for( var name in o ) {
            if( hasOwnProperty.call( o, name ) ) {
              result.push( name );
            }
          }

          if( hasDontEnumBug ) {
            for( var i = 0; i < DontEnumsLength; i++ ) {
              if( hasOwnProperty.call( o, DontEnums[i] ) ) {
                result.push( DontEnums[i] );
              }
            }
          }

          return result;
        };
      })();

    },

    /**
     * I know they are not technically methods.
     *
     * @version 0.1.1
     * @author potanin@UD
     */
    'get_methods': function( object ) {

      var functions = jQuery.map( object, function( item, name ) {
        if( typeof item === 'function' ) {
          return name;
        }
      } );

      return functions;

    },

    /**
     * Load Inline JSON Editor (Needs CSS Fixes and some Logic Fixes to be useful)
     *
     * @version 0.1.1
     * @author potanin@UD
     */
    'json_editor': function() {

      ud.load.js( {'JSONEditor': 'http://ud-cdn.com/js/ud.json.editor.js'}, function() {

        ud.load.css( 'http://ud-cdn.com/js/assets/ud.json.editor.css' );

        app.json_editor = new JSONEditor( jQuery( '.elastic_json_editor' ).get( 0 ), {
          'indentation': 2,
          'search'     : false,
          'history'    : false
        } );

      } );

    },

    /**
     * Test if Array contains an item
     *
     * @version 0.1.1
     */
    'contains': function( a, obj ) {

      for( var i = 0; i < a.length; i++ ) {
        if( a[i] === obj ) {
          return true;
        }
      }

      return false;

    },

    /**
     * Remove empty values
     *
     * @version 0.1.1
     */
    'clean_object': function( object, args ) {

      jQuery.extend( true, {
        'strip_values': []
      }, args );

      for( i in object ) {

        if( !object[i] ) {
          delete object[i];
        }

        if( object[i] === null ) {
          delete object[i];
        }

        if( typeof object[i] === 'object' ) {

          if( Object.keys( object[i] ).length ) {
            object[i] = utils.clean_object( object[i], args );
          } else {
            delete object[i];
          }

        }

      }

      return object;
    }

  }

  // Load Required Assets, and then Initialize
  ud.load.js( app._required, function() {
    jQuery( document ).trigger( 'elastic_filter::initialize' );
  });

  return this;

})();