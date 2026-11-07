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
          id: that.data('id'),
          size: that.data('size'),
          _ajax_nonce: stateless_upload.inline_sync_nonce ?? '',
        }
      })
      .done(function( response ) {
        if ( response.success ) {
          if (that.data('reload_page')) {
            location.reload();
          }
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


jQuery(document).ready(function(){
  jQuery('#sm-attachment-metabox').on('click', '.rwmb-tab-nav a', function(e){
    e.preventDefault();
    var link = jQuery(this);
    var tabs = link.closest('.rwmb-tabs');
    var panel = link.parent().data('panel');
    tabs.find('.rwmb-tab-nav li').removeClass('rwmb-tab-active');
    link.parent().addClass('rwmb-tab-active');
    tabs.find('.rwmb-tab-panel').hide();
    tabs.find('.rwmb-tab-panel[data-panel="' + panel + '"]').show();
  });
});
