/**
 *
 *
 */

// Application
var wpStatelessApp = angular.module('wpStatelessApp', [])

// Controller
.controller('wpStatelessTools', ['$scope', '$http', function ($scope, $http) {

  /**
   * Counters
   * @type {number}
   */
  $scope.objectsCounter = 0;
  $scope.objectsTotal   = 0;

  /**
   * Error storage
   * @type {boolean}
   */
  $scope.error = false;

  /**
   * Flags
   * @type {boolean}
   */
  $scope.isRunning = false;
  $scope.isLoading = false;
  $scope.continue  = true;

  /**
   * IDs storage
   * @type {Array}
   */
  $scope.objectIDs = [];

  /**
   * Log
   * @type {Array}
   */
  $scope.log = [];

  /**
   * Status text
   * @type {boolean}
   */
  $scope.status = false;

  /**
   * Init
   */
  $scope.init = function() {
    jQuery("#regenthumbs-bar").progressbar();
  }

  /**
   * Form submit handler
   * @param e
   * @returns {boolean}
   */
  $scope.processStart = function(e) {

    $scope.error = false;

    var data = jQuery(e.currentTarget).serializeArray().reduce(function(obj, item) {
      obj[item.name] = item.value;
      return obj;
    }, {});

    if ( data.action ) {
      switch( data.action ) {
        case 'regenerate_images':
          $scope.getImagesMedia( $scope.regenerateImages );
          break;
        case 'sync_non_images':
          $scope.getOtherMedia( $scope.syncFiles );
          break;
        default: break;
      }
    }

    return false;
  };

  /**
   * Stop process
   */
  $scope.processStop = function() {
    $scope.status = 'Stopping...';
    $scope.continue = false;
  };

  /**
   * Load images IDs
   * @param callback
   */
  $scope.getImagesMedia = function( callback ) {

    $scope.continue = true;
    $scope.isLoading = true;
    $scope.status = 'Loading Images Media Objects...';

    $http({
      method: 'GET',
      url: ajaxurl,
      params: { action: 'get_images_media_ids' }
    }).then(function(response){
      var data = response.data || {};

      if ( data.success ) {
        if ( typeof callback === 'function' ) {
          if ( typeof data.data !== 'undefined' ) {
            $scope.objectIDs = data.data;
            callback();
          } else {
            $scope.status = 'Error appeared';
            $scope.error = "IDs are malformed";
          }
        }
      } else {
        $scope.status = 'Error appeared';
        $scope.error = data.data || "Request failed";
      }

      $scope.isLoading = false;

    }, function(response) {
      $scope.error = response.data || "Request failed";
      $scope.status = 'Error appeared';
      $scope.isLoading = false;
    });

  };

  /**
   * Load non-images media files
   * @param callback
   */
  $scope.getOtherMedia = function( callback ) {

    $scope.continue = true;
    $scope.isLoading = true;
    $scope.status = 'Loading non-image Media Objects...';

    $http({
      method: 'GET',
      url: ajaxurl,
      params: { action: 'get_other_media_ids' }
    }).then(function(response){
      var data = response.data || {};

      if ( data.success ) {
        if ( typeof callback === 'function' ) {
          if ( typeof data.data !== 'undefined' ) {
            $scope.objectIDs = data.data;
            callback();
          } else {
            $scope.status = 'Error appeared';
            $scope.error = "IDs are malformed";
          }
        }
      } else {
        $scope.status = 'Error appeared';
        $scope.error = data.data || "Request failed";
      }

      $scope.isLoading = false;

    }, function(response) {
      $scope.error = response.data || "Request failed";
      $scope.status = 'Error appeared';
      $scope.isLoading = false;
    });

  };

  /**
   * Run sync for files
   */
  $scope.syncFiles = function() {
    $scope.isRunning = true;
    $scope.status = 'Processing files...';
    $scope.objectsTotal = $scope.objectIDs.length;
    $scope.objectsCounter = 0;

    jQuery("#regenthumbs-bar").progressbar("value", 0);
    jQuery("#regenthumbs-bar-percent").html( "0%" );

    if ( $scope.objectIDs.length ) {
      $scope.syncSingleFile( $scope.objectIDs.shift() );
    }
  }

  /**
   * Run images regeneration
   * @param ids
   */
  $scope.regenerateImages = function() {
    $scope.isRunning = true;
    $scope.status = 'Processing images...';
    $scope.objectsTotal = $scope.objectIDs.length;
    $scope.objectsCounter = 0;

    jQuery("#regenthumbs-bar").progressbar("value", 0);
    jQuery("#regenthumbs-bar-percent").html( "0%" );

    if ( $scope.objectIDs.length ) {
      $scope.regenerateSingle( $scope.objectIDs.shift() );
    }
  };

  /**
   * Process Single Image
   * @param id
   */
  $scope.regenerateSingle = function( id ) {

    $http({
      method: 'GET',
      url: ajaxurl,
      params: { action: "stateless_process_image", id: id }
    }).then(
      function(response) {
        var data = response.data || {};
        $scope.log.push({message:data.data});

        jQuery("#regenthumbs-bar").progressbar( "value", ( ++$scope.objectsCounter / $scope.objectsTotal ) * 100 );
        jQuery("#regenthumbs-bar-percent").html( Math.round( ( $scope.objectsCounter / $scope.objectsTotal ) * 1000 ) / 10 + "%" );

        if ( $scope.objectIDs.length && $scope.continue ) {
          $scope.regenerateSingle( $scope.objectIDs.shift() );
        } else {
          $scope.status = 'Finished';
          $scope.isRunning = false;
        }
      },
      function(response) {
        $scope.error = response.data || "Request failed";
        $scope.status = 'Error appeared';
        $scope.isRunning = false;
      }
    );

  }

  /**
   * Process single file
   * @param id
   */
  $scope.syncSingleFile = function( id ) {

    $http({
      method: 'GET',
      url: ajaxurl,
      params: { action: "stateless_process_file", id: id }
    }).then(
        function(response) {
          var data = response.data || {};
          $scope.log.push({message:data.data});

          jQuery("#regenthumbs-bar").progressbar( "value", ( ++$scope.objectsCounter / $scope.objectsTotal ) * 100 );
          jQuery("#regenthumbs-bar-percent").html( Math.round( ( $scope.objectsCounter / $scope.objectsTotal ) * 1000 ) / 10 + "%" );

          if ( $scope.objectIDs.length && $scope.continue ) {
            $scope.syncSingleFile( $scope.objectIDs.shift() );
          } else {
            $scope.status = 'Finished';
            $scope.isRunning = false;
          }
        },
        function(response) {
          $scope.error = response.data || "Request failed";
          $scope.status = 'Error appeared';
          $scope.isRunning = false;
        }
    );

  }

}]);