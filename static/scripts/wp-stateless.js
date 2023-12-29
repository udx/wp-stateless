var $wildcards_select = jQuery('.select-wildcards').select2({
  tags: true,
  tokenSeparators: ['/'],
  createTag: function (params) {
    var term = jQuery.trim(params.term)

    if (term === '') {
      return null
    }

    let tags = [
      '%site_id%',
      '%site_url%',
      '%site_url_host%',
      '%site_url_path%',
      '%date_year/date_month%',
    ]

    // Remove special chars from tags
    if (!/^[a-zA-Z0-9_\-.]+$/.test(term) && jQuery.inArray(term, tags) == -1) {
      term = term.replace(/[^a-zA-Z0-9_\-.]/g, '')
    }

    return {
      id: term,
      text: term,
    }
  },
  templateSelection: function (state) {
    // Add slash at the en of the tag
    return state.text
  },
  insertTag: function (data, tag) {
    // Insert the tag at the end of the results
    data.push(tag)
  },
})

jQuery('.select-wildcards').on('select2:select', function (evt) {
  var element = evt.params.data.element
  var $element = jQuery(element)
  let $element_value = $element.value

  $element.detach()
  jQuery(this).append($element)

  /**
   * add slash after tag
   */
  if ($element_value !== '/') {
    // Create the DOM option that is pre-selected by default
    var newState = new Option('/', '/', true, true)
    jQuery(newState).prop('disabled', true)
    // Append it to the select
    jQuery(this).append(newState)
  }

  jQuery(this).trigger('change')
  prepare_preview_url()
})

jQuery('.select-wildcards').on('select2:unselect', function (evt) {
  if (evt.params.data.id === '/') {
    var element = evt.params.data.element
    var $element = jQuery(element)
    $element.detach()
  }
  prepare_preview_url()
})

function prepare_preview_url() {
  let root_dir = ''
  let selected_wildcards = jQuery('.select-wildcards').val()

  if (selected_wildcards !== null) {
    root_dir = selected_wildcards.join('/')
  }

  jQuery('#sm_root_dir').val(root_dir)
  jQuery('#sm_root_dir').trigger('change')
}

function replace_wildcard_to_the_end(wildcards, remove_slashes = false) {
  if (remove_slashes) {
    jQuery('.select-wildcards > option').each(function () {
      if (this.value == '/') {
        jQuery(this).detach()
      }
    })
  }
  wildcards.forEach(function (wildcard) {
    wildcard_exist = false
    jQuery('.select-wildcards > option').each(function () {
      if (this.value === wildcard && this.value !== '/') {
        jQuery(this).detach()
        jQuery('.select-wildcards').append(jQuery(this))
        wildcard_exist = true
      }
    })
    if (!wildcard_exist) {
      var newState = new Option('/', '/', true, true)
      jQuery(newState).prop('disabled', true)
      // Append it to the select
      jQuery('.select-wildcards').append(newState)
    }
  })
}

