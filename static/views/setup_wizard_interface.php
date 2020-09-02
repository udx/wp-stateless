<?php
  $consoleUrl = 'https://console.stateless.ci';
  $auth_token = \wpCloud\StatelessMedia\Utility::generate_wizard_auth_token();
  $api_root = get_rest_url();
?>

<script>
  (function($) {
    $(document).ready(function(){
      $('#consoleStatelessIframeWrapper')
        .html('<iframe id="console-stateless-wizard" src="<?php echo $consoleUrl ?>/#/?wp_nonce=<?php echo $auth_token ?>&api_root=<?php echo $api_root ?>" />')
    })
  })(jQuery)
</script>

<div id="consoleStatelessIframeWrapper"></div>
<style>#wpfooter{display:none!important}</style>