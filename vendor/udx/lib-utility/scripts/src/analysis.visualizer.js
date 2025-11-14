/**
 *
 *
 * @todo Consider adding udx.fleck for fixing strings for view.
 *
 * require( 'analysis.visualizer' )._cached
 *
 */
define( 'analysis.visualizer', [ 'http://www.google.com/jsapi/' ], function() {
  console.debug( 'analysis.visualizer' );

  /**
   * Google Visualization Library Loaded
   */
  function googleVisualizationReady() {
    console.debug( 'analysis.visualizer', 'googleVisualizationReady' );
  }

  google.load( 'visualization', '1', {
    packages: [ 'geochart', 'corechart', 'table' ],
    callback: googleVisualizationReady
  });

  var instance = this;

  var _cached = {};

  /**
   * Get Client Location from Google JS-API
   *
   * @returns {*}
   */
  function clientLocation() {
    console.debug( 'analysis.visualizer', 'clientLocation', google.loader.ClientLocation );
    return google.loader.ClientLocation;
  }

  /**
   * Convert  Term to Label
   *
   * @param key
   * @returns {*}
   */
  function termLabel( key ) {

    var labels = {
      raleigh: 'Raleigh',
      port: 'New Port',
      new: 'Wilmington',
      west: 'West River',
      lake: 'Lake',
      south: 'South Raleigh',
      north: 'North Raleigh',
      est: 'Eastern',
      east: 'East Brook',
      wilfred: 'Wilfred',
      zena: 'Durham',
      en_us: 'English',
      twitter: 'Twitter',
      facebook: 'Facebook',
      male: 'Male',
      female: 'Female'
    };

    return labels[ key ] || key;

  }

  /**
   * Render Piegraph
   *
   * @param title
   * @param data
   * @returns {*}
   */
  function Pie( title, data ) {
    console.debug( 'Pie' );

    var parsedData = google.visualization.arrayToDataTable( data );

    var element = jQuery( '<div class="result-piegraph"></div>' );

    jQuery( '.query-result' ).append( element );

    new google.visualization.PieChart( element.get( 0 ) ).draw(parsedData, {
      title: title
    });

    _cached[ title ] = {
      title: title,
      element: element
    };

    return instance;

  }

  /**
   * Render Regional Map.
   *
   * @param title
   * @param data
   * @returns {*}
   */
  function Map( title, data ) {
    console.debug( 'Map' );

    var parsedData = google.visualization.arrayToDataTable( data.raw );

    var element = jQuery( '<div class="result-map"></div>' );
    jQuery( '.query-result' ).append( element );

    new google.visualization.GeoChart( element.get( 0 ) ).draw( parsedData, {
      region: 'US',
      displayMode: 'regions',
      resolution: 'provinces',
      enableRegionInteractivity: true
    });

    _cached[ title ] = {
      title: title,
      element: element
    };

    return instance;

  }

  /**
   * Render Table.
   *
   * @param title
   * @param data
   * @returns {*}
   * @constructor
   */
  function Table( title, data ) {
    console.debug( 'Table', data );

    var parsedData = new google.visualization.DataTable();

    if( 'object' !== typeof data ) {
      // data = {};
    }

    var total = data.total;
    var terms = data.terms || [];

    parsedData.addColumn( 'string', 'Metric' );
    parsedData.addColumn( 'number', 'Percentage' );
    parsedData.addColumn( 'number', 'Count' );

    terms.forEach( function( item ) {

      parsedData.addRows([ [
        termLabel( item.term ),
        {
          v: Math.round( ( item.count / total ) * 100 ),
          f: ( Math.round( ( item.count / total ) * 100 ) ) + '%'
        },
        {
          v: item.count
        }
      ] ]);

    });


    var element = jQuery( '<div class="result-table"></div>' );
    jQuery( '.query-result' ).append( element );

    new google.visualization.Table( element.get( 0 ) ).draw( parsedData, {
      showRowNumber: false,
      pageSize: 5
    });

    _cached[ title ] = {
      title: title,
      element: element
    };

    return instance;

  }

  return {
    Map: Map,
    Table: Table,
    Pie: Pie,
    _cached: _cached
  }

});

