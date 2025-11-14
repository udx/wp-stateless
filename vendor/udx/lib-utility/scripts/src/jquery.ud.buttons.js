/* =========================================================
 * jquery-smart-dom-buttons.js v1.0
 * http://usabilitydynamics.com
 * =========================================================
 * Copyright 2011 Usability Dynamics, Inc.
 *
 * Version 0.0.5
 *
 * Copyright (c) 2011 Usability Dynamics, Inc. (usabilitydynamics.com)
 * ========================================================= */

(function( jQuery ){

  /**
   * Handle AJAX Actions and UI actions
   *
   * {missing detailed description}
   *
   * @author potanin@UD
   * @version 0.2.2
   */
  jQuery.fn.smart_dom_button = function( settings ) {

    /* Set Settings */
    var s = jQuery.extend({
      debug: false,
      action_attribute: 'action_attribute',
      response_container: 'response_container',
      ajax_action: 'action',
      label_attributes: {
        process: 'processing_label',
        revert_label: 'revert_label',
        verify_action: 'verify_action'
      }
    }, settings);

    /* Internal logging function */
    log = function(something, type) {

      if(!s.debug) {
        return;
      }

      if(window.console && console.debug) {

        if (type == 'error') {
          console.error(something);
        } else {
          console.log(something);
        }

      }

    };


    /**
     * Gets label for the type of element
     *
     */
    get_label = function( this_button ) {

      var type = jQuery(this_button).get(0).tagName;
      var label = '';

      switch (type) {

        case 'SPAN':
          label = jQuery(this_button).text();
        break;

        case 'INPUT':
          label = jQuery(this_button).val();
        break;

      }

      return label;

    }


    /**
     * Sets the label for the type
     *
     */
    set_label = function( label,  a ) {

      switch (a.type) {

        case 'SPAN':
          jQuery(a.button).text(label);
        break;

        case 'INPUT':
          jQuery(a.button).val(label);
        break;

      }

      return label;

    }


    /**
     * Execute an action for the button
     *
     * @todo Improve ajax response handling, to include hiding response element on error.
     *
     */
    do_execute = function( this_button ) {

      /* Array of all settings specific to the current button */
      var a = {
        button: this_button,
        type: jQuery(this_button).get(0).tagName,
        original_label: jQuery(this_button).attr('original_label') ? jQuery(this_button).attr('original_label') : get_label(this_button)
      };

      /* Get wrapper if used and exists */
      if(s.wrapper &&  jQuery(a.button).closest(s.wrapper).length) {
        a.wrapper = jQuery(a.button).closest(s.wrapper);
        a.use_wrapper = true;
      } else {
        a.wrapper = a.button;
        a.use_wrapper = false;
      }

      /* Determine action */
      a.the_action = jQuery(a.wrapper).attr(s.action_attribute) ? jQuery(a.wrapper).attr(s.action_attribute) : false;

      /* Get labels */
      if(s.label_attributes.processing && jQuery(a.wrapper).attr(s.label_attributes.processing)) {
        a.processing_label = jQuery(a.wrapper).attr(s.label_attributes.processing) ? jQuery(a.wrapper).attr(s.label_attributes.processing) : false;
      }

      if(s.label_attributes.verify_action && jQuery(a.wrapper).attr(s.label_attributes.verify_action)) {
        a.verify_action = jQuery(a.wrapper).attr(s.label_attributes.verify_action) ? jQuery(a.wrapper).attr(s.label_attributes.verify_action) : false;
      }

      /* Set original label only if a revert label exists */
      if(s.label_attributes.revert_label && jQuery(a.wrapper).attr(s.label_attributes.revert_label)) {
        a.revert_label = jQuery(a.wrapper).attr(s.label_attributes.revert_label) ? jQuery(a.wrapper).attr(s.label_attributes.revert_label) : false;

        /* Set original label if not already set */
        if(!jQuery(a.wrapper).attr('original_label')) {
          a.original_label = get_label(a.button);
          jQuery(a.wrapper).attr('original_label', a.original_label);
        }

      }

      /* If no action found, we leave */
      if(!a.the_action) {
        return;
      }

      if(a.verify_action) {
        if(!confirm(a.verify_action)) {
          return;
        }
      }

      /* Create a response container if we are using a wrapper */
      if(a.use_wrapper) {

        if(!jQuery(s.response_container, a.wrapper).length) {
          jQuery(a.wrapper).append('<span class="response_container"></span>');
        }

        a.response_container = jQuery('.response_container', a.wrapper);

        /* Unset all classes */
        jQuery(a.response_container).removeClass();
        jQuery(a.response_container).addClass('response_container');

        if(a.processing_label) {
          jQuery(a.response_container).html(a.processing_label);
        }

      }

      /* Check if this is a UI action first, otherwise use AJAX */
      if(a.the_action == 'ui') {

        /* If a revert label exists, we toggle them */
        if(a.revert_label) {

          if(get_label(a.button) == a.revert_label) {
            set_label(a.original_label, a);

          } else {
            set_label(a.revert_label, a);

          }

        }

        if(jQuery(a.wrapper).attr('toggle')) {
          jQuery(jQuery(a.wrapper).attr('toggle')).toggle();
        }

        if(jQuery(a.wrapper).attr('show')) {
          jQuery(jQuery(a.wrapper).attr('show')).show();
        }

        if(jQuery(a.wrapper).attr('hide')) {
          jQuery(jQuery(a.wrapper).attr('hide')).hide();
        }

      } else {

        jQuery.post(ajaxurl, {
          _wpnonce: flawless_admin.actions_nonce,
          action: s.ajax_action,
          the_action: a.the_action
        }, function (result) {

          if(result && result.success) {
            jQuery(a.response_container).show();

            if(result.css_class) {
              jQuery(a.response_container).addClass(result.css_class);
            }

            if(result.remove_element && jQuery(result.remove_element).length) {
              jQuery(result.remove_element).remove();
            }

            jQuery(a.response_container).html(result.message);

            setTimeout(function() {

              jQuery(a.response_container).fadeOut('slow', function() {
                jQuery(a.response_container).remove();
              });

            }, 10000);

          }

        }, 'json');


      }

    }


    jQuery( this ).click(function() {
      log("Button triggered.");
      do_execute( this );
    });


    return this;

  };
}) ( jQuery );