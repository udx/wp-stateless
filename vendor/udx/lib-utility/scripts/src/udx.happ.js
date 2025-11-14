/**
 * UD HAPP. Single page Application's Framework
 *
 * @required jquery, knockout, knockout.mapping
 * @version 0.1
 * @author peshkov@UD
 * @copyright Usability Dynamics, inc
 */

var ud, ko, Application = {};

Application.version = '0.1';

/**
 * Expands application.
 * All models and specific app libraries must be added using this function.
 *
 * @author peshkov@UD
 */
Application.define = function( instance, data ) {
  if( typeof this.__ !== 'object' ) return null;
  var self = this.__;
  instance = instance.split( '.' );
  switch( instance[0] ) {
    case 'core':
      self.core = self.core || {};
      self.core[ instance[ 1 ] ] = data;
      break;
    case 'model':
      self.models = self.models || {};
      self.models[ instance[ 1 ] ] = data;
      break;
  }
}

/**
 * Loads application ( init and render it )
 *
 * @author peshkov@UD
 */
Application.load = function( args ) {

  /** If application is already loaded, we just return it. */
  if( typeof this.__ === 'object' ) {
    return this.__;
  }

  /* Try to determine if model's name is set or model's object already exists */
  var self = jQuery.extend( true, {
    /* */
    '_required': {},
    /* Redefines version of HAPP. It's related to HAPP core files: which version files should be loaded. for example 'latest'. */
    'version': Application.version,
    /* host */
    'url': '//' + location.host + '/',
    /* url to models */
    'model_url': '//' + location.host + '/model/',
    /* url to views */
    'view_url': '//' + location.host + '/view/',
    /*  */
    'modules': {},
    /* */
    'models': {},
    /* Socket settings if socket is needed. See socket.js for detailed information. */
    'socket': false,
    /* Default module  */
    'default_module': false,
    'ui': {
      'content': '#content'
    },
    'listeners': {
      /* Application is rendered */
      'rendered': function( self ) {
        return null;
      },
      /* Filter which can be used for menu item's HTML appending */
      'add_menu_item': function( item, module, self ) {
        return item;
      },
      /* Filter which can be used for module content wrapper's HTML appending */
      'add_content_wrapper': function( item, module, self ) {
        return item;
      },
      /* Called after section selected. See Sammy implementation */
      'section_selected': function( section, self ) {
        return null;
      },
      /* Called on successfull connection to socket */
      'socket_connected': function( self ) {
        return null;
      }
    }
  }, typeof args === 'object' ? args : ( typeof args === 'string' ? { 'model': args } : {} ), {} );

  /* Application is ready and rendered */
  self.rendered = false;

  /* */
  self.sections = {};

  /* */
  self._required = jQuery.extend( true, self._required, {
    'js': {
      //'_': '//ud-cdn.com/js/lodash/1.0.0/lodash.js',
      'async': '//ud-cdn.com/js/async/1.0/async.js',
      'ko': '//ud-cdn.com/js/knockout/latest/knockout.js',
      'ko.mapping': '//ud-cdn.com/js/knockout.mapping/latest/knockout.mapping.js',
      'knockout.ud': '//ud-cdn.com/js/knockout.ud/latest/knockout.ud.js',
      'Sammy': '//ud-cdn.com/js/sammy/0.7.1/sammy.js',
      'io': '//ud-cdn.com/js/ud.socket/1.0.0/ud.socket.js',
      //'jQuery.cookie': '//ud-cdn.com/js/jquery.cookie/1.7.3/jquery.cookie.js',
      'bootstrap': '//ud-cdn.com/js/bootstrap/2.2.2/bootstrap.min.js',
      /* Specific Framework libraries */
      'Application.__.core.view_model': '//ud-cdn.com/js/ud.happ/' + self.version + '/core/view_model.js',
      'Application.__.core.socket': '//ud-cdn.com/js/ud.happ/' + self.version + '/core/socket.js',
      'Application.__.core.json_editor': '//ud-cdn.com/js/ud.happ/' + self.version + '/core/json_editor.js'
    },
    'css': {
      //'bootstrap': '//ud-cdn.com/js/bootstrap/2.2.2/assets/bootstrap.min.css',
      //'bootstrap-responsive': '//ud-cdn.com/js/bootstrap/2.2.2/assets/bootstrap-responsive.css'
    }
  } );

  /**
   * Writes error to console.
   *
   * @author peshkov@UD
   */
  self.show_error = function( error, event ) {
    if( typeof console !== 'undefined' ) {
      console.error( typeof event !== 'undefined' ? event : 'ERROR', error );
    }
    return null;
  }

  /**
   * Determine if passed arg is url
   *
   * @param string url
   * @author peshkov@UD
   */
  self.is_url = function( url ) {
    if( typeof url === 'string' && /^((https?|ftp):)?\/\/([\-A-Z0-9.]+)(\/[\-A-Z0-9+&@#\/%=~_|!:,.;]*)?(\?[A-Z0-9+&@#\/%=~_|!:,.;]*)?/i.test( url ) ) {
      return true;
    }
    return false;
  }

  /**
   * Inits application.
   * Should be called on document ready
   *
   * @author peshkov@UD
   */
  self._init = function() {

    if( self.rendered ) return null;

    /* Prepare modules and render modules data */
    for( var i in self.modules ) {

      self.modules[i] = jQuery.extend( true, {
        'name': i,
        'description': '',
        'type': 'module', // Available values: link, module
        'menu': '#modules_menu', // Selector of menu wrapper where the module's link will be added
        'id': 'module_' + i,
        'href': false,
        'parent': false, // Parent module's slug. Can be used for dropdown menu, etc.
        'model': self.model_url + i + '.js',
        'view': self.view_url + i + '.tpl',
        'args': {},
        'view_model': null
      }, typeof self.modules[i] === 'object' ? self.modules[i] : { 'name': self.modules[i] } );

      /**
       *
       */
      switch( self.modules[i].type ) {

        case 'module':
          /* Add all required model files */
          if( typeof self.modules[i].model === 'string' && self.is_url( self.modules[i].model ) ) {
            self._required.js[ 'module.' + i ] = self.modules[i].model;
          }
          /* Push current module to sections list. It's used by Sammy functionality. */
          self.sections[ self.modules[i].id ] = i;
          /* Add module's container if it doesn't exist */
          if( jQuery( self.ui.content ).length > 0 && !jQuery( '#' + self.modules[i].id ).length > 0 ) {
            var content_wrapper = '<div id="' + self.modules[i].id + '" class="module-container"></div>';
            try { content = self.listeners.add_content_wrapper( content_wrapper, self.modules[i], self ) }
            catch( e ) { self.show_error( 'add_content_wrapper', e ) }
            if( typeof content_wrapper === 'string' ) {
              jQuery( self.ui.content ).append( content_wrapper );
            }
          }
          break;

        case 'link':

          break;

      }

      /* Add menu item to HTML */
      if( jQuery( self.modules[i].menu ).length > 0 ) {
        var href = self.is_url( self.modules[i].href ) ? self.modules[i].href : '#' + self.modules[i].id;
        var menu_item = '<a href="' + href + '">' + self.modules[i].name + '</a>';
        try { menu_item = self.listeners.add_menu_item( menu_item, self.modules[i], self ) }
        catch( e ) { self.show_error( 'add_menu_item', e ) }
        if( typeof menu_item === 'string' ) {
          menu_item = jQuery( menu_item );
          menu_item.each( function( index, e ) {
            var a = typeof jQuery( e ).attr( 'href' ) !== 'undefined' ? jQuery( e ) : jQuery( 'a', e );
            if( a.length > 0 ) {
              a.attr( 'module', i ).attr( 'type', self.modules[i].type );
            }
          } );
          jQuery( self.modules[i].menu ).append( menu_item );
        }
      }

    }

    /* Loads required CSS */
    if( typeof self._required.css === 'object' ) {
      jQuery.each( self._required.css, function( style, url ) {
        ud.load.css( url );
      } );
    }

    /* Load all required files before continue */
    ud.load.js( self._required.js, function() {

      /* Initialize Router */
      self.router = self.router();

      /**
       * Determine if we need to load addtional javascript/css files.
       * Specific module javascript/css files must be loaded before continue!
       */
      var _required = {};

      jQuery.each( self.models, function( i, m ) {
        if( typeof m === 'object' && typeof m._required === 'object' ) {
          if( typeof m._required.js === 'object' ) {
            jQuery.each( m._required.js, function( script, url ) {
              _required[ script ] = url;
            } );
          }
          if( typeof m._required.css === 'object' ) {
            jQuery.each( m._required.css, function( style, url ) {
              /* Load module's CSS */
              ud.load.css( url );
            } );
          }
        }
      } );

      Object.size = function( obj ) {
        var size = 0, key;
        for( key in obj ) { if( obj.hasOwnProperty( key ) ) size++; }
        return size;
      };

      if( Object.size( _required ) > 0 ) {
        ud.load.js( _required, self._run );
      } else {
        self._run();
      }

    } );

  }

  /**
   * Runs application.
   *
   * It does the following steps:
   * 1. tries connecting to socket;
   * 2. runs router.
   *
   * Must be called after init.
   *
   * @author peshkov@UD
   */
  self._run = function() {

    if( self.rendered ) return null;

    /* Determine is socket arguments are set */
    if( self.socket && typeof self.socket === 'object' ) {

      self.core.socket( self.socket, function( socket ) {
        self.socket = socket;
        try { self.listeners.socket_connected( self ) }
        catch( e ) { self.show_error( 'socket_connected', e ) }
        /* Run App */
        self.router.run();
      } );

    } else {

      /* Run App */
      self.router.run();

      // console.log( self );

    }

  }

  /**
   * Sets ( inits ) Router
   *
   * @author peshkov@UD
   */
  self.router = function() {

    /* Pagination. History implementation. */
    return new Sammy( function() {

      this.home = self.url;

      this.loading = false;

      this.get( /\#(.*)/, function( router ) {

        /* Looks like router did not finish previous process. */
        if( router.app.loading ) {
          return null;
        }
        router.app.loading = true;

        /* Parse query hash and set params */
        var params = {
          'section' : false,
          'args' : []
        };
        jQuery.each( router.params[ 'splat' ][0].split( '/' ), function( i,e ) {
          if( e.length == 0 ) return null;
          else if ( !params.section ) params.section = e;
          else params.args.push( e );
        } );

        /* Initialize Knockout View Model if it's undefined */
        var module = typeof self.sections[ params.section ] !== 'undefined' ? self.sections[ params.section ] : false;

        /* Get the default ( home ) section if the called one doesn't exist */
        if( !module || typeof self.modules[ module ] === 'undefined' ) {
          self.show_error( 'Module with the hash \'' + params.section + '\' doesn\'t exist.',
            'Sammy.get( \'#:module\' )' );
          router.app.loading = false;
          if( typeof self.modules[ self.default_module ] !== 'undefined' && typeof self.sections[ self.modules[ self.default_module ].id ] !== 'undefined' ) {
            router.app.runRoute( 'get', '#' + self.modules[ self.default_module ].id );
          }
          return null;
        }

        if( self.modules[ module ].view_model === null || typeof self.modules[ module ].view_model !== 'object' ) {
          self.modules[ module ].view_model = self.core.view_model( {
            'scope': self,
            'model': typeof self.models[ module ] === 'object' ? self.models[ module ] : {},
            'view': self.modules[ module ].view,
            'args': jQuery.extend( self.modules[ module ].args, { 'module': module } ),
            'container': '#' + params.section
          } );
          /**
           * Timeout is used to prevent the issues with socket requests. Hope, that 300msec is always enought.
           * I have no idea why it happens, but when page loads on specific section which is using socket request on init,
           * socket doesn't send response. Probably the issue is related to socket object links ( scope ).
           * peshkov@UD
           */
          setTimeout( function() {
            self.modules[ module ].view_model.apply( params.args );
          }, 300 );
        } else {
          self.modules[ module ].view_model.update( params.args );
        }

        /* Determine if the module is already selected we stop process here. */
        var selected_section = self.sections[ params.section ];
        if( typeof self.selected_section !== 'undefined' && selected_section === self.selected_section ) {
          // Finish process
          router.app.loading = false;
          return null;
        }

        self.selected_section = selected_section;

        for( var a in self.sections ) {
          if( typeof self.sections[a] !== 'function' ) {
            jQuery( 'a[href="#' + a + '"]' ).removeClass( 'selected' );
          }
        }

        for( var a in self.sections ) {
          if( typeof self.sections[a] !== 'function' ) {
            var section = jQuery( '#' + a ).get( 0 );
            jQuery( section ).hide();
            if( jQuery( section ).attr( 'id' ) === params.section ) {
              if( self.modules[ module ].parent && typeof self.modules[ self.modules[ module ].parent ] === 'object' ) {
                jQuery( 'a[href="#' + self.modules[ self.modules[ module ].parent ].id + '"][type="module"]' ).addClass( 'selected' );
              }
              jQuery( 'a[href="#' + params.section + '"]' ).addClass( 'selected' );
              jQuery( section ).fadeIn( 500, function() {
                jQuery( this ).show( 500, function() {
                  // Finish process
                  router.app.loading = false;
                } );
              } );
            }
          }
        }

        try { self.listeners.section_selected( params.section, self ) }
        catch( e ) { self.show_error( 'section_selected', e ) }

        if( !self.rendered ) {
          try { self.listeners.rendered( self ) }
          catch( e ) { self.show_error( 'rendered', e ) }
          self.rendered = true;
        }

      });

      this.get( '', function( router ) {
        var reg = new RegExp( "(https?|ftp):", 'g' );
        if( router.app.home.replace( reg, '' ) !== location.href.replace( reg, '' ) ) {
          window.location = location.href;
        } else {
          if( self.default_module && jQuery( '#' + self.default_module ).length > 0 ) {
            router.app.runRoute( 'get', '#' + self.default_module );
          } else {
            //Ignore.
          }
        }
      } );

    });

  }

  this.__ = self;

  self._init();

  return this.__;

}