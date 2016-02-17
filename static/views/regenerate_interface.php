<?php
  /**
   * Stateless Sync Interface
   */
  global $wpdb;

  if ( wp_script_is( 'jquery-ui-widget', 'registered' ) )
    wp_enqueue_script( 'jquery-ui-progressbar', ud_get_stateless_media()->path('static/scripts/jquery-ui/jquery.ui.progressbar.min.js', 'url'), array( 'jquery-ui-core', 'jquery-ui-widget' ), '1.8.6' );
  else
    wp_enqueue_script( 'jquery-ui-progressbar', ud_get_stateless_media()->path( 'static/scripts/jquery-ui/jquery.ui.progressbar.min.1.7.2.js', 'url' ), array( 'jquery-ui-core' ), '1.7.2' );

  wp_enqueue_script( 'wp-stateless-angular', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js', array(), '1.5.0', true );
  wp_enqueue_script( 'wp-stateless', ud_get_stateless_media()->path( 'static/scripts/wp-stateless.js', 'url'  ), array( 'jquery-ui-core' ), ud_get_stateless_media()->version, true );

  wp_enqueue_style( 'jquery-ui-regenthumbs', ud_get_stateless_media()->path( 'static/scripts/jquery-ui/redmond/jquery-ui-1.7.2.custom.css', 'url' ), array(), '1.7.2' );
?>

<div id="message" class="error fade" ng-show="error"><p>{{error}}</p></div>

<div class="wrap" ng-app="wpStatelessApp" ng-controller="wpStatelessTools" ng-init="init()">

  <h2><?php _e('Stateless Images Synchronisation', ud_get_stateless_media()->domain); ?></h2>

  <noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', ud_get_stateless_media()->domain ); ?></em></p></noscript>

  <form id="go" ng-submit="processStart($event)">

    <div class="option">
      <label>
        <input type="radio" name="action" value="regenerate_images" checked="checked" />
        <?php _e( 'Regenerate all stateless images and synchronize Google Storage with local server', ud_get_stateless_media()->domain ); ?>
      </label>
    </div>

    <div class="option">
      <label>
        <input type="radio" name="action" value="sync_non_images" />
        <?php _e( 'Synchronize non-images files between Google Storage and local server', ud_get_stateless_media()->domain ); ?>
      </label>
    </div>

    <div class="status" ng-show="status"><?php _e( 'Status:', ud_get_stateless_media()->domain ); ?> {{status}}</div>

    <div ng-show="isRunning" id="regenthumbs-bar" style="position:relative;height:25px;">
      <div id="regenthumbs-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
    </div>

    <ol ng-show="log.length" id="regenthumbs-debuglist">
      <li ng-repeat="l in log">{{l.message}}</li>
    </ol>

    <div class="buttons">
      <button ng-disabled="isRunning || isLoading" type="submit" class="button-primary"><?php _e( 'Go! (may take a while)' ); ?></button>
      <div ng-disabled="!isRunning" ng-click="processStop($event)" class="button-secondary"><?php _e( 'Stop' ); ?></div>
      <div ng-disabled="!log.length" ng-click="log=[]" class="button-secondary"><?php _e( 'Clear Log' ); ?></div>
    </div>

  </form>

</div>