/** =========================================================
 * jquery.ud.dynamic_filter.js v1.1.4-a2
 * http://usabilitydynamics.com
 * =========================================================
 *
 * Commercial use requires one-time license fee
 * http://usabilitydynamics.com/licenses
 *
 * Copyright © 2012 Usability Dynamics, Inc. (usabilitydynamics.com)
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Alexandru Marasteanu BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * =TODO=
 * - Show More / Show Less are visible when only one option remains, need to decide how to handle - should the filter inputs be visible at all with one option?
 *
 *
 * ========================================================= */

/*jslint devel: true, undef: true, browser: true, continue: true, unparam: true, debug: true, eqeq: true, vars: true, white: true, newcap: true, plusplus: true, maxerr: 50, indent: 2 */
/*global window */
/*global console */
/*global clearTimeout */
/*global setTimeout */
/*global jQuery */

( function ( jQuery ) {
  "use strict";

  jQuery.prototype.dynamic_filter = function ( s ) {

    /** Merge Custom Settings with Defaults */
    this.s = s = jQuery.extend( true, {
      ajax: {
        args: {},
        async: true,
        cache: true,
        format: 'json'
      },
      active_timers: {
        status: {}
      },
      attributes: {},
      attribute_defaults: {
        label: '',
        concatenation_character: ', ',
        display: true,
        default_filter_label: '',
        related_attributes: [],
        sortable: false,
        filter: false,
        filter_always_show: false,
        filter_collapsable: 4, /* zero based */
        filter_multi_select: false,
        filter_show_count: false,
        filter_show_label: true,
        filter_note: '',
        filter_placeholder: '',
        filter_show_disabled_values: false,
        filter_range: {},
        filter_ux: [],
        filter_value_order: 'native', /* Or 'value_count', or 'label' */
        filter_values: []
      },
      callbacks: {
        result_format: function ( result ) { return result; }
      },
      data: {
        filterable_attributes: {},
        current_filters: {},
        sortable_attributes: {},
        dom_results: {},
        rendered_query: []
      },
      filter_types: {
        checkbox: {
          filter_show_count: true,
          filter_multi_select: true
        },
        input: {
          filter_always_show: true,
          filter_ux: [ { 'autocomplete': {} } ]
        },
        dropdown: {
          default_filter_label: 'Show All',
          filter_show_count: true,
          filter_always_show: true
        },
        range: {
          filter_always_show: true
        }
      },
      helpers: {},
      instance: {},
      settings: {
        auto_request: true,
        chesty_puller: false,
        debug: false,
        dom_limit: 200,
        filter_id: jQuery( this ).attr( 'dynamic_filter' ) ?  jQuery( this ).attr( 'dynamic_filter' ) : 'df_' + location.host + location.pathname,
        load_ahead_multiple: 2,
        sort_by: '',
        sort_direction: '',
        set_url_hashes: true,
        per_page: 25,
        request_range: {},
        use_instances: true,
        timers: {
          notice: {
            dim: 5000,
            hide: 2500
          },
          filter_intent: 1600,
          initial_request: 0
        },
        messages: {
          no_results: 'No results found.',
          show_more: 'Show More',
          show_less: 'Show Less',
          loading: 'Loading...',
          server_fail: 'Could not retrieve results due to a server error, please notify the website administrator.',
          total_results: 'There are {1} total results.',
          load_more: 'Showing {1} of {2} results. Show {3} more.'
        },
        unique_tag: false
      },
      classes: {
        wrappers: {
          ui_debug: 'df_ui_debug',
          element: 'df_top_wrapper',
          results_wrapper: 'df_results_wrapper',
          sorter: 'df_sorter',
          results: 'df_results',
          filter: 'df_filter',
          load_more: 'df_load_more',
          status_wrapper: 'df_status_wrapper'
        },
        inputs: {
          input: 'df_input',
          checkbox: 'df_checkbox',
          start_range: 'df_start_range',
          end_range: 'df_end_range',
          range_slider: 'df_range_slider'
        },
        labels: {
          range_slider: 'df_range_slider_label',
          attribute: 'df_attribute_label',
          checkbox: 'df_checkbox'
        },
        status: {
          success: 'df_alert_success',
          error: 'df_alert_error'
        },
        results: {
          row: 'df_result_row',
          result_data: 'df_result_data',
          list_item: 'df_list_item'
        },
        element: {
          ajax_loading: 'df_ajax_loading',
          filter_pending: 'df_filter_pending',
          server_fail: 'df_server_fail',
          have_results: 'df_have_results'
        },
        filter: {
          inputs_list_wrapper: 'df_filter_inputs_list_wrapper',
          inputs_list: 'df_filter_inputs_list',
          value_wrapper: 'df_filter_value_wrapper',
          value_label: 'df_filter_value_label',
          value_title: 'df_filter_title',
          value_count: 'df_filter_value_count',
          trigger: 'df_filter_trigger',
          filter_label: 'df_filter_label',
          filter_note: 'df_filter_note',
          show_more: 'df_filter_toggle_list df_show_more',
          show_less: 'df_filter_toggle_list df_show_less',
          selected: 'df_filter_selected',
          extended_option: 'df_extended_option',
          currently_extended: 'df_currently_extended'
        },
        sorter: {
          button: 'df_sortable_button',
          button_active: 'df_sortable_active'
        },
        close: 'df_close',
        separator: 'df_separator',
        selected_page: 'df_current',
        disabled_item: 'df_disabled_item'
      },
      css: {
        results: {
          hidden_row: ' display: none; ',
          visible_row: ' display: block; '
        }
      },
      ux: {
        element: this,
        results_wrapper: jQuery( '<div></div>' ),
        results: jQuery( '<ul></ul>' ),
        result_item: jQuery( '<li ></li>' ),
        sorter: jQuery( '<div></div>' ),
        sorter_button: jQuery( '<div></div>' ),
        filter: jQuery( '<div></div>' ),
        filter_label: jQuery( '<div></div>' ),
        load_more: jQuery( '<div></div>' ),
        status: jQuery( '<div></div>' )
      },
      status: {},
      supported: {
        isotope: typeof jQuery.prototype.isotope === 'function' ? true : false,
        jquery_ui: typeof jQuery.ui === 'object' ? true : false,
        jquery_widget: typeof jQuery.widget === 'function' ? true : false,
        jquery_position: typeof jQuery.ui.position === 'object' ? true : false,
        autocomplete: typeof jQuery.ui === 'object' && typeof jQuery.widget === 'function' && typeof jQuery.ui.position === 'object' && typeof jQuery.prototype.autocomplete === 'function' ? true : false,
        date_selector: typeof jQuery.prototype.date_selector === 'function' ? true : false,
        slider: typeof jQuery.prototype.slider === 'function' ? true : false,
        //cookies: typeof jaaulde === 'object' && jaaulde.utils.cookies.test() ? true : false,
        window_history: typeof history === 'object' && typeof history.pushState === 'function' ? true : false
      }
    }, s );


    /**
     * Return Log
     *
     * @since 0.1
     * @author potanin@UD
     */
    var get_log = this.get_log = function ( type, console_type ) {

      type = typeof type != 'undefind' ? type : false;
      console_type = typeof console_type != 'undefind' ? console_type : false;

      if( typeof s.log_history === 'object' ) {
        jQuery.each( s.log_history, function( index, entry_data )  {

          if( type && type != entry_data.type ) {
            return;
          }

          log( entry_data.notice, entry_data.type, entry_data.console_type,  true );
        });

      }

    }


    /**
     * Internal logging function
     *
     * @since 0.1
     * @author potanin@UD
     */
    var log = this.log = function ( notice, type, console_type, override_debug ) {

      /** Defaults */
      type = typeof type !== 'undefined' ? type : 'log';
      console_type = console_type ? console_type : 'log';

      /** Save entry to log */
      s.log_history.push( { notice: notice, type: type, console_type: console_type } );

      /** Add Prefix */
      notice = ( typeof notice === 'string' || typeof notice === 'number' ? 'DF::' + notice : notice );

      /** If debugging is disabled, or the current browser does not support it, do nothing */
      if ( !override_debug && ( !s.settings.debug || !window.console ) ) {
        return notice;
      }

      /** Check if this log type should be displayed */
      if( !override_debug && typeof s.debug_detail === 'object' && !s.debug_detail[ type ] ) {
        return notice;
      }

      if ( window.console && console.debug ) {

        switch ( console_type ) {

          case 'error':
            console.error( notice );
          break;

          case 'info':
            console.info( notice );
          break;

          case 'time':
            if( typeof console.time != 'undefined' ) { console.time( notice ); }
          break;

          case 'timeEnd':
            if( typeof console.timeEnd != 'undefined' ) { console.timeEnd( notice ); }
          break;

          case 'debug':
            if( typeof console.debug != 'undefined' ) { console.debug( notice );  } else { console.log( notice ); }
          break;

          case 'dir':
            if( typeof console.dir != 'undefined' ) { console.dir( notice ); } else { console.log( notice ); }
          break;

          case 'warn':
            if( typeof console.warn != 'undefined' ) { console.warn( notice ); } else { console.log( notice ); }
          break;

          case 'clear':
            if( typeof console.clear != 'undefined' ) { console.clear(); }
          break;

          case 'log':
            console.log( notice );
          break;

        }

      }

      if( notice ) {
        return notice;
      }

    };


    /**
     * Create the main status bar container or add a message to it
     *
     * !todo Verify that data-dismiss will work with custom classes. - potanin@UD (4/4/12)
     * @since 0.1
     * @author potanin@UD
     */
    var status = this.status = function ( message, this_status ) {

      /** Set Settings */
      this_status = jQuery.extend( true, {
        element: s.ux.status,
        type: 'default',
        message: message,
        hide: s.settings.timers.notice.hide
      }, this_status );

      log( 'status( ' + message + ' ), type: ' + this_status.type, 'status', 'log' );

      /** Status is added to DOM in render_ui, if it is not visible at this point, it was disabled completely */
      jQuery( s.ux.status ).show().addClass( s.classes.status_wrapper );

      if( message === '' ) {
        jQuery( s.ux.status ).html( '' );
        jQuery( s.ux.status ).hide();
      }

      /** Save original classes, if they are not yet saved */
      if( !jQuery( s.ux.status ).data( 'original_classes' ) ) {
        jQuery( s.ux.status ).data( 'original_classes' , jQuery( s.ux.status ).attr( 'class' ) );
      }

      /** Set classes to original ( to clear out any new classes added previously by this function */
      jQuery( s.ux.status ).attr( 'class' , jQuery( s.ux.status ).data( 'original_classes' ) );

      /** Remove any old timers */
      clearTimeout( s.active_timers.status.hide );

      /** Add a custom class if passeed */
      if( typeof s.classes.status[ this_status.type ] === 'string' ) {
        jQuery( s.ux.status ).addClass( s.classes.status[ this_status.type ] );
      }

      s.ux.status.html( message );

      /** If Trigger callback is set, we call it, otherwise bind Dismiss action */
      if( typeof this_status.click_trigger != 'undefined' ) {
        jQuery( s.ux.status ).one( 'click', function() {
          jQuery( document ).trigger( this_status.click_trigger, {} );
        });

      } else {

        if( typeof jQuery.prototype.alert === 'function' ) {
          jQuery( s.ux.status ).prepend( jQuery( '<a class="' + s.classes.close + '" data-dismiss="alert" href="#">&times;</a>' ) );
          jQuery( s.ux.status ).alert();
        }

      }

      /** Schedule removal */
      if ( this_status.hide ) {
        s.active_timers.status.hide = setTimeout( function () {
          jQuery( s.ux.status ).fadeTo( 3000, 0, function () {
            jQuery( s.ux.status ).hide();
          });
        }, this_status.hide );
      }

      if( this_status.type === 'error' ) {
        jQuery( document ).trigger( 'dynamic_filter::error_status', this_status );
      }

    };


    /**
     * Automates configuration, restores a saved DF state for the current user.
     *
     * Attempt to restore any user-specific configuration for this filter
     *
     * !todo URL hash checking. - potanin@UD
     * !todo URL $_GET variable checking. - potanin@UD
     * @author potanin@UD
     */
    var prepare_system = this.prepare_system = function ( event, args ) {

      /** Debug can be passed as an object configuring what type of information to log in the console */
      s.debug_detail = jQuery.extend( true, {
        ajax_detail: false,
        attribute_detail: true,
        detail: true,
        dom_detail: false,
        event_handlers: true,
        filter_ux: true,
        filter_detail: true,
        helpers: false,
        instance_detail: true,
        log: true,
        procedurals: true,
        status: false,
        supported: true,
        timers: true,
        ui_debug: false
      }, ( typeof s.settings.debug === 'object' ? s.settings.debug : {} ) );

      /** Create Log History */
      s.log_history = [];

      /** First Log Entry */
      log( 'prepare_system', 'procedurals' );

      /** Log found third-party libraries */
      jQuery.each( s.supported, function( library, is_used ) {
        is_used ? log( 'Support for (' + library + ') verified.', 'supported', 'info' ) : false ;
      });

      /** Save Placeholder Results (any content within the UX Element */
      if( jQuery( s.ux.element ).children().length ) {
        s.ux.placeholder_results = jQuery( s.ux.element ).children();
      }

      /** If Chesty is with us, we call him */
      if( s.settings.chesty_puller && typeof jQuery.prototype.animate === 'function' ) {
        chesty_puller();
      }

    }


    /**
     * Analyze the specified attributes, must be run before AJAX request.
     *
     * Builds array of filtertable attributes, which are necessary for server-driven ajax calls.
     *
     * !todo Need to check that the requested Filter is defined before merging settings - potanin@UD (4/4/12)
     * @author potanin@UD
     */
    var analyze_attributes = this.analyze_attributes = function ( attribute ) {
      log( 'analyze_attributes', 'procedurals' );

      jQuery( document ).trigger( 'dynamic_filter::analyze_attributes::initialize' );

      if( typeof s.ajax.args === 'undefined' ) {
        s.ajax.args = {};
      }

      /* Convert strings to booleans in Attribute Deftauls */
      jQuery.each( s.attribute_defaults , function( key, value ) {
        s.attribute_defaults[ key ] = value === 'true'  ? true : ( value === 'false' ? false : value ) ;
      });

      s.ajax.args.attributes = s.ajax.args.attributes ? s.ajax.args.attributes : {};
      s.ajax.args.filter_query = s.ajax.args.filter_query ? s.ajax.args.filter_query : {};


      /**
       * Analyze Single Attribute
       *
       */
      analyze_attributes.add_ux_support = function( attribute_key, ux_type, ux_settings ) {
        log( 'analyze_attributes.add_ux_support(' + attribute_key + ')', 'procedurals' );

        s.attributes[ attribute_key ].verified_ux = s.attributes[ attribute_key ].verified_ux ? s.attributes[ attribute_key ].verified_ux : {};
        s.attributes[ attribute_key ].verified_ux[ ux_type ] = ux_settings ? ux_settings : {};

      }


      /**
       * Analyze Single Attribute
       *
       */
      analyze_attributes.analyze_single = function( attribute_key, attribute_settings ) {
        log( 'analyze_attributes.analyze(' + attribute_key + ')', 'procedurals' );

        attribute_settings = attribute_settings ? attribute_settings : s.attributes[ attribute_key ];

        /* Convert strings to booleans */
        jQuery.each( attribute_settings , function( key, value ) {
          attribute_settings[ key ] = value === 'true'  ? true : ( value === 'false' ? false : value ) ;
        });

        /** Merge Attribute Settings with defaults */
        s.attributes[ attribute_key ] = jQuery.extend( {} , s.attribute_defaults, s.attributes[ attribute_key ].filter ? s.filter_types[ s.attributes[ attribute_key ].filter ] : {}, attribute_settings );

        s.attributes[ attribute_key ].verified_ux = {};

        /** Create AJAX request place for this attribute. */
        s.ajax.args.attributes[ attribute_key ] = s.ajax.args.attributes[ attribute_key ] ? s.ajax.args.attributes[ attribute_key ] : {};

        /** Create AJAX Args query, removing any unsupported values such as callback functions */
        jQuery.each( s.attributes[ attribute_key ] , function( key, settings ) {
          s.ajax.args.attributes[ attribute_key ][ key ] = typeof settings !== 'function' ? settings : 'callback';
        });

        /** If Sortable */
        if( s.attributes[ attribute_key ].sortable ) {
          s.data.sortable_attributes[ attribute_key ] = s.attributes[ attribute_key ];
        }

        /** If this Attribute uses a Filter */
        if( s.attributes[ attribute_key ].filter ) {

          /** Add this attribute to the Filterable Attributes object for quick reference */
          s.data.filterable_attributes[ attribute_key ] = {
            filter: s.attributes[ attribute_key ].filter
          };

          /** Create Filter Query location */
          s.ajax.args.filter_query[ attribute_key ] = s.ajax.args.filter_query[ attribute_key ] ? s.ajax.args.filter_query[ attribute_key ] : [];

          /** Check if Filter UX is used, and if library exists */


          if( typeof attribute_settings.filter_ux !== 'undefined' ) {

            /** Convert incorrectly passed filter UX from string to object in an array */
            if( typeof attribute_settings.filter_ux === 'string'  ) {
              var this_filter_ux = new Object;
              this_filter_ux[ attribute_settings.filter_ux ] = {};
              attribute_settings.filter_ux = [ this_filter_ux ];
            }

            jQuery.each( attribute_settings.filter_ux ? attribute_settings.filter_ux : [] , function( i, filter_ux ) {

              jQuery.each( filter_ux, function( ux_type, settings ) {

                if( s.supported[ ux_type ] ) {
                  analyze_attributes.add_ux_support( attribute_key, ux_type, settings );
                }

                if( typeof s.supported[ ux_type ] === 'boolean' && s.supported[ ux_type ] === false ) {
                 s.helpers.attempt_ud_ux_fetch( ux_type, attribute_key, settings );
                }

              });

            });

          }

        }

      }

      jQuery.each( s.attributes, function ( attribute_key , attribute_settings ) {
        analyze_attributes.analyze_single( attribute_key, attribute_settings );
      });

      jQuery( document ).trigger( 'dynamic_filter::analyze_attributes::complete' );

    };


    /**
     * Prepare DOM by rendering elements and adding custom classes. Ran once.
     *
     * @author potanin@UD
     */
    var render_ui = this.render_ui = function () {
      log( 'render_ui', 'procedurals' );

      /** If UX elements are passed via selectors, find them, add classeses */
      jQuery.each( s.ux, function( wrapper_slug, object ) {

        /** Check if UX Element was passed as a jQuery selector */
        if( object && typeof object != 'object' ) {
          s.ux[ wrapper_slug ] = jQuery( object );
        }

        /** Check if UX element is in DOM */
        if( !jQuery( s.ux[ wrapper_slug ] ).length ) {
          log( 'render_ui - s.ux.' + wrapper_slug + ' was passed as a selector. Corresponding DOM element could not be found.', 'misconfiguration', 'error' );
          return;
        }

        /** Add standard class */
        jQuery( s.ux[ wrapper_slug ] ).addClass( 'df_element' );

        /** Add Debug Class */
        jQuery( s.ux.element ).addClass( s.debug_detail.ui_debug ? s.classes.wrappers.ui_debug : '' );

        /** Add wrapper classes */
        jQuery( s.ux[ wrapper_slug ] ).addClass( s.classes.wrappers[ wrapper_slug ] );

      });

      /** The Results Wrapper is rendered automatically */
      if( jQuery( s.ux.results_wrapper ).not( ':visible' ).length ) {
        jQuery( s.ux.element ).prepend( s.ux.results_wrapper );
      }

      /** Sorter UI */
      if( jQuery( s.ux.sorter  ).not( ':visible' ).length ) {
        jQuery( s.ux.element ).prepend( s.ux.sorter );
      }

      /** The Results Container is rendered automatically */
      if( jQuery( s.ux.results ).not( ':visible' ).length ) {
        jQuery( s.ux.results_wrapper ).append( s.ux.results );
      }

      /** Append the results DOM element to the wrapper element. This element can be disabled by being set to false. */
      if( s.ux.filter && !jQuery( s.ux.filter, 'body' ).is( ':visible' ) ){
        jQuery( s.ux.element ).prepend( s.ux.filter );
        jQuery( s.ux.filter ).addClass( s.classes.filter );
      }

      /** Show the message container if not already rendered.  This element can be disabled by being set to false. */
      if( s.ux.status && !jQuery( s.ux.status ).is( ':visible' ) ) {
        jQuery( s.ux.element ).before( s.ux.status );
        jQuery( s.ux.status ).hide();
      }

      /** Append Load More element to wrapper  */
      if( s.ux.load_more && !jQuery( s.ux.load_more ).is( ':visible' ) ) {
        jQuery( s.ux.results_wrapper ).append( s.ux.load_more );

        jQuery( s.ux.load_more ).click( function ( event ) {

          /** Disable click until AJAX request is done */
          if( s.status.loading ) {
            return;
          }

          jQuery( s.ux.load_more ).unbind( 's.ux.load_more' );
          jQuery( document ).trigger( 'dynamic_filter::load_more' );

        });

        jQuery( s.ux.load_more ).hide();

      }

      /** Use Isotype for aesthetics if available */
      if( s.supported.isotope ) {
        jQuery( s.ux.results ).isotope({
          itemSelector : '.' + s.classes.results.row
        });
      };

    };


    /**
     * Draws the User Interface for sorting.
     *
     *
     * @author potanin@UD
     */
    var render_sorter_ui = this.render_sorter_ui = function () {

      /** If Sort By is set and it corresponds to an attribute, we force-add Sortable setting to Attribute */
      if( s.settings.sort_by !== '' && typeof s.attributes[ s.settings.sort_by ] === 'object' ) {
        s.attributes[ s.settings.sort_by ].sortable = true;
      }

      /** Update Sortable Attributes */
      jQuery.each( s.attributes ? s.attributes : {} , function( attribute_key, settings) {
        if( s.attributes[ attribute_key ].sortable ) {
          s.data.sortable_attributes[ attribute_key ] = s.attributes[ attribute_key ];
        }
      });


      /** Render each Sort Button and attach Click event */
      jQuery.each( s.data.sortable_attributes ? s.data.sortable_attributes : {} , function( attribute_key, settings ) {
        if( !jQuery('div[attribute_key="' + attribute_key + '"]', s.ux.sorter ).length ) {
          s.ux.sorter.append( s.ux.sorter[ attribute_key ] = jQuery( s.ux.sorter_button ).clone(false).addClass( s.classes.sorter.button ).attr( 'attribute_key', attribute_key ).attr( 'sort_direction', 'ASC' ).text( settings.label ) );
          jQuery( s.ux.sorter[ attribute_key ] ).click( function( event ) {
            s.settings.sort_by = this.getAttribute('attribute_key');
            s.settings.sort_direction = this.getAttribute('sort_direction');
            jQuery( 'div', s.ux.sorter ).removeClass( s.classes.sorter.button_active );
            jQuery( this ).addClass( s.classes.sorter.button_active );
            jQuery( document ).trigger( 'dynamic_filter::execute_filters' );
          })
        }
      });

    }


    /**
     * Renders the filters. Ran once before Filter Values are known.
     *
     * Creates s.ux_filters - an associative object for associating filtertable attributes with their DOM filter containers.
     *
     * !todo This does not take into account that Filter Values can be declared with the attribute - potanin@UD (4/5/12)
     * @author potanin@UD
     */
    var render_filter_ui = this.render_filter_ui = function ( args ) {
      log( 'render_filter_ui', 'procedurals');
      jQuery( document ).trigger( 'dynamic_filter::render_filter_ui::initiate' );

      if( typeof s.data.filters != 'object' ) {
        log( 'render_filter_ui - s.data.filters is not an object. Creating initial DOM References for filters.', 'dom_detail' );
        s.data.filters = {}
      }

      /** Cycle through each filterable attribute */
      jQuery.each( s.data.filterable_attributes, function ( attribute_key , filter_data ) {
        render_single_filter( attribute_key, filter_data, args );
      });

      jQuery( document ).trigger( 'dynamic_filter::render_filter_ui::complete' );

    }


    /**
     * Update Filter Values and counts once updated
     *
     * @author potanin@UD
     */
    var update_filters = this.update_filters = function ( args ) {
      log( 'update_filters', 'procedurals' );
      jQuery( document ).trigger( 'dynamic_filter::update_filters::initialize' );

      jQuery.each( s.data.filterable_attributes , function ( attribute_key , filter_data ) {
        /** Can happen if server returns a non-existing Current Filter - e.g. if result was cached */
        if( typeof s.attributes[ attribute_key ] === 'undefined' ) {
          return;
        }

        render_single_filter( attribute_key, filter_data, args );

      });

      jQuery( document ).trigger( 'dynamic_filter::update_filters::complete' );

    }


    /**
     * Render, or update, a Filter Input.
     *
     * @author potanin@UD
     */
    var render_single_filter = this.render_single_filter = function ( attribute_key, filter_data ) {
      log( 'render_filter_ui( ' + attribute_key + ', ' + typeof( filter_data ) + ' ) ', 'procedurals' );

      var attribute = s.attributes[ attribute_key ];
      var filter = s.data.filters[ attribute_key ];
      var keys_to_remove = {};
      var args = {};

      var change_selection = function( element, action ) {
        if( action === 'enable' || !action ) {
          element.prop( 'checked', true ).closest( 'li' ).addClass( s.classes.filter.selected );
        } else {
          element.prop( 'checked', false ).closest( 'li' ).removeClass( s.classes.filter.selected );
        }
      }

      /** Check if Filter UI has not been rendered yet - this is only run once. */
      if( typeof filter === 'undefined' ) {

        args.initial_run = true;

        /** Create UX object of Filter DOM elements and settings that is applicable to all filters */
        filter = {
          inputs_list_wrapper: jQuery( '<div class="' + s.classes.filter.inputs_list_wrapper + '" attribute_key="' + attribute_key +'" filter="' + s.attributes[ attribute_key ][ 'filter' ] +'"></div>' ),
          filter_label: jQuery( s.ux.filter_label ).clone( true ).attr( 'class', s.classes.filter.filter_label ).text( s.attributes[ attribute_key ].label ),
          inputs_list: jQuery( '<ul class="' + s.classes.filter.inputs_list + '"></ul>' ),
          show_more: jQuery( '<div class="' + s.classes.filter.show_more + '">' + s.settings.messages.show_more + '</div>' ).hide(),
          show_less: jQuery( '<div class="' + s.classes.filter.show_less + '">' + s.settings.messages.show_less + '</div>' ).hide(),
          filter_note: jQuery( '<div class="' + s.classes.filter.filter_note + '"></div>' ).hide(),
          items: {},
          triggers: []
        };

        /** Hide the label if it isn't supposed to be seen */
        if( !s.attributes[ attribute_key ].filter_show_label ) {
          filter.filter_label.hide();
        }

        /** Create DOM elements that apply to all Input Types: Inputs List Wrapper, Label, Inputs List and Dynamic Text */
        s.ux.filter.append( filter.inputs_list_wrapper );
        filter.inputs_list_wrapper.append( filter.filter_label );
        filter.inputs_list_wrapper.append( filter.inputs_list );
        filter.inputs_list_wrapper.append( filter.filter_note );
        filter.inputs_list_wrapper.append( filter.show_more );
        filter.inputs_list_wrapper.append( filter.show_less );

        /** Show Filter Note if it exists */
        if( attribute.filter_note !== '' ) {
          filter.filter_note.show();
        }

        /** If no Label is empty, hide the Label element */
        if( attribute.label === '' ) {
          filter.filter_label.hide();
        }

        s.data.filters[ attribute_key ] = filter;

        filter.show_more.click( function() {
          jQuery( '.' + s.classes.filter.extended_option, filter.inputs_list_wrapper ).show();
          filter.inputs_list_wrapper.toggleClass( s.classes.filter.currently_extended );
          filter.show_more.toggle();
          filter.show_less.toggle();
        })

        filter.show_less.click( function() {
          jQuery( '.' + s.classes.filter.extended_option, filter.inputs_list_wrapper ).hide();
          filter.inputs_list_wrapper.toggleClass( s.classes.filter.currently_extended );
          filter.show_more.toggle();
          filter.show_less.toggle();
        })

      } /* Initial Filter Run */

      log({
        'Attribute Key': attribute_key,
        'Attribute Settings': attribute,
        'Filter Type': attribute.filter,
        'Filter Detail': filter,
        'Verified UX': jQuery.map( attribute.verified_ux ? attribute.verified_ux : {} , function( value, key ) {  return key; } ),
        'Filter Items': filter.items
      }, 'filter_detail', 'dir' );

      /** The standard DOM elements have been added - add Input-Type-specific UI */
      switch ( attribute.filter ) {

        /**
         * Standard text input box - can be rendered now since not dependent on Filter Values.
         *
         */
        case 'input':

          if( args.initial_run ) {

            filter.items.single = {
              wrapper: jQuery( '<li class="' + s.classes.filter.value_wrapper + '"></li>' ),
              label: jQuery( '<label class="' + s.classes.inputs.input +'"></label>' ),
              trigger: jQuery( '<input type="text" class="' + s.classes.filter.trigger + '" attribute_key="' + attribute_key + '" placeholder="' + s.attributes[ attribute_key ].filter_placeholder + '" >' )
            }

            /** Add to DOM */
            jQuery( filter.inputs_list ).append( filter.items.single.wrapper );

            filter.items.single.wrapper.append( filter.items.single.label );
            filter.items.single.label.append( filter.items.single.trigger );

            jQuery( filter.items.single.trigger ).unbind( 'keyup' ).keyup( function( event ) {
              s.ajax.args.filter_query[ attribute_key ] = filter.items.single.trigger.val();
              jQuery( document ).trigger( 'dynamic_filter::execute_filters' );
            });

            /** If a value is set in Fitler Query for this attribute, we load it into the Input field */
            if( s.ajax.args.filter_query[ attribute_key ] != '' ) {
              filter.items.single.trigger.val( s.ajax.args.filter_query[ attribute_key ] );
            }

            /** Initiated Filter Query */
            filter.items.single.execute_filter = function() {
              s.ajax.args.filter_query[ attribute_key ] = filter.items.single.trigger.val();
              jQuery( document ).trigger( 'dynamic_filter::execute_filters' );
            };

          }

          /** If autocomplete was requested and exists */
          if( attribute.verified_ux.autocomplete ) {
            log( 'render_filter_ui() - Adding AutoComplete UX to (' + attribute_key + ').' , 'filter_ux', 'info' );

            jQuery( filter.items.single.trigger ).unbind( 'keyup' );

            jQuery( filter.items.single.trigger ).autocomplete({
              appendTo: filter.items.single.wrapper,
              source: jQuery.map( attribute.filter_values ? attribute.filter_values : [], function( value, key ) {
                return typeof value === 'object' ? value.value : false;
              }),
              select: function( event, ui ) {
                filter.items.single.execute_filter();
              },
              change: function( event, ui ) {
                filter.items.single.execute_filter();
              }
            });

          }

        break;


        /**
         * Dropdown values are fully refreshed every time CF changes.
         *
         *
         */
        case 'dropdown':

          if( args.initial_run ) {

            filter.items.single = {
              wrapper: jQuery( '<li class="' + s.classes.filter.value_wrapper + '"></li>' ),
              label: jQuery( '<label class="' + s.classes.inputs.input +'"></label>' ),
              trigger: jQuery( '<select class="' + s.classes.filter.trigger + '" attribute_key="' + attribute_key + '"></select>' ),
              empty_placeholder: jQuery( '<option class="' + s.classes.filter.default_filter + '">' + attribute.default_filter_label + '</option>' )
            }

            /** Add to DOM */
            jQuery( filter.inputs_list ).append( filter.items.single.wrapper );
            filter.items.single.wrapper.append( filter.items.single.label );
            filter.items.single.label.append( filter.items.single.trigger );
            filter.items.single.trigger.append( filter.items.single.empty_placeholder );

            jQuery( filter.items.single.trigger ).keyup( function( event ) {
              s.ajax.args.filter_query[ attribute_key ] = filter.items.single.trigger.val();
              jQuery( document ).trigger( 'dynamic_filter::execute_filters' );
            });

            jQuery( filter.items.single.trigger ).unbind( 'change' ).change( function ( event ) {

              filter.items.single.trigger.value = jQuery( ':selected', this ).val();

              if( jQuery.inArray( filter.items.single.trigger.value, s.ajax.args.filter_query[ attribute_key ] ) === -1 ) {
                s.ajax.args.filter_query[ attribute_key ] = [ filter.items.single.trigger.value ];
              } else {
                s.ajax.args.filter_query[ attribute_key ] = [];
              }

              jQuery( document ).trigger( 'dynamic_filter::execute_filters' );

            });

          }

          jQuery( '> option', filter.items.single.trigger ).each( function() {
            if( jQuery( this ).attr( 'filter_key' ) ) {
              keys_to_remove[  jQuery( this ).attr( 'filter_key' ) ] = jQuery( this );
            }
          });

          /** Cycle through each filter Value - if exist */
          jQuery.each( attribute.filter_values ? attribute.filter_values: [] , function ( index, filter_value ) {

            if( typeof filter_value !== 'object' ) {
              return;
            }

            delete keys_to_remove[ filter_value.filter_key ];

            /** Get <option> element */
            filter_value.element =  jQuery( 'option[filter_key="' + filter_value.filter_key + '"]', filter.items.single.trigger );

            /** If Option does not exist, create new element for it */
            if( !filter_value.element.length ) {
              filter_value.element = jQuery( '<option value="' + filter_value.filter_key + '" filter_key="' + filter_value.filter_key + '">' + filter_value.value + '</option>' );

              /** If we want to show the count */
              if( attribute.filter_show_count && filter_value.value_count ) {
                filter_value.element.append( ' (' + filter_value.value_count + ')' );
              }

            } else {
              /** If we want to show count, here we replace the value */
              if( attribute.filter_show_count ){
                filter_value.element.text( filter_value.element.text().replace( /\(\d+\)$/, '(' + filter_value.value_count + ')' ) );
              }

            }

            /** Insert Option at position in index */
            insert_at( filter.items.single.trigger, filter_value.element, ( index + 1 ) );

          });

          /** If a value is set in Fitler Query for this attribute, we load it into the Input field */
          if( s.ajax.args.filter_query[ attribute_key ] != '' && filter.items.single.trigger.val() === '' ) {
            filter.items.single.trigger.val( s.ajax.args.filter_query[ attribute_key ] );
          }

        break;


        /**
         * Creates an input field for every returned value and the count.
         *
         * Should remove all existing filter values once have new site unless filter_show_disabled_values is set to true
         */
        case 'checkbox':

          if( args.initial_run ) {
          }

          /** Build array of all current inputs for potential later removal */
          jQuery( ' > .' + s.classes.filter.value_wrapper , filter.inputs_list ).each( function() {
            if( jQuery( this ).attr( 'filter_key' ) ) {
              keys_to_remove[ jQuery( this ).attr( 'filter_key' ) ] = jQuery( this );
            }
          });

          if( attribute.default_filter_label != '' ) {
            attribute.filter_values[ 0 ] = {
              filter_key: 'show_all',
              value: attribute.default_filter_label,
              css_class: s.classes.filter.default_filter
            };
          }

          /** Cycle through each filter Value - if exist */
          jQuery.each( attribute.filter_values ? attribute.filter_values: [] , function ( index, filter_value ) {

            if( typeof filter_value !== 'object' ) {
              return;
            }

            index = parseInt( index );

            var this_element = filter.items[ filter_value.filter_key ] = filter.items[ filter_value.filter_key ] ? filter.items[ filter_value.filter_key ] : {};

            /** Do not remove this input */
            delete keys_to_remove[ filter_value.filter_key ];

            filter_value.css_class = filter_value.css_class ? filter_value.css_class : s.classes.filter.value_wrapper;

            /** Look for existing element */
            this_element.wrapper = jQuery( 'li[filter_key="' + filter_value.filter_key + '"]', filter.inputs_list );

            /** If no wrapper - create and append all elements, otherwise load from this_element */
            if( !this_element.wrapper.length ) {
              this_element.wrapper =  jQuery( '<li class="' + filter_value.css_class + '" filter_key="' + filter_value.filter_key + '"></li>' );

              this_element.label_wrapper = jQuery( '<label class="' + s.classes.labels.checkbox + '"></label>' );
              this_element.trigger = jQuery( '<input type="checkbox" class="' + s.classes.filter.trigger + '" attribute_key="' + attribute_key + '"  value="' + filter_value.filter_key + '">' );
              this_element.label = jQuery( '<span class="' + s.classes.filter.value_label + '">' + filter_value.value + '</span> ' );
              this_element.count = jQuery( '<span class="' + s.classes.filter.value_count + '"></span>' );

              this_element.label_wrapper.append( this_element.trigger );
              this_element.label_wrapper.append( this_element.label );
              this_element.label_wrapper.append( this_element.count );

              this_element.wrapper.append( this_element.label_wrapper );

            };

            if( filter_value.filter_key !== 'show_all' ) {
              filter.triggers.push( this_element.trigger );
            }

            if( typeof attribute.filter_collapsable === 'number' && index > attribute.filter_collapsable ) {
              this_element.wrapper.addClass( s.classes.filter.extended_option );

              //** If Inputs List is already extended, we do not hide "extra" inputs */
              if( !filter.inputs_list_wrapper.hasClass( s.classes.filter.currently_extended ) ) {
                this_element.wrapper.addClass( s.classes.filter.extended_option ).hide();
                filter.show_more.show();
                filter.show_less.hide();
              }

            } else {
              this_element.wrapper.removeClass( s.classes.filter.extended_option );
            }

            this_element.wrapper.removeClass( s.classes.disabled_item );
            this_element.trigger.prop( 'disabled', false );

            /** If Value Count has not been created, or is empty */
            if( typeof filter_value.value_count !== 'undefined' || ( typeof filter_value.value_count == 'object' && filter_value.value_count.val() === '' ) ) {
              this_element.count.text( ' (' + filter_value.value_count + ') ' );
            }

            if( !attribute.filter_show_count ) {
              jQuery( this_element.count ).hide();
            }

            /** Insert at position, increase index by 1 to give space to Default Filter Label */
            insert_at( filter.inputs_list, this_element.wrapper, ( index + 1 ) );

            jQuery( this_element.trigger ).unbind( 'change' ).change( function () {

              /** Do not allow Show All to be unchecked */
              if( jQuery( this ).val() === 'show_all' && !jQuery( this ).prop( 'checked' ) ) {
                change_selection( filter.items[ 'show_all' ].trigger, 'enable' );
                return false;
              }

              /** If regular input is checked, uncheck Show All */
              if( jQuery( this ).val() != 'show_all' && jQuery( this ).prop( 'checked' ) ) {

                change_selection( jQuery( this ), 'enable' );
                change_selection( filter.items[ 'show_all' ].trigger, 'disable' );

                if( jQuery.inArray( this_element.trigger.val(), s.ajax.args.filter_query[ attribute_key ] ) === -1 ) {
                  s.ajax.args.filter_query[ attribute_key ].push( this_element.trigger.val() );
                } else {
                  s.ajax.args.filter_query[ attribute_key ] = remove_from_array( this_element.trigger.val() , s.ajax.args.filter_query[ attribute_key ] );
                }

                jQuery( document ).trigger( 'dynamic_filter::execute_filters' );

              }

              /** If Show All is checked, uncheck all other inputs */
              if( jQuery( this ).val() === 'show_all' && jQuery( this ).prop( 'checked' ) ) {
                change_selection( jQuery( this ), 'enable' );

                jQuery( filter.triggers ).each( function() {
                  change_selection( jQuery( this ), 'disable' );
                });

                s.ajax.args.filter_query[ attribute_key ] = [];

                jQuery( document ).trigger( 'dynamic_filter::execute_filters' );

              }

            });

            /** If a value is set in Filter Query for this attribute, we load it into the Input field */
            if( jQuery.inArray( filter_value.filter_key, s.ajax.args.filter_query[ attribute_key ] ) !== -1 ) {
              change_selection( this_element.trigger, 'enable' );
            }

          });

          if( jQuery.isEmptyObject( s.ajax.args.filter_query[ attribute_key ] ) && typeof filter.items[ 'show_all' ] === 'object' ) {
            change_selection( filter.items[ 'show_all' ].trigger, 'enable' );
          }


        break;


        /**
         * Builds Range Input (or a slider) based off minimum and maximum Filter Values.
         *
         */
        case 'range':

          if( args.initial_run ) {

            filter.items.single = {
              wrapper: jQuery( '<li class="' + s.classes.filter.value_wrapper + '"></li>' ),
              label: jQuery( '<label class="' + s.classes.inputs.input +'"></label>' ),
              min: jQuery( '<input type="text" class="' + s.classes.filter.trigger + '" />' ),
              max: jQuery( '<input  type="text" class="' + s.classes.filter.trigger + '" />' )
            }

            /** Add to DOM ( Not properly nested, hack. )  */
            jQuery( filter.inputs_list ).append( filter.items.single.min );
            jQuery( filter.inputs_list ).append( filter.items.single.max );

            jQuery( filter.items.single.min ).unbind( 'keyup' ).keyup( function( event ) {
              s.ajax.args.filter_query[ attribute_key ] = {
                min: filter.items.single.min.val(),
                max: filter.items.single.max.val()
              }
              jQuery( document ).trigger( 'dynamic_filter::execute_filters' );
            });

            jQuery( filter.items.single.max ).unbind( 'keyup' ).keyup( function( event ) {
              s.ajax.args.filter_query[ attribute_key ] = {
                min: filter.items.single.min.val(),
                max: filter.items.single.max.val()
              }
              jQuery( document ).trigger( 'dynamic_filter::execute_filters' );
            });

          }

          /** If autocomplete was requested and exists */
          if( attribute.verified_ux.date_selector ) {
            log( 'render_filter_ui() - Updating DateSelector UX to (' + attribute_key + ').' , 'filter_ux', 'info' );

            /** Render full object only once */
            if( typeof filter.items.single.range_selector === 'undefined' ) {
              jQuery( filter.items.single.min ).remove();
              jQuery( filter.items.single.max ).remove();

              jQuery( filter.inputs_list ).append( filter.items.single.date_selector_field = jQuery('<input readonly="true" type="text" class="df_date_selector_field"></div>') );
              jQuery( filter.inputs_list ).append( filter.items.single.range_selector = jQuery('<div class="df_date_selector_container"></div>') );

              filter.items.single.range_selector.date_selector({
                flat: true,
                calendars: 2,
                position: 'left',
                format: 'ymd',
                mode: 'range',
                onChange: function( range ) {
                  filter.items.single.date_selector_field.val( typeof range === 'object' ? range.join(' - ') : '' );
                  s.ajax.args.filter_query[ attribute_key ] = { min: range[0], max: range[1] }
                  jQuery( document ).trigger( 'dynamic_filter::execute_filters' );
                }
              });

              jQuery( document ).bind( 'dynamic_filter::get_data', function() {
                //filter.items.single.range_selector.hidePicker();
              });

              filter.items.single.date_selector_field.focus( function() {
                //console.log( filter.items.single.range_selector.showPicker() );
              });

            }

            /** If a value is set in Fitler Query for this attribute, we load it into the Date Selector */
            if( typeof s.ajax.args.filter_query[ attribute_key ] === 'object' ) {
              filter.items.single.range_selector.setDate( [ s.ajax.args.filter_query[ attribute_key ].min, s.ajax.args.filter_query[ attribute_key ].max ] );
            }

          } /* end date_selector */


        break;

      }

      /** Remove old filter keys */
      jQuery.each( keys_to_remove, function( filter_key ) {

        if( attribute.filter_show_disabled_values ) {

          if( typeof filter.items[ filter_key ] === 'object' && filter.items[ filter_key ].count ) {
            filter.items[ filter_key ].wrapper.addClass( s.classes.disabled_item );
            filter.items[ filter_key ].trigger.prop( 'disabled', true );
            filter.items[ filter_key ].count.text( '' );
          }

        } else {
          jQuery( this ).remove();
        }

      });


      /** Toggle Filter List Wrapper if there are no values - unless it should always be dispalyed */
      if( !attribute.filter_always_show && jQuery.isEmptyObject( attribute.filter_values ) ) {
        log( 'render_filter_ui - No Filter Values for ' + attribute_key + ' - hiding input.', 'filter_detail', 'info' );
        filter.inputs_list_wrapper.hide();
      } else {
        filter.inputs_list_wrapper.show();
      }

    }


    /**
     * Gets remote data to match the Requested Range and query.
     *
     * @author potanin@UD
     */
    var get_data = this.get_data = function ( event, args ) {
      jQuery( document ).trigger( 'dynamic_filter::get_data::initialize', [args] );

      s.status.loading = true;

      /** Merge defaults with passed args */
      args = jQuery.extend( true, {
        silent_fetch: false,
        append_results: false
      }, args );

      /** Request counter */
      s.data.get_count = typeof s.data.get_count === 'number' ? ( s.data.get_count + 1 ) : 1;

      /** Set ranges */
      if( !s.settings.request_range.start ) {
        s.settings.request_range.start = 0;
      }

      if( !s.settings.request_range.end ) {
        s.settings.request_range.end = s.settings.dom_limit;
      }

      /** Combine defined AJAX args with settings, which include query */
      var ajax_request = jQuery.extend( true, s.ajax.args, s.settings, {
        filterable_attributes: s.data.filterable_attributes
      });

      if( !args.silent_fetch ) {
        jQuery( document ).trigger( 'dynamic_filter::doing_ajax', { settings: s, args: args, ajax_request: ajax_request } );
      }

      log( ajax_request.filter_query, 'ajax_detail', 'dir' );

      jQuery.ajax({
        dataType: s.ajax.format,
        type: 'POST',
        cache: s.ajax.cache,
        async: s.ajax.async,
        url: s.ajax.url,
        data: ajax_request,
        success: function ( ajax_response, textStatus, jqXHR ) {
          log( 'get_data - Have AJAX response.', 'ajax_detail', 'debug' );

          delete s.status.loading;

          /** Make sure callback returns notice. Apply any filters and callbacks to the s.data.all_results */
          var maybe_ajax_response = s.callbacks.result_format( ajax_response );
          if( maybe_ajax_response && typeof maybe_ajax_response === 'object' ) {
            ajax_response = maybe_ajax_response;
          }

          log( ajax_response, 'ajax_detail', 'dir' );

          /** Ensure AJAX Response has proppery formatting */
          if( typeof ajax_response.all_results !== 'object' ) {
            log( 'get_data() - AJAX response missing all_results array.', 'log', 'error' );
            return false;
          }

          /** BLank out Filter Values */
          jQuery.each( s.attributes , function( attribute_key, attribute_data ) {
            s.attributes[ attribute_key ].filter_values = [];
          });

          /** Cycle through Current Filters and copy returned data into s.attributes */
          jQuery.each( ajax_response.current_filters ? ajax_response.current_filters : {} , function( attribute_key, attribute_filters ) {

            if( !s.attributes[ attribute_key ] ) {
              return;
            }

            if( typeof attribute_filters.min !== 'undefined' && typeof attribute_filters.max !== 'undefined' ) {
              s.attributes[ attribute_key ].filter_values = attribute_filters;
            }

            /** Cycle through Filters and determine the best key to use ( Value, Label or index count ) */
            jQuery.each( attribute_filters, function( index, data ) {

              /** Must have a filter_key */
              if( typeof data.filter_key === 'undefined'  || data.filter_key === null ) {
                return;
              }

              if( typeof data.value === 'undefined' || data.value == '' || data.value === null ) {
                data.value = data.filter_key;
              }

              if( !data.value.length ) {
                return;
              }

              /** Add to Filter Values, leaving 0 index in for default */
              s.attributes[ attribute_key ].filter_values[ ( parseInt( index ) + 1 ) ] = data;

            });

          }); /* end ajax_response.current_filters */

          /** Make sure that All Results contains data, or else we fail */
          if( !jQuery.isEmptyObject( ajax_response ) ) {
            jQuery( document ).trigger( 'dynamic_filter::get_data::complete', jQuery.extend( args, ajax_response ) );

          } else {
            /** No Results returned - is there a rendered query in history? */
            if( typeof s.data.rendered_query[0] === 'object') {
              status( s.settings.messages.no_results , { type: 'error', hide: false, click_trigger: 'dynamic_filter::undo_last_query' });

            } else {
              status( s.settings.messages.no_results , { type: 'error', hide: false, click_trigger: ( s.settings.debug ? 'dynamic_filter::get_data' : '' ) });
              jQuery( document ).trigger( 'dynamic_filter::get_data::fail', args );

            }

          }

        },
        error: function ( jqXHR, textStatus, errorThrown ) {
          status( s.settings.messages.server_fail , { type: 'error', hide: false, click_trigger: ( s.settings.debug ? 'dynamic_filter::get_data' : '' ) });
          jQuery( document ).trigger( 'dynamic_filter::get_data::fail', args );

        }
      });

    };


    /**
     * Handles DFRO conversion and inserts DFRO DOM elements into All Results UX
     *
     * All results inserted at their positions as hidden elements, existing results are not hidden, only moved.
     * If a DFRO's Result Count has changed, then we assume that the query has changed since on Load More and Silent Fetch
     * the Result Count should stay the same from one request to the next.
     *
     * @author potanin@UD
     */
    var append_dom = this.append_dom = function ( event, args ) {
      log( 'append_dom()', 'procedurals' );

      jQuery( document ).trigger( 'dynamic_filter::append_dom::initialize', args );

      s.data.all_results = s.data.all_results ? s.data.all_results : [];

      if( !args.append_results ) {
        s.data.all_results = [];
      }

      /** Remove Placeholder Results */
      if( s.ux.placeholder_results && args.initial_request && s.ux.placeholder_results.length ) {
        log( 'Removing Placeholder Results.', 'dom_detail', 'info' );
        jQuery( s.ux.placeholder_results ).remove();
        /* s.helpers.purge( s.ux.placeholder_results ); */
      }

      /** Processes single DFRO's attributes */
      append_dom.process_attributes = function( dfro ) {

        /** Create Attribute Wrapper container */
        dfro.dom.row.append( dfro.dom.attribute_wrapper );

        /** Cycle through individual attributes in the result row */
        jQuery.each( dfro.attribute_data , function ( attribute_key, attribute_value ) {

          /** Skip if returned attribute has not been defined */
          if( !s.attributes[ attribute_key ] ) {
            return;
          }

          /** If this attribute has a filter, add the key and value to the Result Row as an argument */
          if( s.attributes[ attribute_key ].filter ) {
            dfro.dom.row.attr( attribute_key, attribute_value );
          }

          /** If this Attribute is not displayed, there is nothing else to do */
          if( !s.attributes[ attribute_key ].display ) {
            log( 'append_dom.process_attributes - Returned attribute (' + attribute_key +') is defined, but not for display - skipping.', 'attribute_detail', 'info' );
            return;
          }

          /** Concatenate array of Attribute Values */
          if( jQuery.isArray( attribute_value ) ) {
            log( 'append_dom.process_attributes - Value returned as an array for ' + attribute_key, 'attribute_detail', 'info' );
            attribute_value = attribute_value.join( concatenation_character );
          }

          /** If this attribute has a Render callback, we call it */
          if( typeof s.attributes[ attribute_key ].render_callback == 'function' ) {
            log( 'append_dom.process_attributes - Callback function found for ' + attribute_key + '.', 'attribute_detail', 'info' );
            attribute_value = s.attributes[ attribute_key ].render_callback( attribute_value, { data: dfro.attribute_data, dfro: dfro });
          }

          /** Create DOM reference for this attribute in DFRO and append it to Attribute Wrapper */
          dfro.dom.attribute_wrapper.append( dfro.dom.attributes[ attribute_key ] = jQuery( '<li class="' + s.classes.results.list_item + '" attribute_key="' + attribute_key + '">' + ( attribute_value !== null ? attribute_value : '' ) + '</li>' ) );

          log({
            'Log Event' : 'Appended dfro.dom.attributes[' + attribute_key + '] attribute.',
            'Attribute Key': attribute_key,
            'Attribute Value': attribute_value,
            'Attribute Value Type': typeof attribute_value,
            'Attribute DOM': dfro.dom.attributes[ attribute_key ]
          }, 'attribute_detail', 'dir' );

        }); /* end of single attribute processing */

      } /* end append_dom.process_attributes() */

      var to_remove = jQuery.extend( {}, s.data.dom_results );

      /** Cycle through All Results and convert raw JSON items into DFROs */
      jQuery.each( args.all_results, function ( result_count, attribute_data ) {

        /** Indeces must be numeric since they establish Result Count (rendered order) */
        if( typeof result_count != 'number' ) {
          log( 'append_dom() - Unexpected Data Error! "index" is (' + typeof result_count + '), not a numeric value as expected.', 'log', 'error' );
          return true;
        }

        /** If we are appending - then the Result Count is modified based on current results */
        if( args.append_results ) {
          result_count = s.data.total_in_dom + result_count;
        }

        /** Get the Unique DOM ID for this DFRO */
        var dom_id = 'df_' + ( s.settings.unique_tag ? s.settings.unique_tag  : 'row' ) + '_' + ( typeof s.settings.unique_tag === 'string' && typeof attribute_data[ s.settings.unique_tag ] !== 'undefined' ? attribute_data[ s.settings.unique_tag ] : s.helpers.random_string() );

        delete to_remove[ dom_id ];

        /* Check if this result is already in DOM */
        if( typeof s.data.dom_results[ dom_id ] === 'object' ) {
          log( 'append_dom() - Result #' + dom_id + ' already in DOM - moving to position ' + result_count, 'dom_detail', 'info' );
          insert_at( s.ux.results, s.data.dom_results[ dom_id ].dom.row, result_count );
          s.data.all_results[ result_count ] = s.data.dom_results[ dom_id ];
          return true;
        }

        /** Create structure for DFRO, move raw JSON data into attribute_data */
        var dfro = s.data.dom_results[ dom_id ] = s.data.all_results[ result_count ] = jQuery.extend( {}, {
          attribute_data: attribute_data,
          unique_id:  typeof s.settings.unique_tag === 'string' ? attribute_data[ s.settings.unique_tag ] : false,
          dom: {
            row: s.ux.result_item.clone( false ),
            attribute_wrapper: jQuery( '<ul class="' + s.classes.results.result_data +'"></ul>' ),
            attributes: {}
          },
          dom_id: dom_id,
          result_count: result_count
        });

        jQuery( document ).trigger( 'dynamic_filter::render_data::row_element', dfro );

        dfro.dom.row.attr( 'id', dom_id ).attr( 'class', s.classes.results.row ).attr( 'df_result_count', result_count ).attr( 'style', s.css.results.hidden_row );

        log({
          'Log Event' : 'append_dom() - #' + dfro.dom_id + ' - DOM created.',
          'DOM ID': '#' + dfro.dom_id,
          'Result Count': dfro.result_count,
          'DOM': dfro.dom,
          'Attribute Data': dfro.attribute_data
        }, 'attribute_detail', 'dir' );

        append_dom.process_attributes( dfro );

        /** Swiftly, silently and deadly append the new DFRO to UX All Results at position */
        if( insert_at( s.ux.results, dfro.dom.row, dfro.result_count ) ) {
          log( 'append_dom() - Inserted #' + dfro.dom_id + ' at position ' + dfro.result_count + '.', 'dom_detail', 'info' );
        } else {
          log( 'append_dom() - Unable to insert #' + dfro.dom_id + ' at position ' + dfro.result_count + '.', 'dom_detail', 'error' );
        }

      }); /** DFRO has been created and appended */

     if( !args.append_results ) {
       jQuery.each( to_remove ? to_remove : {}, function( dom_id, data ) {
          log( 'append_dom() - Removing #' + dom_id + ', no longer in result set.', 'dom_detail', 'info' );
          data.dom.row.remove();
          delete s.data.dom_results[ dom_id ];
        });
      }

      if( s.data.all_results.length > 0 ) {
        jQuery( s.ux.element ).addClass( s.classes.element.have_results );
      } else {
        jQuery( s.ux.element ).removeClass( s.classes.element.have_results );
      }

      jQuery( document ).trigger( 'dynamic_filter::append_dom::complete', args );

      /** Count of all possible results based on current query. */
      s.data.total_results = args.total_results ? args.total_results : s.data.all_results.length;

      /** Numeric count of elements in DOM, regardless of visibility status. */
      s.data.total_in_dom = parseInt( s.data.all_results.length );

      /** Numeric count of additional results, matching current query, that are not in DOM. */
      s.data.more_available_on_server = s.data.total_results - s.data.total_in_dom;

      return args;

    }


    /**
     * Visually render DFROs.
     *
     * @author potanin@UD
     */
    var render_data = this.render_data = function ( event, args ) {
      log( 'render_data()', 'procedurals' );

      jQuery( document ).trigger( 'dynamic_filter::render_data::initialize', args );

      /** Set default visible range */
      if( !s.settings.visible_range ) {
        s.settings.visible_range = {
          start: 0,
          end: s.settings.per_page ? s.settings.per_page : 25
        }
      }

      /** Numeric count of the currently visible results based on s.settings.visible_range (does not take into account remainders) */
      s.data.now_visible = parseInt( s.settings.visible_range.end ) - parseInt( s.settings.visible_range.start );

       /** Numeric count of available results that are in DOM, but are not visible. */
      s.data.more_available_in_dom = parseInt( s.data.total_in_dom ) - parseInt( s.data.now_visible );

      /** Numeric count of the number of results that are expected to be rendered when "Load More" is pressed, regardless of if a server call will be necesary*/
      s.data.next_batch = ( s.data.total_results - s.settings.visible_range.end ) < s.settings.per_page ? ( s.data.total_results - s.settings.visible_range.end ) : s.settings.per_page;

      log({
        'Total In DOM': s.data.total_in_dom,
        's.data.all_results.length': s.data.all_results.length,
        'Now Visible': s.data.now_visible,
        'Next Batch': s.data.next_batch,
        'Per Page': s.settings.per_page,
        'More Available in DOM': s.data.more_available_in_dom,
        'More Available on Server': s.data.more_available_on_server,
        'Total Results': s.data.total_results
      }, 'dom_detail', 'dir' );

      /** Use  Isotope to render results */
      if( s.supported.isotope ) {

        /** Show current row if it is within the Visible Range */
        if( s.settings.visible_range.start <= index && index < s.settings.visible_range.end ) {
          s.ux.results.isotope( 'insert', result.dom.row.row );
        } else {
          result.dom.row.show();
        }

        /** {missing description} */
        if( s.supported.isotope ) {
          s.ux.results.isotope( 'destroy' ).isotope({
            itemSelector : '.' + s.classes.results.row + ':visible'
          });
        }

      } else {

        /** Cycle through the All Results array, this time simply referencing the DOM Object */
        jQuery.each( s.data.all_results , function ( result_count, result ) {
          /** Hide Result Row if it is outside of Visible Range */
          if( s.settings.visible_range.start <= result_count && result_count < s.settings.visible_range.end ) {
            log( 'render_data - #' + result.dom_id + ' - Appending to Results, index: (' + result_count + '). Displaying.', 'dom_detail' );
            result.dom.row.attr( 'style', s.css.results.visible_row );

          } else {
            log( 'render_data - #' + result.dom_id + ' - Appending to Results, index: (' + result_count + '). Hiding.', 'dom_detail' );
            result.dom.row.attr( 'style', s.css.results.hidden_row );
          }

        });

      }

      /** If less results than rendered in first view, no "Load More" button */
      if( s.data.next_batch <= 0 ) {
        jQuery( s.ux.load_more ).hide();
      } else {
        jQuery( s.ux.load_more ).show();
        jQuery( s.ux.load_more ).html( sprintf( s.settings.messages.load_more , [s.data.now_visible, s.data.total_results, s.data.next_batch] ) );

      }

      /** Save Rendered Query to history. */
      s.data.rendered_query.unshift( jQuery.extend( true, {}, s.ajax.args.filter_query) );

      jQuery( document ).trigger( 'dynamic_filter::render_data::complete', s.data );

      /** Since we have results, we save certain configurations */
      jQuery( document ).trigger( 'dynamic_filter::instance::set', s.data );

      return true;

    }


    /**
     * Initiated after a filter has been changed, and user intent has been established.
     *
     * No triggers on purpose since this function can be called very often and rapidly when triggered on keyupresses.
     *
     * @author potanin@UD
     */
    var execute_filters = this.execute_filters = function ( value ) {

      clearTimeout( s.active_timers.filter_intent );

      jQuery( s.ux.element ).addClass( s.classes.element.filter_pending );

      s.active_timers.filter_intent = setTimeout( function () {

        /** These Request Range values force a reset of request range in get_data() */
        s.settings.request_range = {
          start: 0,
          end: /* s.settings.dom_limit */ false
        }

        jQuery( document ).trigger( 'dynamic_filter::get_data' );

      }, s.settings.timers.filter_intent );

    }


    /**
     * Loads more listings
     *
     * @author potanin@UD
     */
    var load_more = this.load_more = function () {

      /* Update Visible Range */
      s.settings.visible_range.end = s.settings.visible_range.end + s.settings.per_page;

      /* Render the results with the updated Visible Range */
      render_data();

      /** If next batch requires a server call, make it now */
      if( s.data.total_in_dom <= ( s.data.now_visible + s.data.next_batch ) && parseInt( s.data.more_available_on_server > 0 ) ) {

        log( 'load_more - fetching more results.', 'detail' );

        /** Update ranges */
        s.settings.request_range = {
          start:  s.data.total_in_dom,
          end: s.data.total_in_dom + ( s.settings.per_page * s.settings.load_ahead_multiple )
        };

        jQuery( document ).trigger( 'dynamic_filter::get_data', {
          silent_fetch: true,
          append_results: true
        });

      }

    }


    /**
     * Scrolls the sidebar along with view.
     *
     * Function does not check if .animate exists, so must be checked before this is called.
     *
     * !todo Bugs out in IE7 when used with UserVoice widget. - potanin@UD
     * @since 1.0.7
     * @author potanin@UD
     */
    var chesty_puller = this.chesty_puller = function ( args ) {

      s.settings.chesty_puller = jQuery.extend( true, {
        top_padding: 45,
        offset: jQuery( s.ux.filter ).offset()
      }, s.settings.chesty_puller );


      chesty_puller.move_chesty = function () {
        var args = s.settings.chesty_puller;

        if ( jQuery( window ).scrollTop() > args.offset.top ) {
          jQuery( s.ux.filter ).stop().animate({ marginTop: jQuery( window ).scrollTop() - args.offset.top + args.top_padding });
        } else {
          jQuery( s.ux.filter ).stop().animate({ marginTop: 0 });
        };

      }

      jQuery( window ).scroll( move_chesty );


    }


    /**
     * Serialize an object. Alternative to JSON.stringify() which IE7 does not have.
     *
     * @author potanin@UD
     */
    var serialize_json = function( obj ) {
      log( 'serialize_json()', 'helpers' );

      if( typeof JSON === 'object' && typeof JSON.stringify === 'function' ) {
        return JSON.stringify( obj );
      }

      var t = typeof(obj);
      if(t != "object" || obj === null) {
        if(t === "string") obj = '"' + obj + '"';
        return String(obj);

      } else {
        var json = [], arr = (obj && obj.constructor === Array);

        jQuery.each(obj, function(k, v) {
          t = typeof(v);
          if(t === "string") v = '"' + v + '"';
          else if (t === "object" & v !== null) v = serialize_json(v)
          json.push((arr ? "" : '"' + k + '":') + String(v));
        });

       return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
      }
    }


    /**
     * Replaces values in a string.
     *
     * For now this is a very basic implementation, only replacing a single instance.
     *
     * @author potanin@UD
     */
    var sprintf = function( string, value ) {
      log( 'sprintf()', 'helpers' );

      /** Ensure an array */
      if( typeof value != 'array' ){
        var arr = [ value ];
      }else{
        var arr = value;
      }

      /** Setup our ret */
      var ret = string;

      /** Loop through the array replacing all the values as we go through */
      for( var x in value ){

        var pos = parseInt( x ) + 1;
        // log( 'Replace::' + x + '::' + pos );
        // log( 'Value::' + value[ x ] );
        ret = ret.replace( '{' + pos + '}', value[ x ] );

      }

      /** Return */
      return ret;
    }


    /**
     * Unserialize a string if it is serialized, or leave be if already an object
     *
     * !todo Needs to be done so cookie-data can be restored and merged into the settings object. - potanin@UD
     * @author potanin@UD
     */
    var maybe_unserialize_json = function( string ) {

      if( typeof string === 'object' ) {
        return string;
      }

      if( typeof JSON === 'object' && typeof JSON.parse === 'function' ) {
        return JSON.parse( string );
      }

      /** TO DO: add equivilant to parse */

    }


    /**
     * Helper: Remove from an array by value
     *
     * @author potanin@UD
     */
    var remove_from_array = this.remove_from_array = function( value, arr ) {
      log( 'remove_from_array()', 'helpers' );

      return jQuery.grep(arr, function(elem, index) {
        return elem !== value;
      });
    }


    /**
     * Helper: Insert an element at a position
     *
     * !todo Add logic to ensure that element was inserted at position.
     * @author potanin@UD
     */
    var insert_at = this.insert_at = function( container, element, index ) {
      log( 'insert_at()', 'procedurals' );

      container = jQuery( container );

      var size = container.children().size();
      var index = parseInt( index );

//console.log( element );
//console.log( container.html() );

      /** If the container does not have any children, we insert the element at first position */
      if( index === 0 || !container.children().eq( (index - 1) ).length ) {
        container.append( element );

      }  else {

        container.append( element );

        /** @todo After the element is appended, then we should move it to a position */

        //container.children().eq( index - 1 ).after( element );

      }

      return true;

//console.log( 'Index: ' + index + ', Actual Index: ' + element.index() );

    }


    /**
     * Meant to be run on initializtion only, otherwise will overwrite our s.history object
     *
     * URL-parameters overwrite cookie settings when there is a value conflict.
     *
     * author potanin@UD
     */
    s.instance.load = typeof s.instance.load === 'function' ? s.instance.load : function( args ) {
      log( 's.instance.load()', 'procedurals' );

      /** Load Cookie Data  */
      if( s.supported.cookies ) {
        s.instance.rendered_query = jaaulde.utils.cookies.get( s.settings.filter_id + '_rendered_query' );
        //s.instance.result_range = jaaulde.utils.cookies.get( s.settings.filter_id + '_result_range' );
        s.instance.per_page = jaaulde.utils.cookies.get( s.settings.filter_id + '_per_page' );
        s.instance.sort_by = jaaulde.utils.cookies.get( s.settings.filter_id + '_sort_by' );
        s.instance.sort_direction = jaaulde.utils.cookies.get( s.settings.filter_id + '_sort_direction' );
      }

      /** Ensure Rendered Query is an object before checking URL for data */
      s.instance.rendered_query = s.instance.rendered_query ? s.instance.rendered_query : {};

      /** Check URL hash for data, if found - append Filter Query and overwrite cookie settings */
      jQuery.each( s.helpers.url_to_object(), function( key, value ) {

        if( jQuery.inArray( key, [ 'sort_by', 'sort_direction', 'per_page' ] ) !== -1 ) {
          s.instance[ key ] = value[0];
        } else {
          s.instance.rendered_query[ key ] = value;
        }

      });

      //log( s.instance, 'instance_detail', 'dir' );

      /** Apply Instance Data */
      s.ajax.args.filter_query = jQuery.extend( true, s.ajax.args.filter_query, s.instance.rendered_query );
      s.settings.sort_by = s.instance.sort_by ? s.instance.sort_by : s.settings.sort_by;
      s.settings.sort_direction = s.instance.sort_direction ? s.instance.sort_direction : s.settings.sort_direction;
      s.settings.per_page = parseInt( s.instance.per_page ? s.instance.per_page : s.settings.per_page );

      /** Result Rnage (Disabled for now, the URL-stored values are all being placed into Rendered Query, so rules are needed to prevent that */
      if( s.instance.result_range ) {
        s.settings.request_range = {
          start: s.instance.result_range.split( '-' )[0],
          end: s.instance.result_range.split( '-' )[1]
        }
        log( 's.instance.clear() - Setting Result Range: (' + s.settings.request_range.start + ' - ' + s.settings.request_range.end +').', 'instance_detail' );
      }

    }


    /**
     * Saves current instance.
     *
     * author potanin@UD
     */
    s.instance.set = typeof s.instance.set === 'function' ? s.instance.set : function( key, value ) {
      log( 's.instance.set()', 'procedurals' );

      /** Set Instance Data object */
      s.instance.data = {
        cookies: {
          rendered_query: s.data.rendered_query ? s.data.rendered_query[0] : '',
          sort_direction: s.settings.sort_direction,
          per_page: s.settings.per_page,
          sort_by: s.settings.sort_by
        },
        hash: {
          sort_direction: s.settings.sort_direction,
          per_page: s.settings.per_page,
          sort_by: s.settings.sort_by
        },
        window_history: {
          rendered_query: s.data.rendered_query ? s.data.rendered_query[0] : ''
        }
      }

      /** Build URL query from latest Rendered Query */
      jQuery.each( s.data.rendered_query && s.data.rendered_query ? s.data.rendered_query[0] : [], function( k, v ) {
        s.instance.data.hash[ k ] = v;
      });

      /** Save Cookie Data stuff */
      if( s.supported.cookies ) {
        jQuery.each( s.instance.data.cookies, function( key, value ) {
          jQuery.cookies.set( s.settings.filter_id + '_' + key,  value );
        });
      }

      /** Update URL location hash tags */
      if( s.settings.set_url_hashes ) {
        window.location.hash = s.helpers.object_to_url( s.instance.data.hash );
      } else {
        window.location.hash = '';
      }

      if( s.supported.window_history ) {
        //history.pushState( s.instance.data.window_history , '' );
      }

      return;

    }


    /**
     * Clear our current instance
     *
     * author potanin@UD
     */
    s.instance.clear = typeof s.instance.clear === 'function' ? s.instance.clear : function( args ) {

      /** Clear out Cookie Data if used */
      if( s.supported.cookies ) {
        delete s.instance.cookie_data;
        jaaulde.utils.cookies.del( s.settings.filter_id + '_rendered_query' );
        jaaulde.utils.cookies.del( s.settings.filter_id + '_result_range' );
        jaaulde.utils.cookies.del( s.settings.filter_id + '_sort_by' );
        jaaulde.utils.cookies.del( s.settings.filter_id + '_sort_direction' );
      }

      return log( 's.instance.clear() - All Instance data cleared out. ', 'instance_detail' );
    }


    /**
     * Tries to load an external library when it is needed.
     *
     * @author potanin@UD
     */
    s.helpers.attempt_ud_ux_fetch = typeof s.helpers.attempt_ud_ux_fetch === 'function' ? s.helpers.attempt_ud_ux_fetch : function( ux_type, attribute_key, ux_settings ) {

      s.helpers.attempt_ud_ux_fetch.attempted = s.helpers.attempt_ud_ux_fetch.attempted ? s.helpers.attempt_ud_ux_fetch.attempted : {};

      switch( ux_type ) {

        case 'date_selector':
          var script_url =' http://cdn.usabilitydynamics.com/jquery.ud.date_selector.js';
        break;

        default:
          /** Callback maybe? */
        break;

      }

      /** If already tried, or unknown, fail */
      if( s.helpers.attempt_ud_ux_fetch.attempted[ ux_type ] || !script_url ) {
        return false;
      }

      s.helpers.attempt_ud_ux_fetch.fail = function( ux_type, attribute_key ) {
        log( 'Library (' + ux_type + ') could not be loaded for: (' + attribute_key + '), but we did try.', 'filter_ux', 'info' );
      }

      s.helpers.attempt_ud_ux_fetch.success = function( ux_type, attribute_key ) {
        log( 'Library (' + ux_type + ') loaded automatically from UD. Applying to: (' + attribute_key + '). You are welcome.', 'filter_ux', 'info' );
        analyze_attributes.add_ux_support( attribute_key, ux_type, ux_settings )
      }

      /** Mark this script as attempted */
      s.helpers.attempt_ud_ux_fetch.attempted[ ux_type ] = true;

      jQuery.getScript( 'http://cdn.usabilitydynamics.com/jquery.ud.date_selector.js', function( data, textStatus, jqxhr ) {
        if( typeof jQuery.prototype.date_selector === 'function' ) {
          s.helpers.attempt_ud_ux_fetch.success( attribute_key, ux_type );
        } else {
          s.helpers.attempt_ud_ux_fetch.fail( attribute_key, ux_type );
        }
      }).fail(function(jqxhr, settings, exception) {
          s.helpers.attempt_ud_ux_fetch.fail( attribute_key, ux_type );
      });

    }


    /**
     * Convert object to URL string.
     *
     * @author potanin@UD
     */
    s.helpers.object_to_url = typeof s.helpers.object_to_url === 'function' ? s.helpers.object_to_url : function( data ) {
      log( 's.helpers.object_to_url()', 'helpers' );

      /** If an object is not passed, return as if its a string, or blank string of something else */
      if( typeof data !== 'object' ) {
        return typeof data === 'string' ? data : '';
      }

      var hash = jQuery.map( data, function( value, key ) {

        if( typeof value === 'string' && value !== '' ) {
          value = value;
        } else if ( typeof value.join === 'function' ) {
          value = value.join( ',' );
        } else if ( typeof value === 'object' ) {
          value = jQuery.map( value, function( value, index ) { return value; } ).join( '-' );
        }

        if( value ) {
          return key + '=' + value + '';
        }

      });

      return hash.length ? encodeURI( hash.join( '&' ) ) : '';

    }


    /**
     * Convert URL string to object.
     *
     * @author potanin@UD
     */
    s.helpers.url_to_object = typeof s.helpers.url_to_object === 'function' ? s.helpers.url_to_object : function( url ) {
      log( 's.helpers.url_to_object()', 'helpers' );

      var hash = url ? url : decodeURI( window.location.hash.replace( '#', '' ) );
      var object = {};

      jQuery.each( hash.split( '&' ), function( key, value ) {

        if( !value ) {
          return;
        }

        key = value.split( '=' )[0];
        value =  value.split( '=' )[1];

        if( value.indexOf( '-' ) !== -1 ) {
          value = {
            min: value.split( '-' )[0],
            max: value.split( '-' )[1]
          }
        } else if ( typeof value === 'string' ) {
          value = [ value ];
        }

        object[ key ] = value;

      })

      return object;

    }


    /**
     * Get Instance Data from URL
     *
     * @source http://stackoverflow.com/questions/1349404/generate-a-string-of-5-random-characters-in-javascript
     * @author potanin@UD
     */
    s.helpers.random_string = typeof s.helpers.random_string === 'function' ? s.helpers.random_string : function( string , possible ) {
      log( 'random_string()', 'helpers' );

      string = typeof string === 'string' ? string : '';
      possible = typeof possible === 'string' ? possible : 'abcdefghijklmnopqrstuvwxyz';

      for( var i=0; i < 10; i++ ) {
        string += possible.charAt( Math.floor( Math.random() * possible.length ) );
      }

      return string;

    }


    /**
     * {}
     *
     * @source http://javascript.crockford.com/memory/leak.html
     * @author potanin@UD
     */
    s.helpers.purge = typeof s.helpers.purge === 'function' ? s.helpers.purge : function( d ) {
      var a = d.attributes, i, l, n;
      if (a) {
          for (i = a.length - 1; i >= 0; i -= 1) {
              n = a[i].name;
              if (typeof d[n] === 'function') {
                  d[n] = null;
              }
          }
      }
      a = d.childNodes;
      if (a) {
          l = a.length;
          for (i = 0; i < l; i += 1) {
              s.helpers.purge( d.childNodes[i] );
          }
      }
    }


    /**
     * Enable the script, ran once on initialization
     *
     * Binds DF events for API access.
     *
     * @author potanin@UD
     */
    var enable = this.enable = function() {

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::doing_ajax', function() {
        log( 'doing_ajax' , 'event_handlers' );
        jQuery( s.ux.element ).removeClass( s.classes.element.filter_pending );
        jQuery( s.ux.element ).removeClass( s.classes.element.server_fail );
        jQuery( s.ux.element ).addClass( s.classes.element.ajax_loading );
      });

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::ajax_complete', function() {
        log( 'ajax_complete', 'event_handlers' );
        jQuery( s.ux.element ).removeClass( s.classes.element.ajax_loading );
      });

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::get_data', function( event, args ) {
        log( 'get_data', 'event_handlers' );
        get_data( event, args );
      });

      /** Called whenever an instance/history related event takes place.  */
      jQuery( document ).bind( 'dynamic_filter::instance::set', function() {
        s.settings.use_instances ? s.instance.set() : false;
      });

      /** Triggered after AJAX JSON results have been successfully loaded. */
      jQuery( document ).bind( 'dynamic_filter::get_data::complete', function( event, args ) {
        log( 'get_data::complete', 'event_handlers' );
        jQuery( document ).trigger( 'dynamic_filter::ajax_complete' , args );

        append_dom( event, args )

        if( !args.silent_fetch ) {
          render_data( event, args );
          update_filters( event, args );
        }

      });

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::get_data::fail', function() {
        jQuery( document ).trigger( 'dynamic_filter::ajax_complete' );
        jQuery( s.ux.element ).addClass( s.classes.element.server_fail );
      });

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::undo_last_query', function() {
        log( 'undo_last_query', 'event_handlers' );
        jQuery( s.ux.element ).removeClass( s.classes.element.server_fail );
        jQuery( s.ux.element ).removeClass( s.classes.element.ajax_loading );
        status( '' );
       });

      /** Call to display results based on... ? */
      jQuery( document ).bind( 'dynamic_filter::render_data', function() {
        log( 'render_data', 'event_handlers' );
        render_data();
      });

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::render_data::complete', function() {
        log( 'render_data::complete', 'event_handlers' );
      });

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::execute_filters', function() {
        log( 'execute_filters', 'event_handlers' );
        execute_filters();
      });

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::update_filters::complete', function() {
        log( 'update_filters::complete', 'event_handlers' );
      });

      /** {missing description} */
      jQuery( document ).bind( 'dynamic_filter::load_more', function() {
        log( 'load_more', 'event_handlers' );
        load_more();
      });

      /** Whenever History Entry changes (HTML5 browsers as of now)  */
      jQuery( document ).bind( 'dynamic_filter::onpopstate', function( args ) {
        log( 'dynamic_filter::onpopstate', 'log', 'info' );
      });

      /** {missing detail} */
      prepare_system();

      /** Walyze the passed attributes */
      analyze_attributes();

      /** Load instance-specific information */
      s.settings.use_instances ? s.instance.load() : false;

      /** Render UI elements (containers) and add custom classes */
      render_ui();

      /** Render the filters */
      render_filter_ui();

      /** Render UI for sorting, if enabled */
      render_sorter_ui();

      /** If Auto Request enable, Get Data, after an optional pause */
      if( s.settings.auto_request ) {

        if( s.settings.timers.initial_request ) {
          log( 'Doing Initial Request with a ' + s.settings.timers.initial_request + 'ms pause.', 'log', 'info' );
        }

        setTimeout( function () { jQuery( document ).trigger( 'dynamic_filter::get_data', { initial_request: true } ); }, s.settings.timers.initial_request );
      }

    } /* end enable() */

    /** If this browser support Pop State, we bind to it */
    window.onpopstate = function( event ) {
      jQuery( document ).trigger( 'dynamic_filter::onpopstate', event );
    }

    /** Initialize the script */
    enable();

    /** Return object for chaining */
    return this;

  };

} ( jQuery ) /* p.s. No dog balls. */ );


