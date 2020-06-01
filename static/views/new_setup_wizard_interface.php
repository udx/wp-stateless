<?php
  $auth_token = \wpCloud\StatelessMedia\Utility::generate_wizard_auth_token();
  $api_root = get_rest_url();
?>

<style>
  #console-stateless-wizard {
    position: absolute;
    top:0;
    left:0;
    bottom:0;
    right:0;
    height: 100vh;
    width: 100%;
  }
</style>

<iframe id="console-stateless-wizard" src="//udx.github.io/console.stateless.ci?auth_token=<?php echo $auth_token ?>&api_root=<?php echo $api_root ?>" />
