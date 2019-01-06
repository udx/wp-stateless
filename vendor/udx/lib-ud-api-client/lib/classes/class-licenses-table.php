<?php
/**
 * Licenses Table
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Licenses_Table' ) ) {

    if ( ! defined( 'ABSPATH' ) ) exit; //** Exit if accessed directly */

    if( ! class_exists( 'WP_List_Table' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
  
    /**
     * 
     * @author: peshkov@UD
     */
    class Licenses_Table extends \WP_List_Table {
      
      public $data;
      public $found_data;
      public $name;
      public $domain;
      public $page;
      public $per_page = 100;
      public $activation_email;
      
      /**
       * Constructor.
       * @since  1.0.0
       */
      public function __construct ( $args ) {
        global $status, $page;

        $this->name = !empty( $args[ 'name' ] ) ? $args[ 'name' ] : '';
        $this->domain = !empty( $args[ 'domain' ] ) ? $args[ 'domain' ] : false;
        $this->page = !empty( $args[ 'page' ] ) ? $args[ 'page' ] : false;
        $this->activation_email = isset( $args[ 'activation_email' ] ) && $args[ 'activation_email' ] ? true : false;
        
        $args = array(
          'singular'  => 'license',     //singular name of the listed records
          'plural'    => 'licenses',   //plural name of the listed records
          'ajax'      => false        //does this table support ajax?
        );

        $this->data = array();

        //** Make sure this file is loaded, so we have access to plugins_api(), etc. */
        require_once( ABSPATH . '/wp-admin/includes/plugin-install.php' );

        parent::__construct( $args );
      }

      /**
       * Text to display if no items are present.
       *
       * @since  1.0.0
       * @return  void
       */
      public function no_items () {
          echo wpautop( sprintf( __( 'No active %s products found.', $this->domain ), $this->name ) );
      }

      /**
       * The content of each column.
       *
       * @param  array $item         The current item in the list.
       * @param  string $column_name The key of the current column.
       * @since  1.0.0
       * @return string              Output for the current column.
       */
      public function column_default ( $item, $column_name ) {
        switch( $column_name ) {
          case 'product':
          case 'product_status':
          case 'product_version':
            return $item[$column_name];
          break;
        }
      }

      /**
       * Retrieve an array of sortable columns.
       * @since  1.0.0
       * @return array
       */
      public function get_sortable_columns () {
        return array();
      }

      /**
       * Retrieve an array of columns for the list table.
       *
       * @since  1.0.0
       * @return array Key => Value pairs.
       */
      public function get_columns () {
        $columns = array(
          'product_name' => __( 'Product', $this->domain ),
          'product_version' => __( 'Version', $this->domain ),
          'product_status' => __( 'Activation Status', $this->domain ),
        );
        return $columns;
      }

      /**
       * Content for the "product_name" column.
       *
       * @param  array  $item The current item.
       * @since  1.0.0
       * @return string       The content of this column.
       */
      public function column_product_name ( $item ) {
        return wpautop( '<strong>' . $item['product_name'] . '</strong>' );
      }

      /**
       * Content for the "product_version" column.
       * @param  array  $item The current item.
       * @since  1.0.0
       * @return string       The content of this column.
       */
      public function column_product_version ( $item ) {
        return wpautop( $item['product_version'] );
      }

      /**
       * Content for the "status" column.
       *
       * @param  array  $item The current item.
       * @since  1.0.0
       * @return string       The content of this column.
       */
      public function column_product_status ( $item ) {
        $response = '';
        if ( 'active' == $item['product_status'] ) {
          $deactivate_url = wp_nonce_url(\UsabilityDynamics\Utility::current_url( array(
            'action' => 'deactivate-product',
            'filepath' => urlencode( $item['product_file_path'] ),
          ) ), 'bulk-licenses' );
          //$deactivate_url = wp_nonce_url( add_query_arg( 'action', 'deactivate-product', add_query_arg( 'filepath', $item['product_file_path'], add_query_arg( 'page', $this->page, network_admin_url( 'index.php' ) ) ) ), 'bulk-licenses' );
          $response = '<a href="' . esc_url( $deactivate_url ) . '" onclick="return confirm(\'' . __( 'Are you sure you want to deactivate the license?', $this->domain ) . '\');">' . __( 'Deactivate', $this->domain ) . '</a>' . "\n";
        } else {
          $response .= '<ul>' . "\n";
          $response .= '<li><input name="products[' . esc_attr( $item['product_file_path'] ) . '][license_key]" id="license_key-' . esc_attr( $item['product_file_path'] ) . '" type="text" value="" size="37" aria-required="true" placeholder="' . esc_attr( __( 'Place License Key here', $this->domain ) ) . '" /><li>' . "\n";
          
          if( !$this->activation_email ) {
            $response .= '</ul>' . "\n";
            $response .= '<input name="products[' . esc_attr( $item['product_file_path'] ) . '][activation_email]" type="hidden" value="" />' . "\n";
          } else {
            $response .= '<li><input name="products[' . esc_attr( $item['product_file_path'] ) . '][activation_email]" id="activation_email-' . esc_attr( $item['product_file_path'] ) . '" type="text" value="" size="37" aria-required="true" placeholder="' . esc_attr( __( 'Place Activation Email here', $this->domain ) ) . '" /></li>' . "\n";
            $response .= '</ul>' . "\n";
          }
          
        }
        return $response;
      }

      /**
       * Retrieve an array of possible bulk actions.
       *
       * @since  1.0.0
       * @return array
       */
      public function get_bulk_actions () {
        $actions = array();
        return $actions;
      }

      /**
       * Prepare an array of items to be listed.
       * @since  1.0.0
       * @return array Prepared items.
       */
      public function prepare_items () {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $total_items = count( $this->data );
        //** only ncessary because we have sample data */
        $this->found_data = $this->data;
        $this->set_pagination_args( array(
          'total_items' => $total_items, //WE have to calculate the total number of items
          'per_page'    => $total_items //WE have to determine how many items to show on a page
        ) );
        $this->items = $this->found_data;
      }
      
    }
  
  }
  
}
