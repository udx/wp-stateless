<?php
/**
 * System Info (Google Cloud section) class
 * https://cloud.google.com/storage/docs/json_api/v1/buckets#resource-representations
 *
 * @since 4.1.0
 */

namespace wpCloud\StatelessMedia\Status;

use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\Helper;

class GoogleCloudInfo {
  use Singleton;

  protected function __construct() {
    $this->_init_hooks();
  }

  private function _init_hooks() {
    add_filter('wp_stateless_status_info_values_google_cloud', [$this, 'get_bucket_info'], 10);

    add_filter('wp_stateless_status_info_values_google_cloud', [$this, 'format_values'], 99);

    add_filter('wp_stateless_status_info_values_google_cloud_public_access', [$this, 'get_public_access_value'], 10);
    add_filter('wp_stateless_status_info_values_google_cloud_access_control', [$this, 'get_access_control'], 10);
    add_filter('wp_stateless_status_info_values_google_cloud_versioning', [$this, 'get_versioning'], 10);
    add_filter('wp_stateless_status_info_values_google_cloud_soft_delete', [$this, 'get_soft_delete'], 10);
  }

  /**
   * Format values to human-readable
   */
  public function format_values($values) {
    foreach ($values as $key => $value) {
      $values[$key]['value'] = apply_filters("wp_stateless_status_info_values_google_cloud_$key", $values[$key]['value']);
    }

    return $values;
  }

  /**
   * Format 'public_access' value
   */
  public function get_public_access_value($value) {
    switch ($value) {
      case 'enforced':
        return __('Enforced', ud_get_stateless_media()->domain);
      case 'inherited':
        return __('Inherited', ud_get_stateless_media()->domain);
    }

    return $value;
  }

  /**
   * Format 'versioning' value
   */
  public function get_versioning($value) {
    return $value ? __('Enabled', ud_get_stateless_media()->domain) : __('Disabled', ud_get_stateless_media()->domain);
  }

  /**
   * Get 'Access Control' value
   */
  public function get_access_control($value) {
    return $value ? __('Uniform', ud_get_stateless_media()->domain) : __('Fine-grained', ud_get_stateless_media()->domain);
  }

  /**
   * Get 'Soft Delete' value
   */
  public function get_soft_delete($value) {
    return $value > 0 ? __('Enabled', ud_get_stateless_media()->domain) : __('Disabled', ud_get_stateless_media()->domain);
  }

  /**
   * Get the values related to GCS bucket configuration
   */
  public function get_bucket_info($values) {
    $client = ud_get_stateless_media()->get_client();
    $bucket_name = ud_get_stateless_media()->get('sm.bucket');

    if ( empty($client) || empty($bucket_name) ) {
      $rows = [
        'gcs_error' => [
          'label' => __('Error', ud_get_stateless_media()->domain),
          'value' => __('Google Cloud info not accessible', ud_get_stateless_media()->domain),
        ],
      ];

      return $values + $rows;
    }

    // Get bucket info
    $info = $client->service->buckets->get($bucket_name);

    $rows = [
      'storage_class' => [
        'label' => __('Storage Class', ud_get_stateless_media()->domain),
        'value' => $info->storageClass,
      ],
      'public_access' => [
        'label' => __('Public Access Prevention', ud_get_stateless_media()->domain),
        'value' => $info->iamConfiguration->publicAccessPrevention,
      ],
      'access_control' => [
        'label' => __('Access Control', ud_get_stateless_media()->domain),
        'value' => $info->iamConfiguration->uniformBucketLevelAccess->enabled,
      ],
      'versioning' => [
        'label' => __('Versioning', ud_get_stateless_media()->domain),
        'value' => isset($info->versioning) && isset($info->versioning->enabled) ? $info->versioning->enabled : false,
      ],
      'soft_delete' => [
        'label' => __('Soft Delete', ud_get_stateless_media()->domain),
        'value' => isset($info->softDeletePolicy) && isset($info->softDeletePolicy->retentionDurationSeconds) ? $info->softDeletePolicy->retentionDurationSeconds : 0,
      ],
    ];

    return $values + $rows;
  }

}
