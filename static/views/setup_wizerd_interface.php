<div id="wp-stateless-wrapper">
    <div id="wpStatelessInner">
        <div class="wpStateLess-header">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <div class="wpStateLess-welcome-text">
                            <h1>Stateless Setup</h1>
                            <p>Save your media files at one place. Start your free trial now</p>
                            <a class="btn btn-green" href="<?php echo admin_url('upload.php?page=stateless-setup-wizerd&step=splash-screen');?>">Back to splash screen</a>
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

                                    <a href="https://api.usabilitydynamics.com/product/stateless/v1/auth/google?state=<?php echo urlencode(admin_url('upload.php?page=stateless-setup-wizerd')); ?>" class="btn btn-googly-red">Google Login</a>
                                </div>
                                <div class="wpStateLess-s-step step-setup-project">
                                    <div class="wpStateLess-step-title">
                                        <h3>Set Project &amp; Bucket</h3>
                                        <p>Create a Google Cloud Project and bucket that will store your WordPress media.</p>
                                    </div>
                                    <div class="wpStateLess-userinfo">
                                        <img class="user-photo img-circle" src="<?php echo ud_get_stateless_media()->path( 'static/images/author-image.png'); ?>" alt="">
                                        <div class="wpStateLess-user-detais">
                                            <h4><span class="user-name">Paresh Khatri</span> <a class="logout" href="#google-logout">Logout</a></h4>
                                            <p class="user-email">paresh.khatri@usabilitydynamics.com</p>
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
                                                    <input type="text" class="name" value="mywebsite-com">
                                                    <input type="hidden" class="id" value="mywebsite-com">
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-create-new">
                                                            <h5>Create New Project</h5>
                                                            <span>mywebsite-com</span>
                                                        </div>
                                                        <div class="wpStateLess-existing">
                                                            <h5>Existing Projects</h5>
                                                            <ul>
                                                                <li>Disco Donnie Presents (client-ddp)</li>
                                                                <li>Red Door Company (client-rdc)</li>
                                                                <li>HealthyUNow Foundation (client-hun)</li> 
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4>Google Cloud Bucket</h4>
                                                    <p>By default we create a new bucket for you, or if you prefer, select an existing bucket.</p>
                                                </label>
                                                <div class="wpStateLess-combo-box bucket">
                                                    <input type="text" value="mywebsite-com">
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-create-new">
                                                            <h5>Create New Project</h5>
                                                            <span>mywebsite-com</span>
                                                        </div>
                                                        <div class="wpStateLess-existing">
                                                            <h5>Existing Projects</h5>
                                                            <ul>
                                                                <li>Disco Donnie Presents (client-ddp)</li>
                                                                <li>Red Door Company (client-rdc)</li>
                                                                <li>HealthyUNow Foundation (client-hun)</li> 
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="wpStateLess-single-step-input">
                                                <label for="">
                                                    <h4>Google Cloud Billing</h4>
                                                    <p>You will need to set a billing account in order to activate your Google Cloud Storage.</p>
                                                </label>
                                                <div class="wpStateLess-combo-box billing-account">
                                                    <input type="text" value="mywebsite-com">
                                                    <div class="wpStateLess-input-dropdown">
                                                        <div class="wpStateLess-create-new">
                                                            <h5>Create New Project</h5>
                                                            <span>mywebsite-com</span>
                                                        </div>
                                                        <div class="wpStateLess-existing">
                                                            <h5>Existing Projects</h5>
                                                            <ul>
                                                                <li>Disco Donnie Presents (client-ddp)</li>
                                                                <li>Red Door Company (client-rdc)</li>
                                                                <li>HealthyUNow Foundation (client-hun)</li> 
                                                            </ul>
                                                            <a class="btn btn-green">Create New Billing Account</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <a class="btn btn-green">Create New Billing Account</a>
                                            </div>
                                            <div class="wpStateLess-single-step-input text-center input-submit">
                                                <input class="btn btn-green" type="submit" value="Continue">
                                            </div>
                                        </form>
                                    </div>
                                    
                                </div>
                                <div class="wpStateLess-s-step step-final">
                                    <img src="<?php echo ud_get_stateless_media()->path( 'static/images/setup-complete.png'); ?>" alt="">
                                     <div class="wpStateLess-step-title">
                                        <h3>Congrats, Your Setup is Complete</h3>
                                        <p>Any media file you upload to WordPress will now be uploaded to Google and served to your users from Google servers! A background process is now running that will upload any existing media into Google Cloud Storage. </p>
                                    </div>
                                    <p>To further customize your stateless media setup, visit the settings panel!</p>
                                    <a href="" class="btn btn-green">Continue</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>