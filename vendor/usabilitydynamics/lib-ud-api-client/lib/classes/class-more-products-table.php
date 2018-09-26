<?php
/**
 * Licenses Table
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\More_Products_Table' ) ) {

    if ( ! defined( 'ABSPATH' ) ) exit; //** Exit if accessed directly */

    if( ! class_exists( 'WP_List_Table' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
  
    /**
     * 
     * @author: peshkov@UD
     */
    class More_Products_Table extends \WP_List_Table {
      
      public $data;
      public $found_data;
      public $name;
      public $domain;
      public $page;
      public $per_page = 100;
      
      /**
       * Constructor.
       * @since  1.0.0
       */
      public function __construct ( $args ) {
        global $page;

        $this->name = !empty( $args[ 'name' ] ) ? $args[ 'name' ] : '';
        $this->domain = !empty( $args[ 'domain' ] ) ? $args[ 'domain' ] : false;
        $this->page = !empty( $args[ 'page' ] ) ? $args[ 'page' ] : false;
        
        $args = array(
          'singular'  => 'product',     //singular name of the listed records
          'plural'    => 'products',   //plural name of the listed records
          'ajax'      => false        //does this table support ajax?
        );

        $this->data = array();

        parent::__construct( $args );
      }
      
      /**
       * Prepare an array of items to be listed.
       * @since  1.0.0
       * @return array Prepared items.
       */
      public function prepare_items () {
        $total_items = count( $this->data );
        //** only necessary because we have sample data */
        $this->set_pagination_args( array(
          'total_items' => $total_items, //WE have to calculate the total number of items
          'per_page'    => $total_items //WE have to determine how many items to show on a page
        ) );
        $this->items = $this->data;
      }
      
      public function no_items() {
        if ( isset( $this->error ) ) {
          $message = $this->error->get_error_message() . '<p class="hide-if-no-js"><a href="#" class="button" onclick="document.location.reload(); return false;">' . __( 'Try again' ) . '</a></p>';
        } else {
          $message = sprintf( __( 'No More Available Products for %s', $this->domain ), $this->name );
        }
        echo '<div class="no-plugin-results">' . $message . '</div>';
      }
      
      public function get_columns() {
        return array();
      }
      
      protected function truncate( $string, $length = 220, $append = "&hellip;" ) {
        $string = trim($string);
        if( strlen( $string ) > $length ) {
          $string = wordwrap($string, $length);
          $string = explode("\n", $string, 2);
          $string = $string[0] . $append;
        }
        return $string;
      }
      
      /**
       * Override the parent display() so we can provide a different container.
       */
      public function display() {
        $singular = $this->_args['singular'];
        $data_attr = '';
        if ( $singular ) {
          $data_attr = " data-wp-lists='list:$singular'";
        }
        $this->display_tablenav( 'top' );
        ?>
        <div class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
          <div id="the-list"<?php echo $data_attr; ?>>
            <?php $this->display_rows_or_placeholder(); ?>
          </div>
        </div>
        <?php
        $this->display_tablenav( 'bottom' );
      }
      
      protected function get_table_classes() {
        return array( 'widefat', $this->_args['plural'] );
      }
      
      public function display_rows() {
      
        foreach ( (array) $this->items as $product ) {

          $action_links = array();
          if( !empty( $product['url'] ) ) {
            $action_links[] = '<a target="_blank" class="install-now button" href="' . $product['url'] . '" aria-label="' . __( 'More Details' ) . '">' . __( 'More Details' ) . '</a>';
          }
          
          ?>
          <div class="plugin-card">
            <div class="plugin-card-top" style="min-height:200px;">
              <?php if( !empty( $product[ 'icon' ] ) ) { ?>
                <a target="_blank" href="<?php echo esc_url( $product['url'] ); ?>" class="thickbox plugin-icon"><img src="<?php echo esc_attr( $product[ 'icon' ] ) ?>" /></a>
              <?php } ?>
              <div class="name column-name">
                <h4><a target="_blank" href="<?php echo esc_url( $product['url'] ); ?>" class="thickbox"><?php echo $product[ 'name' ]; ?></a></h4>
              </div>
              <div class="action-links">
                <?php
                  if ( $action_links ) {
                    echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
                  }
                ?>
              </div>
              <div class="desc column-description">
                <p><?php echo $this->truncate( $product[ 'description' ] ); ?></p>
              </div>
            </div>
            <div class="plugin-card-bottom">
              <div class="vers column-rating product-type">
                <b><?php echo ucfirst( $product['type'] ); ?></b>
              </div>
              <div class="column-compatibility">
                <?php
                if ( ! empty( $product['tested'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $product['tested'] ) ), $product['tested'], '>' ) ) {
                  echo '<span class="compatibility-untested">' . __( '<strong>Untested</strong> with your version of WordPress' ) . '</span>';
                } elseif ( ! empty( $product['requires'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $product['requires'] ) ), $product['requires'], '<' ) ) {
                  echo '<span class="compatibility-incompatible">' . __( '<strong>Incompatible</strong> with your version of WordPress' ) . '</span>';
                } else {
                  echo '<span class="compatibility-compatible">' . __( '<strong>Compatible</strong> with your version of WordPress' ) . '</span>';
                }
                ?>
              </div>
            </div>
          </div>
          <?php
        }
      
      }
  
    }
  
  }
}
