<?php

use wpCloud\StatelessMedia\Migrator;
use \wpCloud\StatelessMedia\Batch\BatchTaskManager;

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
     * : Which data we want to upgrade. Currently only 'meta' type is supported.
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
     * Run migrations
     *
     * ## OPTIONS
     *
     * [<id|auto>]
     * : start migration by its ID, or automatically run all pending migrations (auto). Auto mode does not support '--force' parameter.
     *
     * --force
     * : Force starting migration even if it is not pending
     * 
     * --progress=<interval>
     * : Monitor migration progress every <interval> seconds (minimum 1)
     * 
     * --email=<email>
     * : Send email notification to specified email when migration is finished. By default it uses email from plugin settings. You can also use a list of emails, comma separated.
     * 
     * --url
     * : Blog URL if multisite installation.
     * 
     * --yes
     * : Confirm automatically.
     * 
     *
     * ## EXAMPLES
     *
     * wp stateless migrate
     * : List migrations information.
     *
     * wp stateless migrate --url=example.com
     * : List migrations information for specific blog in multisite network.
     *
     * wp stateless migrate --progress=3
     * : Display current migration progress every 3 seconds.
     *
     * wp stateless migrate 20240216150177
     * : Start migration with ID 20240216150177.
     *
     * wp stateless migrate auto --email=mail@example.com --yes
     * : Automatically run all pending migrations without confirmation and send notifications to mail@example.com.
     *
     * wp stateless migrate 20240216150177 --progress=2 --yes
     * : Start migration with ID 20240216150177 without confirmation and display progress every 2 seconds.
     *
     * wp stateless migrate 20240216150177 --force --email=mail@example.com,user@domain.com --url=example.com 
     * : Start migration with ID 20240216150177 for specific blog in multisite network. Start migration even if it was already finished or failed. After finishing send email notification to mail@example.com and user@domain.com.
     *
     * @synopsis [<id|auto>] [--force] [--progress=<val>] [--email=<val>] [--yes] [--url=<val>]
     * @param $args
     * @param $assoc_args
     */
    public function migrate($args, $assoc_args) {
      $id = $args[0] ?? '';

      // No migration ID provided, list all migrations and exit
      if ( empty($id) && !isset($assoc_args['progress']) ) {
        $this->_list_migrations();

        return;
      } else if ( !empty($id) ) {
        if ( $id === 'auto' ) {
          if ( isset($assoc_args['force']) ) {
            WP_CLI::error( 'The parameter --force is not supported for auto mode.' );

            return;
          }

          $this->_auto_migrate($assoc_args);

          return;
        } else {
          $this->_run_migration($id, $assoc_args);
        }
      }

      if ( $id !== 'auto' && isset($assoc_args['progress']) ) {
        $this->_check_progress($assoc_args['progress']);
      }
    }

    /**
     * Run all pending migrations
     * 
     * @param array $assoc_args
     */
    private function _auto_migrate($assoc_args) {
      $progress = $assoc_args['progress'] ?? 1;

      do {
        // We need to omit the cache and get the data directly from the db
        $migrations = apply_filters('wp_stateless_get_migrations', []);

        $keys = array_reverse( array_keys($migrations) );
        $id = null;

        // Do we have next pending migration?
        foreach ($keys as $key) {
          if ( $migrations[$key]['status'] === Migrator::STATUS_PENDING ) {
            $id = $key;
            break;
          }
        }

        if ( !empty($id) ) {
          $command = "wp stateless migrate $id --yes --progress=$progress";

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

          continue;
        }

        break;

      } while(true);

      WP_CLI::success('No pending migrations left.');
    }

    /**
     * Run the specific migration
     * 
     * @param string $id
     * @param array $assoc_args
     */
    private function _run_migration($id, $assoc_args) {
      $migrations = $this->_get_migrations();

      if ( !isset($migrations[$id]) ) {
        WP_CLI::error("Invalid migration ID: $id");
      }

      $migration = $migrations[$id];

      // Check if we can run migration
      if ( !$migration['can_start'] && !isset($assoc_args['force']) ) {
        WP_CLI::error( 'Migration ' . $migration['description'] . ' is not ready for starting. ' . PHP_EOL . 
          'Migration status: ' . $migration['status_text'] . ', ' . strip_tags($migration['message']) . PHP_EOL . 
          'Please use --force to run it anyway.'
        );
      }

      $email = $assoc_args['email'] ?? ud_get_stateless_media()->get_notification_email();

      WP_CLI::line( 'Please make a backup copy of your database and try not to upload, change or delete your media while the process continues.' . PHP_EOL . 
        "After the process finishes an email will be sent to: $email" . PHP_EOL 
      );

      WP_CLI::confirm( "Are you sure you want to run the migration $id?", $assoc_args );

      // Run migration
      Migrator::instance()->start_migration([], [
        'id' => $id, 
        'is_migration' => true,
        'force' => true,
      ]);

      WP_CLI::success( "Started migration $id" );
    }

    /**
     * Get migrations state
     * 
     * @return array
     */
    private function _get_migrations() {
      $migrations = apply_filters('wp_stateless_batch_state', [], ['force_migrations' => true]);
      return $migrations['migrations'] ?? [];
    }

    /**
     * List migrations
     */
    private function _list_migrations() {
      $migrations = $this->_get_migrations();

      if ( empty($migrations) ) {
        WP_CLI::success('No migrations found');
      }

      $data = [];

      foreach ($migrations as $id => $migration) {
        $data[$id] = [
          'id' => $id,
          'description' => $migration['description'],
          'status' => $migration['status_text'],
          'message' => strip_tags($migration['message']),
        ];
      }

      WP_CLI\Utils\format_items('table', $data, ['id', 'description', 'status', 'message']);
    }

    /**
     * Check progress
     */
    private function _check_progress($progress) {
      global $wpdb;

      $sleep = max($progress, 1);
      $key = BatchTaskManager::instance()->get_state_key(); 

      $sql = "SELECT option_value FROM $wpdb->options WHERE option_name = '%s' LIMIT 1";
      $sql = $wpdb->prepare($sql, $key);

      $description = '';

      do {
        // We need to omit the cache and get the data directly from the db
        $state = $wpdb->get_var($sql);
        $state = maybe_unserialize($state);

        if ( empty($state) || !isset($state['is_migration']) || !$state['is_migration'] ) {
          $message = empty($description) ? 'Migration finished' : "Migration '$description' finished";
          WP_CLI::success($message);

          return;
        }

        $description = $state['description'] ?? '';
        $completed = $state['completed'] ?? 0;
        $total = $state['total'] ?? 0;

        $percent = $total > 0 ? round($completed / $total * 100, 2) : 0;

        $message = sprintf("Migration '%s' compeleted %.2f%%: %d of %d items processed", $description, $percent, $completed, $total);
        WP_CLI::line($message);

        sleep($sleep);
      } while (true);
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
