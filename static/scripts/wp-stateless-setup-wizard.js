jQuery(document).ready(function ($) {
	var statelessWrapper = jQuery('#wp-stateless-wrapper');

	var setupStepContainer = statelessWrapper.find('.wpStateLess-setup-step');
	var setupStepsBars = setupStepContainer.find('.wpStateLess-setup-step-bars');
	var setupSteps = setupStepContainer.find('.wpStateLess-s-step');
	var stepSetupProject = setupSteps.find('.step-setup-project');
	var userInfo = setupSteps.find('.wpStateLess-userinfo');
	var userDetails = userInfo.find('.wpStateLess-user-details');
	var setupForm = setupSteps.find('.wpStateLess-step-setup-form form');
	var comboBox = setupForm.find('.wpStateLess-combo-box');
	var projectDropdown = comboBox.filter('.project');
	var bucketDropdown = comboBox.filter('.bucket');
	var regionDropdown = comboBox.filter('.region');
  	var billingDropdown = comboBox.filter('.billing-account');
  	var noBillingButton = billingDropdown.parent().find('.create-billing-account.no-billing-account');
	var gLoginUrl = $('#google-login').attr('href');
	var updateJsonNonce = statelessWrapper.attr("data-nonce");

	$("#google-login").attr("href", gLoginUrl + "&allowNotifications=true");
	
	$("#allow-notifications").on('change', function(){
		if($(this).prop('checked'))
			allowNotifications = 'true';
		else
			allowNotifications = "false";
		$("#google-login").attr("href", gLoginUrl + "&allowNotifications=" + allowNotifications);
		
	});

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
		  	if(accounts && accounts.length){
		  		noBillingButton.hide();
				billingDropdown.wpStatelessComboBox({items:accounts, selected: 0}).show();
				billingDropdown.find('.name').trigger('change');
		  	}
			else{
				billingDropdown.hide();
		  		noBillingButton.show();
			}
		  });
	}

	// Remove any warning shown.
	statelessWrapper.siblings().remove();

	setupForm.find('.wpStateLess-combo-box.project .name').wppStatelessValidate({
		name: {
			empty:{
				break: true,
				regex: /.+/,
				errorMessage: stateless_l10n.project_cant_be_empty,
			},
			length:{
				regex: /^.{5,30}$/,
				errorMessage: stateless_l10n.project_length_notice,
			},
			char:{
				regex: /^[a-zA-Z0-9-'"\s!]+$/,
				errorMessage: stateless_l10n.project_invalid_char
			},
		},
		id: {
			replace: [
				['-', /\s/g],
				['-', /^-+|-+$/g],
				['-', '!'],
				['-', /-+/g	],
				[''	, '"'],
				[''	, '\''],
				[''	, /\(.*/],
			],
			regex: /[a-z][a-z0-9\-\s]{3,28}[a-z0-9]/ // 
		},
	});
	setupForm.find('.wpStateLess-combo-box.bucket .name').wppStatelessValidate({
		name: {
			empty:{
				break: true,
				regex: /.+/,
				errorMessage: stateless_l10n.bucket_cant_be_empty,
			},
			length:{
				regex: /^.{5,30}$/,
				errorMessage: stateless_l10n.bucket_length_notice,
				break: true
			},
			char:{
				regex: /^[a-z0-9][a-z0-9\-]*[a-z0-9]$/,
				errorMessage: stateless_l10n.bucket_invalid_char,
			},
		},
	});
	setupForm.find('.wpStateLess-combo-box.billing-account .name').wppStatelessValidate({
		name: {
			empty:{
				break: true,
				regex: /.+/,
				errorMessage: stateless_l10n.select_billing_account,
			},
		},
	});

	// Binding text input to create new in dropdown
	setupForm.find('.wpStateLess-combo-box').wpStatelessComboBox().find('.name').trigger('change');

	// Check if authenticated with google then move to step 2.
	if(checkAuthentication()){

		// Load user profile. Ex Name, image, email address.
		wp.stateless.getProfile()
		  .done(function(profile){
			userInfo.find('img.user-photo').attr('src', profile.photo);
			userInfo.find('.user-name').html(profile.name);
			userInfo.find('.user-email').html(profile.email);
			userDetails.show();
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
		// Project ID without random digit at the end.
		var PDBName = 'stateless-' + projectId.replace(/-\d+$/, '');
		PDBName = PDBName.substring(0, 30).replace(/-$/, '');
		billingDropdown.find('.circle-loader:not(.get-json-loading)').addClass('loading').removeClass('load-complete').show();
		bucketDropdown.find('.circle-loader:not(.get-json-loading)').addClass('loading').removeClass('load-complete').show();
		regionDropdown.find('.name').removeAttr('disabled');
		regionDropdown.find('.circle-loader:not(.get-json-loading)')
						.addClass('loading')
						.removeClass('load-complete').show();

		bucketDropdown.find('.project-derived-name')
			.html(PDBName)
			.attr('data-name', PDBName)
			.attr('data-id', PDBName)
			.show();
		bucketDropdown.find('.name').trigger('change'); // To hide project derived name if it's already exists.
        
        if(typeof wp.stateless.projects[projectId] == 'undefined'){
			bucketDropdown.wpStatelessComboBox({items:{}});
		  	var name = billingDropdown.find('.name').val('');
		  	var id = billingDropdown.find('.id').val('');
		  	var account = billingDropdown.wpStatelessComboBox({get: 0});

		  	name.removeAttr('disabled');
		  	if(account){
				name.val(account.name +  " (" + account.id + ")");
				id.val(account.id);
		  	}
		  	billingDropdown.find('.circle-loader:not(.get-json-loading)').fadeOut(200).removeClass('loading');
		  	bucketDropdown.find('.circle-loader:not(.get-json-loading)').fadeOut(200).removeClass('loading');
		  	return;
        }


		// Need to check if it's existing project.
		wp.stateless.listBucket(projectId)
		  .done(function(buckets){
		  	if(typeof wp.stateless.projects[projectId] != 'undefined'){
				bucketDropdown.find('.wpStateLess-existing h5').html(wp.stateless.projects[projectId].name + " Buckets").show();
		  	}
			bucketDropdown.wpStatelessComboBox({items:buckets});
		  	bucketDropdown.find('.circle-loader:not(.get-json-loading)').fadeOut(200).removeClass('loading');
		  	// If project derived name found more than once then hide it.
		  	if(bucketDropdown.wpStatelessComboBox({has: PDBName}))
				bucketDropdown.find('.project-derived-name').html('').hide();
			else
				bucketDropdown.find('.project-derived-name').show();
	        
		  }).fail(function(){
			bucketDropdown.wpStatelessComboBox({items:{}});
		  	bucketDropdown.find('.circle-loader:not(.get-json-loading)').fadeOut(200).removeClass('loading');
		  });

		//bucketDropdown.find('.name').val("stateless-" + projectId).trigger('change');

		wp.stateless.getServiceAccounts({projectId:projectId});

		wp.stateless.getProjectBillingInfo(projectId)
		  .done(function(billingInfo){
		  	var enabled = billingInfo.billingEnabled? "Active": "Inactive";
		  	billingDropdown.find('.name').removeAttr('disabled');
      		if(typeof billingInfo.id != 'undefined'){
      			var namID = billingInfo.id;
      			if(typeof billingInfo.name != 'undefined')
	      			namID = billingInfo.name + " (" + billingInfo.id + ")";

			  	billingDropdown.find('.id').val(billingInfo.id);
			  	billingDropdown.find('.name').val(namID).attr('disabled', 'disabled');
			}else{
		  		console.log("Something went wrong.");
			}
        	billingDropdown.find('.circle-loader:not(.get-json-loading)').addClass('load-complete');
		  }).fail(function(responseData) {
		  	var name = billingDropdown.find('.name').val('');
		  	var id = billingDropdown.find('.id').val('');
		  	var account = billingDropdown.wpStatelessComboBox({get: 0});

		  	name.removeAttr('disabled');
		  	if(account){
				name.val(account.name +  " (" + account.id + ")");
				id.val(account.id);
		  	}
		  	billingDropdown.find('.circle-loader:not(.get-json-loading)').addClass('load-complete').fadeOut(200);

		  });
	});

	bucketDropdown.on('change', function(event){
		var projectId = projectDropdown.find('.id').val();
		var bucketName = bucketDropdown.find('.name').val();
		var bucketId = bucketName == 'localhost'?'':bucketName;
		var regionId;
		

		regionDropdown.wpStatelessComboBox({select: 'us'}).find('.name').removeAttr('disabled');
		regionDropdown.find('.circle-loader:not(.get-json-loading)')
						.addClass('loading')
						.removeClass('load-complete').show();

		if( typeof wp.stateless.projects[projectId] != 'undefined' &&
			typeof wp.stateless.projects[projectId]['buckets'] != 'undefined' &&
			typeof wp.stateless.projects[projectId]['buckets'][bucketId] != 'undefined' &&
			typeof wp.stateless.projects[projectId]['buckets'][bucketId]['location'] != 'undefined'
		){
			regionId = wp.stateless.projects[projectId]['buckets'][bucketId]['location'].toLowerCase()
			regionDropdown.wpStatelessComboBox({select: regionId})
				.find('.name')
					.attr('disabled', 'disabled');

			regionDropdown.find('.circle-loader:not(.get-json-loading)')
						.addClass('load-complete');
		}
		else{
			regionDropdown.find('.circle-loader:not(.get-json-loading)')
						.removeClass('loading')
						.removeClass('load-complete')
						.fadeOut(200);

		}

	});

	jQuery(document).on('tokenExpired', function(){
		checkAuthentication({triggerEvent: false});
	});

	userInfo.on('click', '.logout', function(e){
		var loginButton = jQuery('#google-login');
		var loginUrl = loginButton.attr('href');
		loginButton.attr('href', loginUrl + "&force_login=true");
		e.preventDefault();
		wp.stateless.clearAccessToken();
		checkAuthentication();
	});

	statelessWrapper.on('click', '.create-billing-account', function(event) {
		event.preventDefault();
		var xhr;
		var counter = 5;
		var interval = 200;
		var _this = jQuery(this)
		var href = _this.attr('href');
		var new_window = window.open(href,'_newtab');

		var loader = _this.find('.wpStateLess-loading');
		loader.addClass('active');
		var checkBillingAccount = function(force) {
			counter ++;
			if(force === true || new_window.closed == true || counter % 35 == 0){
				if(new_window.closed == true){
					clearInterval(billingChecker);
					loader.removeClass('active');
				}
				
				if(xhr && typeof xhr.abort != 'undefined'){
					xhr.abort();
				}

				xhr = listBillingAccounts().done(function(accounts) {
					if(accounts && accounts.length){
						jQuery('.wpStateLess-user-has-no-project-billing', statelessWrapper).hide();
						setupForm.show();
						clearInterval(billingChecker);
						loader.removeClass('active');
						jQuery(window).off( 'focus', checkBillingAccount)
					}
				});
			}
		}
		var billingChecker = setInterval(checkBillingAccount, interval);
		
		jQuery(window).focus( checkBillingAccount.bind(null, true));

		return false;
	})

	setupForm.find('.get-json-key').on('click', function(event){
		event.preventDefault();
		var btnGetJson = jQuery(this);
		if(btnGetJson.hasClass('disabled')){
			return false;
		}
		var projectId = projectDropdown.find('.id').val();
		var projectName = projectDropdown.find('.name').val().replace(/\(.*/, '').replace(/^\s+|\s+$/g,'');
		var bucketName = bucketDropdown.find('.name').val();
		var bucketId = bucketName == 'localhost'?'':bucketName;
		var regionId = regionDropdown.find('.id').val();
		var serviceAccountId = 'stateless-' + bucketId.replace('stateless-', '')
			.replace(/[._]/g, '-')
			.slice(0, 16)
			.replace(/-$/, '') + '-' + Math.floor((Math.random() * 100) + 100);
		var serviceAccountName = 'Stateless ' + bucketName.replace('Stateless', '');
		var billingAccount = billingDropdown.find('.id').val();
		var isValid = true;
		var errorWrapper = setupForm.find('#stateless-notification').removeClass('error');
		errorWrapper.html('').hide();
		comboBox.find('.circle-loader').addClass('get-json-loading').show();
		setupForm.find('.wpStateLess-combo-box').wpStatelessComboBox('validate');

		if(!projectId || !projectName || !bucketId || !billingAccount){ // No valid project id
			isValid = false;
			console.log("Form:: Input not valid.")
			errorWrapper
					 .html(stateless_l10n.invalid_input)
					 .addClass('error')
					 .removeClass('notice notice-info')
					 .show();
			return;
		}
		btnGetJson.addClass('active disabled');
		// return;
		async.auto({
			createProject: function(callback) {
				if(!wp.stateless.projects[projectId]){
					wp.stateless.createProject({"projectId": projectId, "name": projectName})
					.done(function(response) {
						callback(null, {ok: true, task: 'createProject', action: 'project_created', message: stateless_l10n.project_creation_started, operation: response.name});
					}).fail(function(response) {
						response = response.responseJSON || {};
						if(response && typeof response.error != 'undefined' && typeof response.error.status != 'undefined' && response.error.status == 'ALREADY_EXISTS'){
							callback(null, {ok: true, task: 'createProject', action: 'project_exists', message: stateless_l10n.project_exists});
						}
						else{
							if(typeof response.error != 'undefined' && typeof response.error.message != 'undefined')
								callback({ok: false, task: 'createProject', action: 'failed', message: response.error.message});
							else
								callback({ok: false, task: 'createProject', action: 'failed', message: stateless_l10n.project_creation_failed});
						}
					});
				}else{
					callback(null, {ok: true, task: 'createProject', action: 'project_exists', message: stateless_l10n.project_exists});
				}
			},
			createProjectProgress: ['createProject', async.retryable({times: 10, interval: 1500, errorFilter: function(err) {
					return err.action === 'retry'; // only retry on a specific error
				}
			}, function(results, callback) {
				if( results['createProject'].action == 'project_created'){
					wp.stateless.createProjectProgress(results['createProject'].operation)
					.done(function(argument) {
						callback(null, {ok: true, task: 'createProjectProgress', action: 'project_creation_complete', message: stateless_l10n.project_creation_complete});
					}).fail(function(response) {
						if(typeof response.error != 'undefined' && typeof response.error.code != 'undefined' && typeof response.error.message != 'undefined' && (response.error.code == 9 || response.error.message == "Callers must accept Terms of Service"))
							callback({ok: false, task: 'createProjectProgress', action: 'failed', message: "Please Connect once to GCP Console using the Google ID you provided before proceeding with WP-Stateless setup. You must accept Google cloud Terms of Service."});
						else if(typeof response.error != 'undefined' && typeof response.error.message != 'undefined')
							callback({ok: false, task: 'createProjectProgress', action: 'failed', message: response.error.message});
						else
							callback({ok: false, task: 'createProjectProgress', action: 'retry', message: stateless_l10n.project_creation_failed});
					});
				}
				else{
					callback(null, {ok: true, task: 'createProjectProgress', action: 'old_project', message: stateless_l10n.project_exists});
				}
			})],
			enableAPI: ['createProjectProgress', function(results, callback) {
				wp.stateless.enableAPI(projectId)
				.done(function(argument) {
					callback(null, {ok: true, task: 'enableAPI', action: 'service_enabled', message: stateless_l10n.json_api_enabled});
				}).fail(function(response) {
					callback({ok: false, task: 'enableAPI', action: 'failed', message: stateless_l10n.json_api_enabled_failed});
				});
			}],
			updateBilltingInfo: ['createProjectProgress', function(results, callback) {
				if( typeof wp.stateless.projects[projectId] == 'undefined' || typeof wp.stateless.projects[projectId]['billingInfo'] == 'undefined'){
					wp.stateless.updateProjectBillingInfo({"projectID": projectId, "accountName": billingAccount})
					.done(function(argument) {
						callback(null, {ok: true, task: 'updateBilltingInfo', action: 'enabled', message: stateless_l10n.billing_enabled});
					}).fail(function(response) {
						if(typeof response.error != 'undefined' && typeof response.error.message != 'undefined')
							callback({ok: false, task: 'updateBilltingInfo', action: 'failed', message: response.error.message});
						else
							callback({ok: false, task: 'updateBilltingInfo', action: 'failed', message: stateless_l10n.billing_failed});
					});
				}
				else{
					callback(null, {ok: true, task: 'updateBilltingInfo', action: 'already_enabled', message: stateless_l10n.billing_already_enabled});
				}
			}],
			createBucket: ['updateBilltingInfo', async.retryable({times: 10, interval: 1500, errorFilter: function(err) {
					return err.code != 409; // don't retry if Bucket already exist with this name.
				}
			}, function(results, callback){
				if( typeof wp.stateless.projects[projectId] == 'undefined' || typeof wp.stateless.projects[projectId]['buckets'][bucketId] == 'undefined'){
					// Bucket didn't exist.
					wp.stateless.createBucket({"projectId": projectId, "name": bucketId, location: regionId})
					.done(function(argument) {
						callback(null, {ok: true, task: 'createBucket', action: 'created', message: stateless_l10n.bucket_created});
					}).fail(function(response) {
						response = response.responseJSON;
						if(response && typeof response.error != 'undefined' && typeof response.error.message != 'undefined'){
							// Bucket already exist with this name.
							callback({ok: true, task: 'createBucket', action: 'existing', code: response.error.code, message: response.error.message});
						}
						else{
							callback({ok: false, task: 'createBucket', action: 'failed', message: stateless_l10n.bucket_creation_failed});
						}
					});
				}
				else{
					// Bucket exist
					callback(null, {ok: true, task: 'bucket', action: 'existing', message: stateless_l10n.bucket_exists});
				}
			})],
			createServiceAccount: ['createProjectProgress', function(results, callback){
				if( typeof wp.stateless.projects[projectId] != 'undefined' && typeof wp.stateless.projects[projectId]['serviceAccounts'] != 'undefined'){
					// Checking if service account exist.
					var serviceAccounts = wp.stateless.projects[projectId]['serviceAccounts'];
					var accountFound = false;
					jQuery.each(serviceAccounts, function(index, item) {
						if(item.email.replace(/@.*/, '') == serviceAccountId){ //item.displayName == serviceAccountName || 
							callback(null, {ok: true, task: 'createServiceAccount', action: 'existing', email: item.email, message: stateless_l10n.service_account_exist});
							accountFound = true;
							return false;
						}
					});
				}

				if(accountFound) return;

				wp.stateless.createServiceAccount({
					'projectId': projectId,
					'accountId': serviceAccountId,
					'name': serviceAccountName,
				}).done(function(createdSerciceAccount){
					callback(null, {ok: true, task: 'createServiceAccount', action: 'created', email: createdSerciceAccount.email, message: stateless_l10n.service_account_created});
				}).fail(function(response) {
					callback({ok: false, task: 'createServiceAccount', action: 'failed', message: stateless_l10n.service_account_creation_failed});
				});
			}],
			grantServiceAccountRole: [ 'createServiceAccount', function( results, callback ) {
				wp.stateless.grantServiceAccountRole(results['createServiceAccount'].email, projectId).done(function(policy){
					callback(null, {ok: true, task: 'grantServiceAccountRole', policy: policy, action: 'granted', message: stateless_l10n.service_account_role_granted});
				}).fail(function(response) {
					callback({ok: false, task: 'grantServiceAccountRole', action: 'failed', message: stateless_l10n.service_account_role_grant_failed});
				});
			}],
			insertBucketAccessControls: ['createBucket', 'enableAPI', 'createServiceAccount', async.retryable({times: 5, interval: 1500}, function(results, callback) {
				wp.stateless.insertBucketAccessControls({
					"bucket": bucketId,
					"user": results['createServiceAccount'].email,
				}).done(function(iam){
					callback(null, {ok: true, task: 'insertBucketAccessControls', iam: iam, action: 'inserted', message: stateless_l10n.bucket_access_controls_success});
				}).fail(function(response) {
					callback({ok: false, task: 'insertBucketAccessControls', action: 'failed', message: stateless_l10n.bucket_access_controls_failed});
				});
			})],
			createServiceAccountKey: ['insertBucketAccessControls', function(results, callback) {
				wp.stateless.createServiceAccountKeys({
					"project": projectId,
					"account": results['createServiceAccount'].email
				}).done(function(ServiceAccountKey){
					callback(null, {ok: true, task: 'createServiceAccountKey', privateKeyData: ServiceAccountKey.privateKeyData, action: 'created', message: stateless_l10n.service_account_key_created});
				}).fail(function(response) {
					callback({ok: false, task: 'createServiceAccountKey', action: 'failed', message: stateless_l10n.service_account_key_creation_failed});
				});
			}],
			saveServiceAccountKey: ['createServiceAccountKey', 'enableAPI', function(results, callback) {
				jQuery.ajax({
					url: ajaxurl,
					method: "POST",
					// We need to set header because we have se default header for google api auth.
					headers: {
						"content-type": "application/x-www-form-urlencoded",
					},
					data: {//JSON.stringify({
						'action': 'stateless_wizard_update_settings',
						'nonce': updateJsonNonce,
						'bucket': bucketId,
						'privateKeyData': results['createServiceAccountKey'].privateKeyData,
						'enableAPI': results['enableAPI'].action,
					}//)
				}).done(function(response) {
					if(typeof response.success != undefined && response.success == true){
						callback(null, {ok: true, task: 'saveServiceAccountKey', action: 'saved', message: stateless_l10n.service_account_key_saved});
					}
					else{
						callback({ok: false, task: 'saveServiceAccountKey', action: 'failed', message: stateless_l10n.service_account_key_save_failed});
					}
				}).fail(function(response) {
					callback({ok: false, task: 'saveServiceAccountKey', action: 'failed', message: stateless_l10n.service_account_key_save_failed});
				});
			}]
		}, function(err, results) {

			if(err){// || results.task == 'saveServiceAccountKey'){
				jQuery(this).find('.wpStateLess-loading').removeClass('active');
				comboBox.find('.circle-loader').removeClass('get-json-loading');
				errorWrapper.html(err.message).addClass('error').removeClass('notice notice-info').show();
				btnGetJson.removeClass('active disabled');
				return;
			}

			if(results.task == 'createProjectProgress'){
				projectDropdown.find('.circle-loader').addClass('load-complete');
			}
			else if(results.task == 'enableAPI'){
				console.log(results.message);
			}
			else if(results.task == 'updateBilltingInfo'){
				billingDropdown.find('.circle-loader').addClass('load-complete');
			}
			else if(results.task == 'createBucket'){
				bucketDropdown.find('.circle-loader').addClass('load-complete');
				regionDropdown.find('.circle-loader').addClass('load-complete');
			}
			else if(results.task == 'saveServiceAccountKey'){
				// We have access token.
				setupStepsBars.find('li')
					.removeClass('wpStateLess-done active')
					.filter('.step-google-login, .step-setup-project')
					.addClass('wpStateLess-done');
				setupSteps.removeClass('active')
					.filter('.step-final')
					.addClass('active');
				comboBox.find('.circle-loader').removeClass('get-json-loading');

			}

			if(errorWrapper.hasClass('error'))
				return;

			if(typeof results.message != 'undefined'){
				console.log(results.message);
				errorWrapper.html(results.message).addClass('notice notice-info').removeClass('error').show();
			}

		});
				
		return false;
	});
	var resizeId;
	var repositionLogoutButton = function(e) {
		if(jQuery(window).width() && jQuery(window).width() < 767){
			userDetails.append(userInfo.find('h4 .logout'));
		}
		else{
			userDetails.find('h4').append(userDetails.find('> .logout'));
		}
	};

	repositionLogoutButton();

	jQuery(window).on('orientationchange', repositionLogoutButton);
	jQuery(window).on('resize', function(e) {
		clearTimeout(resizeId);
		resizeId = setTimeout(repositionLogoutButton, 400);
	} );

});