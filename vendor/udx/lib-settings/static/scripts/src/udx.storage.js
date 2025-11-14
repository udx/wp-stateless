/**
 * Client Settings
 *
 * Attempts to utilize localStorage if available, otherwise falls back to cookies.
 *
 * @version 0.1.0
 * @returns {Object}
 */
define( 'udx.storage', [ 'udx.utility' ], function settings() {
  console.debug( 'udx.storage', 'loaded' );

  function Settings( name, options ) {
    console.debug( 'udx.storage', 'created' );

    // Return native.
    if( 'object' === typeof window.localStorage && 'function' === typeof window.localStorage.getItem ) {
      console.debug( 'udx.storage', 'Browser supports native localStorage.' );

      return window.localStorage;

    }

    Object.defineProperties( this, {
      store: {
        value: {},
        enumerable: false,
        configurable: true,
        writable: true
      }
    });

    return this;

  }

  /**
   * Settings Instance Properties.
   *
   */
  Object.defineProperties( Settings.prototype, {
    set: {
      value: function set( key, value, vEnd, sPath, sDomain, bSecure ) {
        if( !key || /^(?:expires|max\-age|path|domain|secure)$/i.test( key ) ) {
          return false;
        }
        var sExpires = "";
        if( vEnd ) {
          switch( vEnd.constructor ) {
            case Number:
              sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
              break;
            case String:
              sExpires = "; expires=" + vEnd;
              break;
            case Date:
              sExpires = "; expires=" + vEnd.toUTCString();
              break;
          }
        }
        document.cookie = encodeURIComponent( key ) + "=" + encodeURIComponent( value ) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
        return true;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    get: {
      value: function get( key ) {

        return decodeURIComponent( document.cookie.replace( new RegExp( "(?:(?:^|.*;)\\s*" + encodeURIComponent( key ).replace( /[\-\.\+\*]/g, "\\$&" ) + "\\s*\\=\\s*([^;]*).*$)|^.*$" ), "$1" ) ) || null;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    remove: {
      value: function remove( key, sPath, sDomain ) {
        if( !key || !this.has( key ) ) {
          return false;
        }
        document.cookie = encodeURIComponent( key ) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + ( sDomain ? "; domain=" + sDomain : "") + ( sPath ? "; path=" + sPath : "");
        return true;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    has: {
      value: function has( key ) {
        return (new RegExp( "(?:^|;\\s*)" + encodeURIComponent( key ).replace( /[\-\.\+\*]/g, "\\$&" ) + "\\s*\\=" )).test( document.cookie );

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    keys: {
      value: function keys() {
        var aKeys = document.cookie.replace( /((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "" ).split( /\s*(?:\=[^;]*)?;\s*/ );

        for( var nIdx = 0; nIdx < aKeys.length; nIdx++ ) {
          aKeys[nIdx] = decodeURIComponent( aKeys[nIdx] );
        }
        return aKeys;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    data: {
      value: function data() {

        var data = {};

        var aKeys = document.cookie.replace( /((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "" ).split( /\s*(?:\=[^;]*)?;\s*/ );

        for( var nIdx = 0; nIdx < aKeys.length; nIdx++ ) {
          var key = decodeURIComponent( aKeys[nIdx] );
          data[ key ] = this.get( key );
        }

        return data;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    getItem: {
      value: function getItem( key ) {
        return this.get( key );
      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    setItem: {
      value: function setItem( key, value ) {
        return this.get( key, value );
      },
      enumerable: true,
      configurable: true,
      writable: true
    }
  });

  /**
   * Settings Constructor Properties.
   *
   */
  Object.defineProperties( Settings, {
    create: {
      /**
       *
       * @param name {String|Null}
       * @param options {Object|Null}
       * @returns {settings.Settings}
       */
      value: function create( name, options ) {
        return new Settings( name, options )
      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    version: {
      value: '1.0.1',
      enumerable: false,
      writable: false
    }
  });

  return Settings;

});