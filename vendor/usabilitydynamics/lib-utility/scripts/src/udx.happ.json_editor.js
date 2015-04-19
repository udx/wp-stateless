/**
 * JSON Editor
 * It's just a wrapper for ud.json.editor
 * - loads ud.json.editor library and styles
 * - adds schema and validation functionality.
 *
 * @required ud.loader, ud.async
 * @author peshkov@UD
 */

Application.define( 'core.json_editor', function( args ) {

  /* Set arguments */
  args = jQuery.extend( true, {
    'container' : false, // ID of DOM element where editor/formatter will be initialized
    'instance' : 'editor', // Available value: 'editor', 'formatter'
    'options' : {},
    'json' : null,
    'callback' : function( editor ) { return editor; }, // Object can be got only using callback!
    // Set of actions listeners which are fired on json editor events
    'actions' : {
      /**
       * Called on Save event.
       * @param object json
       */
      'save': function( json ) {
        return null;
      },
      /**
       * Called after validation process
       * @param object result Validation's response
       */
      'validate': function( result ) {
        return null;
      }
    }
  }, typeof args === 'object' ? args : {} );

  /* Check container argument */
  var container = typeof args.container === 'object' ? args.container.get(0) : document.getElementById( args.container.replace( '#', '' ) );

  /**
   *
   */
  var editor = function( container, args ) {

    var self = this;

    self._args = typeof args.options == 'object' ? args.options : {};

    /* */
    self.options = jQuery.extend( true, {
      'change' : function() { return null }, // Set a callback method triggered when the contents of the JSONEditor change. Called without parameters.
      'history' : true, // Enables history, adds a button Undo and Redo to the menu of the JSONEditor. Only applicable when mode is 'editor'.
      'mode' : 'editor', // Set the editor mode. Available values: 'editor', 'viewer', or 'form'. In 'viewer' mode, the data and datastructure is read-only. In 'form' mode, only the value can be changed, the datastructure is read-only.
      //'name' : 'name_' + ( Math.ceil( Math.random() * 10000 ) ), // Initial field name for the root node. Can also be set using JSONEditor.setName(name)
      'search' : true // Enables a search box in the upper right corner of the JSONEditor. True by default.
    }, typeof args.options == 'object' ? args.options : {} );

    /* */
    self.__ = new JSONEditor (container, self.options, typeof self._args.json != 'undefined' ? self._args.json : null );

    /* */
    self.schema = false;

    /**
     *
     */
    self.save = function() {
      if( self.schema && !self.validate() ) {
        return false;
      }
      /* Special Handlers can be added here */
      try { self._args.actions.save( schema.get() ); } catch( error ) { return false; }
      return true;
    }


    /**
     *
     */
    self.set = function ( json, options ) {

      options = jQuery.extend( true, {
        'name' : null,
        'schema' : null
      }, typeof options === 'object' ? options : {} );

      self.schema = typeof options.schema === 'object' && options.schema != null ? options.schema : false;
      if( self.schema ) {
        jQuery( 'button.jsoneditor-validate-object', container ).show();
      } else {
        jQuery( 'button.jsoneditor-validate-object', container ).hide();
      }

      if( typeof options.name === 'string' ) {
        return self.__.set( json, options.name );
      } else {
        return self.__.set( json );
      }

    }

    /**
     *
     */
    self.get = function () {
      console.log( 'JSON EDITOR: GET' );
      return self.__.get();
    }


    /**
     * Validates the current object if schema is set and returns boolean
     *
     * @uses ud.json.validate
     */
    self.validate = function () {
      if( typeof self.schema !== 'object' || typeof validate === 'undefined' ) {
        return true;
      }
      self.last_validation = validate( self.get(), self.schema );
      /* Special Handlers can be added here */
      try { self._args.actions.validate( self.last_validation ); } catch( error ) { return false; }
      return self.last_validation.valid;
    }

    /**
     *
     */
    self._update_menu = function( enable ) {
      var save_button = jQuery( 'button.jsoneditor-save-object', container );

      var validate_button = document.createElement( 'button' );
      validate_button.title = 'Validate';
      validate_button.className = 'jsoneditor-menu jsoneditor-validate-object';
      validate_button.appendChild( document.createTextNode( "Validate" ) );
      validate_button.style.display = 'none';
      validate_button.onclick = function(){ self.validate(); }

      save_button.click( function() { self.save(); } ).after( validate_button );
    }


    /* Add default scope's properties to our wrapper */
    jQuery.each( self.__, function( i, e ) {
      if( typeof self[ i ] === 'undefined' ) {
        self[ i ] = e;
      }
    } );

    self._update_menu();

  };

  /* */
  async.parallel( {
    // Load required scripts
    'js' : function( callback ) {

      ud.load.js( {
        'JSONEditor' : '//ud-cdn.com/js/ud.json.editor/latest/ud.json.editor.js',
        'validate' : '//ud-cdn.com/js/ud.json.validate/1.0/ud.json.validate.js'
      }, function() {
        callback( null, true );
      });
    },
    // Loads required CSS if needed
    'css' : function( callback ) {
      window._flags = typeof window._flags !== 'undefined' ? window._flags : {};
      if( typeof window._flags.json_editor_css === 'undefined' || !window._flags.json_editor_css ) {
        ud.load.css( '//ud-cdn.com/js/ud.json.editor/latest/assets/ud.json.editor.css' );
        window._flags.json_editor_css = true;
      }
      callback( null, true );
    }
  }, function( err ) {

    if ( typeof args.callback === 'function' ) {
      /*  */
      var instance = null;
      switch( args.instance ) {
        case 'editor':
          instance = new editor( container, args );
          break;

        case 'formatter':
          instance = new JSONformatter ( container, args.options );
          break;
      }
      args.callback( instance );
    }

  } );

} );