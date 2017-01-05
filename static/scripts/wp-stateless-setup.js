/**
 * Extend the "wp" object; requires wp-api.
 *
 *
 */

wp.stateless = {

  /**
   * Returns Google API Auth token, either from sessionStorage or from URL, if on settings setup page.
   *
   * wp.stateless.getAccessToken()
   *
   */
  getAccessToken: function getAccessToken() {
    if( 'string' !== typeof wp.stateless.$_GET('access_token') ) {
      var expiry_date = parseInt(sessionStorage.getItem( 'wp.stateless.expiry_date' ));
      var isExpired = new Date().getTime() > expiry_date ? true : false;

      if( sessionStorage.getItem( 'wp.stateless.token' ) && !isExpired) {
        return sessionStorage.getItem( 'wp.stateless.token' );
      }

      return null;
    }

    try {
      var _token = wp.stateless.$_GET('access_token');
      var expiry_date = wp.stateless.$_GET('expiry_date');

      if( _token && 'string' === typeof _token ) {
        sessionStorage.setItem( 'wp.stateless.token', _token );
        sessionStorage.setItem( 'wp.stateless.expiry_date', expiry_date );
        return _token;
      }


    } catch( error ) {
      //console.error( error.message );
    }

    return null;

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

    jQuery.ajax({
      url: 'https://cloudresourcemanager.googleapis.com/v1/projects',
      method: "POST",
      dataType: "json",
      data: JSON.stringify( options ),
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.getAccessToken()
      }
    }).done(function( responseData  ) {
      jQuery.ajax({
        url: 'https://cloudresourcemanager.googleapis.com/v1/' + responseData.name,
        method: "GET",
        dataType: "json",
        headers: {
          "content-type": "application/json",
          "Authorization": " Bearer " + wp.stateless.getAccessToken()
        }
      }).done(function(responseData){
        jQuery('#google-storage').trigger('stateless::projectCreated', options);
      });

    }).fail(function( data ) {
      jQuery('#google-storage').trigger('stateless::projectCreated', false);
      console.log( "error", "data.responseText", JSON.parse( data.responseText ) );
    });


  },

  /**
   * Get Projects
   *
   * wp.stateless.listProjects()
   *
   */
  listProjects: function listProjects() {
    if(!wp.stateless.getAccessToken())
      return;
    var promis = jQuery.ajax({
      url: 'https://cloudresourcemanager.googleapis.com/v1/projects',
      method: "GET",
      dataType: "json",
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.getAccessToken()
      }
    });
    return promis;
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
      url: 'https://www.googleapis.com/storage/v1/b/?project=' + options.project,
      method: "POST",
      dataType: "json",
      data: JSON.stringify({name: options.name}),
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.getAccessToken()
      }
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
    if(!wp.stateless.getAccessToken() || !projectId)
      return false;

    var promis = jQuery.ajax({
      url: 'https://www.googleapis.com/storage/v1/b/',
      method: "GET",
      dataType: "json",
      data: {'project': projectId},
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.getAccessToken()
      }
    });
    return promis;
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
      url: 'https://iam.googleapis.com/v1/projects/' + options.project + '/serviceAccounts',
      method: "GET",
      dataType: "json",
      //data: JSON.stringify({name: options.name}),
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.getAccessToken()
      }
    });

    _promis.done(function(responseData){
      console.log( 'getServiceAccounts:done', responseData );

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
    console.log( 'createServiceAccount' );

    if(!wp.stateless.getAccessToken() || !options)
      return false;

    var promis = jQuery.ajax({
      url: 'https://www.googleapis.com/storage/v1/b/?project=' + options.project,
      method: "POST",
      dataType: "json",
      data: JSON.stringify({name: options.name}),
      headers: {
        "content-type": "application/json",
        "Authorization": " Bearer " + wp.stateless.getAccessToken()
      }
    });
    return promis;
  },

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

};



jQuery(document).ready(function($){
  var gs = $('#google-storage');
  var message = gs.find('#message');
  var projects_dropdown = gs.find('.projects');
  var bucketsWrapper = gs.find('#buckets-wrapper');
  var loadProject = function(projectId){
    var authorize = gs.find('a.button.authorize');
    var $projects = wp.stateless.listProjects();
    projectId = projectId?projectId:'';

    if(!$projects){
      authorize.show();
      projects_dropdown.hide();
      return;
    }

    $projects.done(function(responseData){
      authorize.hide();
      projects_dropdown.show();
      if(responseData.projects){
        responseData.projects = $.grep(responseData.projects, function(project){
          return project.lifecycleState == "ACTIVE";
        });
      }
      if(!responseData.projects || responseData.projects.length == 0){
        projects_dropdown.hide();
        gs.find('#new-project').show();
        return;
      }
      projects_dropdown.find('option').remove();
      projects_dropdown.append("<option value=''>Select Project</option>");
      $.each(responseData.projects, function(index, item){
        projects_dropdown.append("<option value='" + item.projectId + "'>" + item.name + "</option>");
      });
      if(projectId){
        projects_dropdown.val(projectId);
        projects_dropdown.trigger('change');
      }
    }).fail(function(response){
      authorize.show();
      projects_dropdown.hide();
    });

  }

  loadProject();
  gs.find('#create-bucket').on('click', function(e){
    e.preventDefault();
    var projectID = projects_dropdown.val();
    var bucketName = gs.find('#bucket-name').val();
    if(projectID == "" || bucketName == "")
      return false;

    var createdProject = wp.stateless.createBucket({
      "project": projectID,
      "name": bucketName
    });
    console.log(createdProject);
    return false;
  });

  gs.find('#create-project').on('click', function(e){
    e.preventDefault();
    var projectID = gs.find('#project-id').val();
    var projectName = gs.find('#project-name').val();
    if(projectID == "" || projectName == "")
      return false;

    var createdProject = wp.stateless.createProject({
      "projectId": projectID,
      "name": projectName
    });
    console.log(createdProject);
    return false;
  });

  projects_dropdown.on('change', function(){
    var $billing = gs.find('#enable-billing');
    var projectID = projects_dropdown.val();
    var projectName = projects_dropdown.find('option:selected').text();

    if(!projectID){
      $billing.hide();
      return;
    }

    $billing.find('a').attr('href', 'https://console.cloud.google.com/billing?project=' + projectID);
    $billing.find('.pname').html(projectName);
    $billing.show();
    console.log(projectID);
    var buckets = wp.stateless.listBucket(projectID);
    buckets.done(function(responseData){
      var bucket = bucketsWrapper.find('select');
      bucket.find('option').remove();
      bucket.append("<option value=''>Select Bucket</option>");
      $.each(responseData.items, function(index, item){
        bucket.append("<option value='" + item.id + "'>" + item.name + "</option>");
      });
      bucketsWrapper.show();
    });
  });

  gs.find('.button.add-new').on('click', function(e){
    e.preventDefault();
    var id = $(this).attr('href');
    gs.find(id).toggle();
    return false;
  });
  gs.on('stateless::projectCreated', function(e, project){
    if(!project){
      message.addClass('error').html("Something went wrong.");
      return;
    }
    message.hide();
    setTimeout(function(){
      loadProject(project.projectId);
      gs.find('#new-project').hide();
    }, 5000);
    
  });
});