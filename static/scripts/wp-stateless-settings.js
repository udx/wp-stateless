jQuery(document).ready(function ($) {
  var smSelectTab = function (tab) {
    var $tab = $('.nav-tab-wrapper').find("[href='" + tab + "']")
    if ($tab.size() != 0) {
      $tab.addClass('nav-tab-active').siblings().removeClass('nav-tab-active')
      $(tab).addClass('active').siblings().removeClass('active')
    }
  }

  var tab = window.location.hash
  smSelectTab(tab)

  $('.stless_setting_tab').on('click', function (e) {
    e.preventDefault()

    var tab = $(this).attr('href')
    smSelectTab(tab)
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
})
