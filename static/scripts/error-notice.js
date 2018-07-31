/**
 * EVENTS
 */
jQuery( document ).ready( function () {
  
    jQuery( '.ud-admin-notice' ).on( 'click', '.button-action', function(e){
      e.preventDefault();
  
      var _this = jQuery( this );
  
      var data = {
        action: 'button_action',
        key: _this.data('key'),
      }
  
      jQuery.post( ajaxurl, data, function ( result_data ) {
          if( result_data.success == '1' ) {
            _this.closest('.ud-admin-notice').remove();
          } else if ( result_data.success == '0' ) {
            alert(result_data.error);
          }
      }, "json" );
  
    });
  
  } );