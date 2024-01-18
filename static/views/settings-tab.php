

<form method="post" action="">
  <input type="hidden" name="action" value="stateless_settings">
  <?php wp_nonce_field('wp-stateless-settings', '_smnonce'); ?>
  <table class="form-table">
    <tbody>

      <?php if (is_network_admin()) : ?>
        <?php require_once( dirname(__FILE__) . '/settings-sections/network.php'); ?>
      <?php endif; ?>

      <?php require_once( dirname(__FILE__) . '/settings-sections/general.php'); ?>
      <?php require_once( dirname(__FILE__) . '/settings-sections/google-cloud-storage.php'); ?>
      <?php require_once( dirname(__FILE__) . '/settings-sections/file-url.php'); ?>
      
    </tbody>
  </table>

  <?php submit_button(null, 'primary', 'submit', true, array('id' => 'save-settings')); ?>
</form>
