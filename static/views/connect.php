<?php
/**
 * Stateless Connect page.
 *
 * Variables provided by GoogleConnect::render_page():
 * $client, $connection, $auth_url, $projects, $billing, $proj_err, $log, $error,
 * $suggested_bucket, $default_project, $default_bucket, $settings
 */
$domain  = ud_get_stateless_media()->domain;
$msg     = isset($_GET['sm_gc']) ? sanitize_text_field($_GET['sm_gc']) : '';
$connect = wpCloud\StatelessMedia\GoogleConnect::instance();
$post = add_query_arg('sm_gc_network', $connect->network_scope() ? '1' : '0', admin_url('admin-post.php'));
$settings_url = $connect->network_scope() ? network_admin_url('settings.php?page=stateless-settings') : admin_url('upload.php?page=stateless-settings');
$configured = !empty($settings->key_json) && !empty($settings->bucket);
$redirect_uri  = $client ? $client['redirect_uri'] : wpCloud\StatelessMedia\GoogleConnect::LOOPBACK_URI;
$hosted        = $client && $client['source'] === 'broker';
$broker_host   = wp_parse_url(wpCloud\StatelessMedia\GoogleConnect::broker_url(), PHP_URL_HOST);
$site_host     = wp_parse_url(home_url(), PHP_URL_HOST);
$start_error   = wpCloud\StatelessMedia\GoogleConnect::instance()->start_error;
?>
<div class="wrap sm-connect">
  <div class="sm-connect-brand"><img src="<?php echo esc_url(plugins_url('static/images/connect/logo-stateless.svg', dirname(__DIR__, 2) . '/wp-stateless.php')); ?>" alt="Stateless" width="184"><span><?php _e('GOOGLE CLOUD SETUP', $domain); ?></span></div>
  <?php if ($connect->network_scope()) : ?><p class="notice notice-info inline"><?php _e('Network setup: this connection and storage configuration apply to the network. Individual site connections remain separate.', $domain); ?></p><?php endif; ?>
  <h1><?php _e('Stateless Connect', $domain); ?></h1>
  <ol class="sm-connect-steps" aria-label="<?php esc_attr_e('Setup progress', $domain); ?>">
    <?php foreach (array('account' => __('Connect Google', $domain), 'storage' => __('Choose storage', $domain), 'ready' => __('Ready to upload', $domain)) as $step => $label) :
      $available = $step === 'account' || ($step === 'storage' && $connection) || ($step === 'ready' && $client && $connection && $configured);
      $current = !$connection ? 'account' : ((($error || $msg === 'billing-ready') && !empty($draft)) ? 'storage' : ($configured ? 'ready' : 'storage'));
      $number = array('account' => '01', 'storage' => '02', 'ready' => '03'); ?>
      <li data-step="<?php echo esc_attr($step); ?>" class="<?php echo $step === $current ? 'is-current' : ($available ? 'is-done' : ''); ?>" <?php if ($step === $current) echo 'aria-current="step"'; ?>>
        <?php if ($available) : ?><a href="#sm-step-<?php echo esc_attr($step); ?>" data-wizard-step="<?php echo esc_attr($step); ?>"><?php endif; ?>
        <span><?php echo esc_html($number[$step]); ?></span><?php echo esc_html($label); ?>
        <?php if ($available) : ?></a><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>

  <?php if ($configured && !$connection) :
    $mode_labels = array('backup' => __('Backup', $domain), 'cdn' => __('CDN', $domain), 'ephemeral' => __('Ephemeral', $domain), 'stateless' => __('Stateless', $domain), 'disabled' => __('Disabled', $domain));
    $mode_label  = isset($mode_labels[$settings->mode]) ? $mode_labels[$settings->mode] : $settings->mode; ?>
    <div class="notice notice-info inline"><p>
      <?php printf(
        /* translators: 1: bucket name, 2: storage mode, 3: settings link */
        __('This site already serves media from the bucket %1$s in %2$s mode with credentials configured by hand. Nothing changes until you choose storage in step 02; use %3$s to adjust the current configuration.', $domain),
        '<code>' . esc_html($settings->bucket) . '</code>',
        '<strong>' . esc_html($mode_label) . '</strong>',
        '<a href="' . esc_url($settings_url) . '">' . esc_html__('Stateless Settings', $domain) . '</a>'
      ); ?>
    </p></div>
  <?php endif; ?>

  <?php if ($error) : ?>
    <div class="notice notice-error"><p><?php echo wp_kses_post($error); ?></p></div>
  <?php elseif ($msg === 'client-saved') : ?>
    <div class="notice notice-success"><p><?php _e('OAuth client saved.', $domain); ?></p></div>
  <?php elseif ($msg === 'connected' && $connection) : ?>
    <div class="notice notice-success"><p><?php _e('Google account connected.', $domain); ?></p></div>
  <?php elseif ($msg === 'billing-ready') : ?>
    <div class="notice notice-success"><p><?php _e('Billing is enabled for this project. Your storage choices are ready; choose Create my storage to continue.', $domain); ?></p></div>
  <?php elseif ($msg === 'disconnected') : ?>
    <div class="notice notice-success"><p><?php _e('Google account disconnected and access revoked.', $domain); ?></p></div>
  <?php elseif ($msg === 'disconnected-unrevoked') : ?>
    <div class="notice notice-warning"><p><?php _e('Connection removed. Access was not revoked from here because the stored credentials could not be read; review Third-party apps and services in your Google Account to make sure it is gone.', $domain); ?></p></div>
  <?php endif; ?>

  <?php if (is_array($log) && $log) : ?>
    <div class="card" style="max-width:720px">
      <h2 class="title"><?php echo $msg === 'provisioned' ? esc_html__('Done', $domain) : esc_html__('Progress', $domain); ?></h2>
      <ol style="margin-left:1.2em">
        <?php foreach ($log as $line) : ?>
          <li><?php echo esc_html($line); ?></li>
        <?php endforeach; ?>
      </ol>
      <?php if ($msg === 'provisioned') : ?>
        <p>
          <a class="button button-primary" href="<?php echo esc_url(admin_url('media-new.php')); ?>"><?php _e('Upload a test image', $domain); ?></a>
          <a class="button" href="<?php echo esc_url(ud_get_stateless_media()->get_settings_page_url('?page=stateless-settings')); ?>"><?php _e('Open Stateless Settings', $domain); ?></a>
        </p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php /* ---------------- Step 0: OAuth client (dev only until the UDX client ships in the plugin) ---------------- */ ?>
  <?php if (!$client) : ?>
    <div class="notice notice-warning inline"><p><?php printf(esc_html__('Could not reach %s to start the sign-in. Check that this site can make outbound HTTPS requests, or paste your own OAuth client below.', $domain), '<code>' . esc_html(wpCloud\StatelessMedia\GoogleConnect::broker_url()) . '</code>'); ?></p></div>
  <?php endif; ?>
  <?php if (!$client || isset($_GET['edit_client'])) : ?>
    <div class="card" style="max-width:720px">
      <h2 class="title"><?php _e('Your own OAuth client (optional)', $domain); ?></h2>
      <p><?php printf(esc_html__('By default the sign-in goes through %s, which holds the WP-Stateless client. Paste your own client here to bypass it.', $domain), '<code>' . esc_html(wp_parse_url(wpCloud\StatelessMedia\GoogleConnect::broker_url(), PHP_URL_HOST)) . '</code>'); ?></p>
      <p class="description"><?php printf(esc_html__('Create it in the Google Cloud console under Clients as a Desktop app. Google then sends the browser to the loopback address %s, which never loads; you copy the whole address from the address bar.', $domain), '<code>' . esc_html(wpCloud\StatelessMedia\GoogleConnect::LOOPBACK_URI) . '</code>'); ?></p>
      <form method="post" action="<?php echo esc_url($post); ?>">
        <?php wp_nonce_field($connect->nonce_action()); ?>
        <input type="hidden" name="action" value="sm_gc_client">
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="client_id"><?php _e('Client ID', $domain); ?></label></th>
            <td><input name="client_id" id="client_id" type="text" class="large-text code" value="<?php echo ($client && $client['source'] === 'option') ? esc_attr($client['id']) : ''; ?>" autocomplete="off"></td>
          </tr>
          <tr>
            <th scope="row"><label for="client_secret"><?php _e('Client secret', $domain); ?></label></th>
            <td>
              <input name="client_secret" id="client_secret" type="password" class="large-text code" value="" autocomplete="new-password" placeholder="<?php echo ($client && $client['source'] === 'option') ? esc_attr__('Saved. Enter again to change it.', $domain) : ''; ?>">
              <p class="description"><?php _e('Stored on this site only and never shown again.', $domain); ?></p>
            </td>
          </tr>
        </table>
        <?php submit_button(__('Save client', $domain)); ?>
      </form>
    </div>
  <?php endif; ?>

  <?php /* ---------------- Step 1: sign in ---------------- */ ?>
  <?php if ($client && !$connection) : ?>
    <div id="sm-step-account" class="card sm-connect-welcome"><div class="sm-connect-copy">
      <p class="sm-connect-eyebrow"><?php _e('YOUR MEDIA. YOUR CLOUD.', $domain); ?></p><h2 class="sm-connect-headline"><?php _e('A new home for your WordPress media.', $domain); ?></h2>
      <?php if ($hosted && !$auth_url) : ?>
        <div class="notice notice-error inline"><p><?php printf(esc_html__('The sign-in could not be prepared right now (%s).', $domain), esc_html($start_error ?: __('no reply from the sign-in service', $domain))); ?> <a href="<?php echo esc_url(remove_query_arg(array('sm_gc', 'sm_gc_pair', 'sm_gc_rt'))); ?>"><?php _e('Try again', $domain); ?></a></p></div>
      <?php endif; ?>
      <p class="sm-connect-lead"><?php _e('Connect Google Cloud and let WP-Stateless take care of the setup. Your photos, videos and files stay in your own account.', $domain); ?></p>
      <?php if ($auth_url) : ?>
        <form method="post" action="<?php echo esc_url($post); ?>" style="margin:16px 0"<?php if (!$hosted) echo ' target="_blank" rel="noopener"'; ?>>
          <?php wp_nonce_field($connect->nonce_action()); ?>
          <input type="hidden" name="action" value="sm_gc_begin">
          <button type="submit" class="button button-primary button-hero"><img src="<?php echo esc_url(plugins_url('static/images/connect/logo-google.svg', dirname(__DIR__, 2) . '/wp-stateless.php')); ?>" alt="" width="20" height="20"><?php _e('Sign in with Google', $domain); ?></button>
        </form>
      <?php endif; ?>

      <?php if ($hosted) : ?>
        <p class="sm-connect-next"><?php _e('Sign in, confirm your website, then choose where your media lives. We will bring you right back here in the same tab.', $domain); ?></p>
        <p class="sm-connect-next"><?php _e('Setup usually takes about two minutes. Google Cloud requires a billing account. Your storage key goes directly from Google to this site; stateless.ci never receives it.', $domain); ?></p>
        <details style="margin:0 0 12px">
          <summary style="cursor:pointer"><?php _e('Signed in on another device, or shown a code?', $domain); ?></summary>
          <p style="margin:10px 0 6px"><?php _e('Type the code from that page here and the sign-in finishes on this site.', $domain); ?></p>
          <form method="post" action="<?php echo esc_url($post); ?>">
            <?php wp_nonce_field($connect->nonce_action()); ?>
            <input type="hidden" name="action" value="sm_gc_finish">
            <input name="redirect_url" type="text" class="regular-text code" placeholder="ABCD-EFGH" autocomplete="off" spellcheck="false" style="vertical-align:middle">
            <?php submit_button(__('Finish sign-in', $domain), 'secondary', 'submit', false, array('style' => 'vertical-align:middle;margin-left:4px')); ?>
          </form>
        </details>
        <div class="sm-connect-help">
          <p><?php _e('Use your Google Cloud account. You can disconnect anytime.', $domain); ?></p>
          <div class="sm-connect-help-actions">
          <a href="<?php echo esc_url(ud_get_stateless_media()->get_docs_page_url('docs/google-permission-request/')); ?>" target="_blank" rel="noopener"><?php _e('Why Google asks for this access', $domain); ?></a>
          <?php if ($auth_url) : ?>
            <form method="post" action="<?php echo esc_url($post); ?>">
              <?php wp_nonce_field($connect->nonce_action()); ?>
              <input type="hidden" name="action" value="sm_gc_restart">
              <button type="submit" class="button-link"><?php _e('Start over', $domain); ?></button>
            </form>
          <?php endif; ?>
          </div>
        </div>
      <?php else : ?>
        <h3 style="margin-bottom:6px"><?php _e('What happens next', $domain); ?></h3>
        <ol style="margin:0 0 16px 1.4em">
          <li><?php _e('Google asks you to allow WP-Stateless to manage storage in your Google Cloud.', $domain); ?></li>
          <li><?php printf(esc_html__('Google then sends your browser to an address starting with %s that will not load. That is expected.', $domain), '<code>' . esc_html($redirect_uri) . '</code>'); ?></li>
          <li><?php _e('Copy the complete address from the address bar and paste it below.', $domain); ?></li>
        </ol>
        <form method="post" action="<?php echo esc_url($post); ?>">
          <?php wp_nonce_field($connect->nonce_action()); ?>
          <input type="hidden" name="action" value="sm_gc_finish">
          <input name="redirect_url" type="text" class="large-text code" placeholder="<?php echo esc_attr($redirect_uri . '?state=...&code=...&scope=...'); ?>" autocomplete="off" spellcheck="false">
          <?php submit_button(__('Finish sign-in', $domain), 'primary', 'submit', true); ?>
        </form>
        <p class="description"><?php _e('This is the same flow the gcloud command line uses on a machine without a browser. The address only works together with a secret this site generated, and it expires in minutes.', $domain); ?></p>
      <?php endif; ?>
    </div><div class="sm-connect-art" aria-hidden="true"><img src="<?php echo esc_url(plugins_url('static/images/connect/image-woman-with-box.svg', dirname(__DIR__, 2) . '/wp-stateless.php')); ?>" alt=""></div></div>
    <div class="sm-connect-benefits">
      <div><span class="dashicons dashicons-cloud" aria-hidden="true"></span><h3><?php _e('Your Google Cloud', $domain); ?></h3><p><?php _e('Your media stays in a project you control.', $domain); ?></p></div>
      <div><span class="dashicons dashicons-admin-settings" aria-hidden="true"></span><h3><?php _e('We handle the setup', $domain); ?></h3><p><?php _e('Choose your storage. We connect it to WordPress.', $domain); ?></p></div>
      <div><span class="dashicons dashicons-format-image" aria-hidden="true"></span><h3><?php _e('Upload as usual', $domain); ?></h3><p><?php _e('Keep using the Media Library you already know.', $domain); ?></p></div>
    </div>
  <?php endif; ?>

  <?php /* ---------------- Step 2: provision ---------------- */ ?>
  <?php if ($client && $connection) : ?>
    <div class="card" id="sm-step-account" style="max-width:720px">
      <h2 class="title"><?php _e('Connected', $domain); ?></h2>
      <p>
        <?php printf(esc_html__('Signed in as %s.', $domain), '<strong>' . esc_html($connection['email'] ?: __('unknown account', $domain)) . '</strong>'); ?>
        <?php if (!empty($connection['bucket'])) : ?>
          <?php printf(esc_html__('Provisioned bucket %1$s in project %2$s.', $domain), '<code>' . esc_html($connection['bucket']) . '</code>', '<code>' . esc_html($connection['project']) . '</code>'); ?>
        <?php endif; ?>
      </p>
      <?php $unreadable = $connect->connection_unreadable($connection); if ($unreadable) : ?>
        <div class="notice notice-warning inline"><p><?php _e('The stored Google credentials cannot be read, usually because the WordPress authentication salts changed. This site cannot revoke its own access: restore the previous salts, or revoke access under Third-party apps in your Google Account first.', $domain); ?></p></div>
      <?php endif; ?>
      <form method="post" action="<?php echo esc_url($post); ?>" style="display:inline">
        <?php wp_nonce_field($connect->nonce_action()); ?>
        <input type="hidden" name="action" value="sm_gc_disconnect">
        <?php if ($unreadable) : ?>
          <input type="hidden" name="sm_gc_force" value="1">
          <button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js(__('Remove the connection without revoking access? Only do this after revoking it in your Google Account.', $domain)); ?>')"><?php _e('Remove connection without revoking', $domain); ?></button>
        <?php else : ?>
          <button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js(__('Revoke this site\'s access to your Google account? Buckets and keys already created stay in your project.', $domain)); ?>')"><?php _e('Disconnect', $domain); ?></button>
        <?php endif; ?>
      </form>
      <p><a class="button button-primary" href="#sm-step-storage" data-wizard-step="storage"><?php _e('Continue to storage', $domain); ?></a></p>
    </div>

    <?php if ($configured) : ?>
    <div class="card sm-ready-card" id="sm-step-ready"><div>
      <h2><?php _e('Your media is ready for the cloud.', $domain); ?></h2>
      <p><?php echo isset($settings->mode) && $settings->mode === 'backup' ? esc_html__('New uploads will be backed up to Google Cloud while this site continues serving the files.', $domain) : esc_html__('New uploads will be served from Google Cloud using your chosen storage mode.', $domain); ?></p>
      <p><?php printf(esc_html__('This site is configured to use %s. Upload an image to try it out.', $domain), '<strong>' . esc_html($settings->bucket) . '</strong>'); ?></p>
      <p><a class="button button-primary" href="<?php echo esc_url(admin_url('media-new.php')); ?>"><?php _e('Upload an image', $domain); ?></a> <a class="button" href="#sm-step-storage" data-wizard-step="storage"><?php _e('Review storage', $domain); ?></a> <a href="<?php echo esc_url($settings_url); ?>"><?php _e('Stateless Settings', $domain); ?></a></p>
      <p class="description"><?php _e('Already have a media library? Use the synchronization tools in Stateless Settings to copy existing uploads.', $domain); ?> <a href="<?php echo esc_url(ud_get_stateless_media()->get_docs_page_url('docs/synchronization/')); ?>" target="_blank" rel="noopener"><?php _e('Read the synchronization guide', $domain); ?></a></p>
      </div><img src="<?php echo esc_url(plugins_url('static/images/connect/image-woman-machine-learning.svg', dirname(__DIR__, 2) . '/wp-stateless.php')); ?>" alt="" width="280">
    </div>
    <?php endif; ?>
    <div class="card" id="sm-step-storage" style="max-width:720px">
      <h2 class="title"><?php echo $configured ? esc_html__('Create another bucket', $domain) : esc_html__('Choose your media storage', $domain); ?></h2>
      <div class="sm-account-summary"><span><?php printf(esc_html__('Google account: %s', $domain), '<strong>' . esc_html($connection['email']) . '</strong>'); ?></span><a href="#sm-step-account" data-wizard-step="account"><?php _e('Change account', $domain); ?></a></div>
      <?php if ($proj_err) : ?>
        <div class="notice notice-error inline"><p><?php echo esc_html($proj_err); ?></p></div>
      <?php elseif (!empty($proj_note)) : ?>
        <div class="notice notice-warning inline"><p><?php echo esc_html($proj_note); ?></p></div>
      <?php endif; ?>
      <?php $overridden = $connect->storage_blocked(); if ($overridden) : ?>
        <div class="notice notice-warning inline"><p><?php echo esc_html($overridden); ?></p></div>
      <?php else : ?>
      <?php if (!$configured) : ?>
        <?php
        $recommended_locked = $connect->locked_mode();
        $recommended_mode = $requires_stateless ? 'stateless' : ($recommended_locked ? $recommended_locked['mode'] : ($configured && isset($settings->mode) && in_array($settings->mode, array('cdn', 'backup', 'ephemeral', 'stateless'), true) ? $settings->mode : 'cdn'));
        ?>
        <div class="sm-connect-recommended">
          <h3><?php echo $recommended_mode === 'cdn' ? esc_html__('Recommended setup', $domain) : esc_html__('Your setup', $domain); ?></h3>
          <?php if ($recommended_mode === 'cdn') : ?>
            <p><?php _e('We will create a Google Cloud project and a US multi-region bucket, then connect your site in CDN mode. Your media will be served from Google Cloud and a copy will stay on this server.', $domain); ?></p>
          <?php else : ?>
            <p><?php _e('We will create a Google Cloud project and a US multi-region bucket, then connect your site. Review the required storage mode under Advanced before continuing.', $domain); ?></p>
          <?php endif; ?>
          <p class="description"><?php _e('Google Cloud requires a billing account. Nothing is created until you choose Create my storage.', $domain); ?></p>
        </div>
      <?php endif; ?>
      <?php if ($billing_unavailable) : ?>
        <div class="notice notice-warning inline sm-billing-recovery">
          <p><?php _e('Google Cloud requires a billing account before storage can be created. Create one in Google Cloud, then return here and check again.', $domain); ?></p>
          <p>
            <a class="button button-primary" href="https://console.cloud.google.com/billing/create" target="_blank" rel="noopener"><?php _e('Create billing account', $domain); ?></a>
            <a class="button" href="<?php echo esc_url($connect->page_url()); ?>"><?php _e('Check again', $domain); ?></a>
          </p>
        </div>
      <?php endif; ?>
      <?php if ($billing_recovery) : ?>
        <div class="notice notice-warning inline sm-billing-recovery">
          <p><?php _e('Google Cloud billing needs to be enabled for this project before its bucket can be created. Your storage choices are saved.', $domain); ?></p>
          <a class="button" href="https://console.cloud.google.com/billing" target="_blank" rel="noopener"><?php _e('Enable billing in Google Cloud', $domain); ?></a>
          <form method="post" action="<?php echo esc_url($post); ?>" style="display:inline-block;margin-left:6px">
            <?php wp_nonce_field($connect->nonce_action()); ?>
            <input type="hidden" name="action" value="sm_gc_billing_recheck">
            <?php submit_button(__('Check again', $domain), 'secondary', 'submit', false); ?>
          </form>
        </div>
      <?php endif; ?>
      <form method="post" action="<?php echo esc_url($post); ?>" id="sm-gc-provision">
        <?php wp_nonce_field($connect->nonce_action()); ?>
        <input type="hidden" name="action" value="sm_gc_provision">
        <details class="sm-connect-advanced" <?php if ($configured || $error || $default_project !== '__new__' || $default_bucket !== '') echo 'open'; ?>>
          <summary><?php _e('Advanced storage options', $domain); ?></summary>
          <p class="description"><?php _e('Choose an existing project or bucket, another location, or a different storage mode.', $domain); ?></p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="sm_gc_project"><?php _e('Google Cloud project', $domain); ?></label></th>
            <td>
              <input type="search" class="sm-picker-search regular-text" data-filter="sm_gc_project" aria-label="Search projects" placeholder="Search projects by name or ID">
              <select name="project" id="sm_gc_project" class="regular-text" required>
                <option value="" <?php selected($default_project === '' && !empty($projects)); ?>><?php _e('Choose a project', $domain); ?></option>
                <?php foreach ($projects as $p) : ?>
                  <option value="<?php echo esc_attr($p['projectId']); ?>" <?php selected($default_project !== '' && $default_project === $p['projectId']); ?>><?php echo esc_html($p['name']); ?> (<?php echo esc_html($p['projectId']); ?>)</option>
                <?php endforeach; ?>
                <option value="__new__" <?php selected($default_project, '__new__'); ?>><?php _e('Create a new project', $domain); ?></option>
              </select>
              <p class="description"><?php echo count($projects) ? sprintf(esc_html__('%d projects visible to this account.', $domain), count($projects)) : esc_html__('No projects found in this account.', $domain); ?></p>
            </td>
          </tr>
          <tr class="sm-gc-new-project">
            <th scope="row"><label for="new_project_name"><?php _e('New project name', $domain); ?></label></th>
            <td><input name="new_project_name" id="new_project_name" type="text" minlength="4" maxlength="30" pattern="[a-zA-Z0-9\x20\x21\x22\x27\x2d]{4,30}" title="<?php esc_attr_e('Use 4 to 30 letters, numbers, spaces, hyphens, quotes or exclamation marks. Periods are not allowed.', $domain); ?>" class="regular-text" value="<?php echo esc_attr($suggested_project); ?>"></td>
          </tr>
          <tr class="sm-gc-new-project">
            <th scope="row"><label for="billing_account"><?php _e('Billing account', $domain); ?></label></th>
            <td>
              <?php if ($billing) : ?>
                <input type="search" class="sm-picker-search regular-text" data-filter="billing_account" aria-label="Search billing accounts" placeholder="Search billing accounts">
                <select name="billing_account" id="billing_account" class="regular-text">
                  <?php foreach ($billing as $b) : ?>
                    <option value="<?php echo esc_attr($b['name']); ?>"><?php echo esc_html($b['displayName']); ?></option>
                  <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('A new project needs a billing account before it can hold a bucket.', $domain); ?></p>
              <?php else : ?>
                <input type="hidden" name="billing_account" value="">
                <?php if (!empty($billing_err)) : ?><p class="description"><?php echo esc_html($billing_err); ?></p><?php endif; ?>
                <p class="description"><?php printf(wp_kses_post(__('No open billing accounts could be loaded for this Google account. Google requires one for storage. <a href="%s" target="_blank" rel="noopener">Create one in the Google Cloud console</a>, then return here.', $domain)), 'https://console.cloud.google.com/billing/create'); ?></p>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php _e('Bucket', $domain); ?></th>
            <td>
              <fieldset class="sm-bucket-choices">
                <legend class="screen-reader-text"><?php _e('Choose a bucket', $domain); ?></legend>
                <label><input type="radio" name="bucket_choice" value="new" <?php checked($default_bucket === ''); ?>> <?php _e('Create a new bucket', $domain); ?></label>
                <label><input type="radio" name="bucket_choice" value="existing" <?php checked($default_bucket !== ''); ?>> <?php _e('Use an existing bucket', $domain); ?></label>
              </fieldset>
              <div id="sm-bucket-picker" hidden>
                <input type="search" class="regular-text sm-picker-search" id="sm-bucket-search" aria-label="Search existing buckets" placeholder="Search existing buckets">
                <select id="sm-existing-bucket" class="regular-text" aria-label="Existing buckets"><option value="">Choose a bucket</option></select>
                <p id="sm-bucket-status" class="description" role="status"></p>
                <button type="button" class="button" id="sm-bucket-more" hidden>Load more buckets</button>
                <button type="button" class="button" id="sm-bucket-retry" hidden>Try again</button>
                <p id="sm-bucket-location" class="description"></p>
              </div>
              <label for="sm-bucket-name" class="sm-field-label"><?php _e('Bucket name', $domain); ?></label>
              <input id="sm-bucket-name" name="bucket_name" type="text" required class="regular-text code" value="<?php echo esc_attr($default_bucket !== '' ? $default_bucket : $suggested_bucket); ?>">
              <p class="description" id="sm-bucket-help">Names must be globally unique. Use lowercase letters, numbers, dashes or underscores.</p>
            </td>
          </tr>
          <tr class="sm-gc-new-bucket">
            <th scope="row"><label for="location"><?php _e('Location', $domain); ?></label></th>
            <td>
              <p class="description sm-location-help">Choose a location close to your visitors. A region stores data in one area; dual-region and multi-region storage spread it across areas.</p>
              <fieldset class="sm-location-types" aria-label="Location type">
                <?php foreach (array('multi' => array('Multi-region', 'Across a broad geographic area.'), 'dual' => array('Dual-region', 'Across two specific regions.'), 'single' => array('Single region', 'Within one geographic region.')) as $type => $details) : ?>
                <label><input type="radio" name="location_type" value="<?php echo esc_attr($type); ?>" <?php checked($type, 'multi'); ?>><span><strong><?php echo esc_html__($details[0], $domain); ?></strong><small><?php echo esc_html__($details[1], $domain); ?></small></span></label>
                <?php endforeach; ?>
              </fieldset>
              <select name="location" id="location">
                <optgroup data-type="multi" label="<?php esc_attr_e('Multi-region', $domain); ?>">
                  <option value="US">United States (US)</option>
                  <option value="EU">European Union (EU)</option>
                  <option value="ASIA">Asia (ASIA)</option>
                </optgroup>
                <optgroup data-type="dual" label="Dual-region"><option value="NAM4">Iowa and South Carolina (NAM4)</option><option value="EUR4">Netherlands and Finland (EUR4)</option></optgroup>
                <optgroup data-type="single" label="<?php esc_attr_e('Single region', $domain); ?>">
                  <?php foreach (array('northamerica-northeast1' => 'Montreal', 'us-central1' => 'Iowa', 'us-east1' => 'South Carolina', 'us-east4' => 'Northern Virginia', 'us-west1' => 'Oregon', 'us-west2' => 'Los Angeles', 'us-west3' => 'Salt Lake City', 'us-west4' => 'Las Vegas', 'southamerica-east1' => 'São Paulo', 'europe-north1' => 'Finland', 'europe-west1' => 'Belgium', 'europe-west2' => 'London', 'europe-west3' => 'Frankfurt', 'europe-west4' => 'Netherlands', 'europe-west6' => 'Zurich', 'asia-east1' => 'Taiwan', 'asia-east2' => 'Hong Kong', 'asia-northeast1' => 'Tokyo', 'asia-northeast2' => 'Osaka', 'asia-northeast3' => 'Seoul', 'asia-south1' => 'Mumbai', 'asia-southeast1' => 'Singapore', 'asia-southeast2' => 'Jakarta', 'australia-southeast1' => 'Sydney') as $r => $place) : ?>
                    <option value="<?php echo esc_attr($r); ?>"><?php echo esc_html($place . ' (' . $r . ')'); ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row" id="sm-mode-label"><?php _e('Media storage', $domain); ?></th>
            <td>
              <?php
              $mode_options = array(
                'cdn' => array(__('CDN', $domain), __('Serve media from Google Cloud and keep a copy on this server. A good starting point for most sites.', $domain)),
                'ephemeral' => array(__('Ephemeral', $domain), __('Serve media from Google Cloud. Use this server temporarily to process uploads, then remove the local files.', $domain)),
                'stateless' => array(__('Stateless', $domain), __('Store and serve media entirely in Google Cloud, without local storage. Check that your other plugins support this mode.', $domain)),
                'backup' => array(__('Backup', $domain), __('Keep serving media from this server and save an additional copy in Google Cloud.', $domain)),
              );
              $locked_mode = $connect->locked_mode();
              if ($locked_mode) : ?>
              <p><strong><?php echo esc_html($mode_options[$locked_mode['mode']][0]); ?></strong><br><span class="description"><?php echo esc_html($mode_options[$locked_mode['mode']][1]); ?></span></p>
              <p class="description"><?php printf(esc_html__('The storage mode is set by %s and applies to this site. Change it there to use a different mode.', $domain), esc_html($locked_mode['source'])); ?></p>
              <input type="hidden" name="mode" value="<?php echo esc_attr($locked_mode['mode']); ?>">
              <?php else : ?>
              <?php if ($requires_stateless) : ?><p class="description"><?php _e('This environment requires Stateless mode because it uses App Engine or cannot write to the uploads directory.', $domain); ?></p><?php endif; ?>
              <fieldset class="sm-mode-options" aria-labelledby="sm-mode-label">
                <?php
                $selected_mode = $configured && isset($settings->mode) && isset($mode_options[$settings->mode]) ? $settings->mode : 'cdn';
                if ($requires_stateless) $selected_mode = 'stateless';
                foreach ($mode_options as $value => $option) : ?>
                  <label class="sm-mode-option">
                    <input type="radio" name="mode" value="<?php echo esc_attr($value); ?>" <?php checked($selected_mode, $value); ?> <?php disabled($requires_stateless && $value !== 'stateless'); ?>>
                    <span><strong><?php echo esc_html($option[0]); ?><?php if ($value === 'cdn') : ?> <small><?php _e('Recommended', $domain); ?></small><?php endif; ?></strong><span><?php echo esc_html($option[1]); ?></span></span>
                  </label>
                <?php endforeach; ?>
              </fieldset>
              <p class="description"><?php _e('You can change this later in Stateless Settings.', $domain); ?> <a href="<?php echo esc_url(ud_get_stateless_media()->get_docs_page_url('docs/modes/')); ?>" target="_blank" rel="noopener"><?php _e('Compare storage modes', $domain); ?></a></p>
              <?php endif; ?>
            </td>
          </tr>
        </table>
        </details>
        <p class="description"><?php _e('Usually takes 15 to 60 seconds. Keep this page open while Google creates and verifies your storage.', $domain); ?></p>
        <?php submit_button(__('Create my storage', $domain), 'primary', 'submit', true, array('id' => 'sm-gc-go')); ?>
      </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<script>
(function () {
  var root = document.querySelector('.sm-connect');
  if (!root) return;
  function show(step, focus) {
    var target = document.getElementById('sm-step-' + step);
    if (!target) return;
    ['account', 'storage', 'ready'].forEach(function (name) {
      var panel = document.getElementById('sm-step-' + name);
      if (panel) panel.hidden = name !== step;
    });
    root.querySelectorAll('.sm-connect-steps [data-step]').forEach(function (item) {
      var active = item.dataset.step === step;
      item.classList.toggle('is-current', active);
      if (active) item.setAttribute('aria-current', 'step');
      else item.removeAttribute('aria-current');
    });
    if (focus) { target.setAttribute('tabindex', '-1'); target.focus(); }
  }
  root.querySelectorAll('[data-wizard-step]').forEach(function (link) {
    link.addEventListener('click', function (event) { event.preventDefault(); show(link.dataset.wizardStep, true); });
  });
  show(<?php echo wp_json_encode(!$connection ? 'account' : ((($error || $msg === 'billing-ready') && !empty($draft)) ? 'storage' : ($configured ? 'ready' : 'storage'))); ?>, false);
})();
</script>
