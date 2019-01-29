/**
 * Migrated from ud.global.js
 *
 */
define( 'udx.filter', function() {

  return {

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
    }
  }

} );

