wpStatelessBatch = {
  token: window.wp_stateless_batch.REST_API_TOKEN,
  apiRoot: window.wp_stateless_batch.api_root + 'batch/',
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

  updateState: function(state, action = false) {
    var detail = {
      state,
      action,
    }

    var event = new CustomEvent('wp-stateless-batch-state-updated', { detail })
    
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
        that.updateState(response.data, true)
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
  function blockUI() {
    $('#migration-action .button').addClass('disabled')
  }

  function unblockUI() {
    $('#migration-action .button').removeClass('disabled')
  }

  // Process state
  document.addEventListener('wp-stateless-batch-state-updated', function (e) {
    var state = e.detail.state
    // 'action' indicates that the action was just started. 
    // If the migration was quick - the state is empty and we need to update the state
    // Otherwise the state will contain the current running migration ID
    var forceRefresh = e.detail.action && !state.hasOwnProperty('id')

    if ( (!state.is_migration && !state.hasOwnProperty('migrations')) || forceRefresh ) {
      // If have migrations running on the frontend - we should finalize it
      if ( $('#migration-action.can-pause').length || $('#migration-action.can-resume').length || forceRefresh ) {
        wpStatelessBatch.getState({
          force_migrations: true,
        })
      }

      return
    }

    if ( !state.hasOwnProperty('migrations') ) {
      return
    }

    // Check if we need to wait for other migrations to start
    // If there are more migrations - wait 5 seconds and check again
    if ( !state.is_running && !state.is_paused ) {
      var migrationsCanStart = Object.values(state.migrations).some(function(migration) { return migration.can_start })
      if ( migrationsCanStart ) {
        setTimeout(function() {
          wpStatelessBatch.getState()
        }, 5000)
  
        return
      } else {
        $('.metabox-holder.migrations-wrap').remove()
      }
    }

    var migrationElement = $('#migration-action')

    if ( state.migrations &&  state.hasOwnProperty('is_migration') && state.hasOwnProperty('id') ) {
      var migration = state.migrations[ state.id ];
      migrationElement.attr('data-id', state.id)

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

      if ( migration.hasOwnProperty('ui_message') && migration.ui_message ) {
        migrationElement.find('.description').html(migration.ui_message)
      } else {
        migrationElement.find('.description').text('')
      }
    }

    // Display progress
    if ( state.is_running || state.is_paused ) {
      if ( state.hasOwnProperty('total') && state.hasOwnProperty('completed') ) {
        var migrationElement = $('#migration-action')
  
        var percent = state.total > 0 ? Math.floor( (state.completed / state.total) * 100 ) + '%' : ''
        migrationElement.find('.progress .percent').html(percent)
        migrationElement.find('.progress .bar').css('width', percent)
      }
    }
  })

  // Process notifications
  document.addEventListener('wp-stateless-batch-state-updated', function (e) {
    var state = e.detail.state

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
      $('body').css('overflow', 'hidden')

      $('.ui-dialog-buttonset').find('.ui-button').attr('class', 'button')
      $('.ui-dialog-buttonset').find('.button').last().addClass('button-primary')

      $('.ui-dialog').removeClass('ui-corner-all').addClass('stateless-migration-confirm');
      $('.ui-dialog-titlebar').removeClass('ui-corner-all');

      // backdrop
      $('.ui-widget-overlay').attr('class', 'media-modal-backdrop');
    },
    close: function(event, ui) {
      unblockUI()
      $('body').css('overflow', 'auto')
      $('#migration-action').removeClass('processing-action');
      $('#migration-action').find('.button.start').removeClass('disabled');
      $('#migration-action').find('.description').text( '' );
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
  $('#migration-action .button').click(function (e) {
    e.preventDefault()

    if ( $(e.target).hasClass('disabled') ) {
      return;
    }
    
    blockUI()
    
    var id = $('#migration-action').data('id')
    var action = $(e.target).data('action')

    if ( !id || !action ) {
      return
    }

    if ( action === 'start' ) {
      $( '#stateless-migration-confirm' ).attr('data-id', id)

      $('#stateless-migration-confirm').closest('.ui-dialog').find('.ui-dialog-buttonset .ui-button').first().click(function(e) {
        e.preventDefault()

        $( '#stateless-migration-confirm' ).dialog('close')
        var id = $( '#stateless-migration-confirm' ).attr('data-id')
        var migrationElement = $('#migration-action')

        migrationElement.addClass('processing-action');
        migrationElement.find('.button.start').addClass('disabled');
        migrationElement.find('.description').text( stateless_l10n.starting );

        wpStatelessBatch.processAction(action, {
          id,
          is_migration: true,
          email: $('input[name="email-notification"]:checked').val(),
          queue: migrationElement.data('queue'),
        }, function() {
          unblockUI();
          migrationElement.removeClass('processing-action');
          migrationElement.find('.button.start').removeClass('disabled');
          migrationElement.find('.description').text( '' );
        })
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
