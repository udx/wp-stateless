/**
 * Uploads Page specific scripts.
 *
 * @author korotkov@UD
 */

jQuery(document).ready(function(){

  jQuery('.sm_inline_sync').one( 'click', function( e ) {

    var that = jQuery(this);

    that.html('Please wait...');

    jQuery
    .ajax({
      method: 'POST',
      url: ajaxurl,
      data: {
        action: that.data('type') == 'image' ? "stateless_process_image" : "stateless_process_file",
        id: that.data('id')
      }
    })
    .done(function( response ) {
      if ( response.success ) {
        that.replaceWith( '<span style="color:#00520a">'+response.data+'</span>' );
      } else {
        that.replaceWith( '<span style="color:#a00">'+response.data+'</span>' );
      }
    })
    .fail(function( jqXHR, textStatus, message ) {
      that.replaceWith( '<span style="color:#a00">'+message+'. Check your server configuration.</span>' );
    });
  });

});
