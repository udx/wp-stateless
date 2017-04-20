<div id="wp-stateless-wrapper">
    <div id="wpStatelessInner">
        <div class="wpStateLess-header wpStateLess-header-bg">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <div class="wpStateLess-welcome-text">
                            <h1>All Media Files in One Place</h1>
                            <p>Stores and serves WordPress media files directly from Google Cloud Storage.</p>
                            <a class="btn btn-rounded btn-green" href="<?php echo admin_url('upload.php?page=stateless-setup-wizard&amp;step=google-login');?>">Get Started Now</a>
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
                            <p>Painlessly Upload Existing Media Library</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="wpStateLess-s-feature text-center">
                            <img src="<?php echo ud_get_stateless_media()->path( 'static/images/ficon2.png'); ?>" alt="" />
                            <p>Control Google Files From the Media Library</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="wpStateLess-s-feature text-center">
                            <img src="<?php echo ud_get_stateless_media()->path( 'static/images/ficon3.png'); ?>" alt="" />
                            <p>Delightful and Easy to Use Settings Screen</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wpStateLess-plans">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <h1 class="wpStateLess-section-title">Free &amp; Paid Plans</h1>
                    </div>
                </div>
                <div class="row wpStateLess-valigncenter">
                    <div class="col-md-4">
                        <div class="wpStateLess-s-plan wpStateLess-free-plan">
                            <div class="wpStateLess-plan-name">
                                <p>Free</p>
                            </div>
                            <ul>
                                <li>Media backed up in Google Cloud Storage</li>
                                <li>Easy Setup.</li>
                                <li>Automated synchronization.</li>
                                <li>SSL delivery of files</li>
                            </ul>
                            <div class="text-center">
                                <a href="<?php echo admin_url('upload.php?page=stateless-setup-wizard&amp;step=google-login');?>" class="btn btn-green">Get Started Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="wpStateLess-s-plan">
                            <div class="wpStateLess-plan-name">
                                <p>Performance</p>
                            </div>
                            <ul>
                                <li>Amazing image optimization and compression.</li>
                                <li>On-the-fly thumbnail creation.</li>
                                <li>Very fast delivery of media.</li>
                                <li>Media performance reports.</li>
                                <li>Consolidated billing.</li>
                            </ul>
                            <div class="text-center">
                                <a href="https://www.usabilitydynamics.com/" class="btn learn-more btn-green">Learn More</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="wpStateLess-s-plan">
                            <div class="wpStateLess-plan-name">
                                <p>Enterprise</p>
                            </div>
                            <ul>
                                <li>Advanced billing by domain or customer.</li>
                                <li>Automated provisioning of WordPress multisites.</li>
                                <li>Branded domain.</li>
                            </ul>
                            <div class="text-center">
                                <a href="https://www.usabilitydynamics.com/" class="btn learn-more btn-green">Learn More</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="wpStateLess-popup" class="">
            <div class="wpStateLess-pop-area">
                <div class="wpStateLess-pops">
                    <h3>Contact Us</h3>
                    <p>Signin with your google account to setup the plguin</p>
                    <form action="" method="POST">
                        <div class="wpStateLess-contact-input">
                            <label for="wpstateless-fullname">Full Name</label>
                            <input id="wpstateless-fullname" type="text" placeholder="John Smith" />
                        </div>
                        <div class="wpStateLess-contact-input">
                            <label for="wpstateless-phone">Phone</label>
                            <input id="wpstateless-phone" type="text" placeholder="+1 (857) 748 8383" />
                        </div>
                        <div class="wpStateLess-contact-input">
                            <label for="wpstateless-email">Email</label>
                            <input id="wpstateless-email" type="email" placeholder="me@domain.com" />
                        </div>
                        <div class="wpStateLess-contact-input">
                            <label for="wpstateless-url">Website</label>
                            <input id="wpstateless-url" type="url" placeholder="http://www.domain.com" />
                        </div>
                        <div class="text-center">
                            <input type="submit" class="btn btn-googly-red" value="Submit">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>