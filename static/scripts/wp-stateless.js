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

    if ( ['stateless', 'ephemeral'].indexOf(mode) > -1 ) {
      jQuery('#cache_busting').prop('disabled', true)
    } else {
      this.showNotice('hashify_file_name')
    }

    if ( ['stateless', 'ephemeral'].indexOf(mode) > -1 && this.sm.readonly['hashify_file_name'] != 'constant') {
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

  showCustomEmail: function () {
    if( jQuery('#sm_status_email_type').val() == 'custom' ) {
      jQuery('.sm-status-email-address').show()
    } else {
      jQuery('.sm-status-email-address').hide()
    }
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

    // Update root dir depending on folder type
    jQuery('#sm_status_email_type').on('change', this.showCustomEmail)
    this.showCustomEmail()
  }
};

wpStatelessSettingsApp.init();

// Processing application
wpStatelessProcessingApp = {
  errors: [],
  canRun: true,
  processes: [],
  token: window.wp_stateless_configs.REST_API_TOKEN,
  apiRoot: window.wp_stateless_configs.api_root + 'sync/',

  blockUI: function () {
    this.canRun = false

    this.processes.map( function (process) {
      process.refreshButtons()
    })
  },

  unblockUI: function () {
    this.canRun = true

    this.processes.map( function (process) {
      process.refreshButtons()
    })
  },

  /**
   * Prevent global changes
   */
  preventChanges: function () {
    var isRunning = this.processes.find( function (process) {
      return process.is_running
    }) ? true : false

    jQuery('#save-settings,#save-compatibility').prop('disabled', isRunning)
  },

  /**
   * Handle errors display 
   */
  addError: function (error) {
    this.errors.push(error)

    jQuery('#stless_sync_tab #errors').show()

    var html = this.errors.map(function (error) {
      return '<li>' + error + '</li>'
    })

    jQuery('#stless_sync_tab #errors ul').html(html)
  },

  /**
   * Process response error
   */
  processError: function (error) {
    var message = error && error.responseJSON && error.responseJSON.message 
      ? error.responseJSON.message 
      : window.stateless_l10n.something_went_wrong
  
    this.addError(message)
  },

  /**
   * Load process data
   */
  init: function () {
    var that = this
    that.blockUI()

    jQuery.ajax({
      method: 'GET',
      url: that.apiRoot + 'getProcesses',
      headers: {
        'x-wps-auth': that.token,
      },
    })
      .done(function (response) {
        if (response && response.data) {
          var processes = response.data || {}
          for (var i in processes) {
            var process = new ProcessingClass(processes[i], that)
            process.refreshBox()
            that.processes.push(process)
          }
        } else {
          that.addError(window.stateless_l10n.something_went_wrong)
        }
      })
      .fail(function (error) {
        that.processError(error)
      })
      .always(function () {
        that.unblockUI()
      })
  },

  /**
   * Refresh the process data
   */
  refreshProcess: function (process) {
    var that = this

    jQuery.ajax({
      method: 'GET',
      url: that.apiRoot + 'getProcess/' + String(window.btoa(process.id)).replace(/=+/, ''),
      headers: {
        'x-wps-auth': that.token,
      },
    })
      .done(function (response) {
        if ( response && response.data && response.ok ){
          if (!response.data.is_running) {
            process.stopPolling()
            process.queued_items = process.getProgressTotal()
            process.processed_items = process.getProgressTotal()

            setTimeout(function () {
              Object.assign(process, response.data)
            }, 3000)
    
          } else {
            Object.assign(process, response.data)
          }

          process.refreshBox()
        }
      })
      .fail(function (error) {
        process.stopPolling()
        that.processError(error)
      })
  },

  /**
   * Run the process
   */
  runProcess: function (process) {
    var that = this
    that.blockUI()

    var data = {
      id: process.id,
      limit: process.limit,
      order: process.order,
    }

    jQuery.ajax({
      method: 'POST',
      url: that.apiRoot + 'run',
      headers: {
        'x-wps-auth': that.token,
        'Content-Type': 'application/json',
      },
      dataType: 'json',
      data: JSON.stringify(data),
    })
      .then(function (response) {
        if (response && response.ok) {
          process.is_running = true
          process.is_stopping = false

          process.refreshBox()
          process.startPolling()
        } else {
          var message = response && response.data && response.data.message
            ? response.data.message
            : window.stateless_l10n.something_went_wrong

          that.addError(message)
        }
      })
      .fail(function (error) {
        process.stopPolling()
        that.processError(error)
      })
      .always(function () {
        that.unblockUI()
      })
  },

  /**
   * Stop the process
   */
  stopProcess: function (process) {
    var that = this
    process.is_stopping = true
    var data = {
      id: process.id,
    }

    jQuery.ajax({
      method: 'POST',
      url: that.apiRoot + 'stop',
      headers: {
        'x-wps-auth': that.token,
        'Content-Type': 'application/json',
      },
      dataType: 'json',
      data: JSON.stringify(data),
    })
      .done(function (response) {
        if (response && response.ok) {
        }
      })
      .fail(function (error) {
        that.processError(error)
      })
  }
}

wpStatelessProcessingApp.init()

/**
 * ProcessingClass
 *
 * @param {*} data
 */
