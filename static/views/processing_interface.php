<div class="wrap" ng-controller="wpStatelessProcessing" ng-init="init()">

<noscript>
  <p><em><?php _e('You must enable Javascript in order to use this feature!', ud_get_stateless_media()->domain); ?></em></p>
</noscript>

<div ng-show="errors.length" class="stateless-admin-notice admin-error">
  <strong><?php _e('Errors encountered. Try reloading the page.', ud_get_stateless_media()->domain); ?></strong>
  <ul>
    <li ng-repeat="error in errors">{{error}}</li>
  </ul>
</div>

<div ng-show="processes.isLoading"><?php _e('Loading available processes...', ud_get_stateless_media()->domain); ?></div>

<div class="metabox-holder" ng-show="processes.classes.length">
  <p class="processing-hint"><strong><?php _e('Hint', ud_get_stateless_media()->domain) ?>:</strong> <?php _e('You can close this page once processing is started.', ud_get_stateless_media()->domain) ?></p>
  <div class="postbox-container">

    <div class="postbox" ng-repeat="process in processes.classes">
      <div class="postbox-header">
        <h2 class="hndle">
          <div class="title-holder" ng-bind-html="process.name">
          </div>
          <span>
            <span title="<?php _e('Processing in progress...', ud_get_stateless_media()->domain) ?>" ng-show="process.is_running" class="loading dashicons dashicons-update"></span>
            <a ng-show="process.helper" href="javascript:;" data-position='{"edge":"left","align":"center"}' data-title="{{process.helper.title}}" data-text="{{process.helper.content}}" class="pointer dashicons dashicons-info"></a>
          </span>
        </h2>
      </div>
      <div class="inside">
        <ul>
          <li><strong><?php _e('Total Items', ud_get_stateless_media()->domain) ?>:</strong> <span>{{process.total_items}}</span></li>
        </ul>
        <div class="options" ng-show="process.allow_limit">
          <label><?php _e('Enable Limit', ud_get_stateless_media()->domain) ?> <input type="checkbox" ng-model="process.limit_enabled" ng-disabled="process.is_running" ng-change="process.limit = 0" /></label>
          <label ng-style="{visibility: process.limit_enabled || process.limit > 0 ? 'visible' : 'hidden'}">
            <input ng-disabled="!process.limit_enabled || process.is_running" type="number" ng-model="process.limit" style="width:80px" />
          </label>
        </div>
        <div class="options" ng-show="process.allow_sorting">
          <label>
            <?php _e('Start from', ud_get_stateless_media()->domain) ?>
            <select ng-model="process.order" ng-disabled="process.is_running">
              <option value="desc"><?php _e('newest', ud_get_stateless_media()->domain) ?></option>
              <option value="asc"><?php _e('oldest', ud_get_stateless_media()->domain) ?></option>
            </select>
          </label>
        </div>
        <div class="progress" ng-show="process.is_running">
          <div class="bar-wrapper">
            <div class="legend">
              <strong class="total"><?php _e('Total', ud_get_stateless_media()->domain) ?>: {{process.getProgressTotal()}}</strong>
              <strong class="queued"><?php _e('Queued', ud_get_stateless_media()->domain) ?>: {{process.getQueuedTotal()}}</strong>
              <strong class="processed"><?php _e('Processed', ud_get_stateless_media()->domain) ?>: {{process.processed_items}}</strong>
            </div>
            <div class="bar total" ng-style="{'background-color': process.getProgressTotal() == process.getProcessedTotal() ? '#02ae7a' : false}">
              <div class="bar queued" ng-style="{width: percentage(process.getQueuedTotal(), process.getProgressTotal()),'background-color': process.getProgressTotal() == process.getProcessedTotal() ? '#02ae7a' : false}">
                <div class="bar processed" ng-style="{width: percentage(process.getProcessedTotal(), process.getQueuedTotal()), 'background-color': process.getProgressTotal() == process.getProcessedTotal() ? '#02ae7a' : false}">&nbsp;</div>
              </div>
            </div>
          </div>
        </div>
        <div class="progress-notice" ng-show="process.notice.length && process.is_running">
          <p ng-repeat="notice in process.notice" ng-bind-html="notice"></p>
        </div>
        <div class="actions">
          <button type="button" class="button button-primary" ng-class="{disabled: !process.canRun()}" ng-click="!process.canRun() || process.run()"><?php _e('Run', ud_get_stateless_media()->domain) ?></button>
          <button type="button" class="button button-secondary" ng-class="{disabled: !process.canStop()}" ng-click="!process.canStop() || process.stop()"><?php _e('Stop', ud_get_stateless_media()->domain) ?></button>
        </div>
      </div>
    </div>
  </div>
</div>

</div>