<?php
  /**
   * Stateless Sync Interface
   */
  global $wpdb;

?>


<div class="wrap" ng-controller="wpStatelessTools" ng-init="init()">

  <noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', ud_get_stateless_media()->domain ); ?></em></p></noscript>

  <form id="go" ng-submit="processStart($event)">

    <div>

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

      <?php if(!empty($GLOBALS['show_non_library_sync'])):?>
      <div class="option">
        <label>
          <input ng-disabled="isRunning || isLoading" type="radio" name="action" value="sync_non_library_files" ng-model="action" />
          <?php _e( 'Synchronize non media library files between Google Storage and local server', ud_get_stateless_media()->domain ); ?>
        </label>
      </div>
      <?php endif;?>

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

      <div class="start-form" ng-if="method == 'continue'">
        <h3>
          <?php _e( 'Start sync from', ud_get_stateless_media()->domain ); ?>
        </h3>
        <div>
          <input ng-disabled="isRunning || isLoading" type="number" name="startFrom" ng-model="$parent.startFrom" />
          <br />
          <p class="description" style="margin-top: 11px;">
              <?php _e( 'Enter attachment ID from which you want to restart the sync process, will also include attachments added after you started the first sync. The sync process start from new attachment to old attachments.', ud_get_stateless_media()->domain ); ?>
          </p>
        </div>
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

    <div class="status ud-admin-notice {{error?'error':'notice'}}" ng-show="status">
      <p>
        <?php _e( 'Status:', ud_get_stateless_media()->domain ); ?> {{status}}
        <span class="button button-secondary" ng-show="extraStatus" ng-click="showErrorDetails = !showErrorDetails"><?php _e( 'Details', ud_get_stateless_media()->domain ); ?></span>
      </p>
      <div id="sm-error-details" class="extra-info" ng-show="extraStatus && showErrorDetails" ng-bind-html="extraStatus | trust"></div>
    </div>

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