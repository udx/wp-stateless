<div class="wrap" ng-controller="wpStatelessProcessing" ng-init="init()">

  <noscript>
    <p><em><?php _e('You must enable Javascript in order to use this feature!', ud_get_stateless_media()->domain); ?></em></p>
  </noscript>

  <div ng-show="errors.length" class="stateless-admin-notice admin-error">
    <strong><?php _e('Errors encountered', ud_get_stateless_media()->domain); ?></strong>:</strong>
    <ul>
      <li ng-repeat="error in errors">{{error}}</li>
    </ul>
  </div>

  <div class="metabox-holder">
    <div class="postbox-container" ng-show="processes.classes.length">

      <div class="postbox" ng-repeat="process in processes.classes">
        <div class="postbox-header">
          <h2 class="hndle">
            {{process.name}}
            <span>
              <span title="<?php _e('Processing in progress...', ud_get_stateless_media()->domain) ?>" ng-show="process.is_running" class="loading dashicons dashicons-update"></span>
              <a href="#" data-position='{"edge":"left","align":"center"}' data-title="{{process.helper.title}}" data-text="{{process.helper.content}}" class="pointer dashicons dashicons-info"></a>
            </span>
          </h2>
        </div>
        <div class="inside">
          <ul>
            <li><strong><?php _e('Total Items', ud_get_stateless_media()->domain) ?>:</strong> <span>{{process.total_items}}</span></li>
          </ul>
          <div class="options">
            <label><?php _e('Enable Limit', ud_get_stateless_media()->domain) ?> <input type="checkbox" ng-model="process.limit_enabled" ng-disabled="process.is_running" ng-change="process.limit = 0" /></label>
            <label ng-style="{visibility: process.limit_enabled || process.limit > 0 ? 'visible' : 'hidden'}">
              <input ng-disabled="!process.limit_enabled || process.is_running" type="number" ng-model="process.limit" style="width:80px" />
            </label>
          </div>
          <div class="options">
            <label>
              <?php _e('Start from', ud_get_stateless_media()->domain) ?>
              <select ng-model="process.order">
                <option value="desc"><?php _e('newest', ud_get_stateless_media()->domain) ?></option>
                <option value="asc"><?php _e('oldest', ud_get_stateless_media()->domain) ?></option>
              </select>
            </label>
          </div>
          <div class="actions">
            <button type="button" class="button button-primary" ng-class="{disabled: !process.canRun()}" ng-click="process.run()"><?php _e('Run', ud_get_stateless_media()->domain) ?></button>
            <button type="button" class="button button-secondary" ng-class="{disabled: !process.is_running}" ng-click="process.stop()"><?php _e('Stop', ud_get_stateless_media()->domain) ?></button>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>