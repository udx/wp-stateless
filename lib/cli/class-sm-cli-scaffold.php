<?php

class SM_CLI_Scaffold {

  /**
   * Storage for dynamic properties
   * Used by magic __set, __get
   *
   * @protected
   * @type array
   */
  protected $_properties = array();

	/**
	 * @param $args
	 * @param $assoc_args
	 */
  public function __construct( $args, $assoc_args ) {
    if ( php_sapi_name() != 'cli' ) {
      die('Must run from command line');
    }
    
    $this->args = $args;
    $this->assoc_args = $assoc_args;
    foreach( $assoc_args as $k => $v ) {
      $this->{$k} = $v;
    }
    
    /* Set default Limit */
    $this->limit = is_numeric( $this->limit ) && $this->limit > 0 ? $this->limit : 100;
  }

	/**
	 * Forces data printing to command line ignoring buffer.
	 *
	 * @param string $msg
	 */
  public function output( $msg = '' ) {
    $args = $this->assoc_args;
    if( !isset( $args['log'] ) ) return null;
    echo date( 'H:i:s', time() ) . ': ' . $msg . ' ' . $this->memory_usage() . PHP_EOL;        
    @ob_flush();
    flush();
  }
  
  /**
   * Returns Memory Usage information.
   */
  public function memory_usage() {
    $args = $this->assoc_args;
    if( !isset( $args['memory-usage'] ) ) return null;
    static $last_usage = 0;
    $differents = $last_usage ? number_format( ( memory_get_usage() / 1024 / 1024 ) - $last_usage, 3 ) . 'Mb' : 'none';
    $current_usage = number_format( $last_usage = memory_get_usage() / 1024 / 1024, 3 ) . 'Mb';
    return sprintf( "Memory Usage: %s. Diff: %s.", $current_usage, $differents );
  }
  
  /**
   * Returns domain of current blog.
   *
   */
  public function get_current_blog_domain() {
    $url = get_home_url();
    $pieces = parse_url( $url );
    $domain = isset( $pieces[ 'host' ] ) ? $pieces['host'] : false;
    return $domain;
  }

	/**
	 * @param $key
	 *
	 * @return null
	 */
  public function __get( $key ) {
    return isset( $this->_properties[ $key ] ) ? $this->_properties[ $key ] : NULL;
  }

	/**
	 * @param $key
	 * @param $value
	 */
  public function __set( $key, $value ) {
    $this->_properties[ $key ] = $value;
  }

}