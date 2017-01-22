jQuery(document).ready(function ($) {
	var statelessWrapper = jQuery('#wp-stateless-wrapper');

	var setupStepContainer = statelessWrapper.find('.wpStateLess-setup-step');
	var setupStepsBars = setupStepContainer.find('.wpStateLess-setup-step-bars');
	var setupSteps = setupStepContainer.find('.wpStateLess-s-step');
	var stepSetupProject = setupSteps.find('.step-setup-project');
	var userInfo = setupSteps.find('.wpStateLess-userinfo');

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
			var list = stepSetupProject.find('.wpStateLess-combo-box.project ul');
			list.children().remove();

			list.append("<option value=''>Select Project</option>");
			jQuery.each(projects, function(index, item){
				list.append("<option value='" + item.projectId + "'>" + item.name + "</option>");
			});
		  });
	}

	jQuery(document).on('tokenExpired', function(){
		checkAuthentication({triggerEvent: false});
	});

	userInfo.on('click', '.logout', function(e){
		e.preventDefault();
		wp.stateless.clearAccessToken();
		checkAuthentication();
	});

});