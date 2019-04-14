/**
 *
 *
 */

// Application
var wpStatelessApp = angular.module('wpStatelessApp', [])

// Controller
.controller('wpStatelessTools', ['$scope', '$http', function ($scope, $http) {

  var WP_DEBUG = wp_stateless_configs.WP_DEBUG || false;
  $scope.action = 'regenerate_images';
  $scope.method = 'start';
  $scope.bulk_size = 1;

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
   *
   * @type {{images: boolean, other: boolean}}
   */
  $scope.progresses = {
    images: false,
    other: false
  };

  /**
   *
   * @type {{images: boolean, other: boolean}}
   */
  $scope.fails = {
    images: false,
    other: false
  }

  /**
   * IDs storage
   * @type {Array}
   */
  $scope.objectIDs = [];

  /**
   *
   * @type {Array}
   */
  $scope.chunkIDs = [];

  /**
   * Log
   * @type {Array}
   */
  $scope.log = [];

  /**
   * Status message
   * @type {String}
   */
  $scope.status = '';

  /**
   * Status message
   * @type {String}
   */
  $scope.extraStatus = '';

  /**
   * Init
   */
  $scope.init = function() {
    jQuery("#regenthumbs-bar").progressbar();
  }

  /**
   * Get error message
   */
  $scope.getError = function(response, message) {
    if(response.data && typeof response.data.data !== 'undefined' && typeof response.data.success !== 'undefined' && response.data.success == false){
      $scope.extraStatus = response.data.data;
      return message;
    }

    if(response.data && typeof response.data.data !== 'undefined'){
      return response.data.data;
    }

    if(response.data && typeof response.data == 'string'){
      $scope.extraStatus = response.data;
      return message;
    }
    
    if(!response.data && response.statusText){
      return response.statusText + " (" + stateless_l10n.response_code + response.status + ")";
    }

    if(!response.data && response.status == -1){
      return stateless_l10n.unable_to_connect_to_the_server;
    }

    return message;
  }

  /**
   *
   * @param callback
   */
  $scope.getCurrentProgresses = function( callback ) {
    $scope.isLoading = true;

    $http({
      method: 'GET',
      url: ajaxurl,
      params: {
        action: 'stateless_get_current_progresses'
      }
    }).then(function(response){
      var data = response.data || {};

      if ( data.success ) {
        if ( typeof data.data !== 'undefined' ) {
          $scope.progresses.images = data.data.images;
          $scope.progresses.other = data.data.other;
          $scope.startFrom = null;
          if($scope.action == 'regenerate_images' && typeof $scope.progresses.images[1]  == "number"){
            // Subtracting 1 so user get where from the sync will start instead of where it ended. We will add 1 in class-ajax.php.
            $scope.startFrom = $scope.progresses.images[1] - 1;
          }
          else if($scope.action == 'sync_non_images' && typeof $scope.progresses.other[1]  == "number"){
            // Subtracting 1 so user get where from the sync will start instead of where it ended. We will add 1 in class-ajax.php.
            $scope.startFrom = $scope.progresses.other[1] - 1;
          }

          if ( 'function' === typeof callback ) {
            callback();
          }
        } else {
          console.error( stateless_l10n.could_not_retrieve_progress );
        }
      } else {
        console.error( stateless_l10n.could_not_retrieve_progress );
      }

      $scope.isLoading = false;
    }, function(response) {
      console.error( stateless_l10n.could_not_retrieve_progress );

      $scope.isLoading = false;
    });
  };

  /**
   *
   */
  $scope.getCurrentProgresses();

  /**
   *
   * @param callback
   */
  function getAllFails( callback ) {
    $scope.isLoading = true;

    $http({
      method: 'GET',
      url: ajaxurl,
      params: {
        action: 'stateless_get_all_fails'
      }
    }).then(function(response){
      var data = response.data || {};

      if ( data.success ) {
        if ( typeof data.data !== 'undefined' ) {

          $scope.fails.images = data.data.images;
          $scope.fails.other = data.data.other;

          if ( 'function' === typeof callback ) {
            callback();
          }
        } else {
          console.error( stateless_l10n.could_not_get_fails );
        }
      } else {
        console.error( stateless_l10n.could_not_get_fails );
      }

      $scope.isLoading = false;
    }, function(response) {
      console.error( stateless_l10n.could_not_get_fails );

      $scope.isLoading = false;
    });
  };

  getAllFails();

  /**
   * Form submit handler
   * @param e
   * @returns {boolean}
   */
  $scope.processStart = function(e) {

    $scope.error = false;
    $scope.status = '';
    $scope.extraStatus = '';
    $scope.objectsCounter = 0;
    $scope.objectsTotal = 0;
    $scope.objectIDs = [];
    $scope.chunkIDs = [];

    if ( $scope.method === 'fix' ) {

      if ( $scope.action ) {
        switch( $scope.action ) {
          case 'regenerate_images':
            $scope.objectIDs = $scope.fails.images;
            $scope.regenerateImages();
            break;
          case 'sync_non_images':
            $scope.objectIDs = $scope.fails.other;
            $scope.syncFiles();
            break;
          default: break;
        }
      }

      return false;
    }

    var cont = 0;
    if ( 'continue' === $scope.method ) {
      cont = 1;
    }

    if ( $scope.action ) {
      switch( $scope.action ) {
        case 'regenerate_images':
          $scope.getImagesMedia( $scope.regenerateImages, cont );
          break;
        case 'sync_non_images':
          $scope.getOtherMedia( $scope.syncFiles, cont );
          break;
        case 'sync_non_library_files':
          $scope.getNonLibraryFiles( $scope.syncNonLibraryFiles, cont );
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
    $scope.status = stateless_l10n.stopping;
    $scope.continue = false;
  };

  function array_bulk( arr, bulk_size ) {
    var groups = [];
    var i;

    for ( i = 0; i < bulk_size; i++ ) {
      groups[ i ] = [];
    }

    for ( i = 0; i < arr.length; i ++ ) {
      groups[ i % bulk_size ].push( arr[ i ] );
    }

    return groups;
  }

  $scope.finishProcess = function( chunk_id ) {
    var mode = 'images';
    if ( 'sync_non_images' === $scope.action ) {
      mode = 'other';
    }

    if ( $scope.objectsCounter >= $scope.objectsTotal ) {
      // process finished

      $http({
        method: 'GET',
        url: ajaxurl,
        params: {
          action: 'stateless_reset_progress',
          mode: mode
        }
      }).then(function(response){
        $scope.progresses[ mode ] = false;

        $scope.status = stateless_l10n.finished;
        $scope.isLoading = false;
        $scope.isRunning = false;
      }, function(response) {
        console.error( stateless_l10n.could_not_reset_progress );
      });
    } else if ( 'undefined' !== typeof chunk_id ) {
      // process cancelled, but this is only a chunk finishing request
      
      $scope.chunkIDs[ chunk_id ] = false;
      var all_done = true;
      for ( var i in $scope.chunkIDs ) {
        if ( false !== $scope.chunkIDs[ i ] ) {
          all_done = false;
          break;
        }
      }
      if ( all_done ) {
        $scope.getCurrentProgresses( function() {
          $scope.status = stateless_l10n.cancelled;
          $scope.isRunning = false;
        });
      }
    } else {
      // process cancelled

      $scope.getCurrentProgresses( function() {
        $scope.status = stateless_l10n.cancelled;
        $scope.isRunning = false;
      });
    }
  };

  /**
   * Load images IDs
   * @param callback
   */
  $scope.getImagesMedia = function( callback, cont ) {

    $scope.continue = true;
    $scope.isLoading = true;
    $scope.status = stateless_l10n.loading_images_media_objects;

    $http({
      method: 'GET',
      url: ajaxurl,
      params: {
        action: 'get_images_media_ids',
        start_from: $scope.startFrom,
        continue: cont
      }
    }).then(function(response){
      var data = response.data || {};

      if ( data.success ) {
        if ( typeof callback === 'function' ) {
          if ( typeof data.data !== 'undefined' ) {
            $scope.objectIDs = data.data;
            callback();
          } else {
            $scope.status = stateless_l10n.ids_are_malformed;
            $scope.error = true;
          }
        }
      } else {
        $scope.status = $scope.getError(response, stateless_l10n.unable_to_get_images_media_id);
        $scope.error = true;
      }

      $scope.isLoading = false;

      if(WP_DEBUG){
        console.log(stateless_l10n.wp_stateless_get_images_media_id, response);
      }

    }, function(response) {
      $scope.error = true;
      $scope.status = $scope.getError(response, get_images_media_id + ": " + stateless_l10n.request_failed);
      $scope.isLoading = false;

      if(WP_DEBUG){
        console.log(stateless_l10n.wp_stateless_get_images_media_id + ": " + stateless_l10n.request_failed, response, typeof response.headers === 'function'?response.headers() : '');
      }
    });

  };

  /**
   * Load non-images media files
   * @param callback
   */
  $scope.getOtherMedia = function( callback, cont ) {

    $scope.continue = true;
    $scope.isLoading = true;
    $scope.status = stateless_l10n.loading_non_image_media_objects;

    $http({
      method: 'GET',
      url: ajaxurl,
      params: {
        action: 'get_other_media_ids',
        start_from: $scope.startFrom,
        continue: cont
      }
    }).then(function(response){
      var data = response.data || {};

      if ( data.success ) {
        if ( typeof callback === 'function' ) {
          if ( typeof data.data !== 'undefined' ) {
            $scope.objectIDs = data.data;
            callback();
          } else {
            $scope.status = $scope.getError(response, stateless_l10n.ids_are_malformed);
            $scope.error = true;
          }
        }
      } else {
        $scope.status = $scope.getError(response, stateless_l10n.unable_to_get_non_images_media_id);
        $scope.error = true;
      }

      $scope.isLoading = false;

      if(WP_DEBUG){
        console.log("WP-Stateless get non Images Media ID:", response, typeof response.headers === 'function'?response.headers(): "");
      }
    }, function(response) {
      $scope.error = true;
      $scope.status = $scope.getError(response, stateless_l10n.get_non_images_media_id_request_failed);
      $scope.isLoading = false;
      if(WP_DEBUG){
        console.log("WP-Stateless get non Images Media ID: Request failed", response, typeof response.headers === 'function'?response.headers(): "");
      }
    });

  };

  /**
   * Load non-images media files
   * @param callback
   */
  $scope.getNonLibraryFiles = function( callback, cont ) {

    $scope.continue = true;
    $scope.isLoading = true;
    $scope.status = stateless_l10n.loading_non_library_objects;

    $http({
      method: 'GET',
      url: ajaxurl,
      params: {
        action: 'get_non_library_files_id',
        continue: cont
      }
    }).then(function(response){
      var data = response.data || {};

      if ( data.success ) {
        if ( typeof callback === 'function' ) {
          if ( typeof data.data !== 'undefined' ) {
            $scope.objectIDs = data.data;
            callback();
          } else {
            $scope.status = $scope.getError(response, stateless_l10n.ids_are_malformed);
            $scope.error = true;
          }
        }
      } else {
        $scope.error = true;
        $scope.status = $scope.getError(response, stateless_l10n.non_libraries_files_are_not_found);
      }

      $scope.isLoading = false;

      if(WP_DEBUG){
        console.log("WP-Stateless get non library files:", response, typeof response.headers === 'function'?response.headers(): "");
      }
    }, function(response) {
      $scope.error = true;
      $scope.status = $scope.getError(response, stateless_l10n.get_non_library_files_request_failed);
      $scope.isLoading = false;

      if(WP_DEBUG){
        console.log("WP-Stateless get non library files: Request failed", response, typeof response.headers === 'function'?response.headers(): "");
      }
    });

  };

  /**
   * Run sync for files
   */
  $scope.syncNonLibraryFiles = function() {
    $scope.isRunning = true;
    $scope.objectsTotal = $scope.objectIDs.length;
    $scope.objectsCounter = 0;
    $scope.status = stateless_l10n.processing_files + $scope.objectsTotal + stateless_l10n._total___;

    jQuery("#regenthumbs-bar").progressbar("value", 0);
    jQuery("#regenthumbs-bar-percent").html( "0%" );

    if ( $scope.objectIDs.length ) {
      //$scope.syncSingleNonLibraryFile( $scope.objectIDs.shift() );
      $scope.chunkIDs = array_bulk( $scope.objectIDs, $scope.bulk_size );
      for ( var i in $scope.chunkIDs ) {
        $scope.syncSingleNonLibraryFile( $scope.chunkIDs[ i ].shift(), i );
      }
    }
  }

  /**
   * Run sync for files
   */
  $scope.syncFiles = function() {
    $scope.isRunning = true;
    $scope.objectsTotal = $scope.objectIDs.length;
    $scope.objectsCounter = 0;
    $scope.status = stateless_l10n.processing_files + $scope.objectsTotal + stateless_l10n._total___;

    jQuery("#regenthumbs-bar").progressbar("value", 0);
    jQuery("#regenthumbs-bar-percent").html( "0%" );

    if ( $scope.objectIDs.length ) {
      //$scope.syncSingleFile( $scope.objectIDs.shift() );
      $scope.chunkIDs = array_bulk( $scope.objectIDs, $scope.bulk_size );
      for ( var i in $scope.chunkIDs ) {
        $scope.syncSingleFile( $scope.chunkIDs[ i ].shift(), i );
      }
    }
  }

  /**
   * Run images regeneration
   * @param ids
   */
  $scope.regenerateImages = function() {
    $scope.isRunning = true;
    $scope.objectsTotal = $scope.objectIDs.length;
    $scope.objectsCounter = 0;
    $scope.status = stateless_l10n.processing_images + $scope.objectsTotal + stateless_l10n._total___;

    jQuery("#regenthumbs-bar").progressbar("value", 0);
    jQuery("#regenthumbs-bar-percent").html( "0%" );

    if ( $scope.objectIDs.length ) {
      //$scope.regenerateSingle( $scope.objectIDs.shift() );
      $scope.chunkIDs = array_bulk( $scope.objectIDs, $scope.bulk_size );
      for ( var i in $scope.chunkIDs ) {
        $scope.regenerateSingle( $scope.chunkIDs[ i ].shift(), i );
      }
    }
  };

  /**
   * Process Single Image
   * @param id
   */
  $scope.regenerateSingle = function( id, chunk_id ) {

    $http({
      method: 'GET',
      url: ajaxurl,
      params: {
        action: "stateless_process_image",
        id: id
      }
    }).then(
      function(response) {
        var data = response.data || {};
        $scope.log.push({message:data.data || stateless_l10n.regenerate_single_image_failed});

        jQuery("#regenthumbs-bar").progressbar( "value", ( ++$scope.objectsCounter / $scope.objectsTotal ) * 100 );
        jQuery("#regenthumbs-bar-percent").html( Math.round( ( $scope.objectsCounter / $scope.objectsTotal ) * 1000 ) / 10 + "%" );

        if(typeof response.data.success == 'undefined' || response.data.success == false){
          $scope.error = true;
          $scope.status = $scope.getError(response, stateless_l10n.regenerate_single_image_failed);
          // $scope.isRunning = false;
        }
        
        if ( 'undefined' !== typeof chunk_id ) {
          if ( $scope.chunkIDs[ chunk_id ].length && $scope.continue ) {
            $scope.regenerateSingle( $scope.chunkIDs[ chunk_id ].shift(), chunk_id );
          } else {
            $scope.finishProcess( chunk_id );
          }
        } else {
          if ( $scope.objectIDs.length && $scope.continue ) {
            $scope.regenerateSingle( $scope.objectIDs.shift() );
          } else {
            $scope.finishProcess();
          }
        }
        if(WP_DEBUG){
          console.log("WP-Stateless regenerate single image:", response, typeof response.headers === 'function'?response.headers(): "");
        }
      },
      function(response) {
        $scope.error = true;
        $scope.status = $scope.getError(response, stateless_l10n.regenerate_single_image_request_failed);
        $scope.isRunning = false;
        if(WP_DEBUG){
          console.log("WP-Stateless regenerate single image: Request failed", response, typeof response.headers === 'function'?response.headers(): "");
        }
      }
    );

  }

  /**
   * Process single file
   * @param id
   */
  $scope.syncSingleFile = function( id, chunk_id ) {

    $http({
      method: 'GET',
      url: ajaxurl,
      params: {
        action: "stateless_process_file",
        id: id
      }
    }).then(
      function(response) {
        var data = response.data || {};
        $scope.log.push({message: $scope.getError(response, stateless_l10n.sync_single_file_failed)});

        jQuery("#regenthumbs-bar").progressbar( "value", ( ++$scope.objectsCounter / $scope.objectsTotal ) * 100 );
        jQuery("#regenthumbs-bar-percent").html( Math.round( ( $scope.objectsCounter / $scope.objectsTotal ) * 1000 ) / 10 + "%" );

        if(typeof response.data.success == 'undefined' || response.data.success == false){
          $scope.error = true;
          $scope.status = $scope.getError(response, stateless_l10n.sync_single_file_failed);
          // $scope.isRunning = false;
        }
        
        if ( 'undefined' !== typeof chunk_id ) {
          if ( $scope.chunkIDs[ chunk_id ].length && $scope.continue ) {
            $scope.syncSingleFile( $scope.chunkIDs[ chunk_id ].shift(), chunk_id );
          } else {
            $scope.finishProcess( chunk_id );
          }
        } else {
          if ( $scope.objectIDs.length && $scope.continue ) {
            $scope.syncSingleFile( $scope.objectIDs.shift() );
          } else {
            $scope.finishProcess();
          }
        }

        if(WP_DEBUG){
          console.log("WP-Stateless sync single file:", response, typeof response.headers === 'function'?response.headers(): "");
        }
      },
      function(response) {
        $scope.error = true;
        $scope.status = $scope.getError(response, stateless_l10n.sync_single_file_request_failed);
        $scope.isRunning = false;

        if(WP_DEBUG){
          console.log("WP-Stateless sync single file: Request failed", response, typeof response.headers === 'function'?response.headers(): "");
        }
      }
    );

  }

  /**
   * Process single file
   * @param id
   */
  $scope.syncSingleNonLibraryFile = function( id, chunk_id ) {

    $http({
      method: 'GET',
      url: ajaxurl,
      params: {
        action: "stateless_process_non_library_file",
        file_path: id
      }
    }).then(
      function(response) {
        var data = response.data || {};
        $scope.log.push({message: $scope.getError(response, stateless_l10n.failed_to_sync + id)});

        jQuery("#regenthumbs-bar").progressbar( "value", ( ++$scope.objectsCounter / $scope.objectsTotal ) * 100 );
        jQuery("#regenthumbs-bar-percent").html( Math.round( ( $scope.objectsCounter / $scope.objectsTotal ) * 1000 ) / 10 + "%" );

        if(typeof response.data.success == 'undefined' || response.data.success == false){
          $scope.error = true;
          $scope.status = $scope.getError(response, stateless_l10n.sync_non_library_file_failed);
          // $scope.isRunning = false;
        }
        
        if ( 'undefined' !== typeof chunk_id ) {
          if ( $scope.chunkIDs[ chunk_id ].length && $scope.continue ) {
            $scope.syncSingleNonLibraryFile( $scope.chunkIDs[ chunk_id ].shift(), chunk_id );
          } else {
            $scope.finishProcess( chunk_id );
          }
        } else {
          if ( $scope.objectIDs.length && $scope.continue ) {
            $scope.syncSingleNonLibraryFile( $scope.objectIDs.shift() );
          } else {
            $scope.finishProcess();
          }
        }

        if(WP_DEBUG){
          console.log("WP-Stateless sync non library file:", response, typeof response.headers === 'function'?response.headers(): "");
        }
      },
      function(response) {
        $scope.error = true;
        $scope.status = $scope.getError(response, stateless_l10n.sync_non_library_file_request_failed);
        $scope.isRunning = false;

        if(WP_DEBUG){
          console.log("WP-Stateless sync non library file: Request failed", response, typeof response.headers === 'function'?response.headers():{});
        }
      }
    );

  }

}])
.controller('wpStatelessSettings', function($scope, $filter) {
  $scope.backup = {};
  $scope.sm = wp_stateless_settings || {};
  
  $scope.$watch('sm.mode', function(value) {
    if(value == 'stateless' && $scope.sm.readonly.hashify_file_name != 'constant'){
      $scope.backup.hashify_file_name = $scope.sm.hashify_file_name;
      $scope.sm.hashify_file_name = 'true';
      // $scope.apply();
    }
    else{
      if($scope.backup.hashify_file_name){
        $scope.sm.hashify_file_name = $scope.backup.hashify_file_name;
        // $scope.apply();
      }
    }
  });

  $scope.sm.showNotice = function(option){
    if($scope.sm.readonly && $scope.sm.readonly[option]){
      var slug = $scope.sm.readonly[option];
      return $scope.sm.strings[slug];
    }
  }

  $scope.sm.generatePreviewUrl = function() {
    $scope.sm.is_custom_domain = false;
    var host = 'https://storage.googleapis.com/';
    var rootdir = $scope.sm.root_dir ? $scope.sm.root_dir + '/' : '';
    var subdir = $scope.sm.organize_media == '1' ? $filter('date')(Date.now(), 'yyyy/MM') + '/' : '';
    var hash = $scope.sm.hashify_file_name == 'true' ? Date.now().toString(36) + '-' : '';
    var is_ssl = $scope.sm.custom_domain.indexOf('https://');
    var custom_domain = $scope.sm.custom_domain.toString();
    
    custom_domain = custom_domain.replace(/\/+$/, ''); // removing trailing slashes
    custom_domain = custom_domain.replace(/https?:\/\//, ''); // removing http:// or https:// from the beginning.
    host += $scope.sm.bucket ? $scope.sm.bucket : '{bucket-name}';

    if ( custom_domain !== 'storage.googleapis.com' && $scope.sm.bucket && custom_domain && ( is_ssl === 0 || custom_domain == $scope.sm.bucket ) ) {
      $scope.sm.is_custom_domain = true;
      $scope.sm.is_ssl = is_ssl === 0 ? true : false;
      host = is_ssl === 0 ? 'https://' : 'http://';  // bucketname will be host
      host += custom_domain;
    }

    $scope.sm.preview_url = host + "/" + rootdir + subdir + hash + "your-image-name.jpeg";
  }

  $scope.sm.generatePreviewUrl();

})
.controller('wpStatelessCompatibility', function($scope, $filter) {
  $scope.modules = wp_stateless_compatibility || {};
})
.controller('noJSWarning', function($scope, $filter) {
  $scope.jsLoaded = true;
});

wpStatelessApp.filter("trust", ['$sce', function($sce) {
  return function(htmlCode){
    return $sce.trustAsHtml(htmlCode);
  }
}]);
