jQuery(document).ready(function ($) {
	var statelessWrapper = jQuery('#wp-stateless-wrapper');

	var setupStepContainer = statelessWrapper.find('.wpStateLess-setup-step');
	var setupStepsBars = setupStepContainer.find('.wpStateLess-setup-step-bars');
	var setupSteps = setupStepContainer.find('.wpStateLess-s-step');
	var stepSetupProject = setupSteps.find('.step-setup-project');
	var userInfo = setupSteps.find('.wpStateLess-userinfo');
	var setupForm = setupSteps.find('.wpStateLess-step-setup-form');

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
			setupForm.find('.wpStateLess-combo-box.project').wpStatelessComboBox({items:projects});
		  });

		wp.stateless.listProjectBillingAccounts()
		  .done(function(accounts){
			setupForm.find('.wpStateLess-combo-box.billing-account').wpStatelessComboBox({items:accounts});
		  });
	}

	setupForm.find('.wpStateLess-combo-box.project').on('change', function(event){
		event.stopPropagation();
		event.stopImmediatePropagation();
		var _this = jQuery(this);
		var projectId = _this.find('.id').val();

		// Need to check if it's existing project.
		wp.stateless.listBucket(projectId)
		  .done(function(buckets){
			setupForm.find('.wpStateLess-combo-box.bucket').wpStatelessComboBox({items:buckets});
		  });
		wp.stateless.getProjectBillingInfo(projectId)
		  .done(function(billingInfo){
		  	var billingAccount = setupForm.find('.wpStateLess-combo-box.billing-account');
		  	var currentAccount = setupForm.find('.wpStateLess-current-account');
		  	var enabled = billingInfo.billingEnabled? "Enabled": "Disable";
		  	billingAccount.find('.id').val(billingInfo.name);
		  	billingAccount.find('.name').val(billingInfo.billingAccountName);
		  	currentAccount.find('h5 .project').html(wp.stateless.projects[projectId].name);
		  	currentAccount.find('span').html(billingInfo.billingAccountName + " (" + enabled + ")")
		  	currentAccount.show();
		  });
	})

	jQuery(document).on('tokenExpired', function(){
		checkAuthentication({triggerEvent: false});
	});

	userInfo.on('click', '.logout', function(e){
		e.preventDefault();
		wp.stateless.clearAccessToken();
		checkAuthentication();
	});

});