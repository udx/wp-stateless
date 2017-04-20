(function ( $ ) {

	$.fn.wpStatelessComboBox = function(options) {
		options = options || {}

		var normalizeInput = function(value){
			value = value || this.val();

		}
		return this.each(function() {
			var _this = jQuery(this);
			var _new = _this.find('.wpStateLess-create-new span');
			var _id = _this.find('.id');
			var _input = _this.find('.name');
			var existing = _this.find('.wpStateLess-existing');
			var currentAccount = _this.find('.wpStateLess-current-account');
			var dropDown = jQuery('.wpStateLess-input-dropdown', _this);
			var list = existing.find('ul');

			jQuery('h5', existing).hide();
			currentAccount.hide();
			list.children().remove();

			if(options.items && options.items.length > 0){
				jQuery('h5', existing).show();
				jQuery.each(options.items, function(index, item){
					var selected = "";
					if(typeof options.selected != 'undefined' && options.selected == index){
						selected = " class= 'active' ";
						_id.val(item.id);
						_input.val(item.name + " ("  + item.id + ")");
					}
					list.append("<li " + selected + "data-id='" + item.id + "' data-name='" + item.name + "'>" + item.name + " ("  + item.id + ")</li>");
				});
			}

			if(_this.data('comboboxLoaded') == true)
				return;

			_input.focus(function(){
				dropDown.addClass('active');
			});
			_input.focusout(function(){
				setTimeout(function(){
					dropDown.removeClass('active');
				}, 100);
			});

			_input.on( 'change keyup', function(event){
				event.stopPropagation();
				event.stopImmediatePropagation();
				var response = {};

				var resp = jQuery(this).wppStatelessValidate({}, response);

				dropDown.removeClass('active');

				if(response.id == 'localhost'){
					_this.addClass('has-error').find('.error').html("localhost is not acceptable.");
				}
				if(!response.success){
					_this.addClass('has-error').find('.error').html(response.message);
					_new.hide();
				}else{
					_this.removeClass('has-error').find('.error');
					_new.html( response.pName + " (" + response.id + ")" ).show();
				}

				_new.parent().attr('data-id', response.id).attr('data-name', response.pName);
				_id.val(response.id);
				
			});

			_this.on( 'click', '.wpStateLess-existing li, .wpStateLess-create-new', function(){
				var id = jQuery(this).data('id');
				var name = jQuery(this).data('name');
				list.children().removeClass('active');
				jQuery(this).addClass('active');
				if(_this.find('.id').length > 0){
					_this.find('.id').val(id);
					_this.find('.name').val(name);
				}
				else{
					_this.find('.name').val(id);
				}
				_this.trigger('change');
				_new.parent().removeClass('active');
			});
			_this.data('comboboxLoaded', true);
		});
    };
 
}( jQuery ));