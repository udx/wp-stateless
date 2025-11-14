/**
 * Event Analytics
 *
 *  data-event="category/action"
 *  data-track
 *
 * http://www.google-analytics.com/analytics.js
 *
 * @version 0.1.0
 * @returns {Object}
 */
define( 'udx.analytics', [ '//www.google-analytics.com/analytics.js' ], function analyticsModule() {
  // console.debug( 'udx.analytics', 'analyticsModule' );

  function Analytics( settings ) {
    // console.debug( 'udx.analytics', 'Analytics()', settings );

    if( 'string' === typeof settings ) {
      settings = { id: settings }
    }

    if( 'function' !== typeof ga ) {
      return console.error( 'udx.analytics', 'The ga variable is not a function.' )
    }

    if( !settings.id ) {
      return console.error( 'udx.analytics', 'No id provided.' )
    }

    ga( 'create', settings.id, {
      // name: undefined,
      // alwaysSendReferrer: true,
      // cookieName: '_ga',
      // cookieExpires: 63072000
      // clientId: undefined,
      userId: settings.userId || undefined,
      cookieDomain: window.location.hostname
    });

    this.setView();

    return this;

  }

  /**
   * Prototype Properties.
   *
   */
  Object.defineProperties( Analytics.prototype, {
    ga: {
      value: ga,
      enumerable: true,
      configurable: true,
      writable: true
    },
    autoLink: {
      value: function autoLink() {

        ga(function( tracker ) {

          var page = tracker.get('name');

          //var linker = new window.gaplugins.Linker( tracker );
          // var output = linker.decorate('//www.eventbrite.com');
          // ga('require', 'linker');
          // ga('linker:autoLink', ['eventbrite.com', 'www.eventbrite.com']);
          // var clientId = tracker.get('clientId');
          // console.log( 'clientId', clientId );

        });

        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    emit: {
      /**
       * Sent Event
       *
       * category
       *
       * @param eventCategory
       * @param eventAction
       * @param eventLabel
       * @param eventValue
       */
      value: function emitEvent( eventCategory, eventAction, eventLabel, eventValue ) {
        // console.debug( 'udx.analytics', 'emitEvent' );

        ga( 'send', 'event', {
          eventCategory: eventCategory,
          eventAction: eventAction,
          eventLabel: eventLabel,
          eventValue: eventValue
        });

        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    set: {
      /**
       * Set Something
       *
       * referrer
       * campaignName
       * campaignId
       * campaignSource
       * campaignMedium
       * campaignKeyword
       * campaignContent
       * screenResolution
       * viewportSize
       *
       * screenName
       * hostname
       * title
       * page
       *
       *
       * appName
       * appId
       * appVersion
       *
       * @param key
       * @param value
       */
      value: function set( key, value ) {
        // console.debug( 'udx.analytics', 'set' );

        ga('set', key, value );

        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    sendHit: {
      /**
       * Send Hit
       *
       * Hit Types
       * * pageview
       * * screenview
       * * event
       * * transaction
       * * item
       * * social
       * * exception
       * * timing
       *
       */
      value: function sendHit( hitType, page ) {
        // console.debug( 'udx.analytics', 'sendHit' );

        ga( 'send', {
          hitType: hitType || 'pageview',
          page: page || document.location.pathname
        });

        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    setView: {
      /**
       * Determine and set view type.
       *
       * screenview
       *
       * @param page
       * @param title
       */
      value: function setView( page, title ) {
        // console.debug( 'udx.analytics', 'setView' );
        ga( 'send', 'pageview' );
        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    setClient: {
      value: function setClient() {},
      enumerable: true,
      configurable: true,
      writable: true
    },
    setSocial: {
      value: function setSocial() {},
      enumerable: true,
      configurable: true,
      writable: true
    }
  });

  /**
   * Constructor Properties.
   *
   */
  Object.defineProperties( Analytics, {
    create: {
      /**
       * Create Analytics Session
       *
       * @param settings
       */
      value: function create( settings ) {
        return new Analytics( settings );
      },
      enumerable: true,
      configurable: true,
      writable: true
    }
  })

  return Analytics;

});

