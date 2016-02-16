/**
 *
 *
 */

jQuery(document).ready(function($) {
	var i;
	var rt_total = rt_images.length;
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