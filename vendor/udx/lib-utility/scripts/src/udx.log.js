/**
 * UD Global
 *
 * Initial global object extended by product-specific globals.
 *
 * @version 1.0
 * @description UD Global
 * @package UD
 * @dependencies jquery, ud.loader
 * @author team@UD
 */
/* Create UD Global by merging with UD (if exists) */
jQuery.extend( true, ud, {

  /* Slug for Default Scope */
  scope: 'ud',

  /* Global setting. Should be overwritten by child */
  developer_mode: true,

  /* Configuration of which debug messages to display, if any */
  console_options: { show_log: true },

  /* Contains i18n strings */
  strings: {},

  /* Central location for timers */
  timers: {},

  /* Utility Functions */
  utils: {

    /**
     * Deep Data Type Conversion of an Object
     *
     * @since 2.0
     * @author potanin@UD
     */
    type_fix: function( object, args ) {

      jQuery.extend( args, { 'null': true } );

      try {

        var fix = function( value ) {
          switch( typeof value ) {
            case 'string':
              switch( value ) {
                case 'false':
                  value = false;
                  break;
                case 'true':
                  value = true;
                  break;
                case '':
                  value = ( args.nullify ? null : value );
                  break;
              }
              break;
            case 'number':
              value = parseFloat( value );
              break;
            case 'object':
              value = dig( value );
              break;
          }
          return value;
        }

        var dig = function( object ) {
          if( typeof object !== 'object' ) {
            return fix( object );
          }
          for( key in object ) {
            object[ key ] = fix( object[ key ] );
          }
          ;
          return object;
        }

      } catch( e ) {
      }

      return dig( object );

    }

  },

  /**
   * Throw a Warning (Not Fatal Error)
   *
   * WIP.
   *
   * @since 2.0
   * @author potanin@UD
   */
  warning: function() {
    if( typeof console.warn === 'function' ) {
      console.warn( arguments[0] );
      return arguments[0];
    }
  },

  /**
   * Internal logging function.
   *
   * Basic Usage:
   * - ud.log( 'I need a shower.' );
   * - ud.log( 'There is no water.', 'error' );
   * - ud.log( { some: 'information' } );
   * - ud.log( new Error( 'Error message.') );
   *
   * Advanced Usage:
   * - ud.log( 'As long as the second variable is not a string, all additional variables will be added to the log:', { one: 'test', two: 'test' }, { puppies: 'yes', kittens : 'no' }, 7, 2 );
   * - ud.log( cat === big , 'If we have a small cat, this message will not be displayed at all. When first variable is a boolean, it must be true for rest of variables to be displayed..' );
   *
   * @author potanin@UD
   */
  log: function( notice, type, override ) {
    var self = this;

    /* If notice is an error, we strip out the message, send to console, and then return the error object */
    if( notice instanceof Error && typeof console.error === 'object' ) {
      console.error( notice.message, { 'Error': notice, 'Stack': ( typeof notice.stack === 'string' ? notice.stack.split( "\n" ) : null ) } );
      return notice;
    }

    if( !window.console || ( !override && ( !self.developer_mode || !window.console ) ) || typeof notice === 'boolean' && !notice ) {
      return false;
    }

    /* Prevent logs in IE because of issues. */
    if( self.browser_detect( 'browser' ) == 'Explorer' ) {
      return false;
    }

    self.log.console = {
      log: function( args ) {
        switch( args.type ) {
          case 'info':
            return self.log.console.info( args );
            break;
          case 'error':
            return self.log.console.error( args );
            break;
          case 'dir':
            return self.log.console.error( args );
            break;
          case 'warn':
            return self.log.console.warn( args );
            break;
          default:
            return ( self.console_options && self.console_options.show_log ) ? console.log.apply( console, args.items ) : false;
            break;
        }
      },
      info: function( args ) {
        return console.info.apply( console, args.items );
      },
      error: function( args ) {
        return console.error.apply( console, args.items );
      },
      dir: function( args ) {
        return console.dir.apply( console, args.items );
      },
      warn: function( args ) {
        return console.warn.apply( console, args.items );
      }
    }

    return self.log.console.log( {
      items: [  self.scope + '::'  ].concat( jQuery.makeArray( typeof notice === 'string' && typeof type === 'object' ? arguments : [ notice ] ) ),
      type: ( typeof type === 'string' ? type : 'log' )
    } );

  },

  /**
   * Add Remove Notification support.
   *
   * @author peshkov@UD
   */
  get_service: function( service, callback, args ) {
    'use strict';
    this.log( this.scope + '.get_service()', arguments );

    if( typeof callback !== 'function' ) {
      return false;
    }

    // try { } catch( error ) { }

    this.ajax( 'get_api_service', { service: service, args: args }, function( response ) {
      callback( response );
    } );

  },

  /**
   * WPP AJAX Handler
   *
   * Example: [scope].ajax( 'some_action', { arg: value }, function() {  } );
   *
   */
  ajax: function( this_action, ajax_args, callback, args ) {
    'use strict';
    /* this.log( this.scope + '.ajax()' , arguments ); */

    if( !ajaxurl ) {
      return false;
    }

    var self = this;

    /**
     * Perform standard UI operations, error handling and standardize response object.
     *
     */
    var response = function( jqXHR ) {
      'use strict'; // this.log( this.scope + 'ajax.response()', arguments );

      var result = {};

      try {

        result.response_text = jQuery.parseJSON( jqXHR.responseText );

        if( typeof result.response_text !== 'object' || jqXHR.responseText === '' ) {
          throw new Error( self.strings.ajax_response_empty );
        }

        if( jqXHR.status === 500 ) {
          throw new Error( self.strings.internal_server_error );
        }

      } catch( error ) {
        error.message = 'AJAX Error: ' + ( error.message ? error.message : 'Unknown.' );
        result.response_text = { success: false, message: error.message }
      }

      result = jQuery.extend( true, {
        success: false,
        status: jqXHR.status
      }, result.response_text );

      if( jqXHR.statusText === 'timeout' ) {
        result.status = 408;
        result.response_text = result.response_text ? result.response_text : self.strings.server_timeout;
      }

      callback( result );

    };

    return jQuery.ajax( args = jQuery.extend( true, {
      url: ajaxurl + '?action=' + self.scope + '_' + this_action,
      data: jQuery.extend( true, { args: ajax_args }, typeof data === 'object' ? data : {} ),
      dataType: 'json',
      type: 'POST',
      async: true,
      timeout: ( ( typeof self.server === 'object' ? self.server.max_execution_time : 30 ) - 10 ) * 1000,
      beforeSend: function( xhr ) {
        jQuery( document ).trigger( self.scope + '.ajax.beforeSend' );
        xhr.overrideMimeType( 'application/json; charset=utf-8' );
      },
      complete: function( jqXHR ) {
        response( jqXHR );
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      }
    }, typeof args === 'object' ? args : {} ) );

  },

  /**
   * Applies filter for the passed object
   *
   * @param name. string. required. Name of filter
   * @param obj. object. required. Object which will go through called filter
   * @author peshkov@UD
   */
  apply_filter: function( name, obj ) {

    /* Filter's name and callback are required */
    if( typeof obj === 'undefined' || typeof name === 'undefined' || typeof name !== 'string' ) return obj;
    /* jQuery must be inititialized */
    if( typeof jQuery === 'undefined' ) return obj;
    /* Called filter must exist */
    if( typeof window.__ud_filters === 'undefined' || typeof window.__ud_filters[ name ] === 'undefined' ) return obj;

    jQuery.each( window.__ud_filters[ name ], function( i, e ) {
      if( typeof e === 'function' ) {
        obj = e( obj );
      } else if( typeof e === 'object' ) {
        if( typeof obj !== 'object' ) return false;
        obj = jQuery.extend( true, obj, e );
      }
    } );
    return obj;
  },

  /**
   * Adds filter to filters array.
   *
   * @param name. string. required. Name of filter.
   * @param calback. object|function. required. Filter which will be used on filter applying
   * @author peshkov@UD
   */
  add_filter: function( name, callback ) {
    /* Filter's name and callback are required */
    if( typeof callback === 'undefined' || typeof name === 'undefined' || typeof name !== 'string' ) return;
    /* Add object to filter */
    if( typeof window.__ud_filters === 'undefined' ) window.__ud_filters = {};
    if( typeof window.__ud_filters[ name ] === 'undefined' ) window.__ud_filters[ name ] = [];
    window.__ud_filters[ name ].push( callback );
  },

  /**
   * Basic URL Validation
   *
   */
  validate_url: function( string ) {
    var validation_regex = new RegExp( "^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$" );
    return validation_regex.test( string );
  },

  /**
   * Creates slug from the string.
   *
   * @param string slug
   * @return string
   */
  create_slug: function( slug ) {
    if( typeof slug !== 'string' ) return false;
    slug = slug.replace( /[^a-zA-Z0-9_\s]/g, "" );
    slug = slug.toLowerCase();
    slug = slug.replace( /\s/g, '_' );
    return slug;
  },

  /**
   * Detects client's browser
   *
   * Get Browser name: self.browser_detect( 'browser' )
   * Get Browser version: self.browser_detect( 'version' )
   * Get OS name: self.browser_detect( 'OS' )
   *
   * @author peshkov@UD
   */
  browser_detect: function( query ) {
    if( typeof query === 'undefined' ) return false;

    if( typeof window._ud_browser_detect === 'undefined' ) {
      window._ud_browser_detect = {
        init: function() {
          this.browser = this.searchString( this.dataBrowser ) || "An unknown browser";
          this.version = this.searchVersion( navigator.userAgent ) || this.searchVersion( navigator.appVersion ) || "an unknown version";
          this.OS = this.searchString( this.dataOS ) || "an unknown OS";
        },
        searchString: function( data ) {
          for( var i = 0; i < data.length; i++ ) {
            var dataString = data[i].string;
            var dataProp = data[i].prop;
            this.versionSearchString = data[i].versionSearch || data[i].identity;
            if( dataString ) {
              if( dataString.indexOf( data[i].subString ) != -1 )
                return data[i].identity;
            } else if( dataProp )
              return data[i].identity;
          }
        },
        searchVersion: function( dataString ) {
          var index = dataString.indexOf( this.versionSearchString );
          if( index == -1 ) return;
          return parseFloat( dataString.substring( index + this.versionSearchString.length + 1 ) );
        },
        dataBrowser: [
          { string: navigator.userAgent, subString: "Chrome", identity: "Chrome" },
          { string: navigator.userAgent, subString: "OmniWeb", versionSearch: "OmniWeb/", identity: "OmniWeb" },
          { string: navigator.vendor, subString: "Apple", identity: "Safari", versionSearch: "Version" },
          { prop: window.opera, identity: "Opera", versionSearch: "Version" },
          { string: navigator.vendor, subString: "iCab", identity: "iCab" },
          { string: navigator.vendor, subString: "KDE", identity: "Konqueror" },
          { string: navigator.userAgent, subString: "Firefox", identity: "Firefox" },
          { string: navigator.vendor, subString: "Camino", identity: "Camino" },
          { string: navigator.userAgent, subString: "Netscape", identity: "Netscape" },
          { string: navigator.userAgent, subString: "MSIE", identity: "Explorer", versionSearch: "MSIE" },
          { string: navigator.userAgent, subString: "Gecko", identity: "Mozilla", versionSearch: "rv" },
          { string: navigator.userAgent, subString: "Mozilla", identity: "Netscape", versionSearch: "Mozilla" }
        ],
        dataOS: [
          { string: navigator.platform, subString: "Win", identity: "Windows" },
          { string: navigator.platform, subString: "Mac", identity: "Mac" },
          { string: navigator.userAgent, subString: "iPhone", identity: "iPhone/iPod" },
          { string: navigator.platform, subString: "Linux", identity: "Linux" }
        ]
      }
      window._ud_browser_detect.init();
    }
    return typeof window._ud_browser_detect[ query ] !== 'undefined' ? window._ud_browser_detect[ query ] : false;
  }

} );

/**
 * Adds indexOf if it doesn't exist
 * IE8 and less doesn't support indexOf
 *
 */
if( !Array.prototype.indexOf ) {
  Array.prototype.indexOf = function( obj, start ) {
    for( var i = (start || 0), j = this.length; i < j; i++ ) {
      if( this[i] === obj ) {
        return i;
      }
    }
    return -1;
  }
}
