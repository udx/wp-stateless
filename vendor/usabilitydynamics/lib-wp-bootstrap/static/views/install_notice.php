<?php
/**
 * Product Install Notice
 */
?>
<style>
  .ud-install-notice.updated {
    padding: 11px;
    position: relative;
    border-left: 4px solid #f29816;
    background: -moz-linear-gradient(left,  rgba(240,83,35,0.03) 0%, rgba(240,85,37,0.03) 1%, rgba(254,255,255,1) 100%);
    background: -webkit-gradient(linear, left top, right top, color-stop(0%,rgba(240,83,35,0.03)), color-stop(1%,rgba(240,85,37,0.03)), color-stop(100%,rgba(254,255,255,1)));
    background: -webkit-linear-gradient(left,  rgba(240,83,35,0.03) 0%,rgba(240,85,37,0.03) 1%,rgba(254,255,255,1) 100%);
    background: -o-linear-gradient(left,  rgba(240,83,35,0.03) 0%,rgba(240,85,37,0.03) 1%,rgba(254,255,255,1) 100%);
    background: -ms-linear-gradient(left,  rgba(240,83,35,0.03) 0%,rgba(240,85,37,0.03) 1%,rgba(254,255,255,1) 100%);
    background: linear-gradient(to right,  rgba(240,83,35,0.03) 0%,rgba(240,85,37,0.03) 1%,rgba(254,255,255,1) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#08f05323', endColorstr='#feffff',GradientType=1 );

    -webkit-box-shadow: 0 1px 10px 0 rgba(0, 0, 0, 0.1);
    -moz-box-shadow: 0 1px 10px 0 rgba(0, 0, 0, 0.1);
    box-shadow: 0 1px 10px 0 rgba(0, 0, 0, 0.1);
  }
  .ud-install-notice.updated a {
    color: #ef5222;
    font-weight: 500;
  }
  .ud-install-notice.updated a:hover {
    color: #cf481f;
  }
  .ud-install-notice-content {
    font-size: 16px;
    line-height: 21px;
    font-weight: 400;
    margin-bottom: 36px;
  }
  .ud-install-notice-dismiss {
    position: absolute;
    bottom: 11px;
    font-size: 14px;
  }
  .ud-install-notice-icon {
    float: right;
    text-align: left;
    max-width: 75px;
    margin-right: 10px;
  }
  .ud-install-notice-icon.<?php echo $this->slug; ?> {
    background: url( "<?php echo $icon; ?>" ) center center no-repeat;
    background-size: cover;
    width: 75px;
    height: 75px;
    display: inline-block;
  }
  .ud-install-notice-clear {
    display: block;
    clear: both;
    height: 1px;
    line-height: 1px;
    font-size: 1px;
    margin: -1px 0 0 0;
    padding: 0;
  }
</style>
<script>
  jQuery( document ).ready( function () {

    jQuery( '.ud-install-notice-dismiss' ).on( 'click', '.dismiss', function(e){
      e.preventDefault();

      var _this = jQuery( this );

      var data = {
        action: 'ud_bootstrap_dismiss_notice',
      }

      jQuery.each( _this.data(), function(k,v){
        data[k] = v;
      });

      jQuery.post( ajaxurl, data, function ( result_data ) {
        if( result_data.success == '1' ) {
          _this.closest('.ud-install-notice').remove();
        } else if ( result_data.success == '0' ) {
          alert(result_data.error);
        }
      }, "json" );

    });

  } );
</script>
<div class="<?php echo $this->slug; ?> ud-install-notice updated fade">
  <?php if( !empty( $icon ) ) : ?>
    <div class="ud-install-notice-icon <?php echo $this->slug; ?>"></div>
  <?php endif; ?>
  <div class="ud-install-notice-content">
    <?php
    if( !empty( $content ) ) {
      echo $content;
    } else {
      printf( __( 'Thank you for using <a href="%s" target="_blank">Usability Dynamics</a>\' %s <b>%s</b>. Please, proceed to this <a href="%s">link</a> to see more details.' ),
        'https://www.usabilitydynamics.com',
        $type,
        $name,
        $dashboard_link
      );
    }
    do_action( 'ud::bootstrap::upgrade_notice::additional_info', $this, $vars );
    ?>
    <div class="ud-install-notice-dismiss">
      <?php if( !empty( $home_link ) ) : ?>
        | <?php printf( __( '<a href="%s" target="_blank" class="">%s\'s Home page</a>' ), $home_link, ucfirst( $type ) ); ?>
      <?php endif; ?>
      <?php if( !empty( $this->support_url ) ) : ?>
        | <a href="<?php echo $this->support_url ?>" target="_blank" ><?php _e( 'Support' ) ?></a>
      <?php endif; ?>
      <?php if( !empty( $this->uservoice_url ) ) : ?>
        | <a href="<?php echo $this->uservoice_url ?>" target="_blank" ><?php _e( 'Post your idea' ) ?></a>
      <?php endif; ?>
      | <?php printf( __( '<a data-key="%s" data-slug="%s" data-type="%s" data-version="%s" href="#" class="dismiss-notice dismiss">Dismiss this notice</a>' ), sanitize_key( 'dismiss_' . $slug . '_' . str_replace( '.', '_', $version ) . '_notice' ), $slug, $type, $version ); ?>
    </div>
  </div>
  <div class="ud-install-notice-clear"></div>
</div>
