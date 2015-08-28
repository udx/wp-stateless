/**
 * UsabilityDynamics Utility
 *
 * @version 0.2.3
 * @returns {Object}
 */
define( 'udx.utility.facebook.like', function( require, exports, module ) {
  console.log( module.id, 'loaded' );

  var js;
  var fjs = document.getElementsByTagName( 'script' )[0];

  if ( document.getElementById( 'facebook-jssdk' ) ) {
    return;
  }

  js = document.createElement( 'script' );
  js.id = 'facebook-jssdk';
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=373515126019844";
  fjs.parentNode.insertBefore( js, fjs );

});