// Application
var wpStatelessSettingsApp = {
  sm: {},
  backup: {},
  is_ssl: false,

  // Show notices for readonly fields
  showNotice: function (field) {
    if (this.sm.readonly && this.sm.readonly[field]) {
      var slug = this.sm.readonly[field]
     
      jQuery(`#notice-${field}`).html(this.sm.strings[slug])
      jQuery(`[name="sm[${field}]"]`).prop('disabled', true)
    } else {
      jQuery(`#notice-${field}`).html('')
      jQuery(`[name="sm[${field}]"]`).prop('disabled', false)
    }
  },

  showSupportedTypes: function () {
    value = jQuery('#sm_body_rewrite').val()

    if ( ['true', 'enable_editor', 'enable_meta'].indexOf(value) > -1 ) {
      jQuery('.supported-file-types').show()
    } else {
      jQuery('.supported-file-types').hide()
    }
  },

  setIsSSL: function () {
    this.is_ssl = jQuery('#custom_domain').val().indexOf('https://') === 0

    if (this.is_ssl) {
      jQuery('.notice-is-ssl').show()
    } else {
      jQuery('.notice-is-ssl').hide()
    }
  },

  getRadioValue: function(name) {
    return jQuery(`input[name="${name}"]:checked`).val()    
  },

  enableHashifyFileName: function () {
    var mode = this.getRadioValue('sm[mode]') 

    if ( ['stateless', 'ephemeral'].indexOf(mode) > -1 
      && this.sm.readonly['hashify_file_name'] != 'constant' ) {
        this.backup['hashify_file_name'] = jQuery('#cache_busting').val()
        jQuery('#cache_busting').val('true')
    } else if ( this.backup['hashify_file_name'] ) {
      jQuery('#cache_busting').val( this.backup['hashify_file_name'] )
    }

    if (mode == 'stateless') {
      jQuery('#cache_busting').prop('disabled', true)
    } else {
      this.showNotice('hashify_file_name')
    }

    if (mode == 'stateless' && this.sm.readonly['hashify_file_name'] != 'constant') {
      jQuery('#notice-hashify_file_name-mode').show()
    } else {
      jQuery('#notice-hashify_file_name-mode').hide()
    }
  },

  enableDynamicImageSupport: function () {
    var mode = this.getRadioValue('sm[mode]') 

    if ( mode == 'stateless' && this.sm.readonly['dynamic_image_support'] != 'constant' ) {
      this.backup['dynamic_image_support'] = jQuery('#dynamic_image_support').val()
      jQuery('#dynamic_image_support').val('false')
    } else if ( this.backup['dynamic_image_support'] ) {
      jQuery('#dynamic_image_support').val( this.backup['dynamic_image_support'] )
    }

    if (mode == 'stateless') {
      jQuery('#dynamic_image_support').prop('disabled', true)
    } else {
      this.showNotice('dynamic_image_support')
    }

    if (mode == 'stateless' && this.sm.readonly['dynamic_image_support'] != 'constant') {
      jQuery('#notice-dynamic_image_support-mode').show()
    } else {
      jQuery('#notice-dynamic_image_support-mode').hide()
    }
  },

  switchBucketFolderType: function() {
    var value = jQuery('#sm_root_dir').val()
    var folderType = jQuery('#sm_bucket_folder_type').val()

    switch (value) {
      case '%date_year/date_month%':
        folderType = 'single-site'
        break;
      case 'sites/%site_id%/%date_year/date_month%':
        folderType = 'multi-site'
        break;
      case '':
        if ( this.sm.network_admin )
        folderType = ''
        break;
      default:
        folderType = 'custom'
    }

    if ( jQuery('#sm_bucket_folder_type').val() != folderType ) {
      jQuery('#sm_bucket_folder_type').val( folderType)
    }

    setTimeout(function () {
      jQuery('#permalink_structure').trigger('change')
    }, 1)
  },

  switchRootDir: function() {
    var value = jQuery('#sm_bucket_folder_type').val()

    switch (value) {
      case 'single-site':
        replace_wildcard_to_the_end(['/', '%date_year/date_month%', '/'], true)
        $wildcards_select
          .val(['/', '%date_year/date_month%', '/'])
          .trigger('change')
        break;
      case 'multi-site':
        replace_wildcard_to_the_end(
          ['/', 'sites', '/', '%site_id%', '/', '%date_year/date_month%', '/'],
          true
        )
        $wildcards_select
          .val([
            '/',
            'sites',
            '/',
            '%site_id%',
            '/',
            '%date_year/date_month%',
            '/',
          ])
          .trigger('change')
        break;
      case '':
        if ( wp_stateless_settings.network_admin ) {
          $wildcards_select.val(null).trigger('change')
        }
        break;
    }

    prepare_preview_url()
  },

  generatePreviewUrl: function() {
    var host = 'https://storage.googleapis.com/'
    var hash =
      jQuery('#cache_busting').val() == 'true'
        ? Date.now().toString(36) + '-'
        : ''
    var custom_domain = jQuery('#custom_domain').val().toString()
    var root_dir = jQuery('#sm_root_dir').val().toString()
    var bucket = jQuery('#bucket_name').val().toString()

    jQuery.each(this.sm.wildcards, function (index, item) {
      var reg = new RegExp(index, 'g')
      root_dir = root_dir.replace(reg, item[0])
    })
  
    var tags = [
      '%date_year%',
      '%date_month%',
      '%site_id%',
      '%site_url%',
      '%site_url_host%',
      '%site_url_path%',
    ]

    var value_splitted = root_dir.split('/')

    for (var i = 0; i < value_splitted.length; i++) {
      if (
        !/^[a-zA-Z0-9_\-.]+$/.test(value_splitted[i]) &&
        value_splitted[i] != '' &&
        jQuery.inArray(value_splitted[i], tags) == -1
      ) {
        value_splitted[i] = value_splitted[i].replace(/[^a-zA-Z0-9_\-.]/g, '')
      }
    }
    
    root_dir = value_splitted.join('/')
    root_dir = root_dir.replace(/(\/+)/g, '/')
    root_dir = root_dir.replace(/^\//, '')
    root_dir = root_dir.replace(/\/$/, '')

    if (root_dir) {
      root_dir = root_dir + '/'
    }

    custom_domain = custom_domain.replace(/\/+$/, '') // removing trailing slashes
    custom_domain = custom_domain.replace(/https?:\/\//, '') // removing http:// or https:// from the beginning.
    host += bucket.length > 0 ? bucket : '{bucket-name}'

    if (
      custom_domain !== 'storage.googleapis.com' &&
      bucket.length > 0 &&
      custom_domain.length > 0 &&
      (this.is_ssl || custom_domain == bucket)
    ) {
      host = this.is_ssl ? 'https://' : 'http://' // bucket name will be host
      host += custom_domain
    }

    host += '/' + root_dir + hash + 'your-image-name.jpeg'

    jQuery('#file_url_grp_preview').val(host)
  },

  // Init application
  init: function () {
    this.sm = wp_stateless_settings || {}
    this.sm.readonly = this.sm.readonly || {}

    var readonly = Object.keys(this.sm.readonly);

    for (var key of readonly) {
      this.showNotice(key)
    }

    if (this.sm.network_admin) {
      jQuery('#cache_busting').val('true')
      this.sm.readonly.hashify_file_name = true
    }

    // Disable root dir editing if it's readonly
    if (this.sm.readonly['root_dir']) {
      $wildcards_select.prop('disabled', true)
      jQuery('#sm_bucket_folder_type').prop('disabled', true)
    }

    // Show supported file types
    jQuery('#sm_body_rewrite').on('change', this.showSupportedTypes)
    this.showSupportedTypes()

    // Check if custom domain is SSL
    jQuery('#custom_domain').on('change', this.setIsSSL.bind(this))
    this.setIsSSL()

    // Check if hashify file name is enabled
    jQuery('[name="sm[mode]"').on('change', this.enableHashifyFileName.bind(this))
    this.enableHashifyFileName()

    // Check if dynamic image support is enabled
    jQuery('[name="sm[mode]"').on('change', this.enableDynamicImageSupport.bind(this))
    this.enableDynamicImageSupport()

    // Switch folder type depending on root dir
    jQuery('#sm_root_dir').on('change', this.switchBucketFolderType.bind(this))
    this.switchBucketFolderType()

    // Update root dir depending on folder type
    jQuery('#sm_bucket_folder_type').on('change', this.switchRootDir)

    // Generate preview URL
    jQuery('#bucket_name').on('change', this.generatePreviewUrl.bind(this))
    jQuery('#sm_root_dir').on('change', this.generatePreviewUrl.bind(this))
    jQuery('#custom_domain').on('change', this.generatePreviewUrl.bind(this))
    this.generatePreviewUrl()
  }
};

wpStatelessSettingsApp.init();


var wpStatelessApp = angular
  .module('wpStatelessApp', ['ngSanitize'])

  // Controllers
  .controller('wpStatelessProcessing', function ($scope, $http) {
    /**
     * General Errors
     */
    $scope.errors = []

    /**
     * Global level flag for the ability to run any of the processes
     */
    $scope.can_run = true

    /**
     * Block UI to prevent unwanted quick action sequences
     */
    $scope.blockUI = function () {
      $scope.can_run = false
    }

    /**
     * Unblock UI to allow further actions
     */
    $scope.unblockUI = function () {
      $scope.can_run = true
    }

    /**
     * Count percantage
     * @param {*} part
     * @param {*} base
     */
    $scope.percentage = function (part, base) {
      return parseInt((100 / base) * part) + '%'
    }

    /**
     * Processes Model
     */
    $scope.processes = {
      isLoading: false,
      classes: [],
      load: function () {
        var that = this
        that.isLoading = true
        $http({
          method: 'GET',
          url: window.wpApiSettings.root + 'wp-stateless/v1/sync/getProcesses',
          headers: {
            'x-wps-auth': window.wp_stateless_configs.REST_API_TOKEN,
          },
        })
          .then(function (response) {
            that.classes = []
            if (response && response.data) {
              var processes = response.data.data || {}
              for (var i in processes) {
                var sync = new ProcessingClass(processes[i])
                if (sync.is_running) {
                  sync.startPolling()
                } else {
                  sync.stopPolling()
                }
                that.classes.push(sync)
              }
            } else {
              $scope.errors.push(window.stateless_l10n.something_went_wrong)
            }
            that.isLoading = false
          })
          .catch(function (error) {
            $scope.errors.push(
              error.data.message || window.stateless_l10n.something_went_wrong
            )
            that.isLoading = false
          })
      },
    }

    // initialize stuff
    $scope.init = function () {
      ProcessingClass.prototype.$http = $http
      ProcessingClass.prototype.$scope = $scope
      $scope.processes.load()
    }

    // watch for any running process
    $scope.$watch(
      function (scope) {
        if (!scope.processes.classes.length) return false
        return Boolean(
          scope.processes.classes.find(function (process) {
            return process.is_running
          })
        )
      },
      function (is_running) {
        // Disable the ability to save settings if there are running processes
        jQuery('#save-settings,#save-compatibility').attr(
          'disabled',
          is_running
        )

        // maybe remove nag
        if (is_running)
          jQuery('#stateless-notice-processing-in-progress').show()
        else jQuery('#stateless-notice-processing-in-progress').hide()
      }
    )
  })
  .controller('noJSWarning', function ($scope, $filter) {
    $scope.jsLoaded = true
  })

wpStatelessApp.filter('trust', [
  '$sce',
  function ($sce) {
    return function (htmlCode) {
      return $sce.trustAsHtml(htmlCode)
    }
  },
])

/**
 * ProcessingClass
 *
 * @param {*} data
 */
function ProcessingClass(data) {
  this.id = false
  this.total_items = 0
  this.queued_items = 0
  this.processed_items = 0
  this.is_running = false
  this.limit = 0
  this.order = 'desc'

  // Build an instance
  Object.assign(this, data)

  this.interval = null

  /**
   * Run sync
   */
  this.run = function () {
    var that = this
    this.is_running = true
    this.is_stopping = false
    this.$scope.blockUI()
    this.$http({
      method: 'POST',
      url: window.wpApiSettings.root + 'wp-stateless/v1/sync/run',
      headers: {
        'x-wps-auth': window.wp_stateless_configs.REST_API_TOKEN,
      },
      data: {
        id: this.id,
        limit: this.limit,
        order: this.order,
      },
    })
      .then(function (response) {
        if (response.data && response.data.ok) {
          that.startPolling()
        } else {
          that.$scope.errors.push(
            response.data.message || window.stateless_l10n.something_went_wrong
          )
        }
      })
      .catch(function (error) {
        that.stopPolling()
        that.$scope.errors.push(
          error.data.message || window.stateless_l10n.something_went_wrong
        )
      })
      .finally(function () {
        that.$scope.unblockUI()
      })
  }

  /**
   * If process can be ran
   */
  this.canRun = function () {
    return this.total_items > 0 && !this.is_running && this.$scope.can_run
  }

  /**
   * If proccess can be stopped
   */
  this.canStop = function () {
    return this.is_running && !this.is_stopping
  }

  /**
   * Get Progress bar possible total
   */
  this.getProgressTotal = function () {
    if (this.limit > 0 && this.limit <= this.total_items) {
      return this.limit
    }
    return this.total_items
  }

  /**
   * Get Total queued count
   */
  this.getQueuedTotal = function () {
    return this.queued_items > this.total_items
      ? this.total_items
      : this.queued_items
  }

  /**
   * Get processed total
   */
  this.getProcessedTotal = function () {
    return this.processed_items > this.getQueuedTotal()
      ? this.getQueuedTotal()
      : this.processed_items
  }

  /**
   * Stop the process
   */
  this.stop = function () {
    var that = this
    this.is_stopping = true
    this.$http({
      method: 'POST',
      url: window.wpApiSettings.root + 'wp-stateless/v1/sync/stop',
      headers: {
        'x-wps-auth': window.wp_stateless_configs.REST_API_TOKEN,
      },
      data: {
        id: this.id,
      },
    })
      .then(function (response) {
        if (response.data && response.data.ok) {
        }
      })
      .catch(function (error) {
        that.$scope.errors.push(
          error.data.message || window.stateless_l10n.something_went_wrong
        )
      })
  }

  /**
   * Start polling for changes
   */
  this.startPolling = function () {
    var that = this
    this.stopPolling()
    this.interval = setInterval(function () {
      that.refresh()
    }, 5000)
  }

  /**
   * Stop polling for changes
   */
  this.stopPolling = function () {
    clearInterval(this.interval)
  }

  /**
   * Refresh the process data
   */
  this.refresh = function () {
    var that = this
    this.$http({
      method: 'GET',
      url:
        window.wpApiSettings.root +
        'wp-stateless/v1/sync/getProcess/' +
        String(window.btoa(this.id)).replace(/=+/, ''),
      headers: {
        'x-wps-auth': window.wp_stateless_configs.REST_API_TOKEN,
      },
    })
      .then(function (response) {
        if (
          response &&
          response.data &&
          response.data.ok &&
          response.data.data
        ) {
          if (!response.data.data.is_running) {
            that.stopPolling()
            that.queued_items = that.getProgressTotal()
            that.processed_items = that.getProgressTotal()
            setTimeout(function () {
              Object.assign(that, response.data.data)
              that.$scope.$apply()
            }, 3000)
          } else {
            Object.assign(that, response.data.data)
          }
        }
      })
      .catch(function (error) {
        that.stopPolling()
        that.$scope.errors.push(
          error.data.message || window.stateless_l10n.something_went_wrong
        )
      })
  }
}
