/**
 * ServiceBus
 *
 * @version 0.1.0
 * @returns {Object}
 */
define( 'udx.utility.bus', function( require, exports, module ) {
  console.debug( 'udx.utility.bus', 'loaded' );

  /**
   * ServiceBus Instance.
   *
   * @param options
   * @param callback
   * @returns {ServiceBus}
   */
  function ServiceBus( options, callback ) {
    console.debug( 'udx.utility.bus', 'ServiceBus' );

    return this;

  }

  Object.defineProperties( ServiceBus.prototype, {
    on: {
      /**
       *
       * @returns {*}
       */
      value: function on() {
        console.debug( 'udx.utility.bus', 'on()' );

        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    off: {
      /**
       *
       * @returns {*}
       */
      value: function on() {
        console.debug( 'udx.utility.bus', 'off()' );

        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    },
    emit: {
      /**
       *
       * @returns {*}
       */
      value: function on() {
        console.debug( 'udx.utility.bus', 'emit()' );

        return this;

      },
      enumerable: true,
      configurable: true,
      writable: true
    }
  });

  Object.defineProperties( ServiceBus.create, {
    create: {
      /**
       * Create ServiceBus Instance.
       *
       * @param options
       * @param callback
       * @returns {ServiceBus}
       */
      value: function create( options, callback ) {
        return new ServiceBus( options, callback );
      },
      enumerable: true,
      configurable: true,
      writable: true
    }
  });

  return ServiceBus;

});

