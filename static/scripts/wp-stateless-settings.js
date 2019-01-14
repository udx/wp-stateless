jQuery(document).ready(function($){

  var smSelectTab = function(tab){
    var $tab = $(".nav-tab-wrapper").find("[href='" + tab + "']");
    if($tab.size() != 0){
      $tab.addClass("nav-tab-active").siblings().removeClass("nav-tab-active");
      $(tab).addClass("active").siblings().removeClass("active");
    }
  };

  var tab = window.location.hash;
  smSelectTab(tab);


  jQuery('.stless_setting_tab').on('click', function(e){
    e.preventDefault();

    var tab = $(this).attr('href');
    smSelectTab(tab);
    
    return false;
  });

  jQuery(".stateless-admin-notice [data-key^='button_secondary_']").on('click', function(){
    var key = $(this).attr('data-key');
    key = key.replace('button_secondary_', '');
    $("#" + key + " option[value=" + key +"]").attr('selected', 'selected');
    $("#" + key).val('true');
  });

});