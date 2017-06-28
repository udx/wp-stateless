(function ( $ ) {

	$.fn.wpStatelessComboBox = function(options) {
		options = options || {}

		var normalizeInput = function(value){
			value = value || this.val();

		}

		if(options == 'validate'){
			return this.each(function() {
				var _this = jQuery(this);
				var _input = _this.find('.name');
				_input.trigger('change');
			});
		}

		if(typeof options.get != 'undefined'){
			var _items = jQuery(this).parent().find('.wpStateLess-existing').find('ul li');
			console.log('options:', options)
			if(options.get == 'all'){
				var items = [];
				_items.each(function(index, item) {
					var id	 = jQuery(item).attr('data-id');
					var name = jQuery(item).attr('data-name');
					items.push({id: id, name: name});
				});
				return items;
			}
			else if(jQuery.isNumeric(options.get)){
				var _item	= _items.eq(options.get);
				console.log('_items:', _items)
				console.log('_item:', _item)

				if(_item.length){
					var id		= _item.attr('data-id');
					var name	= _item.attr('data-name');
					return {id: id, name: name};
				}
			}
			return;
		}

		if(typeof options.has != 'undefined'){
			var result = false;
			var _items = jQuery(this).parent().find('.wpStateLess-existing').find('ul li');
			_items.each(function(index, item) {
				if(jQuery(item).attr('data-id') == options.has){
					result	 = true;
					return false; // to break each
				}
			});
			return result;
		}

		return this.each(function() {
			var _this = jQuery(this);
			var custom_name = _this.find('.wpStateLess-create-new .custom-name');
			var predefined_name = _this.find('.wpStateLess-create-new .predefined-name');
			var _id = _this.find('.id');
			var _input = _this.find('.name');
			var existing = _this.find('.wpStateLess-existing');
			var dropDown = jQuery('.wpStateLess-input-dropdown', _this);
			var list = existing.find('ul');

			jQuery('h5', existing).hide();
			list.children().remove();

			//_id.val(custom_name.attr('data-id'));
			//_input.val(custom_name.attr('data-name'));

			if(options.items && options.items.length > 0){
				jQuery('h5', existing).show();
				jQuery.each(options.items, function(index, item){
					var selected = "";
					if(typeof options.selected != 'undefined' && options.selected == index){
						selected = " class= 'active' ";
						_id.val(item.id);
						_input.val(item.name + " ("  + item.id + ")");
					}
					var text = item.name;
					if(typeof item.id != 'undefined')
						text += " ("  + item.id + ")";
					item.id = item.id || item.name;
					list.append("<li " + selected + "data-id='" + item.id + "' data-name='" + item.name + "'>" + text + "</li>");
				});
				_input.trigger('change');
			}

			if(_this.data('comboboxLoaded') == true)
				return;

			_input.focus(function(){
				dropDown.addClass('active');
			});
			_input.focusout(function(){
				setTimeout(function(){
					dropDown.removeClass('active');
				}, 200);
			});

			_input.on( 'change keyup', function(event){
				event.stopPropagation();
				event.stopImmediatePropagation();
				var response = {};

				var resp = jQuery(this).wppStatelessValidate({}, response);

				dropDown.removeClass('active');

				_this.removeClass('has-error').find('.error').html("");

				if(response.id == 'localhost' || response.pName == 'localhost'){
					response.id = '';
					_this.addClass('has-error').find('.error').html("localhost is not acceptable.");
				}
				else if(!response.success){
					response.id = '';
					_this.addClass('has-error').find('.error').html(response.message);
					custom_name.hide();
				}else if(response.existing){
					custom_name.show();
					if(custom_name.attr('data-name') == predefined_name.attr('data-name')){
						custom_name.hide();
					}
				}else{
					var name = response.pName;
					var data_predefined_name = predefined_name.attr('data-name');
					var project_derived_name = dropDown.find('.project-derived-name').attr('data-name');
					if(name == data_predefined_name || name == project_derived_name){
						//custom_name.hide();
					}
					else{
						if(response.id)
							name += " (" + response.id + ")";
						custom_name.html( name ).addClass('active').show();
						custom_name.attr('data-id', response.id).attr('data-name', response.pName);
					}
				}

				_id.val(response.id);
				
			});

			_this.on( 'click', '.wpStateLess-input-dropdown li', function(){
				var id = jQuery(this).attr('data-id');
				var name = jQuery(this).attr('data-name');
				dropDown.find('li').removeClass('active');
				jQuery(this).addClass('active');
				if(_this.find('.id').length > 0){
					_this.find('.id').val(id);
					_this.find('.name').val(name + " (" + id + ")");
				}
				else{
					_this.find('.name').val(name);
				}
				_this.removeClass('has-error').find('.error').html("");
				_this.trigger('change');
				_input.trigger('change');
				//custom_name.removeClass('active');
			});
			_this.data('comboboxLoaded', true);
		});
    };
 
}( jQuery ));