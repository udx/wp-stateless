/**
 * Settings
 *
 * @version 0.1.4
 * @returns {Object}
 */
define( 'udx.settings', [ 'udx.utility' ], function settings() {
  console.log( 'udx.settings', 'loaded' );

  function Settings( name, options ) {
    console.log( 'udx.settings', 'created' );

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
      value: function set( sKey, sValue, vEnd, sPath, sDomain, bSecure ) {
        if( !sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test( sKey ) ) {
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
        document.cookie = encodeURIComponent( sKey ) + "=" + encodeURIComponent( sValue ) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
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
      value: function remove( sKey, sPath, sDomain ) {
        if( !sKey || !this.has( sKey ) ) {
          return false;
        }
        document.cookie = encodeURIComponent( sKey ) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + ( sDomain ? "; domain=" + sDomain : "") + ( sPath ? "; path=" + sPath : "");
        return true;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    has: {
      value: function has( sKey ) {
        return (new RegExp( "(?:^|;\\s*)" + encodeURIComponent( sKey ).replace( /[\-\.\+\*]/g, "\\$&" ) + "\\s*\\=" )).test( document.cookie );

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
    }
  });

  /**
   * Settings Constructor Properties.
   *
   */
  Object.defineProperties( Settings, {
    create: {
      value: function create( name, options ) {
        return new Settings( name, options )
      },
      enumerable: true,
      configurable: true,
      writable: true
    }
  });

  return Settings;

});