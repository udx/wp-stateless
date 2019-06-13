<div id="wp-stateless-wrapper">
    <div id="wpStatelessInner">
        <div class="wpStateLess-header wpStateLess-header-bg">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <div class="wpStateLess-welcome-text">
                            <h1><?php _e( 'WP-Stateless', ud_get_stateless_media()->domain ); ?></h1>
                            <p><?php _e( 'Upload and serve your WordPress media files from Google Cloud Storage.', ud_get_stateless_media()->domain ); ?></p>
                            <a class="btn btn-rounded btn-green" href="<?php echo ud_get_stateless_media()->get_settings_page_url('?page=stateless-setup&step=google-login');?>"><?php _e( 'Begin Setup Assistant', ud_get_stateless_media()->domain ); ?></a>
                            <p class="manual-instruction"> <?php _e( sprintf('or read <a target="_blank" href="%s">manual setup instructions.</a>', 'https://wp-stateless.github.io/docs/manual-setup/'))?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wpStateLess-feature">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <div class="wpStateLess-s-feature text-center">
                            <img src="<?php echo ud_get_stateless_media()->path( 'static/images/ficon1.png', 'url'  ); ?>" alt="" />
                            <p><?php _e( 'Media Uploaded to Google Cloud', ud_get_stateless_media()->domain ); ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="wpStateLess-s-feature text-center">
                            <img src="<?php echo ud_get_stateless_media()->path( 'static/images/ficon2.png'); ?>" alt="" />
                            <p><?php _e( 'Google Cloud Serves Media', ud_get_stateless_media()->domain ); ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="wpStateLess-s-feature text-center">
                            <img src="<?php echo ud_get_stateless_media()->path( 'static/images/ficon3.png'); ?>" alt="" />
                            <p><?php _e( 'Managed by WP-Stateless', ud_get_stateless_media()->domain ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="wpStateLess-footerLogo">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <a href="https://www.usabilitydynamics.com/" target="_blank"><img src="https://www.usabilitydynamics.com/assets/powered-by-usability-dynamics.png"></a>
                </div>
            </div>
        </div>
    </div>
</div>

