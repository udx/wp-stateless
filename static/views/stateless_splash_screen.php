<div id="wp-stateless-wrapper">
    <div id="wpStatelessInner">
        <div class="wpStateLess-header wpStateLess-header-bg">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <div class="wpStateLess-welcome-text">
                            <h1>WP-Stateless</h1>
                            <p>Upload and serve your WordPress media files from Google Cloud Storage.</p>
                            <a class="btn btn-rounded btn-green" href="<?php echo ud_get_stateless_media()->get_settings_page_url('?page=stateless-setup&step=google-login');?>">Get Started Now</a>
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
                            <p>Media Uploaded to Google Cloud</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="wpStateLess-s-feature text-center">
                            <img src="<?php echo ud_get_stateless_media()->path( 'static/images/ficon2.png'); ?>" alt="" />
                            <p>Google Cloud Serves Media</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="wpStateLess-s-feature text-center">
                            <img src="<?php echo ud_get_stateless_media()->path( 'static/images/ficon3.png'); ?>" alt="" />
                            <p>Managed by WP-Stateless</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>