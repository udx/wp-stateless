<?php
  $consoleUrl = 'https://console.stateless.ci';
  $auth_token = \wpCloud\StatelessMedia\Utility::generate_wizard_auth_token();
  $api_root = get_rest_url();
  $is_network = intval(is_network_admin());
  $admin_url = admin_url();
?>

<script>
  (function($) {
    $(document).ready(function(){
      $('#consoleStatelessIframeWrapper')
        .html('<iframe id="console-stateless-wizard" src="<?php esc_html_e($consoleUrl) ?>?wp_nonce=<?php esc_html_e($auth_token) ?>&api_root=<?php esc_html_e($api_root) ?>&is_network=<?php esc_html_e($is_network) ?>&admin_url=<?php esc_html_e($admin_url) ?>" />')
    })
  })(jQuery)
</script>

<div id="consoleStatelessIframeWrapper"></div>
<style>#wpfooter{display:none!important}</style>