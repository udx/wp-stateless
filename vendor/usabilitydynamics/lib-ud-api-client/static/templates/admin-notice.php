<?php
/**
 * Admin Notice
 */
?>
<style>
  .ud-ping-notice.updated {
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
  .ud-ping-notice.updated a {
    color: #ef5222;
    font-weight: 500;
  }
  .ud-ping-notice.updated a:hover {
    color: #cf481f;
  }
  .ud-ping-notice-content {
    font-size: 16px;
    line-height: 21px;
    font-weight: 400;
    margin-bottom: 36px;
  }
  .ud-ping-notice-dismiss {
    position: absolute;
    bottom: 11px;
    font-size: 14px;
  }
  .ud-ping-notice-icon {
    float: right;
    text-align: left;
    max-width: 75px;
    margin-right: 10px;
  }
  .ud-ping-notice-icon {
    background: url( "<?php echo $icon; ?>" ) center center no-repeat;
    background-size: cover;
    width: 75px;
    height: 75px;
    display: inline-block;
  }
  .ud-ping-notice-clear {
    display: block;
    clear: both;
    height: 1px;
    line-height: 1px;
    font-size: 1px;
    margin: -1px 0 0 0;
    padding: 0;
  }
</style>
<div class="ud-ping-notice updated fade">
  <?php if( !empty( $icon ) ) : ?>
    <div class="ud-ping-notice-icon"></div>
  <?php endif; ?>
  <div class="ud-ping-notice-content">
    <?php if( !empty( $notice ) ) echo $notice; ?>
    <?php if( !empty( $dismiss_url ) ) : ?>
      <div class="ud-ping-notice-dismiss">
        <?php printf( __( '<a href="%s" class="">Dismiss this notice</a>' ), $dismiss_url ); ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="ud-ping-notice-clear"></div>
</div>