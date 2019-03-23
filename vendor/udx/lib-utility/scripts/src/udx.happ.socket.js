/**
 * Socket
 *
 * @required ud.socket
 * @author peshkov@UD
 */

Application.define( 'core.socket', function( args, callback ) {

  /* Set arguments */
  args = jQuery.extend( true, {
    'port': 443,
    'url': false,
    'resource': 'websocket.api/v1.5',
    'secure': true,
    'account-id': false,
    'access-key': false
  }, typeof args === 'object' ? args : {} );

  if( typeof callback === 'undefined' ) {
    callback = function() {
      return null;
    }
  }

  return new ud.socket.connect( args.url, args, function( error, socket ) {
    if( error ) {
      console.error( 'Socket Callback', 'Connection Failed', error );
      return null;
    }
    try { callback( socket ); }
    catch( e ) { console.error( 'Socket Callback', 'Custom callback failed', e ); }
  } );

} );