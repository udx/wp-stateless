/**
 * ElasticSearch Analysis Client
 *
 * @todo Switch to vanilla version of elasticsearch to avoid dependency and resolve shim issue.
 */
define( 'analysis.client', [ 'jquery.elasticsearch', 'analysis.visualizer' ], function() {
  console.debug( 'analysis.client', 'loaded' );

  var options = {};
  var client;

  /**
   * Get Current Client
   *
   * @returns {*|jQuery.es.Client}
   */
  function getClient() {
    return client || createClient();
  }

  /**
   * Create Client Instance
   *
   * @returns {tinylr.Client}
   */
  function createClient( host, index, type ) {

    options = {
      host: host,
      index: index,
      type: type
    };

    return client = new jQuery.es.Client({
      hosts: host
    });

  }

  /**
   * Parse Response
   *
   * @param error
   * @param res
   */
  function parseResponse( error, res ) {
    console.debug( 'parseResponse', error, res );
    console.log( res.hits.hits );
  }

  /**
   * Get Mapping
   *
   * @param query
   * @param handleResponse
   * @returns {*|Promise}
   */
  function getMapping( query, handleResponse ) {
    console.debug( 'getMapping', typeof handleResponse );

    var request = {
      index: options.index || 'deafult',
      type: options.type || 'profile'
    };

    return getClient().indices.getMapping( request ).then( function parseResponse( res ) {

      if( 'function' === typeof handleResponse ) {
        handleResponse.call( null, res[ 'jezf-truq-qgox-hfxp' ].mappings[ 'profile' ] || {}, request )
      }

    });

  }

  function getSuggestion( query, handleResonse ) {
    console.debug( 'getResults', typeof handleResponse );

    var request = {
      index: options.index || 'deafult',
      type: options.type || 'profile',
      from: 0,
      size: 0,
      body: query
    };

    return getClient().suggest( request ).then( function parseResponse( res ) {
      console.debug( 'getSuggestion', 'parseResponse', typeof res );

      if( 'function' === typeof handleResponse ) {
        handleResponse.call( null, null, res, 'suggest', request )
      }

    });
  }

  /**
   * Get Type Mapping
   *
   * @param query
   * @param handleResponse
   * @returns {*|Promise}
   */
  function getMeta( query, handleResponse ) {
    console.debug( 'getMeta', typeof handleResponse );

    var request = {
      index: options.index || 'deafult',
      type: options.type || 'profile',
    };

    return getClient().indices.getMapping( request ).then( function parseResponse( res ) {

      if( 'function' === typeof handleResponse ) {
        handleResponse.call( null, res[ 'jezf-truq-qgox-hfxp' ].mappings[ options.type ]._meta || {}, 'meta', request )
      }

    });

  }

  /**
   * Get Facets
   *
   * @param query
   * @param facets
   * @param handleResponse
   * @returns {*|Promise}
   */
  function getFacets( query, facets, handleResponse ) {
    console.debug( 'getFacets', typeof handleResponse );

    var request = {
      index: options.index || 'deafult',
      type: options.type || 'profile',
      from: 0,
      size: 0,
      body: {
        query: {
          filtered: {
            query: query
          }
        },
        facets: facets
      }
    };

    return getClient().search( request ).then( function parseResponse( res ) {

      if( 'function' === typeof handleResponse ) {
        handleResponse.call( null, null, res.facets, 'facets', request )
      }

    }).then( function handleError( error ) {
      console.debug( 'handleError', arguments );

      if( 'function' === typeof handleResponse ) {
        handleResponse.call( error, null, {}, 'facets', request )
      }

    });

  }

  /**
   * Get Search
   *
   * @param query
   * @param handleResponse
   * @returns {*|Promise}
   * @constructor
   */
  function getResults( query, handleResponse ) {
    console.debug( 'getResults', typeof handleResponse );

    var request = {
      index: options.index || 'deafult',
      type: options.type || 'profile',
      from: 0,
      size: 0,
      body: query
    };

    return getClient().search( request ).then( function parseResponse( res ) {
      console.debug( 'getResults', 'parseResponse', typeof res );

      if( 'function' === typeof handleResponse ) {
        handleResponse.call( null, null, res, 'search', request )
      }

    });

  }

  return {
    client: getClient,
    createClient: createClient,
    getSuggestion: getSuggestion,
    getMeta: getMeta,
    getMapping: getMapping,
    getFacets: getFacets,
    getResults: getResults
  };

});