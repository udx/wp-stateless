<?php
$server_name    = $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']: $_SERVER["SERVER_NAME"];
$id             = str_replace('.', '-', $server_name);
$project_name   = trim(substr($id, 0, 30), '-');
$project_id     = trim(substr($id, 0, 23), '-') . "-" . rand(100000, 999999);

$bucket_id      = trim("stateless-" . substr($id, 0, 20), '-');

?>
<div id="wp-stateless-wrapper">
    <div id="wpStatelessInner">

        <div class="wpStateLess-header">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <div class="wpStateLess-welcome-text">
                            <h1><?php _e( 'WP-Stateless Setup', ud_get_stateless_media()->domain ); ?></h1>
                            <p><?php _e( 'Get up and running in less than 90 seconds! Just follow these three steps.', ud_get_stateless_media()->domain ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wpStateLess-setup-step-area">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="wpStateLess-setup-step">
                            <div class="wpStateLess-setup-step-bars">
                                <ul>
                                    <li class="step-google-login"> <span>1</span> <?php _e( 'Google Login', ud_get_stateless_media()->domain ); ?></li>
                                    <li class="step-setup-project"> <span>2</span> <?php _e( 'Project &amp; Bucket', ud_get_stateless_media()->domain ); ?></li>
                                    <li class="step-final"> <span><?php echo number_format_i18n(3);?></span> <?php _e( 'Complete', ud_get_stateless_media()->domain ); ?></li>
                                </ul>
                            </div>
                            <div class="wpStateLess-setup-steps">
                                <div class="wpStateLess-s-step active step-google-login">
                                    <img src="<?php echo ud_get_stateless_media()->path( 'static/images/authenticate-login.png'); ?>" alt=""/>
                                    <div class="wpStateLess-step-title">
                                        <h3><?php _e( 'Google Login', ud_get_stateless_media()->domain ); ?></h3>
                                        <p class="description"><?php _e( sprintf('Login with the Google Account you want to be associated with this website and consent to the permissions request. If you\'re unsure about granting access to your Google account, check over our documentation on the <a target="_blank" href="%1$s">permissions request</a> and <a target="_blank" href="%2$s">manual setup alternative.</a>', "https://github.com/wpCloud/wp-stateless/wiki/Google-Permission-Request", "https://github.com/wpCloud/wp-stateless/wiki/Manual-Setup"), ud_get_stateless_media()->domain ); ?></p>
                                    </div>
                                    <p>
                                        <input id="allow-notifications" type="checkbox" checked="checked" />
                                        <label class="cursor-normal" for="allow-notifications"><?php _e( sprintf('Receive email updates from plugin author (<a target="_blank" href="%s">Usability Dynamics</a>)?', 'https://www.usabilitydynamics.com/'));?></label>
                                    </p>
                                    <a id="google-login" href="https://api.usabilitydynamics.com/product/stateless/v1/auth/google?state=<?php echo urlencode(ud_get_stateless_media()->get_settings_page_url('?page=stateless-setup&step=google-login') ); ?>" class="btn btn-googly-red"><?php _e( 'Google Login', ud_get_stateless_media()->domain ); ?></a>
                                </div>
                                <div class="wpStateLess-s-step step-setup-project">
                                    <div class="wpStateLess-step-title">
                                        <h3><?php _e( 'Set Project &amp; Bucket', ud_get_stateless_media()->domain ); ?></h3>
                                        <p><?php _e( 'Create a Google Cloud project and bucket that will store your WordPress media files.', ud_get_stateless_media()->domain ); ?></p>
                                    </div>
                                    <div class="wpStateLess-userinfo">
                                        <div class="photo-wrapper">
                                            <img class="user-photo img-circle" src="<?php echo ud_get_stateless_media()->path( 'static/images/author-image.png'); ?>" alt="">
                                        </div>
                                        <div class="wpStateLess-user-detais">
                                            <h4><span class="user-name"></span> <a class="logout" href="#google-logout"><?php _e( 'Logout', ud_get_stateless_media()->domain ); ?></a></h4>
                                            <p class="user-email"></p>
                                        </div>
                                    </div>
                                    <div class="wpStateLess-step-setup-form">
                                        <form action="#" method="POST">
                                            <div id="stateless-notification" class="error">
                                                
                                            </div>
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4><?php _e( 'Google Cloud Project', ud_get_stateless_media()->domain ); ?></h4>
                                                    <p><?php _e( 'By default we create a new project for you, or if you prefer, select an existing project.', ud_get_stateless_media()->domain ); ?></p>
                                                </label>
                                                <div class="wpStateLess-combo-box project">
                                                    <input type="hidden" class="id" value="<?php echo $project_id;?>">
                                                    <input type="text" class="name" value="<?php echo $project_name;?>" placeholder="Select or Create New Project">
                                                    <div class="circle-loader">
                                                        <div class="checkmark draw"></div>
                                                    </div>
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-create-new">
                                                            <h5><?php _e( 'Create New Project', ud_get_stateless_media()->domain ); ?></h5>
                                                            <ul>
                                                                <li class="custom-name"></li>
                                                                <li class="predefined-name active" data-id="<?php echo $project_id?>" data-name="<?php echo $project_name?>">
                                                                    <?php echo "$project_name ($project_id)";?>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="wpStateLess-existing">
                                                            <h5><?php _e( 'Existing Projects', ud_get_stateless_media()->domain ); ?></h5>
                                                            <ul></ul>
                                                        </div>
                                                    </div>
                                                    <div class="error"></div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4><?php _e( 'Google Cloud Bucket', ud_get_stateless_media()->domain ); ?></h4>
                                                    <p><?php _e( 'By default we create a new bucket for you, or if you prefer, select an existing bucket.', ud_get_stateless_media()->domain ); ?></p>
                                                </label>
                                                <div class="wpStateLess-combo-box bucket">
                                                    <input type="text" class="name" value="<?php echo $bucket_id;?>" placeholder="Select or Create New Bucket">
                                                    <div class="circle-loader">
                                                        <div class="checkmark draw"></div>
                                                    </div>
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-create-new">
                                                            <h5><?php _e( 'Create New Bucket', ud_get_stateless_media()->domain ); ?></h5>
                                                            <ul>
                                                                <li class="custom-name"></li>
                                                                <li class="project-derived-name"></li>
                                                                <li class="predefined-name active" data-id="<?php echo $bucket_id?>" data-name="<?php echo $bucket_id?>">
                                                                    <?php echo $bucket_id;?>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="wpStateLess-existing">
                                                            <h5><?php _e( 'Existing Projects', ud_get_stateless_media()->domain ); ?></h5>
                                                            <ul></ul>
                                                        </div>
                                                    </div>
                                                    <div class="error"></div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4><?php _e( 'Google Cloud Bucket Multi-Regional Location', ud_get_stateless_media()->domain ); ?></h4>
                                                    <p><?php _e( 'Your newly created bucket will be provisioned with a multi-regional storage class. Select the region that is closest to the majority of your website\'s visitors.', ud_get_stateless_media()->domain ); ?></p>
                                                </label>
                                                <div class="wpStateLess-combo-box region">
                                                    <input type="hidden" class="id" value="us">
                                                    <input type="text" class="name" readonly="readonly" value="United States" placeholder="Select Location">
                                                    <div class="circle-loader">
                                                        <div class="checkmark draw"></div>
                                                    </div>
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-static">
                                                            <ul>
                                                                <li data-id="asia" data-name="Asia Pacific">Asia Pacific</li>
                                                                <li data-id="eu"   data-name="European Union">European Union</li>
                                                                <li data-id="us"   data-name="United States">United States</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    <div class="error"></div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4><?php _e( 'Google Cloud Billing', ud_get_stateless_media()->domain ); ?></h4>
                                                    <p><?php _e( 'You will need to set a billing account in order to activate your Google Cloud Storage.', ud_get_stateless_media()->domain ); ?></p>
                                                </label>
                                                <a  href="https://console.cloud.google.com/billing" class="btn btn-green create-billing-account no-billing-account"><?php _e( 'Create New Billing Account', ud_get_stateless_media()->domain ); ?> <span class="wpStateLess-loading">(<?php _e( 'Checking', ud_get_stateless_media()->domain ); ?> <span>.</span><span>.</span><span>.</span>)</span></a>
                                                <div class="wpStateLess-combo-box billing-account" style="display: none;">
                                                    <input type="hidden" class="id" value="">
                                                    <input type="text" class="name" value="" readonly="readonly" placeholder="Select Billing Account">
                                                    <div class="circle-loader">
                                                        <div class="checkmark draw"></div>
                                                    </div>
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-existing">
                                                            <h5><?php _e( 'Existing Billing Accounts', ud_get_stateless_media()->domain ); ?></h5>
                                                            <ul></ul>
                                                            <a  href="https://console.cloud.google.com/billing" class="btn btn-green create-billing-account"><?php _e( 'Create New Billing Account', ud_get_stateless_media()->domain ); ?> <span class="wpStateLess-loading">(<?php _e( 'Checking', ud_get_stateless_media()->domain ); ?> <span>.</span><span>.</span><span>.</span>)</span></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input text-center input-submit">
                                                <a class="btn btn-green get-json-key" type="submit"><span class="submit-button-text"><?php _e( 'Continue', ud_get_stateless_media()->domain ); ?> </span><span class="wpStateLess-loading"><?php _e( 'Building', ud_get_stateless_media()->domain ); ?> <span>.</span><span>.</span><span>.</span></span></a>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="wpStateLess-user-has-no-project-billing">
                                        <h4><?php _e( 'Set Google Cloud Billing Account', ud_get_stateless_media()->domain ); ?></h4>
                                        <p><?php _e( 'Click the button below to setup a billing account with Google Cloud. Once configured, return here and click continue.', ud_get_stateless_media()->domain ); ?></p>
                                        <a href="https://console.cloud.google.com/billing" class="btn btn-green create-billing-account" target="_blank"><span class=""><?php _e( 'Set Google Billing', ud_get_stateless_media()->domain ); ?></span> <p class="wpStateLess-loading">(<?php _e( 'Checking', ud_get_stateless_media()->domain ); ?> <span>.</span><span>.</span><span>.</span>)</p></a>
                                        
                                    </div>
                                </div>
                                <div class="wpStateLess-s-step step-final">
                                    <img src="<?php echo ud_get_stateless_media()->path( 'static/images/setup-complete.png'); ?>" alt="">
                                     <div class="wpStateLess-step-title">
                                        <h3><?php _e( 'Setup is Complete!', ud_get_stateless_media()->domain ); ?></h3>
                                        <p><?php _e( 'Any media file you upload to WordPress will now be uploaded to Google Cloud Storage and served to your users from Google servers!', ud_get_stateless_media()->domain ); ?></p>
                                    </div>
                                    <p><?php printf(__( 'To further customize your WP-Stateless setup, visit the <a class="btn-link" href="%s"> WP-Stateless settings panel!</a>', ud_get_stateless_media()->domain ), ud_get_stateless_media()->get('page_url.stateless_settings')); ?></p>
                                    <a href="<?php echo admin_url('media-new.php');?>" class="btn btn-green"><?php _e( 'Upload Something!', ud_get_stateless_media()->domain ); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>