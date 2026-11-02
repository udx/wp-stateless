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
$post    = admin_url('admin-post.php');
$configured = !empty($settings->key_json) && !empty($settings->bucket);
$redirect_uri  = $client ? $client['redirect_uri'] : wpCloud\StatelessMedia\GoogleConnect::LOOPBACK_URI;
$hosted        = $client && $client['source'] === 'broker';
$broker_host   = wp_parse_url(wpCloud\StatelessMedia\GoogleConnect::broker_url(), PHP_URL_HOST);
$site_host     = wp_parse_url(home_url(), PHP_URL_HOST);
$start_error   = wpCloud\StatelessMedia\GoogleConnect::instance()->start_error;
?>
<div class="wrap">
  <h1><?php _e('Stateless Connect', $domain); ?></h1>
  <p class="description" style="max-width:720px">
    <?php _e('Connect this site to your own Google Cloud in two steps. WP-Stateless creates a storage bucket for your media, plus a key that can reach only that bucket. Everything lives in your Google Cloud project and on this site.', $domain); ?>
  </p>

  <?php if ($error) : ?>
    <div class="notice notice-error"><p><?php echo wp_kses_post($error); ?></p></div>
  <?php elseif ($msg === 'client-saved') : ?>
    <div class="notice notice-success"><p><?php _e('OAuth client saved.', $domain); ?></p></div>
  <?php elseif ($msg === 'connected' && $connection) : ?>
    <div class="notice notice-success"><p><?php _e('Google account connected.', $domain); ?></p></div>
  <?php elseif ($msg === 'disconnected') : ?>
    <div class="notice notice-success"><p><?php _e('Google account disconnected and access revoked.', $domain); ?></p></div>
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
        <?php wp_nonce_field(wpCloud\StatelessMedia\GoogleConnect::NONCE); ?>
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
    <div class="card" style="max-width:720px">
      <h2 class="title"><?php _e('Step 1 of 2: connect your Google account', $domain); ?></h2>
      <?php if ($hosted && !$auth_url) : ?>
        <div class="notice notice-error inline"><p><?php printf(esc_html__('The sign-in could not be prepared right now (%s).', $domain), esc_html($start_error ?: __('no reply from the sign-in service', $domain))); ?> <a href="<?php echo esc_url(remove_query_arg(array('sm_gc', 'sm_gc_pair', 'sm_gc_rt'))); ?>"><?php _e('Try again', $domain); ?></a></p></div>
      <?php endif; ?>
      <p><?php _e('Use the Google account that owns your Google Cloud project, or the one you want a new project created in.', $domain); ?></p>
      <?php if ($auth_url) : ?>
        <p style="margin:16px 0">
          <a class="button button-primary button-hero" target="_blank" rel="noopener" href="<?php echo esc_url($auth_url); ?>"><?php _e('Sign in with Google', $domain); ?></a>
        </p>
      <?php endif; ?>

      <?php if ($hosted) : ?>
        <h3 style="margin-bottom:6px"><?php _e('What happens next', $domain); ?></h3>
        <ol style="margin:0 0 16px 1.4em">
          <li><?php _e('Google asks you to allow WP-Stateless to manage storage in your Google Cloud.', $domain); ?></li>
          <li><?php printf(esc_html__('%1$s asks you to confirm that the site being connected is %2$s. Click Connect.', $domain), '<strong>' . esc_html($broker_host) . '</strong>', '<strong>' . esc_html($site_host) . '</strong>'); ?></li>
          <li><?php _e('You land back on this page, signed in, ready to pick a project and create the bucket.', $domain); ?></li>
        </ol>
        <details style="margin:0 0 12px">
          <summary style="cursor:pointer"><?php _e('Signed in on another device, or shown a code?', $domain); ?></summary>
          <p style="margin:10px 0 6px"><?php _e('Type the code from that page here and the sign-in finishes on this site.', $domain); ?></p>
          <form method="post" action="<?php echo esc_url($post); ?>">
            <?php wp_nonce_field(wpCloud\StatelessMedia\GoogleConnect::NONCE); ?>
            <input type="hidden" name="action" value="sm_gc_finish">
            <input name="redirect_url" type="text" class="regular-text code" placeholder="ABCD-EFGH" autocomplete="off" spellcheck="false" style="vertical-align:middle">
            <?php submit_button(__('Finish sign-in', $domain), 'secondary', 'submit', false, array('style' => 'vertical-align:middle;margin-left:4px')); ?>
          </form>
        </details>
        <p class="description" style="display:flex;justify-content:space-between;gap:16px;align-items:baseline">
          <span><?php printf(esc_html__('%s hands the sign-in to this site and keeps nothing afterwards. You can revoke access any time from your Google account.', $domain), esc_html($broker_host)); ?></span>
          <?php if ($auth_url) : ?>
            <form method="post" action="<?php echo esc_url($post); ?>" style="display:inline;white-space:nowrap">
              <?php wp_nonce_field(wpCloud\StatelessMedia\GoogleConnect::NONCE); ?>
              <input type="hidden" name="action" value="sm_gc_restart">
              <button type="submit" class="button-link"><?php _e('Start over', $domain); ?></button>
            </form>
          <?php endif; ?>
        </p>
      <?php else : ?>
        <h3 style="margin-bottom:6px"><?php _e('What happens next', $domain); ?></h3>
        <ol style="margin:0 0 16px 1.4em">
          <li><?php _e('Google asks you to allow WP-Stateless to manage storage in your Google Cloud.', $domain); ?></li>
          <li><?php printf(esc_html__('Google then sends your browser to an address starting with %s that will not load. That is expected.', $domain), '<code>' . esc_html($redirect_uri) . '</code>'); ?></li>
          <li><?php _e('Copy the complete address from the address bar and paste it below.', $domain); ?></li>
        </ol>
        <form method="post" action="<?php echo esc_url($post); ?>">
          <?php wp_nonce_field(wpCloud\StatelessMedia\GoogleConnect::NONCE); ?>
          <input type="hidden" name="action" value="sm_gc_finish">
          <input name="redirect_url" type="text" class="large-text code" placeholder="<?php echo esc_attr($redirect_uri . '?state=...&code=...&scope=...'); ?>" autocomplete="off" spellcheck="false">
          <?php submit_button(__('Finish sign-in', $domain), 'primary', 'submit', true); ?>
        </form>
        <p class="description"><?php _e('This is the same flow the gcloud command line uses on a machine without a browser. The address only works together with a secret this site generated, and it expires in minutes.', $domain); ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php /* ---------------- Step 2: provision ---------------- */ ?>
  <?php if ($client && $connection) : ?>
    <div class="card" style="max-width:720px">
      <h2 class="title"><?php _e('Connected', $domain); ?></h2>
      <p>
        <?php printf(esc_html__('Signed in as %s.', $domain), '<strong>' . esc_html($connection['email'] ?: __('unknown account', $domain)) . '</strong>'); ?>
        <?php if (!empty($connection['bucket'])) : ?>
          <?php printf(esc_html__('Provisioned bucket %1$s in project %2$s.', $domain), '<code>' . esc_html($connection['bucket']) . '</code>', '<code>' . esc_html($connection['project']) . '</code>'); ?>
        <?php endif; ?>
      </p>
      <form method="post" action="<?php echo esc_url($post); ?>" style="display:inline">
        <?php wp_nonce_field(wpCloud\StatelessMedia\GoogleConnect::NONCE); ?>
        <input type="hidden" name="action" value="sm_gc_disconnect">
        <button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js(__('Revoke this site\'s access to your Google account? Buckets and keys already created stay in your project.', $domain)); ?>')"><?php _e('Disconnect', $domain); ?></button>
      </form>
    </div>

    <div class="card" style="max-width:720px">
      <h2 class="title"><?php echo $configured ? esc_html__('Create another bucket', $domain) : esc_html__('Step 2 of 2: create the bucket', $domain); ?></h2>
      <?php if ($proj_err) : ?>
        <div class="notice notice-error inline"><p><?php echo esc_html($proj_err); ?></p></div>
      <?php endif; ?>
      <form method="post" action="<?php echo esc_url($post); ?>" id="sm-gc-provision">
        <?php wp_nonce_field(wpCloud\StatelessMedia\GoogleConnect::NONCE); ?>
        <input type="hidden" name="action" value="sm_gc_provision">
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="sm_gc_project"><?php _e('Google Cloud project', $domain); ?></label></th>
            <td>
              <select name="project" id="sm_gc_project" class="regular-text">
                <?php foreach ($projects as $p) : ?>
                  <option value="<?php echo esc_attr($p['projectId']); ?>" <?php selected($default_project !== '' && $default_project === $p['projectId']); ?>><?php echo esc_html($p['name']); ?> (<?php echo esc_html($p['projectId']); ?>)</option>
                <?php endforeach; ?>
                <option value="__new__" <?php selected(empty($projects)); ?>><?php _e('Create a new project', $domain); ?></option>
              </select>
              <p class="description"><?php echo count($projects) ? sprintf(esc_html__('%d projects visible to this account.', $domain), count($projects)) : esc_html__('No projects found in this account.', $domain); ?></p>
            </td>
          </tr>
          <tr class="sm-gc-new-project">
            <th scope="row"><label for="new_project_name"><?php _e('New project name', $domain); ?></label></th>
            <td><input name="new_project_name" id="new_project_name" type="text" class="regular-text" value="<?php echo esc_attr(get_bloginfo('name')); ?>"></td>
          </tr>
          <tr class="sm-gc-new-project">
            <th scope="row"><label for="billing_account"><?php _e('Billing account', $domain); ?></label></th>
            <td>
              <?php if ($billing) : ?>
                <select name="billing_account" id="billing_account" class="regular-text">
                  <?php foreach ($billing as $b) : ?>
                    <option value="<?php echo esc_attr($b['name']); ?>"><?php echo esc_html($b['displayName']); ?></option>
                  <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('A new project needs a billing account before it can hold a bucket.', $domain); ?></p>
              <?php else : ?>
                <input type="hidden" name="billing_account" value="">
                <p class="description"><?php printf(wp_kses_post(__('This Google account has no open billing account. Google requires one for storage. <a href="%s" target="_blank" rel="noopener">Create one in the Google Cloud console</a>, then return here.', $domain)), 'https://console.cloud.google.com/billing/create'); ?></p>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php _e('Bucket', $domain); ?></th>
            <td>
              <fieldset>
                <label><input type="radio" name="bucket_choice" value="new" <?php checked($default_bucket === ''); ?>> <?php _e('Create a new bucket named', $domain); ?></label>
                <input name="bucket_name" type="text" class="regular-text code" value="<?php echo esc_attr($default_bucket !== '' ? $default_bucket : $suggested_bucket); ?>" style="margin-left:4px">
                <p class="description" style="margin-top:4px"><?php _e('Or type the name of a bucket that already exists in that project and choose:', $domain); ?></p>
                <label><input type="radio" name="bucket_choice" value="existing" <?php checked($default_bucket !== ''); ?>> <?php _e('Use the existing bucket with that name', $domain); ?></label>
                <?php if ($default_bucket !== '') : ?><p class="description" style="margin-top:4px"><?php printf(esc_html__('Preselected from this site\'s last setup: %s. Change either field to use something else.', $domain), '<code>' . esc_html($default_bucket) . '</code>'); ?></p><?php endif; ?>
              </fieldset>
            </td>
          </tr>
          <tr class="sm-gc-new-bucket">
            <th scope="row"><label for="location"><?php _e('Location', $domain); ?></label></th>
            <td>
              <select name="location" id="location">
                <optgroup label="<?php esc_attr_e('Multi-region', $domain); ?>">
                  <option value="US">US</option>
                  <option value="EU">EU</option>
                  <option value="ASIA">ASIA</option>
                </optgroup>
                <optgroup label="<?php esc_attr_e('Single region', $domain); ?>">
                  <?php foreach (array('us-central1', 'us-east1', 'us-east4', 'us-west1', 'northamerica-northeast1', 'southamerica-east1', 'europe-west1', 'europe-west2', 'europe-west3', 'europe-north1', 'asia-east1', 'asia-northeast1', 'asia-southeast1', 'asia-south1', 'australia-southeast1') as $r) : ?>
                    <option value="<?php echo esc_attr($r); ?>"><?php echo esc_html($r); ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="mode"><?php _e('Mode', $domain); ?></label></th>
            <td>
              <select name="mode" id="mode">
                <option value="cdn" <?php selected(!isset($settings->mode) || in_array($settings->mode, array('disabled', 'cdn'), true)); ?>>CDN</option>
                <option value="ephemeral" <?php selected(isset($settings->mode) && $settings->mode === 'ephemeral'); ?>>Ephemeral</option>
                <option value="stateless" <?php selected(isset($settings->mode) && $settings->mode === 'stateless'); ?>>Stateless</option>
                <option value="backup" <?php selected(isset($settings->mode) && $settings->mode === 'backup'); ?>>Backup</option>
              </select>
              <p class="description"><?php _e('CDN copies media to the bucket and serves it from there while keeping local files. You can change this later in Stateless Settings.', $domain); ?></p>
            </td>
          </tr>
        </table>
        <p class="description"><?php _e('Takes 15 to 60 seconds. The page will wait.', $domain); ?></p>
        <?php submit_button(__('Create bucket and connect this site', $domain), 'primary', 'submit', true, array('id' => 'sm-gc-go')); ?>
      </form>
    </div>
    <script>
      (function () {
        var form = document.getElementById('sm-gc-provision');
        if (!form) return;
        var project = document.getElementById('sm_gc_project');
        var rows = form.querySelectorAll('.sm-gc-new-project');
        var bucketRows = form.querySelectorAll('.sm-gc-new-bucket');
        var choice = form.querySelectorAll('input[name="bucket_choice"]');
        function sync() {
          var isNew = project.value === '__new__';
          rows.forEach(function (r) { r.style.display = isNew ? '' : 'none'; });
          var newBucket = form.querySelector('input[name="bucket_choice"]:checked').value === 'new';
          bucketRows.forEach(function (r) { r.style.display = newBucket ? '' : 'none'; });
        }
        project.addEventListener('change', sync);
        choice.forEach(function (c) { c.addEventListener('change', sync); });
        form.addEventListener('submit', function () {
          var go = document.getElementById('sm-gc-go');
          go.value = '<?php echo esc_js(__('Working, please wait', $domain)); ?>';
          go.classList.add('disabled');
        });
        sync();
      })();
    </script>
  <?php endif; ?>
</div>
