<?php
/**
 * Javascript Localization
 *
 * @version 0.1
 * @since 1.37.3.2
 * @author peshkov@UD
 * @package WP-Property
 */

$l10n = array(

  //** Edit Property page */
  'clone_property'                  => sprintf( __( 'Clone %s', ud_get_wp_property()->domain ), WPP_F::property_label() ),
  'delete'                            => __( 'Delete', ud_get_wp_property()->domain ),

  //** Admin Overview page */
  'show'                            => __( 'Show', ud_get_wp_property()->domain ),
  'hide'                            => __( 'Hide', ud_get_wp_property()->domain ),
  'featured'                        => __( 'Featured', ud_get_wp_property()->domain ),
  'add_to_featured'                 => __( 'Add to Featured', ud_get_wp_property()->domain ),

  //** Admin Settings page */
  'undefined_error'                 => __( 'Undefined Error.', ud_get_wp_property()->domain ),
  'set_property_type_confirmation'  => sprintf( __( 'You are about to set ALL your %s to the selected %s type. Are you sure?', ud_get_wp_property()->domain ), WPP_F::property_label( 'plural' ), WPP_F::property_label() ),
  'processing'                      => __( 'Processing...', ud_get_wp_property()->domain ),
  'geo_attribute_usage'             => __( 'Attention! This attribute (slug) is used by Google Validator and Address Display functionality. It is set automatically and can not be edited on Property Adding/Updating page.',ud_get_wp_property()->domain ),
  'default_property_image'          => sprintf( __( 'Default %s Image', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
  'remove_image'                    => __( 'Remove Image', ud_get_wp_property()->domain ),
  'error_types_one'                    => __( 'Settings can\'t be saved. You need to enter at least one property type.', ud_get_wp_property()->domain ),
  //** Ajaxupload */
  'uploading'                       => __( 'Uploading', ud_get_wp_property()->domain ),
  'drop_file'                       => __( 'Drop files here to upload', ud_get_wp_property()->domain ),
  'upload_images'                   => __( 'Upload Image', ud_get_wp_property()->domain ),
  'cancel'                          => __( 'Cancel', ud_get_wp_property()->domain ),
  'fail'                            => __( 'Failed', ud_get_wp_property()->domain ),

  //** Datatables Library */
  'dtables' => array(
    'first'                         => __( 'First', ud_get_wp_property()->domain ),
    'previous'                      => __( 'Previous', ud_get_wp_property()->domain ),
    'next'                          => __( 'Next', ud_get_wp_property()->domain ),
    'last'                          => __( 'Last', ud_get_wp_property()->domain ),
    'processing'                    => __( 'Processing...', ud_get_wp_property()->domain ),
    'show_menu_entries'             => sprintf( __( 'Show %s entries', ud_get_wp_property()->domain ), '_MENU_' ),
    'no_m_records_found'            => __( 'No matching records found', ud_get_wp_property()->domain ),
    'no_data_available'             => __( 'No data available in table', ud_get_wp_property()->domain ),
    'loading'                       => __( 'Loading...', ud_get_wp_property()->domain ),
    'showing_entries'               => sprintf( __( 'Showing %s to %s of %s entries', ud_get_wp_property()->domain ), '_START_', '_END_', '_TOTAL_' ),
    'showing_entries_null'          => sprintf( __( 'Showing % to % of % entries', ud_get_wp_property()->domain ), '0', '0', '0' ),
    'filtered_from_total'           => sprintf( __( '(filtered from %s total entries)', ud_get_wp_property()->domain ), '_MAX_' ),
    'search'                        => __( 'Search:', ud_get_wp_property()->domain ),
    'display'                       => __( 'Display:', ud_get_wp_property()->domain ),
    'records'                       => __( 'records', ud_get_wp_property()->domain ),
    'all'                           => __( 'All', ud_get_wp_property()->domain ),
  ),

  'feps' => array(
    'unnamed_form'                  => __( 'Unnamed Form', ud_get_wp_property()->domain ),
    'form_could_not_be_removed_1'   => __( 'Form could not be removed because of some server error.', ud_get_wp_property()->domain ),
    'form_could_not_be_removed_2'   => __( 'Form could not be removed because form ID is undefined.', ud_get_wp_property()->domain ),
  ),
  
  'fbtabs' => array(
    'unnamed_canvas'                  => __( 'Unnamed Canvas', ud_get_wp_property()->domain ),
  ),
  'attr_not_support_default'        => __('Default Value not supported for this data entry.', ud_get_wp_property()->domain),
  'are_you_sure'                    => __('Are you sure?', ud_get_wp_property()->domain),
  'replace_all'                     => __('Replace all', ud_get_wp_property()->domain),
  'replace_empty'                   => __('Replace only empty', ud_get_wp_property()->domain),
  '_done'                           => __('Done!', ud_get_wp_property()->domain),

);

