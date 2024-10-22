jQuery(document).ready(function ($) {
  jQuery('.nav-tab-wrapper a').on('click', function (e) {
    e.preventDefault()

    var tab = jQuery(this).attr('href')
    
    if ( tab.indexOf('#') === 0 ) {
      jQuery(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active')
      jQuery(`.stless_settings ${tab}`).addClass('active').siblings().removeClass('active')

      var url = new URL(window.location.href)
      url.searchParams.set('tab', tab.replace('#', ''))
  
      window.history.replaceState(null, '', url.toString())
    }
  })
  
  $(document).on('click', '.pointer', function (e) {
    e.stopPropagation()
    var pointer = $(this)
    pointer
      .pointer({
        content:
          '<h3>' +
          pointer.data('title') +
          '</h3><p>' +
          pointer.data('text') +
          '</p>',
        position: pointer.data('position'),
      })
      .pointer('open')
  })

  $(document).on('click', function () {
    $('.wp-pointer').hide()
  })

  $(document).on('click', '.wp-pointer', function (e) {
    e.stopPropagation()
  })

  $(document).on('click', '.stateless-info-button', function (e) {
    e.stopPropagation()
    e.preventDefault()

    var opened = $(this).closest('.stateless-info-heading').hasClass('open')
    var id = $(this).data('section')
    
    if (opened) {
      $(this).closest('.stateless-info-heading').removeClass('open')
      $('#' + id).addClass('hidden')
    } else {
      $(this).closest('.stateless-info-heading').addClass('open')
      $('#' + id).removeClass('hidden')
    }
  })

  // Copy Status Info to clipboard
  var clipboard = new ClipboardJS('.stateless-info-heading .copy-button')

  clipboard.on('success', function(e) {
    $('.stateless-info-copy-success').show();

    setTimeout(function() {
      $('.stateless-info-copy-success').fadeOut(500);
    }, 5000);
  })

  // Check if API and AJAX is available
  function setServiceStatus(id, status) {
    var data = $('.stateless-info-heading .button.copy-button').attr('data-clipboard-text');
    $('.stateless-info-heading .button.copy-button').attr( 'data-clipboard-text', data.replace('%' + id + '%', status));

    $(`#stateless-info-block-stateless .${id} .value`).text(status);
  }

  $.ajax({
    method: 'GET',
    url: window.wp_stateless_configs.api_root + 'status',
  })
    .then(function() {
      setServiceStatus( 'api_status', window.wp_stateless_configs.text_ok );
    })
    .fail(function() {
      setServiceStatus( 'api_status', window.wp_stateless_configs.text_fail );
    })

  $.ajax({
    method: 'POST',
    url: window.wp_stateless_configs.ajaxurl,
    data: {
      action: 'stateless_check_ajax',
      _ajax_nonce: window.wp_stateless_configs.stateless_check_ajax_nonce,
    }
  })
    .then(function() {
      setServiceStatus( 'ajax_status', window.wp_stateless_configs.text_ok );
    })
    .fail(function() {
      setServiceStatus( 'ajax_status', window.wp_stateless_configs.text_fail );
    })

})
