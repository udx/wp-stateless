<?php

class SM_CLI {

  /**
	 * Launch an external process that takes over I/O.
	 *
	 * @param string Command to call
	 * @param bool Whether to exit if the command returns an error status
	 * @param bool Whether to return an exit status (default) or detailed execution results
	 *
	 * @return int|ProcessRun The command exit status, or a ProcessRun instance
	 */
	public static function launch( $command, $exit_on_error = true, $return_detailed = false ) {
    if( !class_exists( 'SM_CLI_Process' ) ) {
      require_once( dirname( __FILE__ ) . '/class-sm-cli-process.php' );
    }
  
		$proc = SM_CLI_Process::create( $command );
		$results = $proc->run();

		if ( $results->return_code && $exit_on_error )
			exit( $results->return_code );

		if ( $return_detailed ) {
			return $results;
		} else {
			return $results->return_code;
		}
	}

}