/* =========================================================
 * jquery.ud.social.js v1.0.0
 * http://usabilitydynamics.com
 * =========================================================
 *
 * Handle interaction with social sites.
 *
 * Validation: http://www.jslint.com/
 *
 * Copyright ( c ) 2012 Usability Dynamics, Inc. ( usabilitydynamics.com )
 * ========================================================= */

( function ( jQuery ) {
  "use strict";

  jQuery.fn.social = function ( s ) {

    /* Set Settings */
    s = jQuery.extend( {
      element: this,
      networks: {
        linkedin: {
          profile_fields: {
            id: 'network_id',
            firstName: 'first_name',
            lastName: 'last_name',
            pictureUrl: 'user_image',
            headline: 'headline',
            industry: 'industry',
            summary: 'summary',
            specialties: 'specialties',
            location: 'location',
            associations: 'associations',
            certifications: 'certifications',
            educations: 'educations',
            skills: 'skills',
            patents: 'patents',
            honors: 'honors',
            proposalComments: 'proposal_comments',
            'three-current-positions': 'current_positions',
            'recommendations-received': 'recommendations',
            'main-address': 'primary_address',
            'member-url-resources': 'url_resources',
            'phone-numbers': 'phone_number',
            'public-profile-url': 'profile_url',
            'im-accounts': 'im_accounts'
          }
        }
      },
      user_data: {},
      debug: true
    }, s );

    /* Internal logging function */
    var log = this.log = function ( something, type ) {

      if ( !s.debug ) {
        return;
      }

      if ( window.console && console.debug ) {

        if ( type === 'error' ) {
          console.error( something );
        } else {
          console.log( something );
        }

      }

    };


    /**
     * The main function ran when the script is executed.
     *
     * Profile Fields: https://developer.linkedin.com/documents/profile-fields
     *
     * @called onLoad
     * @author potanin@UD
     */
    var handle_linkedin = this.handle_linkedin = function ( ) {
      log( 'handle_linkedin()' );

      s.networks.linkedin.active = true;

      if( typeof IN.Event == 'undefined') {
        return;
      }

      jQuery( '.linkedin_asset' ).show();

      /* Executed after the current user has been authenticated */
      IN.Event.on( IN, "auth", function() {
        log( 'IN.Event::auth ' );

        var these_fields = [];

        /* Create a simple array of LinkedIn-friendly fields */
        jQuery.each( s.networks.linkedin.profile_fields, function( network_key , global_key) {
          these_fields.push( network_key );
        });

        IN.API.Profile( 'me' ).fields( these_fields ).result( function( profile ) {
          log( 'IN.API.Profile()' );

          /* Cycle through returnes values and match the values up with global user meta keys */
          jQuery.each( profile.values[0], function( network_key, value ) {

            var global_key = s.networks.linkedin.profile_fields[ network_key ];

            if( typeof global_key == 'undefined' || value == '' ) {
              return;
            }

            switch( network_key ) {

              case 'location':
                value = value.name;
              break;

            }

            s.user_data[global_key] = value;

          });

          s.user_data.display_name = s.user_data.first_name + ' ' + s.user_data.last_name;

          if( s.user_data ) {
            jQuery( '.linked_in_login' ).html(
            '<p class="linkedin_authentication alert alert-info">'
            + (  typeof s.user_data.user_image == 'string' ? '<img src="' + s.user_data.user_image + '" class="user_image">' : '' )
            + '<span class="welcome_text">Hello, <b>'  +  s.user_data.first_name + '</b>! </span>'
            + ( typeof s.user_data.headline == 'string' ? '<span class="linkedin_headline">' + s.user_data.headline + '</span>' : '' )
            + ( typeof s.user_data.industry == 'string' ? '<span class="linkedin_industry">' + s.user_data.industry + '</span>' : '' )
            + '</p>');
          }

          jQuery( document ).trigger( 'social::user_data_update' , s.user_data );

        });

        jQuery( document ).bind( 'social::user_logout', function() {

          if( typeof IN  == 'object' && typeof IN.User != 'undefined' ) {
            IN.User.logout();
          }

        });



      });

    }


    /**
     * The main function ran when the script is executed.
     *
     */
    var user_logout = this.user_logout = function() {

      jQuery( document ).trigger( 'social::user_logout' );

    }


    /**
     * The main function ran when the script is executed.
     *
     */
    var enable = this.enable = function ( ) {
      log( 'social::enable()' );

      /* Detect Social Networks */
      if( typeof (IN) == 'object' ) {
        handle_linkedin();
      }

    };


    /* Authormatically enable */
    this.enable( );

    /* Return object for chaining */
    return this;

  };

} ( jQuery ) );
