jQuery(document).ready(function ($) {
	var statelessWrapper = jQuery('#wp-stateless-wrapper');

	var setupStepContainer = statelessWrapper.find('.wpStateLess-setup-step');
	var setupStepsBars = setupStepContainer.find('.wpStateLess-setup-step-bars');
	var setupSteps = setupStepContainer.find('.wpStateLess-s-step');
	var stepSetupProject = setupSteps.find('.step-setup-project');
	var userInfo = setupSteps.find('.wpStateLess-userinfo');
	var setupForm = setupSteps.find('.wpStateLess-step-setup-form');
	var projectDropdown = setupForm.find('.wpStateLess-combo-box.project');
	var bucketDropdown = setupForm.find('.wpStateLess-combo-box.bucket');
  	var billingDropdown = setupForm.find('.wpStateLess-combo-box.billing-account');

	var checkAuthentication = function checkAuthentication(options){
		// Checking if we have access token is session.
		if(!wp.stateless.getAccessToken(options)){
			// We don't have access token.
			setupStepsBars.find('li').removeClass('wpStateLess-done');
			setupSteps.removeClass('active')
				.filter('.step-google-login')
				.addClass('active');
			return false;
		}
		else{
			// We have access token.
			setupStepsBars.find('li')
				.removeClass('wpStateLess-done')
				.filter('.step-google-login')
				.addClass('wpStateLess-done');
			setupSteps.removeClass('active')
				.filter('.step-setup-project')
				.addClass('active');
			return true;
		}
	};

	// Remove any warning shown.
	statelessWrapper.siblings().remove();

	// Binding text input to create new in dropdown
	setupForm.find('.wpStateLess-combo-box').wpStatelessComboBox();
	
	statelessWrapper.find('.learn-more').on('click', function(event){
		event.preventDefault();
		statelessWrapper.find('#wpStateLess-popup').addClass('active');
		return false;
	});

	// Check if authenticated with google then move to step 2.
	if(checkAuthentication()){

		// Load user profile. Ex Name, image, email address.
		wp.stateless.getProfile()
		  .done(function(profile){
			userInfo.find('img.user-photo').attr('src', profile.photo);
			userInfo.find('.user-name').html(profile.name);
			userInfo.find('.user-email').html(profile.email);
		  });

		wp.stateless.listProjects()
		  .done(function(projects){
			projectDropdown.wpStatelessComboBox({items:projects});
		  });

		wp.stateless.listProjectBillingAccounts()
		  .done(function(accounts){
			billingDropdown.wpStatelessComboBox({items:accounts});
		  });
	}

	projectDropdown.on('change', function(event){
		event.stopPropagation();
		event.stopImmediatePropagation();
		var _this = jQuery(this);
		var projectId = _this.find('.id').val();

		// Need to check if it's existing project.
		wp.stateless.listBucket(projectId)
		  .done(function(buckets){
			bucketDropdown.wpStatelessComboBox({items:buckets});
		  });
		wp.stateless.getProjectBillingInfo(projectId)
		  .done(function(billingInfo){
		  	var currentAccount = setupForm.find('.wpStateLess-current-account');
		  	var enabled = billingInfo.billingEnabled? "Enabled": "Disable";
		  	billingDropdown.find('.id').val(billingInfo.name);
		  	billingDropdown.find('.name').val(billingInfo.billingAccountName);
		  	currentAccount.find('h5 .project').html(wp.stateless.projects[projectId].name);
		  	currentAccount.find('span').html(billingInfo.billingAccountName + " (" + enabled + ")")
		  	currentAccount.show();
		  });
	});

	jQuery(document).on('tokenExpired', function(){
		checkAuthentication({triggerEvent: false});
	});

	userInfo.on('click', '.logout', function(e){
		e.preventDefault();
		wp.stateless.clearAccessToken();
		checkAuthentication();
	});

	setupForm.find('.get-json-key').on('click', function(event){
		event.preventDefault();
		var projectId = projectDropdown.find('.id').val();
		var projectName = projectDropdown.find('.name').val();
		var bucket = bucketDropdown.find('.id').val();
		var billingAccount = billingDropdown.find('.id').val();
		var isValid = true;

		if(!projectId || !projectName || !bucket || !billingAccount){ // No valid project id
			isValid = false;
			console.log("Form:: Input not valid.")
			return;
		}


		// Checking if user want to create new project.
		if(!wp.stateless.projects[projectId]){
			wp.stateless.createProject({"projectId": projectId, "name": projectName}).done(function(argument) {
				jQuery(document).trigger("wp::stateless::updateProjectBillingInfo");
			});
		}else{
			jQuery(document).trigger("wp::stateless::updateProjectBillingInfo");
		}

		jQuery(document).on("wp::stateless::updateProjectBillingInfo", function(){
			if(!wp.stateless.projects[projectId]['billingInfo']){
				wp.stateless.updateProjectBillingInfo({"projectID": projectId, "accountName": billingAccount}).done(function(argument) {
					jQuery(document).trigger("wp::stateless::createBucket");
				});
			}
			else{
				jQuery(document).trigger("wp::stateless::createBucket");
			}
		});

		jQuery(document).on("wp::stateless::createBucket", function(){
			if(!wp.stateless.projects[projectId]['buckets'][bucket]){
				wp.stateless.createBucket({"projectId": projectId, "name": bucket}).done(function(argument) {
					// body...
				});
			}
			else{
				
			}
		});

		jQuery(document).on("wp::stateless::createServiceAccountKeys", function(){
			wp.stateless.createServiceAccountKeys({"projectId": projectId, "accountId": bucket, "name": bucket}).done(function(argument) {
				// body...
			});
		});
		
		return false;
	});

});