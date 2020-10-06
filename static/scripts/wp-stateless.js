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
var wpStatelessApp = angular
  .module('wpStatelessApp', ['ngSanitize'])

  // Controllers
  .controller('wpStatelessSettings', function ($scope, $filter) {
    $scope.backup = {}
    $scope.sm = wp_stateless_settings || {}
    $scope.sm.readonly = $scope.sm.readonly || {}

    if ($scope.sm.network_admin) {
      $scope.sm.hashify_file_name = 'true'
      $scope.sm.readonly.hashify_file_name = true
    }

    $scope.$watch('sm.mode', function (value) {
      if (
        (value == 'stateless' || value == 'ephemeral') &&
        $scope.sm.readonly.hashify_file_name != 'constant'
      ) {
        $scope.backup.hashify_file_name = $scope.sm.hashify_file_name
        $scope.sm.hashify_file_name = 'true'
        // $scope.apply();
      } else {
        if ($scope.backup.hashify_file_name) {
          $scope.sm.hashify_file_name = $scope.backup.hashify_file_name
          // $scope.apply();
        }
      }
    })

    $scope.$watch('sm.bucket_folder_type', function (value) {
      if (value == 'single-site') {
        replace_wildcard_to_the_end(['/', '%date_year/date_month%', '/'], true)
        $wildcards_select
          .val(['/', '%date_year/date_month%', '/'])
          .trigger('change')
      } else if (value == 'multi-site') {
        //changins wildcards position
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
      } else if (value == '' && $scope.sm.network_admin) {
        $wildcards_select.val(null).trigger('change')
      }
      prepare_preview_url()
    })

    $scope.$watch('sm.root_dir', function (value) {
      if (value == '%date_year/date_month%') {
        $scope.sm.bucket_folder_type = 'single-site'
      } else if (value == 'sites/%site_id%/%date_year/date_month%') {
        $scope.sm.bucket_folder_type = 'multi-site'
      } else if (value == '' && $scope.sm.network_admin) {
        $scope.sm.bucket_folder_type = ''
      } else {
        $scope.sm.bucket_folder_type = 'custom'
      }

      setTimeout(function () {
        jQuery('#permalink_structure').trigger('change')
      }, 1)
    })

    var readonlyTag = $scope.sm.readonly.root_dir || false
    if (readonlyTag) {
      jQuery('.available-structure-tags .button')
        .off('click')
        .css('opacity', '.5')
    }
    $scope.tagClicked = function () {
      if (readonlyTag) return false
      $scope.sm.bucket_folder_type = 'custom'
      setTimeout(function () {
        jQuery('#permalink_structure').trigger('change')
      }, 1)
    }

    $scope.sm.showNotice = function (option) {
      if ($scope.sm.readonly && $scope.sm.readonly[option]) {
        var slug = $scope.sm.readonly[option]
        return $scope.sm.strings[slug]
      }
    }

    $scope.sm.generatePreviewUrl = function () {
      $scope.sm.is_custom_domain = false
      var host = 'https://storage.googleapis.com/'
      var hash =
        $scope.sm.hashify_file_name == 'true'
          ? Date.now().toString(36) + '-'
          : ''
      var is_ssl = $scope.sm.custom_domain.indexOf('https://')
      var custom_domain = $scope.sm.custom_domain.toString()
      var root_dir = $scope.sm.root_dir ? $scope.sm.root_dir : ''

      jQuery.each($scope.sm.wildcards, function (index, item) {
        var reg = new RegExp(index, 'g')
        root_dir = root_dir.replace(reg, item[0])
      })
      let tags = [
        '%date_year%',
        '%date_month%',
        '%site_id%',
        '%site_url%',
        '%site_url_host%',
        '%site_url_path%',
      ]
      let value_splitted = root_dir.split('/')
      for (let i = 0; i < value_splitted.length; i++) {
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
      host += $scope.sm.bucket ? $scope.sm.bucket : '{bucket-name}'

      if (
        custom_domain !== 'storage.googleapis.com' &&
        $scope.sm.bucket &&
        custom_domain &&
        (is_ssl === 0 || custom_domain == $scope.sm.bucket)
      ) {
        $scope.sm.is_custom_domain = true
        $scope.sm.is_ssl = is_ssl === 0 ? true : false
        host = is_ssl === 0 ? 'https://' : 'http://' // bucketname will be host
        host += custom_domain
      }

      $scope.sm.preview_url =
        host + '/' + root_dir + hash + 'your-image-name.jpeg'
    }

    $scope.sm.generatePreviewUrl()
  })
  .controller('wpStatelessCompatibility', function ($scope, $filter) {
    $scope.modules = wp_stateless_compatibility || {}
  })
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
