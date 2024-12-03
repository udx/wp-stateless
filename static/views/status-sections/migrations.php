
<div class="metabox-holder migrations-wrap">
  <div class="postbox">
	  <h2 class="hndle"><?php _e('Data Optimization', ud_get_stateless_media()->domain); ?></h2>
    <div class="hndle-notice">
      <p><?php _e('Beginning with WP-Stateless 4.0, the method used to store and access plugin data has improved significantly. We\'ve detected that your data still needs to be updated using this new method.', ud_get_stateless_media()->domain); ?></p>
      <p><?php _e('Start your data optimization process below to experience a faster and more performant WP-Stateless. Before you begin, please consider the following:', ud_get_stateless_media()->domain); ?></p>
      <ul>
        <li><?php _e('Create a backup copy of your WordPress database.', ud_get_stateless_media()->domain); ?></li>
        <li><?php _e('Do not upload, edit, or delete media or files while the optimization process is underway.', ud_get_stateless_media()->domain); ?></li>
        <li><?php _e('Perform this update during a low period in your website traffic.', ud_get_stateless_media()->domain); ?></li>
      </ul>
    </div>

    <div id="migration-action" class="inside <?php echo $migration['classes']; ?>" data-id="<?php echo $migration_id; ?>" data-queue="<?php echo implode(':', $migration_ids); ?>">
      <div class="main">
        <div class="actions">
          <a href="#" class="button button-primary start" data-action="start"><?php _e('Start Data Optimization', ud_get_stateless_media()->domain); ?></a>
          <a href="#" class="button button-secondary pause" data-action="pause"><?php _e('Pause Data Optimization', ud_get_stateless_media()->domain); ?></a>
          <a href="#" class="button button-primary resume" data-action="resume"><?php _e('Resume Data Optimization', ud_get_stateless_media()->domain); ?></a>
        </div>

        <div class="progress-wrap">
          <div class="progress"><div class="bar"></div><span class="percent"></span></div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php
  $default_email = ud_get_stateless_media()->get_notification_email(); 
  $default_email = empty($default_email) ? __('Disabled', ud_get_stateless_media()->domain) : $default_email;

  $current_user = wp_get_current_user();
  $current_email = $current_user->user_email ?? '';
?>

<div id="stateless-migration-confirm" title="<?php _e('Optimize WP-Stateless Data', ud_get_stateless_media()->domain); ?>">
  <p><?php _e('You are about to optimize your WP-Stateless data. This process makes changes to your WordPress database.', ud_get_stateless_media()->domain); ?></p>
  <p><?php _e('Create a backup copy of your database, and do not upload, edit, or delete media or files during the optimization process.', ud_get_stateless_media()->domain); ?></p>
  <p>
    <?php _e('WP-Stateless will send an email notification when the process is complete. Who would you like to receive this notification?', ud_get_stateless_media()->domain); ?>
    
    <label for="stateless-migration-email-default">
      <input type="radio" name="email-notification" id="stateless-migration-email-default" value="<?php echo $default_email;?>" checked="checked" /> 
      <?php printf( __('Administration Email Address (%s)', ud_get_stateless_media()->domain), $default_email ); ?>
    </label>

    <label for="stateless-migration-email-user">
      <input type="radio" name="email-notification" id="stateless-migration-email-user" value="<?php echo $current_email?>" /> 
      <?php printf( __('Current User (%s)', ud_get_stateless_media()->domain), $current_email ); ?>
    </label>
  </p>
  <p><?php _e('Are you ready to continue?', ud_get_stateless_media()->domain); ?></p>
</div>
