(function( $ ) {
 
    $.fn.wppStatelessValidate = function(options, response) {

        var input = jQuery(this);

        if (typeof input.data('settings') == 'undefined'){
            var _settings = $.extend({
                name: {},
                id: {},
            }, options );

            input.data('settings', _settings);
        }

        var pName = input.val();
        var settings = _settings || input.data('settings');
        if(pName){
            pName = pName.trim().replace(/-$/, '');
        }
        response = response || {};

        response.id      = '';
        response.pName   = pName;
        response.success = true;
        response.existing = false;
        response.message = '';

        var isExisting = /(.+)\((.+)\)/.exec(pName);

        if(isExisting != null && isExisting.length){
            response.id     = isExisting[2].trim();
            response.pName  = isExisting[1].trim();
            pName = response.pName;
        }

        if(input.parent().wpStatelessComboBox({has: response.id || response.pName})){
            response.existing = true;
            return response;
        }

        jQuery.each(settings.name, function(index, item) {
            if(!item.regex.test(pName)){
                response.success = false;
                response.message += item.errorMessage + '<br />';
                if(typeof item.break != 'undefined' && item.break == true)
                    return false;
            }
        });

        if(response.success == false || typeof settings.id == 'undefined' || typeof settings.id.regex == 'undefined'){
            return response;
        }

        var _id = settings.id.regex.exec(pName.toLowerCase());
        if(_id && typeof _id[0] != 'undefined'){
            response.id = _id[0];
        }

        jQuery.each(settings.id.replace, function(index, array) {
            var search  = array[1];
            var replace = array[0];
            response.id = response.id.replace(search, replace);
        });

        response.id = response.id.slice(0, 23).replace(/-$/, '') + '-' + Math.floor((Math.random() * 100000) + 100000);

        return response;

    };
 
}( jQuery ));
