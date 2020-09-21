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
    <div class="postbox-container">
      <div class="postbox">
        <div class="postbox-header">
          <h2 class="hndle">
            Media Library Objects
            <span>
              <span ng-show="stats.isLoading" class="loading dashicons dashicons-update"></span>
              <a href="#" data-position='{"edge":"left","align":"center"}' data-title="What are Media Library Objects?" data-text="All images and other files that were uploaded via the media library or via plugins that use standard uploading API." class="pointer dashicons dashicons-info"></a>
            </span>
          </h2>
        </div>
        <div class="inside">
          <ul>
            <li><strong>Total Objects:</strong> <span>{{stats.values.images + stats.values.other}}</span></li>
            <li><strong>Images:</strong> <span>{{stats.values.images}}</span></li>
            <li><strong>Other:</strong> <span>{{stats.values.other}}</span></li>
          </ul>
          <div class="actions">
            <button type="button" class="button button-primary" ng-class="{disabled: !canSync()}" ng-click="runSync('images', stats.limit.value)">Sync Images</button>
            <button type="button" class="button button-primary" ng-class="{disabled: !canSync()}" ng-click="runSync('other', stats.limit.value)">Sync Other</button>
            <button type="button" class="button button-primary" ng-class="{disabled: !canSync()}" ng-click="runSync('all', stats.limit.value)">Sync All</button>
          </div>
          <div class="options">
            <label>Enable Limit <input type="checkbox" ng-model="stats.limit.enabled" ng-change="stats.limit.value = 0" /></label>
            <label>
              <input ng-disabled="!stats.limit.enabled" type="number" ng-model="stats.limit.value" />
            </label>
          </div>
        </div>
      </div>

      <div class="postbox">
        <div class="postbox-header">
          <h2 class="hndle">
            Custom Folders Objects
            <span>
              <span ng-show="stats.isLoading" class="loading dashicons dashicons-update"></span>
              <a href="#" data-position='{"edge":"left","align":"center"}' data-title="What are Custom Folders?" data-text="Custom Folders are the upload folders that was automatically created by plugins outside of the scope of Media Library. They store media files created by these plugins. WP-Stateless considers such folders if there is a compatibility developed for a particular plugins." class="pointer dashicons dashicons-info"></a>
            </span>
          </h2>
        </div>
        <div class="inside">
          <ul>
            <li><strong>Total Objects:</strong> <span>{{stats.custom}}</span></li>
          </ul>
          <div class="actions">
            <button type="button" class="button button-primary" ng-class="{disabled: !canSync()}">Sync All</button>
          </div>
        </div>
      </div>
    </div>
    <div class="postbox-container">

    </div>
  </div>

</div>