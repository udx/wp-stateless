<?php

/**
 * Helper.
 *
 * @since 3.3.0
 */

namespace wpCloud\StatelessMedia {

  class Helper {

    /**
     * Checks theme name against the current theme or it's parent.
     */
    public static function is_theme_name($theme_name) {
      $theme = wp_get_theme();

      if ($theme->Name == $theme_name) {
        return true;
      }
  
      $parent_theme = $theme->parent();
      if ( is_a($parent_theme, 'WP_Theme') && $parent_theme->Name == $theme_name ) {
        return true;
      }
  
      return false;
    }

    /**
     * Checks if plugin is active.
     */
    public static function get_active_plugins() {
      $active_plugins = [];

      // If multisite then check if plugin is network active
      if ( is_multisite() ) {
        $active_plugins = (array)get_site_option('active_sitewide_plugins', []);
        $active_plugins = array_keys($active_plugins);

        // If we are in network admin then return, unless it will get data from main site.
        if ( is_network_admin() ) {
          return $active_plugins;
        }
      }

      return array_merge(
        $active_plugins, 
        (array)get_option('active_plugins', [])
      );
    }
  
    /**
     * Convert array to objects.
     * 
     * @param array $array
     * 
     * @return array
     */
    public static function array_of_objects($array) {
      return array_map(function($item) {
        return (object)$item;
      }, $array);
    }

    /**
     * Writes to error log.
     * 
     * @param mixed $data
     */
    public static function log($data, $json = false) {
      if (!WP_DEBUG) {
        return;
      }

      if ( is_array($data) || is_object($data) || !is_string($data) ) {
        if ( $json ) {
          error_log( json_encode($data) );
        } else {
          error_log( print_r($data, true) );
        }

        return;
      } 
      
      error_log($data);
    }

    /**
     * Writes debug to error log.
     * 
     * @param mixed $data
     */
    public static function debug($data, $json = false) {
      self::log($data, $json);
    }
  }
}