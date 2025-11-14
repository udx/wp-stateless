/**
 * UsabilityDynamics Utility - Process
 *
 * Handles pocess running.
 *
 * @example
 *
 *    require( 'udx.utility.process' ).create({
 *      id: 'some-task',
 *      url: 'admin-ajax.php?action=some-task',
 *      interval: 100,
 *      onError: function() {},
 *      onComplete: function() {}
 *    });
 *
 * @version 0.1.0
 */
define( 'udx.utility.process', [ 'udx.utility', 'async', 'jquery' ], function() {
  console.debug( 'udx.utility.process', 'loaded' );

  // Modules.
  var Auto      = require( 'async' ).auto;
  var Series    = require( 'async' ).series;
  var Utility   = require( 'udx.utility' );

  /**
   * Process Instance
   *
   * @param args              {Object}
   * @param args.id           {String}
   * @param args.ajax         {String}
   * @param args.args         {Object}
   * @param args.poll         {Number}
   * @param args.timeout      {Number}
   * @param args.protocol     {String}
   * @param args.format       {String}
   * @param args.headers      {Object}
   * @param args.onStart      {Function}
   * @param args.onComplete   {Function}
   * @param args.onError      {Function}
   * @param args.onTimeout    {Function}
   *
   * @returns {Process}
   * @constructor
   */
  function Process( args ) {
    console.debug( 'udx.utility.process', 'new Process' );

    // Configure Settings.
    this.settings = Utility.defaults( args, {
      id: null,
      ajax: null,
      args: {},
      poll: 5000,
      timeout: 10000,
      protocol: 'ajax',
      format: 'json',
      headers: {},
      onStart: function onStart( error, data ) {},
      onComplete: function onComplete( error, data ) {},
      onError: function onError( error, data ) {},
      onTimeout: function onTimeout( error, data ) {}
    });

    this._start = new Date().getTime();

    //console.debug( 'process.settings', this.settings );

    // @chainable
    return Process.instances[ args.id ] = this;

  }

  /**
   * Instance Properties.
   *
   */
  Object.defineProperties( Process.prototype, {
    start: {
      /**
       * Start Process.
       *
       */
      value: function startProcess() {
        console.debug( 'udx.utility.process', 'startProcess' );

        var _context = this;

        jQuery.ajax({
          url: _context.settings.ajax,
          timeout: _context.settings.timeout,
          async: true,
          cache: false,
          type: 'GET',
          contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
          dataType: _context.settings.format || 'json',
          headers: _context.settings.headers || {},
          data: Utility.extend({
            event: 'start-process',
            id: _context.settings.id,
            type: _context.settings.type
          }, _context.settings.args ),
          beforeSend: function beforeSend() {
            // console.debug( 'udx.utility.process', 'beforeSend', arguments );
          },
          error: function error( error ) {
            console.debug( 'udx.utility.process', 'error', arguments );

            _context.settings.onStart( new Error( 'Process Start Error: ' + error ) );

          },
          complete: function complete( response, status ) {
            console.debug( 'udx.utility.process', 'complete', status );

            if( response.responseJSON ) {
              _context.settings.onStart( null, response.responseJSON );
            }

          },
          success: function success( response ) {
            console.debug( 'udx.utility.process', 'success' );

            if( response.ok ) {
              // _context.settings.onStart( null, response );
            }

          }
        });

        // @chainable
        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    poll: {
      /**
       * Poll Started Process.
       *
       */
      value: function pollProcess() {
        console.debug( 'udx.utility.process', 'pollProcess' );

        // @chainable
        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    recover: {
      /**
       * Poll Started Process.
       *
       */
      value: function recoverProcess() {
        console.debug( 'udx.utility.process', 'recoverProcess' );

        // @chainable
        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    }
  });

  /**
   * Constructor Properties.
   *
   */
  Object.defineProperties( Process, {
    create: {
      /**
       * Create Process.
       *
       * @returns {Process}
       */
      value: function create() {
        return new Process( arguments[0] );
      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    instances: {
      /**
       * Track Instances.
       *
       */
      value: {},
      enumerable: false,
      configurable: true,
      writable: true
    }
  });

  // Expose Process.
  return Process;

});
