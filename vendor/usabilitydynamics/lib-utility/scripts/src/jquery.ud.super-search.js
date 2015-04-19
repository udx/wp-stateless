/* =========================================================
 * jquery.ud.super_search.js v1.0.1
 * http://usabilitydynamics.com
 * =========================================================
 * Copyright 2012 Usability Dynamics, Inc.
 *
 * Validation: http://www.jslint.com/
 *
 * Copyright (c) 2012 Usability Dynamics, Inc. ( usabilitydynamics.com )
 * ========================================================= */

(function( jQuery ){
  jQuery.fn.super_search = function( ss_settings ) {

    var element = this;
    var abandoned_timer;
    var ui= {};
    var ux = {};
    var typing_timer;
    var query = {
      current: '',
      previous: ''
    };

    /* Add ss_eleemnt attribute to this element so it can be IDied later */
    this.attr('ss_element', 'search_input');

   /* Default Settings */
    var ss = jQuery.extend({
      action : 'super_search',
      ajax_url : ajaxurl,
      input_classes : {
        no_results: 'ss_no_results',
        processing: 'ss_processing',
        error: 'ss_error'
      },
      response_classes: {
        response_wrapper: 'ss_response_container',
        show_scroll: 'ss_show_scroll',
        item_class: ''
      },
      append_to: jQuery( element ).parent(),
      search_trigger: false,
      search_result_gap : 200,
      limit : 5,
      timers: {
        abandonment: 1000,
        search_entry : 2000
      },
      async : false,
      debug : true,
      success : false,
      beforeSend : false,
      ui: {}
    }, ss_settings);

    /* Internal logging function */

    if( typeof ss.log !== 'function' ) {
      ss.log = function( something, type ) {

        if(!ss.debug) {
          return;
        }

        console.log(something);

      };
    }

    /* Do some QC early on - make sure append to element exist */
    if(!jQuery(ss.append_to).length) {
      ss.log('The (' + ss.append_to + ') element does not exist.', 'warning');
    }

    if( ss.search_trigger && typeof ss.search_trigger === 'object' ) {
      jQuery( ss.search_trigger ).click( function() {
      
        /* If current search is same as old, we do nothing */
        if(query.current == query.previous) {
          return;
        }
      
        jQuery.fn.super_search.do_search();
        
      });
    }

    this.keyup(function() {

      query.current = element.val();

      /* If no query, we stop scheduld search, if it scheduled */
      if(typing_timer && !query.current) {
        clearTimeout(typing_timer);
        return;
      }

      /* If current search is same as old, we do nothing */
      if(query.current == query.previous) {
        return;
      }

      /* Clear timer because something was changed, and reset */
      if(typing_timer) {
        clearTimeout(typing_timer);
      }

      /* All is good, schedule search to happen in few seconds */
      typing_timer = setTimeout(jQuery.fn.super_search.do_search, ss.timers.search_entry);

    });

    /* Watch for when user enteres input area */
    this.focus(function() {
      jQuery.fn.super_search.ux_change();
    });

    /* Watch for when user leaves input area */
    this.blur(function() {
      jQuery.fn.super_search.ux_change();
    });


    /**
     * Search function, fired when user is done typing
     *
     * @todo Need error handling.
     * @todo Before send should probably accept data returned from callback function to manipulate what happens.
     */
    jQuery.fn.super_search.do_search = function() {
      ss.log('do_search()');

      //** Update to latest value */
      query.current = element.val();

      /* Build ajax post array */
      var post_data = {
          action: ss.action,
          limit: ss.limit,
          query: query.current
      }

      /* Build object for callback functions */
      cb_data = {
        post_data: post_data,
        settings: ss
      }

      /* Remove all status-related classes */
      jQuery.each(ss.input_classes, function(slug, css_class) {
        jQuery(element).removeClass(css_class);
      });

      jQuery(element).addClass(ss.input_classes.processing);

      jQuery.ajax({
        url: ss.ajax_url,
        async: ss.async,
        data: post_data,
        beforeSend: function(jqXHR, settings) {
          ss.log('do_search.beforeSend() - have callback, executing');

          /* Load current settings into CB data */
          cb_data.settings = settings;

          if(typeof ss.beforeSend == 'function'){
            if(ss.beforeSend.call(this, cb_data)) {
              return;
            }
          }

        },
        complete: function(jqXHR, textStatus) {
          ss.log('do_search.complete( jqXHR, ' + textStatus + ' )');

          /* Clear out processing class, regardless of success */
          jQuery(element).removeClass(ss.input_classes.processing);

          ss.log('Ajax response received.');

        },
        success: function(data, textStatus, jqXHR) {
          ss.log('do_search.success()');

          if(typeof ss.success == 'function') {
            ss.log('do_search.success() - have callback, executing');
            if(ss.success.call(data, textStatus, jqXHR)) {
              return;
            }
          }

          /* Update "Last Searched" query */
          query.previous = query.current;

          //** Delete existing results, regardless of result to avoid confusion. */
          jQuery.fn.super_search.remove_rendered_results();

          if(data.results) {
            ss.last_results = data.results;
            jQuery.fn.super_search.render_results(data.results)
          } else {
            jQuery(element).addClass(ss.input_classes.no_results);
          }

          if(data.other) {
            ss.log("Search Debug Data:" . data.debug_response);
          }

        },
        error: function(jqXHR, textStatus, errorThrown) {
          ss.log('do_search.error()');
          jQuery(element).addClass(ss.input_classes.error);

        },
        dataType: "json"
      });

    }


    /**
     * Remove search results.
     *
     */
    jQuery.fn.super_search.remove_rendered_results = function() {
      ss.log('remove_rendered_results()');

      if(ss.rendered_element && ss.rendered_element.length) {
        jQuery(ss.rendered_element).fadeOut(300, function() {
          /* jQuery(this).remove();*/
        });
      }

      jQuery.fn.super_search.update_dom_triggers();

    }


    /**
     * Monitors visual changes to the DOM
     *
     */
    jQuery.fn.super_search.update_dom_triggers = function() {
      ss.log('update_dom_triggers()');

      /* Remove to avoid multiple triggers */
      jQuery(ss.rendered_element).off('mouseenter');
      jQuery(ss.rendered_element).off('mouseleave');

      /* Create event to hide rearch result if user's mouse leaves the area, and update UX status */
      jQuery(ss.rendered_element).mouseenter(function() {
        ux.results_over = true;
        jQuery.fn.super_search.ux_change();
      }).mouseleave(function() {
        ux.results_over = false;
        jQuery.fn.super_search.ux_change();
      });

    }

    /**
     * Monitors user interactions with the interface, and updates any trigger events.
     *
     * Called on various events such as user working with input box, or leaving the input box
     *
     */
    jQuery.fn.super_search.ux_change = function() {
      ss.log('ux_change()');

      /* Check if user is focused on input element */
      if(jQuery(element).is(":focus")) {
        ux.input_focus = true;

        /* If search box is clicked, the query has not changed, and search result exists, we show them again */
        if(query.current == query.previous && ss.rendered_element) {
          ss.rendered_element.show();
        }

      } else {
        ux.input_focus = false;
      }

      /* If user is not focused on input, and HAS moused over results, and left, we started a timer to hide the results */
      if(ss.rendered_element && !ux.input_focus && ux.results_over === false) {
        abandoned_timer = setTimeout(jQuery.fn.super_search.remove_rendered_results, ss.timers.abandonment);
      } else if(abandoned_timer) {
        clearTimeout(abandoned_timer);
      }

    }


    /**
     * Render search results from query
     *
     */
    jQuery.fn.super_search.render_results = function(results) {
      ss.log('render_results()');

      /* Account for zero based keys */
      var total_results = (results.length - 1);

      html = [];

      html.push( ss.ui.response_container = '<ul ss_element="response_container" class="ss_element ' + ss.response_classes.response_wrapper + '">' );

      console.log(ss.ui.response_container);

      jQuery.each(results, function(i, data) {

        var classes = [];

        if(ss.response_classes.item_class) {
          classes.push(ss.response_classes.item_class);
        }

        if(data.item_class) {
          classes.push(data.item_class);
        }

        if(i == total_results) {
          classes.push('last_item');
        }

        html.push('<li ss_element="single_item" class="ss_element ' + classes.join(' ') + '">');

        if(data.url) {
          html.push('<a href="' + data.url + '">');
        }

        html.push(data.title);

        if(data.url) {
          html.push('</a>');
        }


        html.push('</li>');
      });

      html.push('</ul>');

      ss.rendered_element = jQuery(html.join(''));

      jQuery(ss.append_to).append(ss.rendered_element);

      ui.window_height = jQuery(window).height();
      ui.rendered_element = jQuery(ss.rendered_element).height();

      if((ui.rendered_element + ss.search_result_gap) > ui.window_height) {
        jQuery(ss.rendered_element).css('max-height', (ui.window_height - ss.search_result_gap) + 'px');
        jQuery(ss.rendered_element).addClass(ss.response_classes.show_scroll);
        ui.rendered_element = jQuery(ss.rendered_element).height();
      }

      jQuery.fn.super_search.update_dom_triggers();

    }


  };
})( jQuery );