function ProcessingClass(data, app) {
  this.id = ''
  this.total_items = 0
  this.queued_items = 0
  this.processed_items = 0
  this.is_running = false
  this.limit = 0
  this.limitEnabled = false
  this.order = 'desc'

  // Build an instance
  Object.assign(this, data)

  this.app = app
  this.interval = null
  this.htmlId = this.id.replace(/[^a-zA-Z0-9]/g, '') // remove double backslashes
  this.htmlBox = jQuery(`[data-id="${this.htmlId}"]`)
  
  /**
   * Start polling for changes
   */
  this.startPolling = function () {
    var that = this
    this.stopPolling()
    this.interval = setInterval(function () {
      that.app.refreshProcess(that)
    }, 5000)
  }

  /**
   * Stop polling for changes
   */
  this.stopPolling = function () {
    clearInterval(this.interval)
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
   * Calculate percentage
   */
  this.percentage = function (part, base) {
    return parseInt((100 / base) * part) + '%'
  }

  /**
   * Stop the process
   */
  this.refreshProgress = function () {
    this.htmlBox.find('.legend .total span').html( this.getProgressTotal() )
    this.htmlBox.find('.legend .queued span').html( this.getQueuedTotal() )
    this.htmlBox.find('.legend .processed span').html( this.processed_items )
    
    this.htmlBox.find('.bar.total').css({
      'background-color': this.getProgressTotal() == this.getProcessedTotal() ? '#02ae7a' : false
    })
    this.htmlBox.find('.bar.queued').css({
      width: this.percentage( this.getQueuedTotal(), this.getProgressTotal() ),
      'background-color': this.getProgressTotal() == this.getProcessedTotal() ? '#02ae7a' : false
    })
    this.htmlBox.find('.bar.processed').css({
      width: this.percentage( this.getProcessedTotal(), this.getQueuedTotal() ), 
      'background-color': this.getProgressTotal() == this.getProcessedTotal() ? '#02ae7a' : false
    })

    if (this.notices && this.notices.length) {
      this.htmlBox.find('.progress-notice').show()

      for (var i in this.notices) {
        this.htmlBox.find('.progress-notice').append('<p>' + this.notices[i] + '</p>')
      }
    }
  }

  /**
   * Check if Run button can be pressed
   */
  this.canRun = function () {
    return this.total_items > 0 && !this.is_running && this.app.canRun
  }

  /**
   * Check if Stop button can be pressed
   */
  this.canStop = function () {
    return this.is_running && !this.is_stopping
  }

  /**
   * Refresh buttons state
   */
  this.refreshButtons = function () {
    if ( this.canStop() ) {
      this.htmlBox.find('.actions .button-secondary').removeClass('disabled')
    } else {
      this.htmlBox.find('.actions .button-secondary').addClass('disabled')
    }

    if ( this.canRun() ) {
      this.htmlBox.find('.actions .button-primary').removeClass('disabled')
    } else {
      this.htmlBox.find('.actions .button-primary').addClass('disabled')
    }
  }

  /**
   * Refresh the progress data
   */
  this.refreshBox = function () {
    this.htmlBox.find('.inside ul span').html(this.total_items)

    if (this.is_running) {
      this.startPolling()

      this.htmlBox.find('.dashicons-update').css('display', 'inline-block')
      this.htmlBox.find('.progress').show()

      this.htmlBox.find('.limit_enabled').prop('disabled', true)
      this.htmlBox.find('.limit_field input').prop('disabled', true)
      this.htmlBox.find('.order_value').prop('disabled', true)

      this.refreshProgress()
    } else {
      this.stopPolling()

      this.htmlBox.find('.dashicons-update').css('display', 'none')
      this.htmlBox.find('.progress').hide()
      this.htmlBox.find('.progress-notice').hide()

      this.htmlBox.find('.limit_enabled').prop('disabled', false)
      this.htmlBox.find('.limit_field input').prop('disabled', !this.limitEnabled)
      this.htmlBox.find('.order_value').prop('disabled', false)
    }

    if (this.limitEnabled || this.limit > 0) {
      this.htmlBox.find('.limit_field input').css('visibility', 'visible')
    } else {
      this.htmlBox.find('.limit_field input').css('visibility', 'hidden')
    }

    this.refreshButtons()
    this.app.preventChanges()
  }

  /**
   * Enable limit handler
   */
  this.enableLimit = function (event) {
    this.limitEnabled = jQuery(event.target).is(':checked')

    this.limit = 0
    this.htmlBox.find('.limit_field input').val(0)

    this.refreshBox()
  }

  /**
   * Change limit handler
   */
  this.changeLimit = function (event) {
    this.limit = jQuery(event.target).val()
  }

  /**
   * Change sorting handler
   */
  this.changeOrder = function (event) {
    this.order = jQuery(event.target).val()
  }

  /**
   * Run the process
   */
  this.run = function () {
    if ( this.canRun() ) {
      this.app.runProcess(this)
    }
  }

  /**
   * Stop the process
   */
  this.stop = function () {
    if ( this.canStop() ) {
      this.app.stopProcess(this)
    }
  }

  /**
   * Bind event handlers
   */
  jQuery(document).on('change', `[data-id="${this.htmlId}"] .limit_enabled`, this.enableLimit.bind(this))
  this.htmlBox.find('.limit_field input').change( this.changeLimit.bind(this) )
  this.htmlBox.find('.order_value').change( this.changeOrder.bind(this) )
  this.htmlBox.find('.actions .button-primary').click( this.run.bind(this) )
  this.htmlBox.find('.actions .button-secondary').click( this.stop.bind(this) )
}
