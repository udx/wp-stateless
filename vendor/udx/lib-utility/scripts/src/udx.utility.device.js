/**
 * Devide State Detection
 *
 * @version 0.2.3
 * @returns {Object}
 */
define( 'udx.utility.device', function( require, exports, module ) {
  console.debug( 'udx.utility.device', 'loaded' );

  function getDeviceState() {
    console.debug( 'udx.utility.device', 'getDeviceState()' );

    if( getDeviceState.indicator ) {
      return window.getComputedStyle( document.querySelector( '.udx-state-indicator' ), ':before' ).getPropertyValue( 'content' );
    }

    return 'desktop';

  }

  getDeviceState.indicator = document.createElement( 'div' );
  getDeviceState.indicator.className = 'udx-state-indicator';
  document.body.appendChild( getDeviceState.indicator );

  return getDeviceState;

});

