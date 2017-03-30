/**
 * Extend the "wp" object; requires wp-api.
 *
 *
 */

/**
 * To do: Check regular expression before submit.
 *
 *
 *
 */

wp.stateless = {
  access_token: '',
  projects: {},
  selectedSettings: {},


  $_GET: function (name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
  },

  /**
   * Returns Google API Auth token, either from sessionStorage or from URL, if on settings setup page.
   *
   * wp.stateless.getAccessToken()
   *
   */
  clearAccessToken: function clearAccessToken() {
    try {
        var title = jQuery('head').find("title").text();
        wp.stateless.access_token = null;
        sessionStorage.removeItem( 'wp.stateless.token' );
        sessionStorage.removeItem( 'wp.stateless.expiry_date' );
        History.replaceState('', title, '?page=stateless-setup-wizard&step=google-login');
        return true;
    } catch( error ) {
      console.error( error.message );
    }

    return false;

  },
  getAccessToken: function getAccessToken(options) {
    if(wp.stateless.access_token)
      return wp.stateless.access_token;

    if( 'string' !== typeof wp.stateless.$_GET('access_token') ) {
      // There is no token in query string. Lets look in session.
      var expiry_date = parseInt(sessionStorage.getItem( 'wp.stateless.expiry_date' ));
      var isExpired = new Date().getTime() > expiry_date ? true : false;

      if( sessionStorage.getItem( 'wp.stateless.token' ) && !isExpired) {
        wp.stateless.access_token = sessionStorage.getItem( 'wp.stateless.token' );
      }
      else if(typeof options == 'undefined' || options.triggerEvent !== false){
        jQuery(document).trigger('tokenExpired');
        return false;
      }
    }

    try {
      // Checking for token in query string. If have save in session.
      if(_token = wp.stateless.$_GET('access_token')){
        var expiry_date = wp.stateless.$_GET('expiry_date');
        var isExpired = new Date().getTime() > expiry_date ? true : false;
        var title = jQuery('head').find("title").text();

        if( _token && 'string' === typeof _token  && !isExpired) {
          wp.stateless.access_token = _token;
          sessionStorage.setItem( 'wp.stateless.token', _token );
          sessionStorage.setItem( 'wp.stateless.expiry_date', expiry_date );
          History.replaceState('', title, '?page=stateless-setup-wizard&step=setup-project');
        }
      }

    } catch( error ) {
      //console.error( error.message );
      return false;
    }

    jQuery.ajaxSetup({
      method: "GET",
      dataType: "json",
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.access_token
      }
    });

    return wp.stateless.access_token;
  },
  /**
   *
   * wp.stateless.getProfile({project:'uds-io-154013'})
   *
   * @param options
   * @returns {boolean}
   */
  getProfile: function getProfile() {

    var defer = new jQuery.Deferred();
    var getPrimaryData = function getPrimaryData(items){
      var primaryItem = items[0];
      jQuery.each(items, function(index, item){
        if(item.metadata.primary == true){
          primaryItem = item;
          return false;
        }
      });
      return primaryItem;
    };
    
    jQuery.ajax({
      url: 'https://people.googleapis.com/v1/people/me',
    }).done(function(responseData){
      var name = getPrimaryData(responseData.names);
      var email = getPrimaryData(responseData.emailAddresses);
      var photo = getPrimaryData(responseData.photos);
      var profile = {
        name: name.displayName,
        email: email.value,
        photo: photo.url
      }
      defer.resolve(profile);
    }).fail(function(){
      defer.reject();
    });
    

    return defer.promise();
  },

  /**
   * Create Project
   *
   *
   *  wp.stateless.createProject( {"projectId": "uds-test-project-4","name": "uds-test-project-4"} );
   *
   *
   * @todo After this is implemented we also need to assign the user to the project. - potanin@UD
   * @param options
   */
  createProject: function createProject( options ) {
    var defer = new jQuery.Deferred();
    
    if(!wp.stateless.getAccessToken()){
      defer.reject();
      return defer.promise("In valid access token.");
    }

    jQuery.ajax({
      url: 'https://cloudresourcemanager.googleapis.com/v1/projects',
      method: "POST",
      data: JSON.stringify( options ),
    }).done(function( responseData  ) {
      wp.stateless.createProjectProgress(responseData.name);

      jQuery(document).on('wp-stateless-project-created-' + responseData.name, function(argument) {
        defer.resolve(responseData);
      });
      jQuery(document).on('project-creation-faild-' + responseData.name, function(argument) {
        defer.reject(responseData);
      });
    }).fail(function(responseData) {
      defer.reject(responseData);
    });



    return defer.promise();
  },


  createProjectProgress: function createProjectProgress(name){
    jQuery.ajax({
      url: 'https://cloudresourcemanager.googleapis.com/v1/' + name,
    }).done(function(responseData){
      if(typeof responseData.done != 'undefined' && responseData.done == true){
        jQuery(document).trigger('wp-stateless-project-created-' + name);
      }else{
        setTimeout(function(argument) {
          wp.stateless.createProjectProgress(name);
        }, 800);
      }
    }).fail(function(responseData) {
      jQuery(document).trigger('project-creation-faild-' + name);
    });
  },

  /**
   * Get Projects
   *
   * wp.stateless.listProjects()
   *
   */
  listProjects: function listProjects() {
    var defer = new jQuery.Deferred();
    
    if(!wp.stateless.getAccessToken()){
      defer.reject();
      return defer.promise();
    }

    jQuery.ajax({
      url: 'https://cloudresourcemanager.googleapis.com/v1/projects',
    }).done(function(responseData){
      var projects = [];

      if(typeof responseData.projects == 'undefined'){
        responseData.projects = {};
      }

      responseData.projects = jQuery.grep(responseData.projects, function(project){
        return project.lifecycleState == "ACTIVE";
      });

      jQuery.each(responseData.projects, function(index, item){
        projects.push({id: item.projectId, name: item.name});
        wp.stateless.projects[item.projectId] = item;
        wp.stateless.projects[item.projectId]['buckets'] = {};
        wp.stateless.projects[item.projectId]['serviceAccounts'] = {};
      });

      defer.resolve(projects);
    }).fail(function(){
      defer.reject();
    });
    return defer.promise();
  },

  /**
   * Get Projects
   *
   * wp.stateless.listProjects()
   *
   */
  createBucket: function createBucket(options) {
    if(!wp.stateless.getAccessToken() || !options)
      return false;

    var promis = jQuery.ajax({
      url: 'https://www.googleapis.com/storage/v1/b/?project=' + options.projectId,
      method: "POST",
      data: JSON.stringify({name: options.name}),
    });
    return promis;
  },
  /**
   * Get Projects
   *
   * wp.stateless.listProjects()
   *
   */
  listBucket: function listBucket(projectId) {
    var defer = new jQuery.Deferred();

    if(!wp.stateless.getAccessToken() || !projectId){
      defer.reject();
      return defer.promise();
    }

    jQuery.ajax({
      url: 'https://www.googleapis.com/storage/v1/b/',
      data: {'project': projectId},
    }).done(function(responseData){
      var buckets = [];

      jQuery.each(responseData.items, function(index, item){
        var bucket = {id: item.id, name: item.name};
        buckets.push(bucket);
        wp.stateless.projects[projectId]['buckets'][item.id] = bucket;
      });

      defer.resolve(buckets);
    }).fail(function(){
      defer.reject();
    });
    return defer.promise();
  },

  /**
   *
   * wp.stateless.getServiceAccounts({project:'uds-io-154013'})
   *
   * @param options
   * @returns {boolean}
   */
  getServiceAccounts: function getServiceAccounts(options) {
    console.log( 'getServiceAccounts', options );

    if(!wp.stateless.getAccessToken() || !options)
      return false;

    var _promis = jQuery.ajax({
      url: 'https://iam.googleapis.com/v1/projects/' + options.projectId + '/serviceAccounts',
      //data: JSON.stringify({name: options.name}),
    });

    _promis.done(function(responseData){
      console.log( 'getServiceAccounts:done', responseData.accounts );
      wp.stateless.projects[options.projectId]['serviceAccounts'] = responseData.accounts;

    });

    return _promis;

  },

  /**
   *
   * wp.stateless.createServiceAccount()
   *
   * @param options
   * @returns {boolean}
   */
  createServiceAccount: function createServiceAccount(options) {

    if(!wp.stateless.getAccessToken() || !options)
      return false;

    var promis = jQuery.ajax({
      url: 'https://iam.googleapis.com/v1/projects/' + options.projectId + '/serviceAccounts',
      method: "POST",
      data: JSON.stringify({
        accountId: options.accountId,
        serviceAccount: {
          displayName: options.name
        }
      }),
    });
    return promis;
  },

  /**
   *
   * wp.stateless.createServiceAccount()
   *
   * @param options
   * @returns {boolean}
   */
  listServiceAccountKeys: function listServiceAccountKeys(options) {

    if(!wp.stateless.getAccessToken() || !options)
      return false;

    var promis = jQuery.ajax({
      url: 'https://iam.googleapis.com/v1/projects/' + options.project + '/serviceAccounts/' + options.account + "/keys",
    });
    return promis;
  },

  /**
   *
   * wp.stateless.createServiceAccount()
   *
   * @param options
   * @returns {boolean}
   */
  createServiceAccountKeys: function createServiceAccountKeys(options) {

    if(!wp.stateless.getAccessToken() || !options)
      return false;
    var promis = jQuery.ajax({
      url: 'https://iam.googleapis.com/v1/projects/' + options.project + '/serviceAccounts/' + options.account + "/keys",
      method: "POST",
      data: JSON.stringify({
        privateKeyType: options.privateKeyType || 'TYPE_GOOGLE_CREDENTIALS_FILE',
        keyAlgorithm: options.keyAlgorithm || 'KEY_ALG_RSA_2048',
      }),
    });
    return promis;
  },

  /**
   *
   * wp.stateless.createServiceAccount()
   * Doc: https://cloud.google.com/storage/docs/json_api/v1/bucketAccessControls/insert
   * @param options
   * @returns {boolean}
   */
  insertBucketAccessControls: function insertBucketAccessControls(options) {

    if(!wp.stateless.getAccessToken() || !options)
      return false;
    var promis = jQuery.ajax({
      url: 'https://www.googleapis.com/storage/v1/b/' + options.bucket + '/acl',
      method: "POST",
      data: JSON.stringify({
        entity: "user-" + options.user,
        role: options.role || 'OWNER',
      }),
    });
    return promis;
  },

  /**
   *
   * wp.stateless.createServiceAccount()
   * Doc: https://cloud.google.com/storage/docs/json_api/v1/bucketAccessControls/insert
   * @param options
   * @returns {boolean}
   * 
   */
  getProjectBillingInfo: function getProjectBillingInfo(projectID) {
    var defer = new jQuery.Deferred();

    if(!wp.stateless.getAccessToken() || !projectID)
      return false;

    jQuery.ajax({
      url: 'https://cloudbilling.googleapis.com/v1/projects/' + projectID + '/billingInfo',
    }).done(function(responseData){
      if(typeof responseData.billingAccountName != 'undefined'){
        responseData.billingAccountName = responseData.billingAccountName.replace('billingAccounts/', '');
      }

      if(typeof responseData.billingEnabled != 'undefined' && responseData.billingEnabled == true){
        wp.stateless.projects[projectID]['billingInfo'] = responseData;
      }

      defer.resolve(responseData);
    }).fail(function() {
      defer.reject();
    });
    return defer.promise();
  },

  updateProjectBillingInfo: function updateProjectBillingInfo(options) {

    if(!wp.stateless.getAccessToken() || !options.projectID)
      return false;
    var promis = jQuery.ajax({
      method: 'PUT',
      url: 'https://cloudbilling.googleapis.com/v1/projects/' + options.projectID + '/billingInfo',
      data: JSON.stringify({
        'billingAccountName': 'billingAccounts/' + options.accountName
      })
    });
    return promis;
  },

  listBillingAccounts: function listBillingAccounts() {
    var defer = new jQuery.Deferred();

    if(!wp.stateless.getAccessToken()){
      defer.reject();
      return defer.promise();
    }

    jQuery.ajax({
      url: 'https://cloudbilling.googleapis.com/v1/billingAccounts',
    }).done(function(responseData){
      var billingAccounts = [];
      if(responseData.billingAccounts){
        responseData.billingAccounts = jQuery.grep(responseData.billingAccounts, function(accounts){
          return accounts.open == true;
        });

        jQuery.each(responseData.billingAccounts, function(index, item){
          item.name = item.name.replace('billingAccounts/', '');
          billingAccounts.push({id: item.name, name: item.displayName});
        });

      }
      defer.resolve(billingAccounts);
    }).fail(function(){
      defer.reject();
    });
    return defer.promise();
  },

};

