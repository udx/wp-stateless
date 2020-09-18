<div class="wrap" ng-controller="wpStatelessProcessing" ng-init="init()">

  <noscript>
    <p><em><?php _e('You must enable Javascript in order to use this feature!', ud_get_stateless_media()->domain); ?></em></p>
  </noscript>

  <div class="metabox-holder">
    <div class="postbox-container">
      <div class="postbox">
        <div class="postbox-header">
          <h2 class="hndle">
            Media Library Objects
            <a href="#" data-position='{"edge":"left","align":"center"}' data-title="What are Media Library Objects?" data-text="All images and other files that were uploaded via the media library or via plugins that use standard uploading API." class="pointer dashicons dashicons-info"></a></h2>
        </div>
        <div class="inside">
          <ul>
            <li><strong>Total Objects:</strong> <span class="">2343322</span></li>
            <li><strong>Images:</strong> <span class="">3456</span></li>
            <li><strong>Other:</strong> <span class="">34</span></li>
          </ul>
          <div class="actions">
            <button type="button" class="button button-primary">Sync Images</button>
            <button type="button" class="button button-primary">Sync Other</button>
            <button type="button" class="button button-primary">Sync All</button>
          </div>
        </div>
      </div>

      <div class="postbox">
        <div class="postbox-header">
          <h2 class="hndle">
            Custom Folders Objects
            <a href="#" data-position='{"edge":"left","align":"center"}' data-title="What are Custom Folders?" data-text="Custom Folders are the upload folders that was automatically created by plugins outside of the scope of Media Library. They store media files created by these plugins. WP-Stateless considers such folders if there is a compatibility developed for a particular plugins." class="pointer dashicons dashicons-info"></a></h2>
        </div>
        <div class="inside">
          <ul>
            <li><strong>Total Objects:</strong> <span class="">34</span></li>
          </ul>
          <div class="actions">
            <button type="button" class="button button-primary">Sync All</button>
          </div>
        </div>
      </div>
    </div>
    <div class="postbox-container">

    </div>
  </div>

</div>