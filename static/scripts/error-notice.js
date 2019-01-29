/**
 * EVENTS
 */
jQuery( document ).ready( function ($) {
  
    jQuery( document ).on( 'click', '.stateless-admin-notice.ud-admin-notice .button-action', function(e){
      var _this = jQuery( this );
      if(_this.attr('href') != '#'){
        return;
      }
      
      e.preventDefault();
  

      var data = {
        action: 'stateless_enable_notice_button_action',
        key: _this.data('key'),
      }
  
      jQuery.post( ajaxurl, data, function ( result_data ) {
          if( result_data.success == '1' ) {
            _this.closest('.ud-admin-notice').remove();
            
            var key = _this.attr('data-key');
            key = key.replace('button_secondary_', '');
            $("#" + key + " option[value=" + key +"]").attr('selected', 'selected');
            $("#" + key).val('true');
          } else if ( result_data.success == '0' ) {
            // alert(result_data.error);
          }
      }, "json" );
      return false;
    });

    jQuery( '.ud-admin-notice' ).off( 'click', '.dismiss');
    jQuery( document ).on( 'click', '.stateless-admin-notice.ud-admin-notice .dismiss-warning', function(e){
      e.preventDefault();

      var _this = jQuery( this );

      var data = {
        action: 'stateless_notice_dismiss',
        key: _this.data('key'),
      }

      jQuery.post( ajaxurl, data, function ( result_data ) {
          if( result_data.success == '1' ) {
            _this.closest('.ud-admin-notice').remove();
          } else if ( result_data.success == '0' ) {
            // alert(result_data.error);
          }
      }, "json" );
      return false;
    });

    jQuery('#stless_settings_tab .sm-mode input[type=radio]').on('change', function(){
      var $this = jQuery('#sm_mode_stateless');
      if($this.is(':checked')){
        var notice = jQuery('#stateless-notice-stateless-cache-busting');
        if(!notice.length){
          notice = jQuery(jQuery('#template-stateless-cache-busting').html());
          notice.appendTo("#stateless-settings-page-title");
        }
        jQuery('#stateless-notice-stateless-cache-busting').show();
      }
      else{
        jQuery('#stateless-notice-stateless-cache-busting').hide();
      }
    });
  
  } );