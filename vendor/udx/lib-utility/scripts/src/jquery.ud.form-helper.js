/* =========================================================
 * jquery.ud.form_helper.js v1.1.0
 * http://usabilitydynamics.com
 * =========================================================
 *
 * Handles various functions related to forms.
 *
 *  ==ClosureCompiler==
 * @compilation_level ADVANCED_OPTIMIZATIONS
 * @output_file_name jquery.ud.form_helper.min.js
 * @js_externs form_helper, jQuery,
 * ==/ClosureCompiler==
 *
 * Copyright (c) 2012 Usability Dynamics, Inc. ( usabilitydynamics.com )
 *
 * ========================================================= */

( function( jQuery ) {
  "use strict";

  jQuery.fn.form_helper = function( s ) {

    /* Set Settings */
    /**
     * Primary function, ran on initialization by default, or on demand when needed.
     *
     * - Cycles through all forms
     * - Processes all required fields
     * - Sets up control groups
     * - Binds listener events to Input Fields
     *
     * @todo Make debug-override form-specific, right now it's global. - potanin@UD
     * @todo Add check to prevent Form Helper being bound to same form more than once. - potanin@UD
     * @todo Add similar function to helpers.css_class_query() that creates class strings that are ready to be inserted into DOM. - potanin@UD
     * @author potanin@UD
     */
    s = jQuery.extend( true, {
      element : this,
      settings : {
        auto_enable : true,
        validate_on_enable : false,
        error_on_blank : false,
        auto_hide_helpers : true,
        check_required_fields : true,
        disable_html5_validation : true,
        markup_all_fields_on_form_fail : true,
        intent_delay : 2500,
        initialization_pause : 0
      },
      ajax : {
        ajax_url : false,
        data : {}
      },
      timers : {},
      helpers : {},
      class_selector : {},
      classes : {
        help_block : 'help-block',
        validate_group : 'validate',
        helper_item : 'helper_item',
        disabled_helper : 'hide',
        active_helper : 'show',
        status : {
          error : 'error',
          blank : 'blank',
          success : 'success',
          warning : 'warning'
        },
        checkbox : {
          on : 'c_on',
          off : 'c_off'
        },
        ajax_form : 'form-ajax'
      },
      debug : false
    }, s );

    /* Internal logging function */
    var log = this.log = function( notice, type, force ) {

      if( !s.debug && !force ) {
        return;
      }

      if( window.console && console.debug ) {

        if( type === 'error' ) {
          console.error( notice );
        }
        else if( type === 'dir' && typeof console.dir == 'function' ) {
          console.dir( notice );
        }
        else {
          console.log( typeof notice == 'string' ? 'form_helper::' + notice : notice );
        }

      }

    };

    /**
     * Enable Script
     *
     * @author potanin@UD
     */
    var enable = this.enable = function() {
      log( 'enable()' );

      /* Create jQuery selector-ready class versions */
      jQuery.each( s.classes, function( key, settings ) {
        s.class_selector[ key] = s.helpers.css_class_query( settings );
      } )

      /* Cycle through each element - which is usually a Form */
      jQuery( s.element ).each( function( index ) {
        var form = this;

        if( form.initialized ) {
          log( 'Form Helper already enabled on this form.' );
        }

        /* Allow Debug override. */
        if( jQuery( form ).attr( 'debug_form' ) == 'true' ) {
          s.debug = true;
        }

        /* Unbind any events in case this is initialized more than once */
        jQuery( form ).unbind( 'submit' );

        /* Reference for all Input Fields for this form */
        form.form_helper_fields = {};

        /* Simple Reference for all Control Groups for this form */
        form.form_control_groups = [];

        /* Analyze Attributes with the HTML5 "required" attribute, attempt to figure out the Validation Type, and add classes and attributes to the element's Control Group */
        if( s.settings.check_required_fields ) {
          check_required_fields( form );
        }

        /* Automate things by adding the Validate Group class when validation_type or validation_required attributes are set */
        jQuery( '.control-group[validation_required="true"], .control-group[validation_type]', form ).addClass( s.classes.validate_group );

        /* Add extra classes to checkboxes */
        handle_special_styling();

        /* Cycle through each Control Group that requires validation, build args, and associate validation function to $.change() event */
        jQuery( '.control-group.' + s.classes.validate_group, form ).each( function() {

          /* Prepare Control Group Settings */
          var control_group = {
            form : form,
            messages : {},
            timers : {},
            control_group : this,
            helpers : false,
            attributes : {}
          };

          /* Add this Control Group to the Form Control Group Reference */
          form.form_control_groups.push( control_group.control_group );

          /* Create placeholders for messages types based on available classes, and get messages passed as Control Group attributes */
          jQuery.each( s.classes.status, function( status_key, status_class ) {
            control_group.messages[ status_key ] = jQuery( control_group.control_group ).attr( status_key + '_message' );
          } );

          /* Convert field attributes from the jQuery format into an associate array  */
          jQuery.each( control_group.control_group.attributes, function( index, attr ) {
            control_group.attributes[ attr.name ] = attr.value;
          } );

          /* Convert to jQuery Object */
          control_group.control_group = jQuery( control_group.control_group );

          control_group.validation_required = control_group.control_group.attr( 'validation_required' ) == 'true' ? true : false;
          control_group.validation_type = control_group.control_group.attr( 'validation_type' ) != '' ? control_group.control_group.attr( 'validation_type' ) : 'not_empty';
          control_group.do_not_markup = control_group.control_group.attr( 'do_not_markup' ) !== undefined ? true : false;

          if( control_group.validation_type && jQuery( control_group.control_group ).attr( control_group.validation_type ) ) {
            control_group[ control_group.validation_type ] = jQuery( control_group.control_group ).attr( control_group.validation_type );
          }

          /* Check if conditional help exists */
          if( jQuery( s.class_selector.help_block, control_group.control_group ).length ) {
            control_group.helpers = jQuery( s.class_selector.help_block, control_group.control_group );
          }

          /* Run Validation on every single field, and attach Validation to future change events */
          jQuery( 'input,textarea,select', control_group.control_group ).each( function() {

            /* Build Input Field arg */
            var input_field = {
              input_element : jQuery( this ),
              input_type : jQuery( this ).attr( 'type' ),
              name : jQuery( this ).attr( 'name' ),
              type : this.nodeName.toLowerCase()
            }

            /* Simple array of Input Fields of same type in same group */
            input_field.related_fields = jQuery.map( jQuery( input_field.type, control_group.control_group ).not( input_field.input_element ), function( element, index ) {
              return element;
            } );

            /* Remove any bound change events from this input field */
            jQuery( input_field.input_element ).unbind( 'change' );

            /* Add any attributes that will not change as the input is interacted with */
            if( !s.settings.disable_html5_validation ) {
              jQuery( input_field.input_element ).attr( 'aria-required', 'true' );
            }

            /* Combine Field and Control Group settings and add to Input Field to reference. This groups fields with same name (e.g. checkboxes) together. */
            form.form_helper_fields[ input_field.name ] = jQuery.extend( {}, control_group, input_field );

          } );
          /* End of single Input Field processing */

        } );
        /* End of single Control Group processing */

        log( form.form_helper_fields, 'dir' );

        /* All Monitored Fields are loaded at this point.  Now we cycle through all fields, run initial validaiton, and attach revent handlers to them */
        jQuery.each( form.form_helper_fields, function( name, vs ) {

          /* Save the Validation Settings in DOM object */
          jQuery( vs.input_element ).data( 'validation_settings', vs );

          vs.inputs_in_group = jQuery( 'input,textarea,select', vs.control_group ).length;

          /* Render, or update, Message via Helpers */
          s.helpers.update_inline_help( vs.control_group, vs );

          /* Bind initial Validation to Window.load to utilize any autocompleted fields.  */
          jQuery( window ).load( function() {
            setTimeout( function() {
              log( 'enable() - Executing initial field validation.' );
              validate_field( vs.input_element, vs );

            }, s.settings.initialization_pause );
          } );

          // Allow Intent Delay override via attribute.
          // @todo This should be migrated elsewhere and integrated w/ a better override system - potanin@UD
          vs.intent_delay = typeof vs.control_group.attr( 'intent_delay' ) == 'string' ? vs.control_group.attr( 'intent_delay' ) : s.settings.intent_delay;

          monitor_field( vs.input_element, vs, {
            check_related : true
          } );

        } );
        /* End of Form Helper Fields processing */

        form.initialized = true;

        /* Monitor submit event */
        jQuery( form ).submit( function( event ) {
          handle_submission( form, event );
        } );

      } );
      /* End of single Form Procesing */

    };

    /**
     * Attaches all events to monitored Input Fields
     *
     * @author potanin@UD
     */
    var monitor_field = function( input_element, vs, args ) {

      args = jQuery.extend( {}, {
        check_related : false
      }, args );

      /* Monitor change events */
      jQuery( input_element ).bind( 'change', function( e ) {
        validate_field( input_element, vs );
      } );

      /* Monitor keyup events for Inputs and Textareas */
      if( vs.type == 'input' || vs.type == 'textarea' ) {

        jQuery( input_element ).keyup( function( event ) {

          /* Reset Done Typing timer */
          clearTimeout( vs.timers.intent_delay );

          vs.timers.intent_delay = setTimeout( function() {
            validate_field( input_element, vs );
          }, vs.intent_delay );

        } );

        /* Kill Typing Timer when user leaves Input Field */
        jQuery( input_element ).blur( function( event ) {
          clearTimeout( vs.timers.intent_delay );
        } );

      }

      jQuery( vs.input_element ).addClass( 'monitored' );

      /* Attach monitoring to any related fields, that have the same name and are therefore not in form.form_helper_fields */
      if( args.check_related ) {
        jQuery.each( vs.related_fields, function( i, related_element ) {

          if( !jQuery( related_element ).hasClass( 'monitored' ) ) {
            monitor_field( related_element, vs );
          }

        } );
      }

    }

    /**
     * Style checkboxes by adding custom classes to the label element.
     *
     * @todo This does not seem to be very efficient.
     * @author potanin@UD
     */
    function handle_special_styling() {
      log( 'handle_special_styling()' );

      if( !jQuery( '.checkbox.styled input' ).length ) {
        return;
      }

      jQuery( '.checkbox.styled' ).each( function() {
        jQuery( this ).closest( 'label' ).removeClass( s.classes.checkbox.on ).addClass( s.classes.checkbox.off );
      } );

      jQuery( '.checkbox.styled input:checked' ).each( function() {
        jQuery( this ).closest( 'label' ).addClass( s.classes.checkbox.on ).removeClass( s.classes.checkbox.off );
      } );

      jQuery( '.checkbox.styled input' ).click( handle_special_styling );

    };

    /**
     * A shortcut that looks for enabled Input Fields with a "required" attribute in the form, and modifies the Control Group automatically.
     *
     * Attempts to determine Validation Type based on Input Type.
     *
     * Adds to the Control Group of each required field:
     * - class: validate
     * - attr: validation_required
     * - attr: validation_type
     *
     * Optionally removes the "required" attribute to stop HTML5 validation on modern browsers.
     *
     * @author potanin@UD
     */
    function check_required_fields( form ) {
      log( 'check_required_fields()' );

      jQuery( 'input[required],textarea[required],select[required]', form ).each( function() {

        /* Don't do anything with disabled fields */
        if( this.disabled ) {
          log( ' Skipping ' + this.name + ' because it is disabled.' );
          return;
        }

        /* If configured, remove attribute to disable built-in browser support for validation */
        if( s.settings.disable_html5_validation ) {
          jQuery( this ).removeAttr( 'required' );
        }

        /* Identify our Control Group */
        var control_group = jQuery( this ).closest( '.control-group' );

        /* If no CG, as of now we do nothing */
        if( !control_group.length ) {
          return;
        }

        /* Check if Validation Type attribute is declared */
        var validation_type = jQuery( this ).attr( 'validation_type' ) != '' ? jQuery( this ).attr( 'validation_type' ) : false;

        /* If Control Group does not have a defined Validation Requirement, we set it */
        if( !control_group.attr( 'validation_required' ) ) {
          control_group.attr( 'validation_required', 'true' );
        }

        /* Mark the CG as needing to be validateed */
        control_group.addClass( s.classes.validate_group );

        /* If Validation Type was not set, we check Input Type for possible Validation Type  */
        if( !validation_type && jQuery( this ).attr( 'type' ) ) {

          switch( jQuery( this ).attr( 'type' ).toLowerCase() ) {

            case 'email' :
              control_group.attr( 'validation_type', 'email' );
              break;

            case 'url' :
              control_group.attr( 'validation_type', 'url' );
              break;

            case 'tel' :
              control_group.attr( 'validation_type', 'tel' );
              break;

          }

        }

        /* If we found a Validation Type, we add it and the type-specific setting to the Control Group */
        if( validation_type ) {
          control_group.attr( 'validation_type', validation_type );

          if( jQuery( this ).attr( validation_type ) ) {
            control_group.attr( validation_type, jQuery( this ).attr( validation_type ) );
          }

        }

        jQuery( document ).trigger( 'form_helper::check_required_fields::field_complete', { element : this, validation_type : validation_type } );

      } );

    }

    /**
     * Ran when from is submitted.
     *
     * @author potanin@UD
     */
    function handle_submission( form, e ) {
      log( 'handle_submission()' );

      /* Sometimes causes error on IE7 */
      form.status = form_status( form );

      if( form.status.validation_fail ) {
        e.preventDefault();

        /* Cycle through all fields and validate them, to show user what they missed */
        if( s.settings.markup_all_fields_on_form_fail ) {

          jQuery.each( form.form_helper_fields, function( name, settings ) {

            if( settings.status_code != 'success' ) {
              validate_field( settings.input_field, settings );
            }

          } );

        }

        /* Cycle only through failed fields */
        if( !s.settings.markup_all_fields_on_form_fail ) {

          jQuery.each( form.status.failed_fields, function( i, name ) {
            validate_field( name );

          } );

        }

        return false;

      }
      else {

        jQuery( form ).trigger( 'form_helper::success', { form : form, event : e } );

        if( jQuery( form ).hasClass( s.classes.ajax_form ) ) {
          e.preventDefault();
          return false;
        }

      }

      //return true;

    }

    /**
     * Checks a single field, in relation to it's control group, against the specified validation settings
     *
     * Function executed on $.ready() and everytime the input is updated, or on demand.
     * To call on demand, pass two arguments:
     * - input_element (jQuery Object) - target field
     * - validation_settings (Object) - see enable() for object information, will vary from one situations to the next
     *
     * @author potanin@UD
     */
    var validate_field = this.validate_field = function( input_element, validation_settings ) {
      log( 'validate_field(' + ( typeof validation_settings == 'object' ? validation_settings.name : '?' ) + ')' );

      /* If this function is being called with only a Field Name, we attempt to get the DOM Oject */
      if( input_element && typeof input_element != 'object' ) {
        input_element = jQuery( '[name="' + input_element + '"]' );

        if( input_element.length ) {
          log( 'validate_field(): Input Element not passed as object, but found using name in DOM.' );
        }
        else {
          log( 'validate_field(): Input Element not passed as object, and could not be found by name  (' + input_element + ') in DOM.' );
        }
      }

      /* If we have the DOM Input Element Object, but no Validation Settings */
      if( input_element && !validation_settings ) {
        validation_settings = input_element.data( 'validation_settings' );
      }

      /* If cannot get Validation Settings, we re-enable */
      if( !validation_settings ) {
        return log( 'validate_field(): Warning. validate_field() was called on an Input Field that could not be found, or does not have Validation Settings.', 'error', true );
      }

      /* Merge passed Validation Settings with Defaults */
      var args = jQuery.extend( true, {
        validation_type : 'not_empty'
      }, validation_settings );

      var current_value = typeof jQuery( input_element ).val() == 'string' ? jQuery( input_element ).val() : '';

      /* Determine if this is the first time this field has been put through validation */
      if( typeof args.form.form_helper_fields[ args.name ].status_code === 'undefined' ) {
        args.initial_run = true;
      }

      var result = {
        detail_log : []
      }

      result.detail_log.push( ( args.initial_run ? 'Initial Run' : 'Secondary Run' ) + ': ' + args.name );
      result.detail_log.push( 'args.validation_required: ' + args.validation_required );
      result.detail_log.push( 'args.inputs_in_group: ' + args.inputs_in_group );
      result.detail_log.push( 'current_value: ' + current_value );
      result.detail_log.push( 'validation_type: ' + args.validation_type );

      switch( args.validation_type ) {

        case 'checked':

          if( jQuery( input_element ).attr( 'type' ) != 'checkbox' ) {
            args.status_code = 'success';

          }
          else {
            if( jQuery( input_element ).is( ':checked' ) ) {
              args.status_code = 'success';

            }
            else {
              args.status_code = 'error';

            }
          }

          break;

      /**
       * Compare all elements of same type within the Control Group
       *
       * @todo Bug with last element being excluded from related. So when it is checked, it does not count. - potanin@UD
       */
        case 'selection_limit':

          result.detail_log.push( 'Total related: ' + args.related_fields.length );

          /* Build array of checked elements */
          var checked = jQuery.map( jQuery( args.related_fields ), function( element, index ) {
            if( element.checked ) {
              return element;
            }
          } );

          result.detail_log.push( 'Total checked: ' + checked.length );
          result.detail_log.push( 'Selection limit: ' + validation_settings.selection_limit );

          if( checked.length === 0 ) {
            args.status_code = 'error';
            args.messages[ 'success' ] = 'Please make a selection.';
          }

          if( checked.length > 0 && checked.length < validation_settings.selection_limit ) {
            args.status_code = 'success';
            args.messages[ 'success' ] = 'You may select ' + ( validation_settings.selection_limit - checked.length ) + ' more.';
          }

          if( checked.length == validation_settings.selection_limit ) {
            args.status_code = 'success';
            args.messages[ 'success' ] = 'You can not select anymore.';
          }

          if( checked.length > validation_settings.selection_limit ) {
            jQuery( input_element ).removeAttr( 'checked' )
          }

          /* Update Inline Help */
          s.helpers.update_inline_help( args.control_group, args );

          break;


      /**
       * Compare all elements of same type within the Control Group
       *
       * @todo The password strength is temporary.
       */
        case 'password':
        case 'matching_passwords':

          var other_value;

          /* For password there should only be two, but we cycle through full loop */
          jQuery.each( args.related_fields, function( i, element ) {
            other_value = jQuery( element ).val();
          } );

          if( current_value === '' && other_value === '' ) {
            result.detail_log.push( 'Passwords are empty: ' + current_value + ' - ' + other_value );
            args.status_code = 'error';
            break;
          }

          if( current_value === other_value ) {
            result.detail_log.push( 'Passwords match: ' + current_value + ' - ' + other_value );
            args.status_code = 'success';

            /* Simple Password Strength (should be done much better via third-party of sort */
            args.password_strength = Math.round( ( other_value.length / 13 ) * 100 );
            args.messages[ 'success' ] = jQuery( '<div class="">Password Strength:</div><div class="progress progress-striped"><div class="bar" style="width: ' + args.password_strength + '%;"></div></div>' );

            /* Update Inline Help */
            s.helpers.update_inline_help( args.control_group, args );

            break;
          }

          if( current_value != other_value ) {
            result.detail_log.push( 'Passwords do not match: ' + current_value + ' - ' + other_value )
            args.status_code = 'error';
            break;
          }

          break;

        case 'email':

          if( current_value == '' ) {
            args.status_code = 'blank';

          }
          else if( s.helpers.validate_email( current_value ) ) {
            args.status_code = 'success';

          }
          else {
            args.status_code = 'error';

          }

          break;

        case 'url':

          if( current_value == '' ) {
            args.status_code = 'blank';

          }
          else if( s.helpers.validate_url( current_value ) ) {
            args.status_code = 'success';

          }
          else {
            args.status_code = 'error';

          }

          break;

        case 'domain':
          if( current_value == '' ) {
            args.status_code = 'blank';
          }
          else if( s.helpers.validate_url( current_value, { use_http : false } ) ) {
            args.status_code = 'success';
          }
          else {
            args.status_code = 'error';
          }

          break;

      /**
       * Uses Google Maps, if available, to get quality of address.
       *
       * @todo This is not complete, mostly proof of concept. - potanin@UD
       */
        case 'address':

          if( current_value == '' ) {
            args.status_code = 'blank';
            break;
          }

          /* Make sure Google Maps API is loaded */
          if( typeof google != 'object' || typeof google.maps != 'object' ) {
            break;
          }

          args.remote_request = true;

          args.geocoder = args.geocoder ? args.geocoder : new google.maps.Geocoder();

          /* Remove line breaks from GM request */
          args.clean_value = current_value.replace( /(\r\n|\n|\r)/gm, " " );

          args.geocoder.geocode( { 'address' : args.clean_value }, function( results, status ) {

            if( typeof results == 'object' ) {

              jQuery.each( results, function( i, data ) {

                if( data.geometry.location_type == 'ROOFTOP' ) {
                  args.messages[ 'success' ] = 'Validated: ' + data.formatted_address;
                  args.status_code = 'success';

                }
                else if( data.status == 'ZERO_RESULTS' ) {
                  args.status_code = 'error';

                }
                else {
                  args.status_code = 'warning';
                }

              } );

              /* Render, or update, Message via Helpers */
              s.helpers.update_inline_help( args.control_group, args );

              finalize_field_validation( current_value, result, args );

            }

          } );

          break;

        case 'ajax':

          if( !args.validation_ajax ) {
            break;
          }

          args.remote_request = true;

          /* Load default AJAX arguments */
          var ajax_request = jQuery.extend( {}, {
            action : args.validation_ajax,
            field_name : args.name,
            field_value : current_value,
            field_type : args.type
          }, s.settings.ajax_url );

          jQuery.ajax( {
            url : s.settings.ajax_url,
            data : ajax_request,
            success : function( response ) {
              args.status_code = ( response.success == 'false' ) ? 'error' : 'success';

              /* Add new message to Control Group's Messages */
              args.messages[ args.status_code ] = response.message;

              update_control_group_ui( args.control_group, args );

              result.detail_log.push( args.name + ' - Custom Ajax Validation. Result: ' + args.status_code );

              finalize_field_validation( current_value, result, args );

            },
            dataType : "json"
          } );

          break;

        case 'pattern':

          var this_regex = new RegExp( args.attributes.pattern, "g" );

          if( current_value == '' ) {
            args.status_code = 'blank';

          }
          else if( this_regex.test( current_value ) ) {
            args.status_code = 'success';

          }
          else {
            args.status_code = 'error';
            args.messages[ args.status_code ] = "Please, match the requested format" + ( ( typeof args.title != 'undefined' ) ? ":" + args.title : '' );
          }

          break;

        case 'not_empty':
        default:

          if( current_value == '' ) {
            args.status_code = 'blank';

          }
          else {
            args.status_code = 'success';

          }

          if( current_value == '' ) {
            args.status_code = 'blank';
          }
          else {
            args.status_code = 'success';
          }

          break;

      }
      /* Attribute Validation Complete */

      if( !args.remote_request ) {
        finalize_field_validation( current_value, result, args );
      }

    }

    /**
     * Ran after validate_field() has completed processing.
     *
     * @todo Migrate this into a bound function within validate_field(). - potanin@UD
     * @author potanin@UD
     */
    var finalize_field_validation = this.finalize_field_validation = function( current_value, result, args ) {

      if( args.status_code == 'blank' && s.settings.error_on_blank ) {
        args.status_code = 'error';
      }

      result.detail_log.push( 'new status_code: ' + args.status_code );

      /* Not used yet, jsut added for reference */
      jQuery( args.input_element ).attr( 'validation_status_code', args.status_code );
      jQuery( args.control_group ).attr( 'validation_status_code', args.status_code );

      /* Save new Status Code into Form Helper Fields */
      args.form.form_helper_fields[ args.name ].status_code = args.status_code;

      /* Actions for Input Fields that are monitored, e.g. the chang of which can affect the overall Form Status */
      if( args.validation_required ) {

        /* If Validation is Required for submitting, and the result is anything other than success - we stop the form */
        if( args.status_code != 'success' ) {
          result.detail_log.push( 'Field validation fail.' );
        }

        /* Class added for quick reference, not markup */
        if( form_status( args.form ).validation_fail ) {
          jQuery( args.form ).addClass( 'validation_fail' );
          jQuery( args.form ).data( 'do_not_process', true );
          result.detail_log.push( 'Form validation fail.' );

        }
        else {
          jQuery( args.form ).removeClass( 'validation_fail' );
          jQuery( args.form ).removeData( 'do_not_process' );
          result.detail_log.push( 'Form passed validation.' );

        }

      }
      /* End Validation-only Actions */

      log( result.detail_log, 'dir' );

      /* If this is not initialization, do the markup */
      if( !args.initial_run || s.settings.validate_on_enable ) {
        update_control_group_ui( args.control_group, args )
      }

    }

    /**
     * Updates the visual styles of a Control Group and displays helpers.
     *
     * control_group is passed as first argument for API access as an object
     *
     * @author potanin@UD
     */
    var update_control_group_ui = this.update_control_group_ui = function( control_group, args ) {
      log( 'update_control_group_ui()' );

      /* If this Control Group has a do_not_markup attribute, we let it be */
      if( args.do_not_markup ) {
        return;
      }

      /* Remove all Status Classes from CG */
      jQuery.each( s.classes.status, function( key, value ) {
        jQuery( control_group ).removeClass( value );
      } );

      /* Render applicable Notices, if they exists */
      if( args.helpers ) {

        /* Hide all immediate children and remove any .active classes */
        jQuery( '> .' + s.classes.helper_item, args.helpers ).removeClass( s.classes.active_helper ).addClass( s.classes.disabled_helper );

        /* Find the helper by class, if exists, and show it */
        jQuery( '.' + args.status_code, args.helpers ).removeClass( s.classes.disabled_helper ).addClass( s.classes.active_helper );

      }

      /* If this is a Validated field that has failed validation: */
      jQuery( control_group ).addClass( s.classes.status[ args.status_code ] );

    }

    /**
     * Determine and update that overall status of a form
     *
     * @author potanin@UD
     */
    var form_status = this.form_status = function( form ) {
      log( 'form_status()' );

      /* Ensure Form Helper Fields are set, if not return an empty object */
      if( typeof form.form_helper_fields != 'object' ) {
        log( 'form_status() - form.form_helper_fields is not an object, leaving.' );
        return {};
      }

      var response = {
        validation_fail : false,
        failed_fields : []
      };

      jQuery.each( form.form_helper_fields, function( name, settings ) {

        if( settings.status_code != 'success' ) {
          log( 'form_status() - Input Field failed validation: ' + name );
          response.failed_fields.push( name );
        }

      } );

      /* If we have Failed Fields, then the form is failed */
      if( response.failed_fields.length ) {
        log( 'form_status() - Form failed validation. Invalid fields: ' + response.failed_fields.length );
        form.validation_fail = response.validation_fail = true;
        form.failed_fields = response.failed_fields;
      }
      else {
        log( 'form_status() - Form is valid. ' );
      }

      return response;

    };

    /**
     * Updates Inline Help based on available messages in the Control Group Message Handler.
     *
     * Ran when on enable() and on other events that may result in updates to Control Group
     * status message text, such as AJAX returned data.
     *
     * @todo Not sure at which point this should be ran first. - potanin@UD
     * @author odokienko@UD
     */
    if( typeof s.helpers.update_inline_help != 'function' ) {
      s.helpers.update_inline_help = function( control_group, args ) {
        log( 'helpers.update_inline_help()' );

        /* Try to load exisitng Help Block */
        if( !args.helpers ) {
          args.helpers = jQuery( s.class_selector.help_block, control_group );
        }

        /* If no Help Block found, create one by inserting it after the last input type in CG */
        if( !args.helpers.length ) {
          jQuery( args.type + ':last', control_group ).after( args.helpers = jQuery( '<span class="' + s.classes.help_block + '"></span>' ) );
        }

        /* Update Status-based Help Messages by going through all available statuses */
        jQuery.each( args.messages, function( status_key, text ) {

          /* Get Status Class form global settings */
          var status_class = s.classes.status[ status_key ];
          var this_line = jQuery( 'span.' + status_class, args.helpers );

          if( !this_line.length ) {
            jQuery( args.helpers ).append( this_line = jQuery( '<span></span>' ) );
          }

          /* Always add the main Helper Item class */
          this_line.addClass( s.classes.helper_item );

          /* Add status-specific class */
          this_line.addClass( status_class );

          /* If enabled, as on default, the Disabled Helper class is added to all helper items */
          if( s.settings.auto_hide_helpers ) {
            this_line.addClass( s.classes.disabled_helper );
          }

          /* Insert text into Line Item */
          jQuery( this_line ).html( text );

        } );

      }
    }

    /**
     * Flexible converter of user-specified classes into a jQuery-friendly query.
     *
     * Classes may be passed in several ways:
     * - 'my_class'
     * - 'my_class another_class'
     * - ['my_class', 'another_class']
     *
     * Purposely ignores associative arrays returning them as they were passed.
     *
     * @author potanin@UD
     */
    if( typeof s.helpers.css_class_query != 'function' ) {
      s.helpers.css_class_query = function( css_class ) {

        var css_query = [];

        if( typeof css_class == 'object' || typeof css_class == 'array' ) {

          var associative_array = false;

          jQuery.each( css_class, function( i, single_class ) {
            css_query.push( single_class );

            if( !jQuery.isNumeric( i ) ) {
              associative_array = true;
              return;
            }

          } );

          if( associative_array ) {
            return css_class;
          }

        }
        else if( typeof css_class == 'string' ) {
          css_query = css_class.split( ' ' );
        }

        /* Joing pieces together and replace double-periods with single */
        return  ('.' + css_query.join( '.' ) ).replace( /\.\./g, '.' );

      }
    }

    /**
     * Helper for properly removing an element from array by value
     *
     * {Not currently used}
     *
     * @author potanin@UD
     */
    if( typeof s.helpers.remove_from_array != 'function' ) {
      s.helpers.remove_from_array = function( value, arr ) {
        return jQuery.grep( arr, function( elem, index ) {
          return elem !== value;
        } );
      }
    }

    /**
     * Helper for URL Validation
     *
     * @author potanin@UD
     */
    if( typeof s.helpers.validate_url != 'function' ) {
      s.helpers.validate_url = function( value, args ) {
        log( 'helpers.validate_url(' + value + ')' );
        args = jQuery.extend( { use_http : true }, args );
        if( args.use_http ) return /^(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?/i.test( value );
        return /^[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?/i.test( value );
      }
    }

    /**
     * Helper for Email Validation
     *
     * @author potanin@UD
     */
    if( typeof s.helpers.validate_email != 'function' ) {
      s.helpers.validate_email = function( value ) {
        log( 'helpers.validate_email(' + value + ')' );
        var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test( value );
      }
    }

    /* Enable functionality on each instance */
    if( s.settings.auto_enable ) {
      enable();
    }

    /* Return object for chaining */
    return this;

  };

}( jQuery ) );
