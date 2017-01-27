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

	var listBillingAccounts = function listBillingAccounts() {
		return wp.stateless.listBillingAccounts()
		  .done(function(accounts){
		  	if(accounts && accounts.length)
				billingDropdown.wpStatelessComboBox({items:accounts}).show();
			else
				billingDropdown.hide();
		  });
	}

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

		var projects = wp.stateless.listProjects()
		  .done(function(projects){
			projectDropdown.wpStatelessComboBox({items:projects});
		  });

		var billingAccounts = listBillingAccounts();

		jQuery.when(projects, billingAccounts).done(function function_name(projects, billingAccounts) {
			if((!projects || !projects.length) && (!billingAccounts || !billingAccounts.length)){
				setupForm.hide();
				jQuery('.wpStateLess-user-has-no-project-billing', statelessWrapper).show();
			}
		})
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

		wp.stateless.getServiceAccounts({projectId:projectId});

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

	statelessWrapper.on('click', '.create-billing-account', function(event) {
		event.preventDefault();
		var _this = jQuery(this)
		var href = _this.attr('href');
		var new_window = window.open(href,'_newtab');

		_this.find('.wpStateLess-loading').addClass('active');

		var billingChecker = setInterval(function() {
			if(new_window.closed == true){
				listBillingAccounts().done(function(argument) {
					console.log("billingAccounts loaded");
					jQuery('.wpStateLess-user-has-no-project-billing', statelessWrapper).hide();
					setupForm.show();
				});
				clearInterval(billingChecker);
				console.log("window closed");
			}
		}, 100);

		return false;
	})

	setupForm.find('.get-json-key').on('click', function(event){
		event.preventDefault();
		var projectId = projectDropdown.find('.id').val();
		var projectName = projectDropdown.find('.name').val().replace(/^\s+|\s+$/g,'');
		var bucketId = bucketDropdown.find('.id').val();
		var bucketName = bucketDropdown.find('.name').val().replace(/\(.*/, '').replace(/^\s+|\s+$/g,'');
		var serviceAccountId = 'stateless-' + bucketId.replace('stateless-', '');
		var serviceAccountName = 'Stateless ' + bucketName.replace('Stateless', '');
		var billingAccount = billingDropdown.find('.id').val();
		var isValid = true;

		if(!projectId || !projectName || !bucketId || !billingAccount){ // No valid project id
			isValid = false;
			console.log("Form:: Input not valid.")
			return;
		}


		// Checking if user want to create new project.
		if(!wp.stateless.projects[projectId]){
			wp.stateless.createProject({"projectId": projectId, "name": projectName}).done(function(argument) {
				_updateProjectBillingInfo();
			}).fail(function(response) {
				console.log(response);
				if(response && typeof response.error != 'undefined' && typeof response.error.status != 'undefined' && response.error.status == 'ALREADY_EXISTS'){
					_updateProjectBillingInfo();
				}
			});
		}else{
			_updateProjectBillingInfo();
		}

		function _updateProjectBillingInfo(){
			if( typeof wp.stateless.projects[projectId] == 'undefined' || typeof wp.stateless.projects[projectId]['billingInfo'] == 'undefined'){
				wp.stateless.updateProjectBillingInfo({"projectID": projectId, "accountName": billingAccount}).done(function(argument) {
					_createBucket();
				});
			}
			else{
				_createBucket();
			}
		}

		function _createBucket(){
			if( typeof wp.stateless.projects[projectId] == 'undefined' || typeof wp.stateless.projects[projectId]['buckets'][bucketId] == 'undefined'){
				wp.stateless.createBucket({"projectId": projectId, "name": bucketId}).done(function(argument) {
					_createServiceAccount();
				}).fail(function(response) {
					console.log(response);
					if(response && typeof response.error != 'undefined' && typeof response.error.message != 'undefined' && response.error.message == 'You already own this bucket. Please select another name.'){
						_createServiceAccount();
					}
				});
			}
			else{
				_createServiceAccount();
			}
		};

		function _createServiceAccount(){
			console.log("wp::stateless::bucketCreated");
			if( typeof wp.stateless.projects[projectId] != 'undefined' || typeof wp.stateless.projects[projectId]['serviceAccounts'] != 'undefined'){
				var serviceAccounts = wp.stateless.projects[projectId]['serviceAccounts'];
				var accountFound = false;
				jQuery.each(serviceAccounts, function(index, item) {
					if(item.displayName == bucketName || item.email.replace(/@.*/, '') == serviceAccountId){
						_insertBucketAccessControls(item.email);
						accountFound = true;
						return false;
					}
				})
			}

			if(accountFound) return;


			wp.stateless.createServiceAccount({
				'projectId': projectId,
				'accountId': serviceAccountId,
				'name': serviceAccountName,
			}).done(function(createdSerciceAccount){
				_insertBucketAccessControls(createdSerciceAccount.email);
			}).fail(function(response) {
				alert("Something wrong."); // Remove
			});
		};

		function _insertBucketAccessControls(createdSerciceAccountEmail) {
			wp.stateless.insertBucketAccessControls({
				"bucket": bucketId,
				"user": createdSerciceAccountEmail
			}).done(function(responseData){
				console.log("Bucket Access Control inserted", responseData);
				_createServiceAccountKeys(responseData.email);
				
			});
		}

		function _createServiceAccountKeys(createdSerciceAccountEmail) {
			wp.stateless.createServiceAccountKeys({
				"project": projectId,
				"account": createdSerciceAccountEmail
			}).done(function(ServiceAccountKey){
				jQuery.ajax({
					url: ajaxurl,
					method: "POST",
					headers: {
						"content-type": "application/x-www-form-urlencoded",
					},
					data: {//JSON.stringify({
						'action': 'stateless_wizard_update_settings',
						'bucket': bucketId,
						'privateKeyData': ServiceAccountKey.privateKeyData,
					}//)
				}).done(function(response) {
					if(typeof response.success != undefined && response.success == true){
						console.log("Option updated");
					}
				})
			});
		}
		
		return false;
	});

});