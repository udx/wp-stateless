<?php
$server_name    = $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']: $_SERVER["SERVER_NAME"];
$id             = str_replace('.', '-', $server_name);
$project_name   = substr($id, 0, 30);
$project_id     = substr($id, 0, 23) . "-" . rand(100000, 999999);

$bucket_id      = substr($id, 0, 30);
$bucket_name    = str_replace(array('.', '-'), ' ', substr($server_name, 0, 30));

?>
<div id="wp-stateless-wrapper">
    <div id="wpStatelessInner">

        <div class="wpStateLess-header">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <div class="wpStateLess-welcome-text">
                            <h1>Stateless Setup</h1>
                            <p>Save your media files at one place. Start your free trial now</p>
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
                                    <li class="step-google-login"> <span>1</span> Login to Google</li>
                                    <li class="step-setup-project"> <span>2</span> Setup Project</li>
                                    <li class="step-final"> <span>3</span> Complete</li>
                                </ul>
                            </div>
                            <div class="wpStateLess-setup-steps">
                                <div class="wpStateLess-s-step active step-google-login">
                                    <img src="<?php echo ud_get_stateless_media()->path( 'static/images/authenticate-login.png'); ?>" alt=""/>
                                    <div class="wpStateLess-step-title">
                                        <h3>Autheticate the Login</h3>
                                        <p>Signin with your google account to setup the plguin</p>
                                    </div>

                                    <a href="https://api.usabilitydynamics.com/product/stateless/v1/auth/google?state=<?php echo urlencode(admin_url('upload.php?page=stateless-setup-wizard&step=setup-project')); ?>" class="btn btn-googly-red">Google Login</a>
                                </div>
                                <div class="wpStateLess-s-step step-setup-project">
                                    <div class="wpStateLess-step-title">
                                        <h3>Set Project &amp; Bucket</h3>
                                        <p>Create a Google Cloud Project and bucket that will store your WordPress media.</p>
                                    </div>
                                    <div class="wpStateLess-userinfo">
                                        <div class="photo-wrapper">
                                            <img class="user-photo img-circle" src="<?php echo ud_get_stateless_media()->path( 'static/images/author-image.png'); ?>" alt="">
                                        </div>
                                        <div class="wpStateLess-user-detais" style="display: none;">
                                            <h4><span class="user-name"></span> <a class="logout" href="#google-logout">Logout</a></h4>
                                            <p class="user-email"></p>
                                        </div>
                                    </div>
                                    <div class="wpStateLess-step-setup-form">
                                        <form action="#" method="POST">
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4>Google Cloud Project</h4>
                                                    <p>By default we create a new project for you, or if you prefer, select an existing project.</p>
                                                </label>
                                                <div class="wpStateLess-combo-box project">
                                                    <input type="hidden" class="id" value="<?php echo $project_id;?>">
                                                    <input type="text" class="name" value="<?php echo $project_name;?>" placeholder="Select or Create New Project">
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-create-new active" data-id="<?php echo $project_id?>" data-name="<?php echo $project_name?>">
                                                            <h5>Create New Project</h5>
                                                            <span><?php echo "$project_name ($project_id)";?></span>
                                                        </div>
                                                        <div class="wpStateLess-existing">
                                                            <h5>Existing Projects</h5>
                                                            <ul></ul>
                                                        </div>
                                                    </div>
                                                    <div class="error"></div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4>Google Cloud Bucket</h4>
                                                    <p>By default we create a new bucket for you, or if you prefer, select an existing bucket.</p>
                                                </label>
                                                <div class="wpStateLess-combo-box bucket">
                                                    <input type="hidden" class="id" value="stateless-<?php echo $bucket_id;?>">
                                                    <input type="text" class="name" value="Stateless <?php echo $bucket_name;?>" placeholder="Select or Create New Bucket">
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-create-new active" data-id="<?php echo $bucket_id?>" data-name="<?php echo $bucket_id?>">
                                                            <h5>Create New Bucket</h5>
                                                            <span>stateless-<?php echo $bucket_id;?></span>
                                                        </div>
                                                        <div class="wpStateLess-existing">
                                                            <h5>Existing Projects</h5>
                                                            <ul></ul>
                                                        </div>
                                                    </div>
                                                    <div class="error"></div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4>Google Cloud Billing</h4>
                                                    <p>You will need to set a billing account in order to activate your Google Cloud Storage.</p>
                                                </label>
                                                <a  href="https://console.cloud.google.com/billing" class="btn btn-green create-billing-account no-billing-account">Create New Billing Account <span class="wpStateLess-loading">(Checking <span>.</span><span>.</span><span>.</span>)</span></a>
                                                <div class="wpStateLess-combo-box billing-account" style="display: none;">
                                                    <input type="hidden" class="id" value="">
                                                    <input type="text" class="name" value="" readonly="readonly" placeholder="Select Billing Account">
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-current-account">
                                                            <h5>Billing Account for Project <b class="project"></b></h5>
                                                            <span></span>
                                                        </div>
                                                        <div class="wpStateLess-existing">
                                                            <h5>Existing Projects</h5>
                                                            <ul></ul>
                                                            <a  href="https://console.cloud.google.com/billing" class="btn btn-green create-billing-account">Create New Billing Account <span class="wpStateLess-loading">(Checking <span>.</span><span>.</span><span>.</span>)</span></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input text-center input-submit">
                                                <a class="btn btn-green get-json-key" type="submit">Continue <span class="wpStateLess-loading">(Checking <span>.</span><span>.</span><span>.</span>)</span></a>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="wpStateLess-user-has-no-project-billing">
                                        <h4>Set Google Cloud Billing Account</h4>
                                        <p>Click the button below to setup a billing account with Google Cloud. Once configured, return here and click continue.</p>
                                        <a href="https://console.cloud.google.com/billing" class="btn btn-green create-billing-account" target="_blank"><span class="">Set Google Billing</span> <p class="wpStateLess-loading">(Checking <span>.</span><span>.</span><span>.</span>)</p></a>
                                        
                                    </div>
                                </div>
                                <div class="wpStateLess-s-step step-final">
                                    <img src="<?php echo ud_get_stateless_media()->path( 'static/images/setup-complete.png'); ?>" alt="">
                                     <div class="wpStateLess-step-title">
                                        <h3>Congrats, Your Setup is Complete</h3>
                                        <p>Any media file you upload to WordPress will now be uploaded to Google and served to your users from Google servers! A background process is now running that will upload any existing media into Google Cloud Storage. </p>
                                    </div>
                                    <p>To further customize your stateless media setup, visit the <a class="btn-link" href="<?php echo admin_url('options-media.php#stateless-media');?>">settings panel!</a></p>
                                    <a href="<?php echo admin_url('media-new.php');?>" class="btn btn-green">Upload Something!</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>