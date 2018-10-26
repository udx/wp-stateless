<?php
/**
 * Plugin Name: WordPress LMS Plugin by LearnDash®
 * Plugin URI: https://www.learndash.com/
 *
 * Compatibility Description: Ensures compatibility with LearnDash®.
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\LearnDash')) {
        
        class LearnDash extends ICompatibility {
            protected $id = 'sfwd-lms';
            protected $title = 'LearnDash LMS';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_LEARNDASH_LMS';
            protected $description = 'Ensures compatibility with LearnDash.';
            protected $plugin_file = ['sfwd-lms/sfwd_lms.php'];

            public function module_init($sm){
                // exclude randomize_filename from LearnDash page
                add_filter('stateless_skip_cache_busting', array($this, 'skip_cache_busting'), 10, 2);
            }

            /**
             * Whether skip cache busting or not.
             */
            public function skip_cache_busting($return, $filename){
                if(strpos($filename, 'sfwd-') === 0 || $this->hook_from_learndash()){
                    return $filename;
                }
                return $return;
            }

            /**
             * Determine where we hook from
             * We need to do this only for something specific in LearnDash plugin
             *
             * @return bool
             */
            private function hook_from_learndash() {
                $call_stack = debug_backtrace();
                if( 
                    !empty($call_stack[6]['function']) && 
                    $call_stack[6]['function'] == 'sanitize_file_name' && 
                    (
                        strpos( $call_stack[6]['file'], 'class-ld-semper-fi-module.php' ) ||
                        strpos( $call_stack[6]['file'], 'class-ld-cpt-instance.php' )
                    )
                ){
                    return true;
                }

                return false;
            }

        }

    }

}
