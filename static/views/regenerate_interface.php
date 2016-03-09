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

  <h1><?php _e('Stateless Images Synchronisation', ud_get_stateless_media()->domain); ?></h1>

  <noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', ud_get_stateless_media()->domain ); ?></em></p></noscript>

  <form id="go" ng-submit="processStart($event)">

    <div>

      <h2><?php _e( 'Action', ud_get_stateless_media()->domain ); ?></h2>

      <div class="option">
        <label>
          <input ng-disabled="isRunning || isLoading" type="radio" name="action" value="regenerate_images" ng-model="action" />
          <?php _e( 'Regenerate all stateless images and synchronize Google Storage with local server', ud_get_stateless_media()->domain ); ?>
        </label>
      </div>

      <div class="option">
        <label>
          <input ng-disabled="isRunning || isLoading" type="radio" name="action" value="sync_non_images" ng-model="action" />
          <?php _e( 'Synchronize non-images files between Google Storage and local server', ud_get_stateless_media()->domain ); ?>
        </label>
      </div>

    </div>

    <div ng-if="action == 'regenerate_images' && progresses.images || action == 'sync_non_images' && progresses.other">

      <h2><?php _e( 'Method', ud_get_stateless_media()->domain ); ?></h2>

      <div class="option">
        <label>
          <input ng-disabled="isRunning || isLoading" type="radio" name="method" value="start" ng-model="$parent.method" />
          <?php _e( 'Start a new process', ud_get_stateless_media()->domain ); ?>
          <span class="notice notice-warning" style="margin-left:20px;">
            <?php _e( '<strong>Warning:</strong> This will make it impossible to continue the last process.', ud_get_stateless_media()->domain ); ?>
          </span>
        </label>
      </div>

      <div class="option">
        <label>
          <input ng-disabled="isRunning || isLoading" type="radio" name="method" value="continue" ng-model="$parent.method" />
          <?php _e( 'Continue the last process', ud_get_stateless_media()->domain ); ?>
        </label>
      </div>

    </div>

    <div ng-if="(action == 'regenerate_images' && fails.images) || (action == 'sync_non_images' && fails.other)">

      <h2><?php _e( 'Fix errors', ud_get_stateless_media()->domain ); ?></h2>

      <div class="option">
        <label>
          <input ng-disabled="isRunning || isLoading" type="checkbox" name="method" ng-true-value="'fix'" ng-model="$parent.method" />
          <?php _e( 'Try to fix previously failed items', ud_get_stateless_media()->domain ); ?>
          <span class="notice notice-warning" style="margin-left:20px;">
            <?php _e( '<strong>Warning:</strong> This will make it impossible to continue the last process.', ud_get_stateless_media()->domain ); ?>
          </span>
        </label>
      </div>

    </div>

    <div>

      <h2><?php _e( 'Bulk Size', ud_get_stateless_media()->domain ); ?></h2>

      <div class="option">
        <label>
          <input ng-disabled="isRunning || isLoading" type="number" name="bulk_size" ng-model="bulk_size" />
          <?php _e( 'How many items to process at once', ud_get_stateless_media()->domain ); ?>
        </label>
      </div>

    </div>

    <div class="status" ng-show="status"><?php _e( 'Status:', ud_get_stateless_media()->domain ); ?> {{status}}</div>

    <div ng-show="isRunning" id="regenthumbs-bar" style="position:relative;height:25px;">
      <div id="regenthumbs-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
    </div>

    <ol ng-show="log.length" id="regenthumbs-debuglist">
      <li ng-repeat="l in log">{{l.message}}</li>
    </ol>

    <div class="buttons">
      <button ng-disabled="isRunning || isLoading" type="submit" class="button-primary"><?php _e( 'Run (may take a while)' ); ?></button>
      <div ng-disabled="!isRunning" ng-click="processStop($event)" class="button-secondary"><?php _e( 'Stop' ); ?></div>
      <div ng-disabled="!log.length" ng-click="log=[]" class="button-secondary"><?php _e( 'Clear Log' ); ?></div>
    </div>

  </form>

</div>