
<div class="metabox-holder">
  <div class="postbox">
	  <h2 class="hndle"><?php _e('Data Optimization', ud_get_stateless_media()->domain); ?></h2>
    <div class="hndle-notice">
      <p><?php _e('Please consider the following:', ud_get_stateless_media()->domain); ?></p>
      <ul>
        <li><?php _e('Before running any optimizations please make a backup copy of your database', ud_get_stateless_media()->domain); ?></li>
        <li><?php _e('Please try not to add/update/delete any media while optimization is in progress', ud_get_stateless_media()->domain); ?></li>
      </ul>
    </div>

    <div class="inside">
      <div class="main">
        <?php foreach ($migrations as $id => $migration ) : ?>
          <div class="migration <?php echo $migration->classes; ?>" data-id="<?php echo $id; ?>">
            <p class="title">
              <strong><?php echo $migration->description; ?></strong> 
              <span class="actions">
                <a href="#" class="button button-small button-primary start" data-action="start"><?php _e('Optimize', ud_get_stateless_media()->domain); ?></a>
                <a href="#" class="button button-small button-secondary pause" data-action="pause"><?php _e('Pause', ud_get_stateless_media()->domain); ?></a>
                <a href="#" class="button button-small button-primary resume" data-action="resume"><?php _e('Resume', ud_get_stateless_media()->domain); ?></a>
              </span>
            </p>

            <div class="progress-wrap">
              <p class="description"><?php echo $migration->message; ?></p>
  
              <div class="progress">
                <div class="bar"></div>
                <span class="percent"></span>
            </div>
            </div>
          </div>
        <?php endforeach;?>
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

<div id="stateless-migration-confirm" title="Migration">
  <p><?php _e('You are about to start the <strong>Migration</strong>.', ud_get_stateless_media()->domain); ?></p>
  <p><?php _e('Please make a backup copy of your database and try not to upload, change or delete your media while the process continues.', ud_get_stateless_media()->domain); ?></p>
  <p>
    <?php _e('Notify when process finishes:', ud_get_stateless_media()->domain); ?>
    
    <label for="stateless-migration-email-default">
      <input type="radio" name="email-notification" id="stateless-migration-email-default" value="<?php echo $default_email;?>" checked="checked" /> 
      <?php echo $default_email; ?>
    </label>

    <label for="stateless-migration-email-user">
      <input type="radio" name="email-notification" id="stateless-migration-email-user" value="<?php echo $current_email?>" /> 
      <?php printf( __('Current User (%s)', ud_get_stateless_media()->domain), $current_email ); ?>
    </label>
  </p>
  <p><?php _e('Are you sure you want to continue?', ud_get_stateless_media()->domain); ?></p>
</div>
