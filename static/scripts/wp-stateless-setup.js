/**
 * Extend the "wp" object; requires wp-api.
 *
 *
 */

wp.stateless = {

  /**
   * Returns Google API Auth token, either from sessionStorage or from URL, if on settings setup page.
   *
   * wp.stateless.getAccessToken()
   * wp.stateless.getAccessToken().access_token
   *
   */
  getAccessToken: function getAccessToken() {
    console.log( "wp.stateless.getAccessToken." );

    if( 'string' !== typeof location.search ) {
      console.log( "wp.stateless.getAccessToken", 'checking sessionStorage' );

      if( sessionStorage.getItem( 'wp.stateless.token' ) ) {
        console.log( "wp.stateless.getAccessToken", 'returning sessionStorage' );
        return JSON.parse( sessionStorage.getItem( 'wp.stateless.token' ) ) ;
      }

      console.log( "wp.stateless.getAccessToken.", 'no token in sessionStorage or url' );

      return null;
    }

    try {
      console.log( "wp.stateless.getAccessToken", 'checking URL' );

      var _token = JSON.parse(decodeURIComponent( location.search.replace( '?access_token=', '' )  ));

      if( _token && 'object' === typeof _token.token ) {
        console.log( "wp.stateless.getAccessToken", 'setting token from url to sessionStorage' );
        sessionStorage.setItem( 'wp.stateless.token', JSON.stringify( _token.token ) );
        return _token.token;

      }


    } catch( error ) {
      //console.error( error.message );
    }

    return null;

  },

  /**
   * Create Project
   *
   *
   *  wp.stateless.createProject( {"projectId": "uds-test-project-4","name": "uds-test-project-4"} );
   *
   *
   * @todo After this is implemented we also need to assign the user to the project. - potanin@UD
   * @param options
   */
  createProject: function createProject( options ) {

    jQuery.ajax({
      url: 'https://cloudresourcemanager.googleapis.com/v1/projects',
      method: "POST",
      dataType: "json",
      data: JSON.stringify( options ),
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.getAccessToken().access_token
      }
    }).done(function( responseData  ) {

      console.log( 'responseData ', responseData  );

    }).fail(function( data ) {
      console.log( "error", "data.responseText", JSON.parse( data.responseText ) );
    });


  },

  /**
   * Get Projects
   *
   * @param name
   */
  listProjects: function listProjects( name ) {

    jQuery.ajax({
      url: 'https://cloudresourcemanager.googleapis.com/v1/projects',
      method: "GET",
      dataType: "json",
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.getAccessToken().access_token
      }
    }).done(function( responseData  ) {

      console.log( 'responseData ', arguments );

    }).fail(function( data ) {
      console.log( "error", "data.responseText", JSON.parse( data.responseText ) );
    });


  },

};

