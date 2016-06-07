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
        action: "stateless_process_image",
        id: jQuery( e.target).data('id')
      }
    })
    .done(function( response, message, xhr ) {
      if ( response.success ) {
        that.replaceWith( response.data );
      }
    });
  });

});
