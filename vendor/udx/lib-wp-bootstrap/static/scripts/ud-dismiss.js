/**
 * EVENTS
 */
jQuery( document ).ready( function () {
  
  jQuery( '.ud-admin-notice' ).on( 'click', '.dismiss', function(e){
    e.preventDefault();

    var _this = jQuery( this );

    var data = {
      action: 'ud_dismiss',
      key: _this.data('key'),
    }

    jQuery.post( _ud_vars.ajaxurl, data, function ( result_data ) {
        if( result_data.success == '1' ) {
          _this.closest('.ud-admin-notice').remove();
        } else if ( result_data.success == '0' ) {
          alert(result_data.error);
        }
    }, "json" );

  });

} );