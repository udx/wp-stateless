<?php

/**
 * WP CLI SM Commands
 */
if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI_Command')) {

  /**
   * WP-CLI command
   */
  class SM_CLI_Command extends WP_CLI_Command {

    public $url;

    /**
     * @param $args
     * @param $assoc_args
     */
    public function __construct($args = array(), $assoc_args = array()) {
      parent::__construct();

      if (php_sapi_name() != 'cli') {
        die('Must run from command line');
      }

      //** Setup some server settings */
      set_time_limit(0);
      ini_set('memory_limit', '2G');
      //** Setup error handling */
      error_reporting(E_ALL);
      ini_set('display_errors', 1);
      ini_set('log_errors', 0);
      ini_set('html_errors', 0);

      if (!class_exists('SM_CLI_Process')) {
        require_once(dirname(__FILE__) . '/class-sm-cli-process.php');
      }

      if (!class_exists('SM_CLI')) {
        require_once(dirname(__FILE__) . '/class-sm-cli.php');
      }

      /** Be sure that we add url parameter to commands if we have MultiSite installation. */
      $this->url = is_multisite() ? WP_CLI::get_runner()->config['url'] : false;
    }

    /**
     * Sync Data
     *
     * ## OPTIONS
     *
     * <type>
     * : Which data we want to sync. May be images or files.
     * 
     * --url
     * : Blog URL if multisite installation.
     *
     * --start
     * : Indent (sql start). It's ignored on batches.
     *
     * --limit
     * : Limit per query (sql limit)
     *
     * --end
     * : Where ( on which row ) we should stop script. It's ignored on batches
     *
     * --batch
     * : Number of Batch. Default is 1.
     *
     * --batches
     * : General amount of batches.
     *
     * --b
     * : Runs command using batches till it's done. Other parameters will be ignored. There are 10 batches by default. Batch is external command process
     *
     * --log
     * : Show more information in command line
     *
     * --o
     * : Process includes database optimization and transient removing.
     *
     * --order
     * : Order. May be ASC or DESC
     *
     * ## EXAMPLES
     *
     * wp stateless sync images --url=example.com --b
     * : Run process looping 10 batches. Every batch is external command 'wp stateless sync images --url=example.com --batch=<number> --batches=10'
     *
     * wp stateless sync images --url=example.com --b --batches=100
     * : Run process looping 100 batches.
     *
     * wp stateless sync images --url=example.com --b --batches=10 --batch=2
     * : Run second batch from 10 batches manually.
     *
     * wp stateless sync images --url=example.com --log
     * : Run default process showing additional information in command line.
     *
     * wp stateless sync images --url=example.com --end=3000 --limit=50
     * : Run process from 1 to 3000 row. Splits process by limiting queries to 50 rows. So, the current example does 60 queries ( 3000 / 50 = 60 )
     *
     * wp stateless sync images --url=example.com --start=777 --end=3000 --o
     * : Run process from 777 to 3000 row. Also does database optimization and removes transient in the end.
     *
     * @synopsis <type> [--url=<val>] [--start=<val>] [--limit=<val>] [--end=<val>] [--batch=<val>] [--batches=<val>] [--b] [--log] [--o] [--order=<val>]
     * @param $args
     * @param $assoc_args
     */
    public function sync($args, $assoc_args) {

      $sm_mode = ud_get_stateless_media()->get('sm.mode');
      if ($sm_mode === 'stateless') {
        WP_CLI::error('Sync is not supported in Stateless mode');
      }
      //** DB Optimization process */
      if (isset($assoc_args['o'])) {
        $this->_before_command_run();
      }
      //** Run batches */
      if (isset($assoc_args['b'])) {
        if (empty($args[0])) {
          WP_CLI::error('Invalid type parameter');
        }
        $this->_run_batches('sync', $args[0], $assoc_args);
      }
      //** Or run command as is. */
      else {
        if (!class_exists('SM_CLI_Sync')) {
          require_once(dirname(__FILE__) . '/class-sm-cli-sync.php');
        }
        if (class_exists('SM_CLI_Sync')) {
          $object = new SM_CLI_Sync($args, $assoc_args);
          $controller = !empty($args[0]) ? $args[0] : false;
          if ($controller && is_callable(array($object, $controller))) {
            call_user_func(array($object, $controller));
          } else {
            WP_CLI::error('Invalid type parameter');
          }
        } else {
          WP_CLI::error('Class SM_CLI_Sync is undefined.');
        }
      }
      //** Get rid of all transients and run DB optimization again */
      if (isset($assoc_args['o'])) {
        $this->_after_command_run();
      }
    }

    /**
     * Upgrade Data
     *
     * ## OPTIONS
     *
     * <type>
     * : Which data we want to upgrade
     *
     * --start
     * : Indent (sql start). It's ignored on batches.
     *
     * --limit
     * : Limit per query (sql limit)
     *
     * --end
     * : Where ( on which row ) we should stop script. It's ignored on batches
     *
     * --batch
     * : Number of Batch. Default is 1.
     *
     * --batches
     * : General amount of batches.
     *
     * --b
     * : Runs command using batches till it's done. Other parameters will be ignored. There are 10 batches by default. Batch is external command process
     *
     * --log
     * : Show more information in command line
     *
     * --o
     * : Process includes database optimization and transient removing.
     * 
     * --url
     * : Blog URL if multisite installation.
     *
     * ## EXAMPLES
     *
     * wp stateless upgrade meta --url=example.com --b
     * : Run process looping 10 batches. Every batch is external command 'wp stateless upgrade meta --url=example.com --batch=<number> --batches=10'
     *
     * wp stateless upgrade meta --url=example.com --b --batches=100
     * : Run process looping 100 batches.
     *
     * wp stateless upgrade meta --url=example.com --b --batches=10 --batch=2
     * : Run second batch from 10 batches manually.
     *
     * wp stateless upgrade meta --url=example.com --log
     * : Run default process showing additional information in command line.
     *
     * wp stateless upgrade meta --url=example.com --end=3000 --limit=50
     * : Run process from 1 to 3000 row. Splits process by limiting queries to 50 rows. So, the current example does 60 queries ( 3000 / 50 = 60 )
     *
     * wp stateless upgrade meta --url=example.com --start=777 --end=3000 --o
     * : Run process from 777 to 3000 row. Also does database optimization and removes transient in the end.
     *
     * @synopsis <type> [--url=<val>] [--start=<val>] [--limit=<val>] [--end=<val>] [--batch=<val>] [--batches=<val>] [--b] [--log] [--o]
     * @param $args
     * @param $assoc_args
     */
    public function upgrade($args, $assoc_args) {
      //** DB Optimization process */
      if (isset($assoc_args['o'])) {
        $this->_before_command_run();
      }
      //** Run batches */
      if (isset($assoc_args['b'])) {
        if (empty($args[0])) {
          WP_CLI::error('Invalid type parameter');
        }
        $this->_run_batches('upgrade', $args[0], $assoc_args);
      }
      //** Or run command as is. */
      else {
        if (!class_exists('SM_CLI_Upgrade')) {
          require_once(dirname(__FILE__) . '/class-sm-cli-upgrade.php');
        }
        if (class_exists('SM_CLI_Upgrade')) {
          $object = new SM_CLI_Upgrade($args, $assoc_args);
          $controller = !empty($args[0]) ? $args[0] : false;
          if ($controller && is_callable(array($object, $controller))) {
            call_user_func(array($object, $controller));
          } else {
            WP_CLI::error('Invalid type parameter');
          }
        } else {
          WP_CLI::error('Class SM_CLI_Upgrade is undefined.');
        }
      }
      //** Get rid of all transients and run DB optimization again */
      if (isset($assoc_args['o'])) {
        $this->_after_command_run();
      }
    }

    /**
     * Runs batches
     */
    private function _run_batches($method, $type, $assoc_args) {
      $batches = isset($assoc_args['batches']) ? $assoc_args['batches'] : 10;
      if (!is_numeric($batches) || $batches <= 0) {
        WP_CLI::error('Parameter --batches must have numeric value.');
      }
      $limit = isset($assoc_args['limit']) ? $assoc_args['limit'] : 100;
      if (!is_numeric($limit) || $limit <= 0) {
        WP_CLI::error('Parameter --limit must have numeric value.');
      }
      $force = isset($assoc_args['force']) ? '--force' : '';

      for ($i = 1; $i <= $batches; $i++) {

        if (!empty($this->url)) {
          $command = "wp stateless {$method} {$type} {$force} --batch={$i} --batches={$batches} --limit={$limit} --url={$this->url}";
        } else {
          $command = "wp stateless {$method} {$type} {$force} --batch={$i} --batches={$batches} --limit={$limit}";
        }

        WP_CLI::line('...');
        WP_CLI::line("Launching external command '{$command}'");
        WP_CLI::line('Waiting...');

        @ob_flush();
        flush();

        $r = SM_CLI::launch($command, false, true);

        if ($r->return_code) {
          WP_CLI::error("Something went wrong. External command process failed.");
        } else {
          echo $r->stdout;
        }
      }
    }

    /**
     * Optimization process
     * Runs before command's process
     */
    private function _before_command_run() {
      WP_CLI::line("Starting Database optimization process. Waiting...");
      @ob_flush();
      flush();
      $command = !empty($this->url) ? "wp db optimize --url={$this->url}" : "wp db optimize";
      $r = SM_CLI::launch($command, false, true);
      if ($r->return_code) {
        WP_CLI::error("Something went wrong. Database optimization process failed.");
      } else {
        WP_CLI::success("Database is optimized");
      }
    }

    /**
     * Optimization process
     * Runs after command's process
     */
    private function _after_command_run() {
      //** Run transient flushing */
      WP_CLI::line("Starting remove transient. Waiting...");
      @ob_flush();
      flush();
      $command = !empty($this->url) ? "wp transient delete-all --url={$this->url}" : "wp transient delete-all";
      $r = SM_CLI::launch($command, false, true);
      if ($r->return_code) {
        WP_CLI::error("Something went wrong. Transient process failed.");
      } else {
        WP_CLI::success("Transient is removed");
      }
      //** Run MySQL optimization */
      WP_CLI::line("Starting Database optimization process. Waiting...");
      @ob_flush();
      flush();
      $command = !empty($this->url) ? "wp db optimize --url={$this->url}" : "wp db optimize";
      $r = SM_CLI::launch($command, false, true);
      if ($r->return_code) {
        WP_CLI::error("Something went wrong. Database optimization process failed.");
      } else {
        WP_CLI::success("Database is optimized");
      }
    }
  }

  /** Add the commands from above */
  WP_CLI::add_command('stateless', 'SM_CLI_Command');
}
