<?php

namespace wpCloud\StatelessMedia;

if (!class_exists('wpCloud\StatelessMedia\AttachmentPanel')) {
  /**
   * Renders WP-Stateless's attachment fields using native WordPress meta boxes and tabs.
   */
  class AttachmentPanel {
    /** Registers the attachment panel during WordPress meta-box initialization. */
    public function __construct() {
      add_action('add_meta_boxes', array($this, 'register'));
    }

    /** Adds the low-priority Stateless meta box to the attachment editor. */
    public function register() {
      add_meta_box(
        'sm-attachment-metabox',
        __('Stateless', ud_get_stateless_media()->domain),
        array($this, 'render'),
        'attachment',
        'normal',
        'low'
      );
    }

    /**
     * Renders the attachment's existing field definitions as accessible tabs.
     *
     * Labels and headings are escaped. The `std` field content is rendered as
     * supplied by the existing media filters so generated action links and URLs
     * retain their intended markup.
     */
    public function render() {
      $meta_boxes = apply_filters('rwmb_meta_boxes', array());
      $meta_box = array();
      foreach ($meta_boxes as $candidate) {
        if (isset($candidate['id']) && $candidate['id'] === 'sm-attachment-metabox') {
          $meta_box = $candidate;
          break;
        }
      }
      if (empty($meta_box['fields']) || !is_array($meta_box['fields'])) return;

      $tabs = isset($meta_box['tabs']) && is_array($meta_box['tabs']) ? $meta_box['tabs'] : array('thumbnails' => array('label' => __('Thumbnails', ud_get_stateless_media()->domain), 'icon' => 'dashicons-format-gallery'));
      $fields = array();
      foreach ($meta_box['fields'] as $field) {
        $tab = isset($field['tab']) ? $field['tab'] : key($tabs);
        if (!isset($fields[$tab])) $fields[$tab] = array();
        $fields[$tab][] = $field;
      }
      echo '<div class="rwmb-tabs rwmb-tabs-left"><ul class="rwmb-tab-nav">';
      $index = 0;
      foreach ($tabs as $id => $tab) {
        if (is_string($tab)) $tab = array('label' => $tab, 'icon' => '');
        $icon = !empty($tab['icon']) ? '<i class="dashicons ' . esc_attr($tab['icon']) . '"></i>' : '';
        echo '<li class="' . ($index === 0 ? 'rwmb-tab-active' : '') . '" data-panel="' . esc_attr($id) . '"><a href="#sm-panel-' . esc_attr($id) . '">' . $icon . esc_html(isset($tab['label']) ? $tab['label'] : $id) . '</a></li>';
        $index++;
      }
      echo '</ul><div class="rwmb-tab-panels">';
      $index = 0;
      foreach ($tabs as $id => $tab) {
        echo '<div class="rwmb-tab-panel rwmb-tab-panel-' . esc_attr($id) . '" data-panel="' . esc_attr($id) . '"' . ($index === 0 ? ' style="display:block"' : '') . '>';
        if (!empty($fields[$id])) {
          foreach ($fields[$id] as $field) {
            if (isset($field['type']) && $field['type'] === 'heading') {
              echo '<h4>' . esc_html($field['name']) . '</h4>';
              continue;
            }
            echo '<div class="rwmb-field">';
            if (!empty($field['name'])) echo '<p><strong>' . esc_html($field['name']) . '</strong></p>';
            if (isset($field['std'])) echo $field['std'];
            echo '</div>';
          }
        }
        echo '</div>';
        $index++;
      }
      echo '</div></div>';
    }
  }
}
