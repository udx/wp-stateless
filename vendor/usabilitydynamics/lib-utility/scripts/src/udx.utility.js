/**
 * UsabilityDynamics Utility
 *
 * @version 0.2.3
 * @returns {Object}
 */
define( 'udx.utility', function Utility( require, exports, module ) {
  // console.debug( module.id, 'loaded' );

  return {

    /**
     * Checks if a DOM element is visible. Takes into
     * consideration its parents and overflow.
     *
     * @param (el)      the DOM element to check if is visible
     *
     * These params are optional that are sent in recursively,
     * you typically won't use these:
     *
     * @param (t)       Top corner position number
     * @param (r)       Right corner position number
     * @param (b)       Bottom corner position number
     * @param (l)       Left corner position number
     * @param (w)       Element width number
     * @param (h)       Element height number
     */
    isVisible: function isVisible( el, t, r, b, l, w, h ) {
      // console.debug( 'udx.utility', 'isVisible', typeof el );

      if( 'string' === typeof el ) {
        el = document.getElementById( el );
      }

      var p = el.parentNode;
      var VISIBLE_PADDING = 2;

      if( !this.elementInDocument( el ) ) {
        return false;
      }

      //-- Return true for document node
      if( 9 === p.nodeType ) {
        return true;
      }

      //-- Return false if our element is invisible
      if( '0' === this.getStyle( el, 'opacity' ) || 'none' === this.getStyle( el, 'display' ) || 'hidden' === this.getStyle( el, 'visibility' ) ) {
        return false;
      }

      if( 'undefined' === typeof(t) || 'undefined' === typeof(r) || 'undefined' === typeof(b) || 'undefined' === typeof(l) || 'undefined' === typeof(w) || 'undefined' === typeof(h) ) {
        t = el.offsetTop;
        l = el.offsetLeft;
        b = t + el.offsetHeight;
        r = l + el.offsetWidth;
        w = el.offsetWidth;
        h = el.offsetHeight;
      }

      //-- If we have a parent, let's continue:
      if( p ) {
        //-- Check if the parent can hide its children.
        if( ('hidden' === this.getStyle( p, 'overflow' ) || 'scroll' === this.getStyle( p, 'overflow' )) ) {
          //-- Only check if the offset is different for the parent
          if( //-- If the target element is to the right of the parent elm
            l + VISIBLE_PADDING > p.offsetWidth + p.scrollLeft || //-- If the target element is to the left of the parent elm
              l + w - VISIBLE_PADDING < p.scrollLeft || //-- If the target element is under the parent elm
              t + VISIBLE_PADDING > p.offsetHeight + p.scrollTop || //-- If the target element is above the parent elm
              t + h - VISIBLE_PADDING < p.scrollTop ) {
            //-- Our target element is out of bounds:
            return false;
          }
        }

        //-- Add the offset parent's left/top coords to our element's offset:
        if( el.offsetParent === p ) {
          l += p.offsetLeft;
          t += p.offsetTop;
        }

        //-- Let's recursively check upwards:
        return this.isVisible( p, t, r, b, l, w, h );

      }

      return true;

    },

    /**
     * Cross browser method to get style properties:
     * @param el
     * @param property
     * @returns {*}
     */
    getStyle: function getStyle( el, property ) {
      // console.debug( 'udx.utility', 'getStyle', el, property );

      if( window.getComputedStyle ) {
        return document.defaultView.getComputedStyle( el, null )[property];
      }

      if( el.currentStyle ) {
        return el.currentStyle[property];
      }

    },

    /**
     * Find Element in Document
     *
     * @param element
     * @returns {boolean}
     */
    elementInDocument: function elementInDocument( element ) {
      // console.debug( 'udx.utility', 'elementInDocument', element );

      while( element = element.parentNode ) {
        if( element == document ) {
          return true;
        }

      }

      return false;

    },

    /**
     * HTTP GET Request
     *
     * The callback method follows Node.js format of returning an Error object as the first argument, or null.
     *
     * @method remote_get
     * @for Utility
     *
     * @param url {String|Object}
     * @param callback {Function}
     */
    remote_get: function remote_get() {
      var request = makeHttpObject();

      var url = arguments[0];
      var callback = arguments[1];

      request.open( 'GET', url, true );

      request.send( null );

      request.onreadystatechange = function() {

        if( request.readyState == 4 ) {
          if( request.status == 200 ) {
            callback( null, request.responseText );
          } else if( failure ) {
            callback( new Error( request.statusText ) );
          }
        }

      };

    },

    /**
     * HTTP POST Request
     *
     */
    remote_post: function remote_post() {
      var request = makeHttpObject();

      var url = arguments[0];
      var callback = arguments[1];

      request.open( 'POST', url, true );

      request.send( null );

      request.onreadystatechange = function() {

        if( request.readyState == 4 ) {
          if( request.status == 200 ) {
            callback( null, request.responseText );
          } else if( failure ) {
            callback( new Error( request.statusText ) );
          }
        }

      };

    },

    /**
     * Deep Extend Object.
     *
     * @param destination
     * @param source
     * @returns {*}
     */
    extend: function extend( destination, source ) {
      // console.debug( 'udx.utility', 'extend' );

      for( var property in source ) {
        if( source[property] && source[property].constructor && source[property].constructor === Object ) {
          destination[property] = destination[property] || {};
          arguments.callee( destination[property], source[property] );
        } else {
          destination[property] = source[property];
        }
      }
      return destination;
    },

    /**
     * Extend Defaults into Object
     *
     * @param str
     * @return
     */
    defaults: function( target, defaults ) {
      // console.debug( 'udx.utility', 'defaults' );

      return this.extend( defaults || {}, target || {} );

    },

    /**
     * Create Slug from String.
     *
     * @param str
     * @return
     */
    create_slug: function create_slug( str ) {
      // console.debug( 'udx.utility', 'create_slug' );

      return str.replace( /[^a-zA-Z0-9_\s]/g, "" ).toLowerCase().replace( /\s/g, '_' );
    }

  }

} );