
jQuery(document).ready(function($){


  jQuery('.stless_setting_tab').on('click', function(e){

    e.preventDefault();

    var selector = $(this).attr('href');

    console.log(selector);

    $(this).addClass('active').siblings().removeClass('active');

    $(selector).addClass('active').siblings().removeClass('active');

  });

});