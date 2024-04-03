wpStatelessBatch = {
  token: window.wp_stateless_batch.REST_API_TOKEN,
  apiRoot: window.wpApiSettings.root + 'wp-stateless/v1/batch/',
  interval: null,

  startPolling: function () {
    var that = this
    this.stopPolling()
    this.interval = setInterval(function () {
      that.getState()
    }, 1000)
  },

  stopPolling: function () {
    clearInterval(this.interval)
  },

  updateState: function(state) {
    var event = new CustomEvent('wp-stateless-batch-state-updated', { detail: state })
    
    document.dispatchEvent(event)

    if (state.is_running) {
      this.startPolling()
    } else {
      this.stopPolling()
    }
  },

  processFail: function(error) {
    console.log(error)

    var event = new CustomEvent('wp-stateless-batch-error', { detail: error })
    
    document.dispatchEvent(event)
  },

  processAction: function(action, payload, callback = null) {
    var that = this

    var data = {
      action,
      ...payload,
    }

    jQuery.ajax({
      method: 'POST',
      url: that.apiRoot + 'action',
      headers: {
        'x-wps-auth': that.token,
        'Content-Type': 'application/json',
      },
      dataType: 'json',
      data: JSON.stringify(data),
    })
      .then(function (response) {
        that.updateState(response.data)
      })
      .fail(function (error) {
        that.processFail(error)
      })
      .always(function () {
        if (callback) {
          callback()
        }
      })
  },

  getState: function(data = {}) {
    var that = this

    jQuery.ajax({
      method: 'GET',
      url: that.apiRoot + 'state',
      data,
      headers: {
        'x-wps-auth': that.token,
        'Content-Type': 'application/json',
      },
    })
      .then(function (response) {
        that.updateState(response.data)
      })
      .fail(function (error) {
        that.processFail(error)
      })
  },

  init: function() {
    if ( window.wp_stateless_batch.is_running ) {
      this.startPolling()
    }

    // Check if we have a batch running on the backend
    jQuery(document).on('heartbeat-tick', function (e, data) {
      if ( data.hasOwnProperty('stateless-batch-running') && data['stateless-batch-running'] ) {
        this.startPolling()
      }
    }.bind(this))
  }
}

wpStatelessBatch.init()

/**
 * Manage data updates
 */
function wpMigrations($) {
  function getId(element) {
    return element.closest('.migration').data('id')
  }

  function blockUI() {
    $('#stless_status_tab .migration .button').addClass('disabled')
  }

  function unblockUI() {
    $('#stless_status_tab .migration .button').removeClass('disabled')
  }

  // Process state
  document.addEventListener('wp-stateless-batch-state-updated', function (e) {
    var state = e.detail

    if ( !state.is_migration && !state.hasOwnProperty('migrations') ) {
      // If have migrations running on the frontend - we should finalize it
      if ( $('#stless_status_tab .migration.can-pause').length || $('#stless_status_tab .migration.can-resume').length ) {
        wpStatelessBatch.getState({
          force_migrations: true,
        })
      }

      return
    }

    if ( !state.hasOwnProperty('migrations') ) {
      return
    }

    var notify = state.hasOwnProperty('migrations_notify') ? state.migrations_notify : false

    if ( state.is_running ) {
      $('#stateless-notice-migrations-required').addClass('hidden')
      $('#stateless-notice-migrations-finished').addClass('hidden')
      $('#stateless-notice-migrations-running').removeClass('hidden')
    } else {
      $('#stateless-notice-migrations-running').addClass('hidden')
      $('#stateless-notice-migrations-required').addClass('hidden')
      $('#stateless-notice-migrations-finished').addClass('hidden')

      if ( notify === 'require' ) {
        $('#stateless-notice-migrations-required').removeClass('hidden')
      } else if ( notify === 'finished' ) {
        $('#stateless-notice-migrations-finished').removeClass('hidden')
      }
    }

    if ( state.migrations ) {
      for (var key of Object.keys(state.migrations)) {
        var migration = state.migrations[key]
        var migrationElement = $('#stless_status_tab .migration[data-id="' + key + '"]')

        if ( migration.can_start ) {
          migrationElement.addClass('can-start')
        } else {
          migrationElement.removeClass('can-start')
        }

        if ( migration.can_pause ) {
          migrationElement.addClass('can-pause')
        } else {
          migrationElement.removeClass('can-pause')
        }

        if ( migration.can_resume ) {
          migrationElement.addClass('can-resume')
        } else {
          migrationElement.removeClass('can-resume')
        }

        if (migration.message) {
          migrationElement.find('.description').html(migration.message)
        }
      }
    }

    // Display progress
    if ( state.is_running || state.is_paused ) {
      if ( state.hasOwnProperty('total') && state.hasOwnProperty('completed') ) {
        var migrationElement = $('#stless_status_tab .migration[data-id="' + state.id + '"]')
  
        var percent = state.total > 0 ? Math.floor( (state.completed / state.total) * 100 ) + '%' : ''
        migrationElement.find('.progress .percent').html(percent)
        migrationElement.find('.progress .bar').css('width', percent)
      }
    }
  })

  // Migration confirmation dialog
  $( "#stateless-migration-confirm" ).dialog({
    resizable: false,
    height: "auto",
    width: 500,
    modal: true,
    draggable: false,
    autoOpen: false,
    position: { my: "center", at: "center", of: window },
    open: function(event, ui) {
      $(this).closest('.ui-dialog').find('.ui-button').addClass('button')
      $(this).closest('.ui-dialog').find('.ui-button').last().addClass('button-primary')
      $('body').css('overflow', 'hidden')
    },
    close: function(event, ui) {
      unblockUI()
      $('body').css('overflow', 'auto')
    },
    buttons: [
      {
        text: stateless_l10n.start_optimization,
        click: function() {},
      },
      {
        text: stateless_l10n.cancel,
        click: function() {
          $( this ).dialog( 'close' )
        },
      },
    ],
  })

  // Migration actions
  $('#stless_status_tab .migration .button').click(function (e) {
    e.preventDefault()

    if ( $(e.target).hasClass('disabled') ) {
      return;
    }
    
    blockUI()
    
    var id = getId( $(e.target) )
    var action = $(e.target).data('action')

    if ( !id || !action ) {
      return
    }

    if ( action === 'start' ) {
      // Title
      // var migrationElement = $('#stless_status_tab .migration[data-id="' + id + '"]')
      // var title = migrationElement.find('.title strong').text()

      // $( '#stateless-migration-confirm' ).dialog('option', 'title', title)
      // $( '#stateless-migration-confirm' ).find('strong').text(title)

      $('#stateless-migration-confirm').closest('.ui-dialog').find('.ui-dialog-buttonset .ui-button').first().click(function(e) {
        e.preventDefault()

        $( '#stateless-migration-confirm' ).dialog('close')

        wpStatelessBatch.processAction(action, {
          id,
          is_migration: true,
          email: $('input[name="email-notification"]:checked').val(),
        }, unblockUI)
      })

      $( "#stateless-migration-confirm" ).dialog('open')
      return
    } else {
      wpStatelessBatch.processAction(action, {
        id,
        is_migration: true,
      }, unblockUI)
    }
  }.bind(this))
}

wpMigrations(jQuery)
