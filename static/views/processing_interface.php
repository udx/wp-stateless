<div class="wrap" ng-controller="wpStatelessProcessing" ng-init="init()">

  <noscript>
    <p><em><?php _e('You must enable Javascript in order to use this feature!', ud_get_stateless_media()->domain); ?></em></p>
  </noscript>

  <div ng-show="errors.length" class="stateless-admin-notice admin-error">
    <strong>Errors encountered:</strong>
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
              <span ng-show="stats.isLoading" class="loading dashicons dashicons-update"></span>
              <a href="#" data-position='{"edge":"left","align":"center"}' data-title="{{process.helper.title}}" data-text="{{process.helper.content}}" class="pointer dashicons dashicons-info"></a>
            </span>
          </h2>
        </div>
        <div class="inside">
          <ul>
            <li><strong>Total Items:</strong> <span>{{process.total_items}}</span></li>
          </ul>
          <div class="options">
            <label>Enable Limit <input type="checkbox" ng-model="process.limit_enabled" ng-disabled="process.is_running" ng-change="process.limit = 0" /></label>
            <label ng-show="process.limit_enabled || process.limit > 0">
              <input ng-disabled="!process.limit_enabled || process.is_running" type="number" ng-model="process.limit" style="width:80px" />
            </label>
          </div>
          <div class="actions">
            <button type="button" class="button button-primary" ng-class="{disabled: !process.canRun()}" ng-click="process.run()">Run</button>
            <button type="button" class="button button-secondary" ng-class="{disabled: !process.is_running}" ng-click="process.stop()">Stop</button>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>