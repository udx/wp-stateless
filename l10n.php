<?php
/**
 * Javascript Localization
 *
 * @since 2.1
 * @author alim@UD
 * @package WP-Stateless
 */

$l10n = array(

  //** Edit Stateless page */
  'something_went_wrong'                  => __( "Something went wrong", ud_get_stateless_media()->domain ),
  'invalid_input'                         => __( "Form has invalid input. Please fix them.", ud_get_stateless_media()->domain ),

  'json_api_enabled'                      => __( "Google Cloud Storage JSON API Service Enabled", ud_get_stateless_media()->domain ),
  'json_api_enabled_failed'               => __( "Google Cloud Storage JSON API Service failed.", ud_get_stateless_media()->domain ),

  'project_cant_be_empty'                 => __( "Project name can't be empty.", ud_get_stateless_media()->domain ),
  'project_length_notice'                 => __( "Project name must be between 5 and 30 characters.", ud_get_stateless_media()->domain ),
  'project_invalid_char'                  => __( "Project name has invalid characters. Enter letters, numbers, quotes, hyphens, spaces or exclamation points.", ud_get_stateless_media()->domain ),
  'project_creation_started'              => __( "Project creation started.", ud_get_stateless_media()->domain ),
  'project_exists'                        => __( "Project Exists", ud_get_stateless_media()->domain ),
  'project_creation_complete'             => __( "Project creation complete.", ud_get_stateless_media()->domain ),
  'project_creation_failed'               => __( "Project creation failed.", ud_get_stateless_media()->domain ),

  'bucket_cant_be_empty'                  => __( "Bucket name can't be empty.", ud_get_stateless_media()->domain ),
  'bucket_length_notice'                  => __( "Bucket name must be between 5 and 30 characters.", ud_get_stateless_media()->domain ),
  'bucket_invalid_char'                   => __( "A bucket name can contain lowercase alphanumeric characters, hyphens, and underscores. Bucket names must start and end with an alphanumeric character.", ud_get_stateless_media()->domain ),
  'bucket_created'                        => __( "Bucket Created", ud_get_stateless_media()->domain ),
  'bucket_creation_failed'                => __( "Bucket creation failed", ud_get_stateless_media()->domain ),
  'bucket_exists'                         => __( "Bucket Exist", ud_get_stateless_media()->domain ),

  'bucket_access_controls_success'        => __( "Bucket access control inserted.", ud_get_stateless_media()->domain ),
  'bucket_access_controls_failed'         => __( "Bucket access control failed.", ud_get_stateless_media()->domain ),

  'select_billing_account'                => __( "Select a billing account.", ud_get_stateless_media()->domain ),
  'billing_enabled'                       => __( "Billing Enabled", ud_get_stateless_media()->domain ),
  'billing_already_enabled'               => __( "Billing already enabled.", ud_get_stateless_media()->domain ),
  'billing_failed'                        => __( "Field to enable billing.", ud_get_stateless_media()->domain ),
  'billing_info'                          => __( "Billing Info", ud_get_stateless_media()->domain ),

  'service_account_exist'                 => __( "Service Account Exists", ud_get_stateless_media()->domain ),
  'service_account_created'               => __( "Service Account Created", ud_get_stateless_media()->domain ),
  'service_account_creation_failed'       => __( "Service Account creation failed", ud_get_stateless_media()->domain ),

  'service_account_key_created'           => __( "Service Account Key Created", ud_get_stateless_media()->domain ),
  'service_account_key_creation_failed'   => __( "Service Account Key creation failed", ud_get_stateless_media()->domain ),

  'service_account_key_saved'             => __( "Service Account Key Saved", ud_get_stateless_media()->domain ),
  'service_account_key_save_failed'       => __( "Failed to  Save Service Account Key", ud_get_stateless_media()->domain ),

  'service_account_role_granted'          => __( "Service Account Role Granted", ud_get_stateless_media()->domain ),
  'service_account_role_grant_failed'     => __( "Service Account Role Grant Failed", ud_get_stateless_media()->domain ),
  'unable_to_connect_to_the_server'       => __( "Unable to connect to the server", ud_get_stateless_media()->domain ),
  'could_not_retrieve_progress'           => __( "Could not retrieve progress", ud_get_stateless_media()->domain ),
  'could_not_get_fails'                   => __( "Could not get fails", ud_get_stateless_media()->domain ),
  'could_not_reset_progress'              => __( "Could not reset progress", ud_get_stateless_media()->domain ),
  'loading_images_media_objects'          => __( "Loading Images Media Objects...", ud_get_stateless_media()->domain ),
  'stopping'                              => __( "Stopping...", ud_get_stateless_media()->domain ),
  'finished'                              => __( "Finished", ud_get_stateless_media()->domain ),
  'cancelled'                             => __( "Cancelled", ud_get_stateless_media()->domain ),
  'ids_are_malformed'                     => __( "IDs are malformed", ud_get_stateless_media()->domain ),
  'unable_to_get_images_media_id'         => __( "Unable to get Images Media ID", ud_get_stateless_media()->domain ),
  'wp_stateless_get_images_media_id'      => __( "WP-Stateless get Images Media ID", ud_get_stateless_media()->domain ),
  'request_failed'                        => __( "Request failed", ud_get_stateless_media()->domain ),
  'get_images_media_id'                   => __( "Get Images Media ID", ud_get_stateless_media()->domain ),
  'loading_non_image_media_objects'       => __( "Loading non-image Media Objects...", ud_get_stateless_media()->domain ),

  'unable_to_get_non_images_media_id'     => __('Unable to get non Images Media ID', ud_get_stateless_media()->domain ),
  'non_libraries_files_are_not_found'     => __('There are no files to process.', ud_get_stateless_media()->domain ),
  'get_non_library_files_request_failed'  => __('Get non library files: Request failed', ud_get_stateless_media()->domain ),
  'regenerate_single_image_failed'        => __('Regenerate single image: Failed', ud_get_stateless_media()->domain ),
  'sync_single_file_failed'               => __('Sync single file: Failed', ud_get_stateless_media()->domain ),
  'sync_single_file_request_failed'       => __('Sync single file: Request failed', ud_get_stateless_media()->domain ),
  'failed_to_sync'                        => __('Failed to sync ', ud_get_stateless_media()->domain ),
  'sync_non_library_file_failed'          => __('Sync non library file: Failed', ud_get_stateless_media()->domain ),
  'sync_non_library_file_request_failed'  => __('Sync non library file: Request failed', ud_get_stateless_media()->domain ),
  'response_code'                         => __('Response code: ', ud_get_stateless_media()->domain ),
  'loading_non_library_objects'           => __('Loading non library Objects...', ud_get_stateless_media()->domain ),
  'processing_files'                      => __('Processing files (', ud_get_stateless_media()->domain ),
  '_total___'                             => __(' total)...', ud_get_stateless_media()->domain ),
  'processing_images'                     => __('Processing images (', ud_get_stateless_media()->domain ),
  
  'get_non_images_media_id_request_failed'  => __('Get non Images Media ID: Request failed', ud_get_stateless_media()->domain ),
  'regenerate_single_image_request_failed'  => __('Regenerate single image: Request failed', ud_get_stateless_media()->domain ),
  
);

