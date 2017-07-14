
jQuery(document).ready(function($){


  jQuery('.stless_setting_tab').on('click', function(e){

    e.preventDefault();

    var selector = $(this).attr('href');

    console.log(selector);

    $(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active');

    $(selector).addClass('active').siblings().removeClass('active');

  });

});