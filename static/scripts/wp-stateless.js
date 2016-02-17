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
   *
   * @type {boolean}
   */
  $scope.status = false;

  /**
   * Form submit handler
   * @param e
   * @returns {boolean}
   */
  $scope.processStart = function(e) {

    // Get form data
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

          break;
        default: break;
      }
    }

    return false;
  };

  /**
   *
   */
  $scope.processStop = function() {
    $scope.status = 'Stopping...';
    $scope.continue = false;
  };

  /**
   *
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
   *
   * @param ids
   */
  $scope.regenerateImages = function() {
    $scope.isRunning = true;
    $scope.status = 'Processing images...';
    $scope.objectsTotal = $scope.objectIDs.length;

    jQuery("#regenthumbs-bar").progressbar();
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

}]);

jQuery(document).ready(function($) {
	var i;
	var rt_total = 0
	var rt_count = 1;
	var rt_percent = 0;
	var rt_successes = 0;
	var rt_errors = 0;
	var rt_failedlist = '';
	var rt_resulttext = '';
	var rt_timestart = new Date().getTime();
	var rt_timeend = 0;
	var rt_totaltime = 0;
	var rt_continue = true;

	// Create the progress bar
	$("#regenthumbs-bar").progressbar();
	$("#regenthumbs-bar-percent").html( "0%" );

	// Stop button
	$("#regenthumbs-stop").click(function() {
		rt_continue = false;
		$('#regenthumbs-stop').val("Stopping...");
	});

	// Clear out the empty list element that's there for HTML validation purposes
	$("#regenthumbs-debuglist li").remove();

	// Called after each resize. Updates debug information and the progress bar.
	function RegenThumbsUpdateStatus( id, success, response ) {
		$("#regenthumbs-bar").progressbar( "value", ( rt_count / rt_total ) * 100 );
		$("#regenthumbs-bar-percent").html( Math.round( ( rt_count / rt_total ) * 1000 ) / 10 + "%" );
		rt_count = rt_count + 1;

		if ( success ) {
			rt_successes = rt_successes + 1;
			$("#regenthumbs-debug-successcount").html(rt_successes);
			$("#regenthumbs-debuglist").append("<li>" + response.data + "</li>");
		}
		else {
			rt_errors = rt_errors + 1;
			rt_failedlist = rt_failedlist + ',' + id;
			$("#regenthumbs-debug-failurecount").html(rt_errors);
			$("#regenthumbs-debuglist").append("<li>" + response.data + "</li>");
		}
	}

	// Called when all images have been processed. Shows the results and cleans up.
	function RegenThumbsFinishUp() {
		rt_timeend = new Date().getTime();
		rt_totaltime = Math.round( ( rt_timeend - rt_timestart ) / 1000 );

		$('#regenthumbs-stop').hide();

		if ( rt_errors > 0 ) {
			rt_resulttext = 'All done, but some errors appeared.';
		} else {
			rt_resulttext = 'All done without errors.';
		}

		$("#message").html("<p><strong>" + rt_resulttext + "</strong></p>");
		$("#message").show();
	}

	// Regenerate a specified image via AJAX
	function RegenThumbs( id ) {
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: { action: "stateless_process_image", id: id },
			success: function( response ) {
				if ( response !== Object( response ) || ( typeof response.success === "undefined" && typeof response.error === "undefined" ) ) {
					response = new Object;
					response.success = false;
					response.error = "The resize request was abnormally terminated (ID "+id+"). This is likely due to the image exceeding available memory or some other type of fatal error.";
				}

				RegenThumbsUpdateStatus( id, response.success, response );

				if ( rt_images.length && rt_continue ) {
					RegenThumbs( rt_images.shift() );
				}
				else {
					RegenThumbsFinishUp();
				}
			},
			error: function( response ) {
				RegenThumbsUpdateStatus( id, false, response );

				if ( rt_images.length && rt_continue ) {
					RegenThumbs( rt_images.shift() );
				}
				else {
					RegenThumbsFinishUp();
				}
			}
		});
	}
});