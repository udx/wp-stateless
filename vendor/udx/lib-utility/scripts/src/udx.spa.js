/**
 * SPA Hybrid Bundle
 * =================
 * Build library for supporting  SPAs on mobile, tablet and web platforms.
 *
 *
 * Bundled Dependencies
 * --------------------
 * * udx.spa.sticky.js
 * * knockout.js
 * * pace.js
 * * history.js
 *
 * @todo Use jQuery.load() if available to fetch seondary pages.
 * @todo bindNavigation should be live.
 */
define( 'udx.spa', [ 'module', 'require', 'exports', 'knockout', 'knockout.mapping', 'knockout.localStorage', 'pace', 'udx.utility', 'udx.utility.imagesloaded' ], function spaReady( module, require, exports ) {
  console.debug( 'udx.spa', 'spaReady' );

  // Modules.
  var Pace          = require( 'pace' );
  var ko            = require( 'knockout' );
  var mapping       = require( 'knockout.mapping' );
  var utility       = require( 'udx.utility' );
  var imagesloaded  = require( 'udx.utility.imagesloaded' );

  Pace.options = {
    // elements: { selectors: ['.sdf'] }
    document: true,
    ajax: true,
    eventLag: false,
    restartOnPushState: true,
    restartOnRequestAfter: true
  };

  Pace.start();

  /**
   *
   *
   * @constructor
   */
  function SPA( options ) {
    // console.debug( 'udx.spa', 'create' );

    Object.extend( options || {}, {
      name: undefined,
      debug: undefined,
      api: window.location.href + 'api',
      bodyClass: 'spa-enabled'
    });

    if( !options.node ) {
      options.node = document.body;
    }

    if( 'function' !== typeof options.node.getAttribute ) {
      console.error( 'udx.spa', 'Target not specifed.' );
      return this;
    }

    // Instance Models and Variables.
    var _version    = options.node.getAttribute( 'data-version' );
    var _ajax       = options.node.getAttribute( 'data-ajax' );
    var _home       = options.node.getAttribute( 'data-home' );
    var _debug      = options.node.getAttribute( 'data-debug' ) || false;
    var _settings   = {};
    var _locale     = {};

    // ko.applyBindings( new SPA.prototype.ViewModel, options.node );

    return this;

  }

  /**
   * Instance Prototype.
   *
   */
  Object.defineProperties( SPA.prototype, {
    configure: {
      value: function configure() {
        // console.debug( 'udx.spa', 'configure' );
      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    ViewModel: {
      /**
       * Initialize View Model
       *
       * window.setInterval( function() { this.schedules.push( { name: 'Mario', credits: 5800 } ); }.bind( this ), 3000 );
       *
       */
      value: function ViewModel() {
        console.debug( 'udx.spa', 'ViewModel' );

        var self = this;

        // Observable Objects.
        self.schedules  = require( 'knockout' ).observableArray([]);
        self.processes  = require( 'knockout' ).observableArray([]);
        self.state      = require( 'knockout' ).observableArray([]);

        if( 'function' === typeof jQuery ) {
          jQuery( 'li.menu-item > a' ).click( SPA.bindNavigation );
        }

        // Modify Body Class.
        // document.body.className = [ document.body.className, this.options.bodyClass ].join( ' ' );

        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    bindNavigation: {
      value: function bindNavigation( e ) {
        // console.debug( 'udx.spa', 'bindNavigation' );

        e.preventDefault();

        // Do nothing.
        if( !e.target.pathname || e.target.pathname === '/' ) {
          return null;
        }

        //history.pushState(null, 'sadf', e.target.pathname );

        jQuery( 'main' ).load( e.target.href + ' main' );

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    applyBindings: {
      value: function applyBindings() {
        // console.debug( 'udx.spa', 'applyBindings' );
      },
      enumerable: true,
      configurable: true,
      writable: true
    }
  });

  /**
   * Create Instance.
   *
   */
  Object.defineProperties( SPA, {
    create: {
      /**
       * Start Instance.
       *
       * @param options
       * @returns {spaReady.SPA}
       */
      value: function create( options ) {

        if( this instanceof Node && this.parentElement ) {
          console.log( 'SPA is instance of Node' );
          options = options || {};
          //options.node = this;
        }

        return new SPA( options || {} );
      },
      enumerable: true,
      configurable: true,
      writable: true
    }
  });

  return SPA;

});

/*
// Pace.on('start',   function () { console.debug( 'udx.spa', 'pace started'); });
// Pace.on('restart', function () { console.debug( 'udx.spa', 'pace restart'); });
// Pace.on('done',    function () { console.debug( 'udx.spa', 'pace done'); });
// Pace.on('hide',    function () { console.debug( 'udx.spa', 'pace hide'); });
// Pace.on('stop',    function () { console.debug( 'udx.spa', 'pace stop'); });
// Pace.on('done',    function () { console.debug( 'udx.spa', 'pace done'); });
*/