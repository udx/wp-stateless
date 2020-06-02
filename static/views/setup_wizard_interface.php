<?php
  $auth_token = \wpCloud\StatelessMedia\Utility::generate_wizard_auth_token();
  $api_root = get_rest_url();
?>

<script>
  // TODO: change URL and params if needed
  (function($) {
    $(document).ready(function(){
      $('#consoleStatelessIframeWrapper')
        .html('<iframe id="console-stateless-wizard" src="//udx.github.io/console.stateless.ci?auth_token=<?php echo $auth_token ?>&api_root=<?php echo $api_root ?>" />')
    })
  })(jQuery)
</script>

<div id="consoleStatelessIframeWrapper"></div>