/**
 * UsabilityDynamics Utility - Activity
 *
 * Handles pocess running.
 *
 * @example
 *
 *    var Activity = require( 'udx.utility.activity' );
 *
 *    Activity.create({
 *      ajax: 'admin-ajax.php?action=some-task',
 *      args: {
 *        id: 'some-task',
 *      },
 *      interval: 100,
 *      onError: function() {},
 *      onComplete: function() {}
 *    });
 *
 * @version 0.1.0
 */
define( 'udx.utility.activity', [ 'udx.utility', 'async', 'jquery' ], function() {
  console.debug( 'udx.utility.activity', 'loaded' );

  // Modules.
  var Auto      = require( 'async' ).auto;
  var Series    = require( 'async' ).series;
  var Utility   = require( 'udx.utility' );

  /**
   * Activity Instance
   *
   * @param args              {Object}
   * @param args.id           {String} ID of activity.
   * @param args.activity     {String} Name of activity type.
   * @param args.ajax         {String} URL of AJAX handler.
   * @param args.args         {Object} Extra arguments to add to request.
   * @param args.poll         {Number} Update poll interval.
   * @param args.timeout      {Number}
   * @param args.protocol     {String}
   * @param args.format       {String}
   * @param args.headers      {Object}
   * @param args.onStart      {Function}
   * @param args.onCreate     {Function}
   * @param args.onPoll       {Function}
   * @param args.onComplete   {Function}
   * @param args.onError      {Function}
   * @param args.onTimeout    {Function}
   *
   * @returns {Activity}
   * @constructor
   */
  function Activity( args ) {
    console.debug( 'udx.utility.activity', 'new Activity' );

    // Configure Settings.
    this.settings = Utility.defaults( args, {
      ajax: null,
      id: null,
      activity: '',
      args: {},
      poll: 15000,
      timeout: 5000,
      protocol: 'ajax',
      format: 'json',
      headers: {},
      onStart: function onStart( error, data ) {},
      onCreate: function onCreate( error, data ) {},
      onPoll: function onPoll( error, data ) {},
      onComplete: function onComplete( error, data ) {},
      onError: function onError( error, data ) {},
      onTimeout: function onTimeout( error, data ) {}
    });

    // Create Timers Container.
    this._timers = {
      create: new Date().getTime(),
      poll: undefined
    };

    // @chainable
    return Activity.instances[ this.settings.id ] = this;

  }

  /**
   * Instance Properties.
   *
   */
  Object.defineProperties( Activity.prototype, {
    create: {
      /**
       * Create Activity.
       *
       */
      value: function createActivity() {
        console.debug( 'udx.utility.activity', 'createActivity' );

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
            id: _context.settings.id,
            activity: _context.settings.type,
            event: 'create'
          }, _context.settings.args ),
          error: function error( error ) {
            console.debug( 'udx.utility.activity', 'create', '::error', error );

            _context.settings.onStart( new Error( 'Activity Start Error: ' + error ) );

          },
          complete: function complete( response, status ) {
            console.debug( 'udx.utility.activity', 'create', '::complete', status );

            if( response.responseJSON ) {
              _context.settings.onCreate( null, response.responseJSON );
            }

            // Create Poll.
            _context._timers.poll = window.setInterval( _context.poll.bind( _context ), _context.settings.poll );

          }
        });

        // @chainable
        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    start: {
      /**
       * Start Activity.
       *
       */
      value: function startActivity() {
        console.debug( 'udx.utility.activity', 'startActivity' );

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
            id: _context.settings.id,
            activity: _context.settings.type,
            event: 'start'
          }, _context.settings.args ),
          error: function error( error ) {
            console.debug( 'udx.utility.activity', 'start', '::error', error );

            _context.settings.onStart( new Error( 'Activity Start Error: ' + error ) );

          },
          complete: function complete( response, status ) {
            console.debug( 'udx.utility.activity', 'start', 'complete', status );

            if( response.responseJSON ) {
              _context.settings.onStart( null, response.responseJSON );
            }

            // Create Poll.
            _context._timers.poll = window.setInterval( _context.poll.bind( _context ), _context.settings.poll );

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
       * Poll Started Activity.
       *
       */
      value: function pollActivity() {
        // console.debug( 'udx.utility.activity', 'pollActivity' );

        // Run Poll Callback.
        // this.settings.onPoll( error, data );

        // @chainable
        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    recover: {
      /**
       * Poll Started Activity.
       *
       */
      value: function recoverActivity() {
        console.debug( 'udx.utility.activity', 'recoverActivity' );

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
  Object.defineProperties( Activity, {
    create: {
      /**
       * Create Activity.
       *
       * @returns {Activity}
       */
      value: function create() {
        return new Activity( arguments[0] );
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

  // Expose Activity.
  return Activity;

});
