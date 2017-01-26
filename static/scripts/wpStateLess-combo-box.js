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
			var list = existing.find('ul');

			jQuery('h5', existing).hide();
			currentAccount.hide();

			if(options.items && options.items.length > 0){
				list.children().remove();
				jQuery('h5', existing).show();
				jQuery.each(options.items, function(index, item){
					list.append("<li data-id='" + item.id + "'>" + item.name + "</li>");
				});
			}

			if(_this.data('comboboxLoaded') == true)
				return;

			_input.focus(function(){
				jQuery('.wpStateLess-input-dropdown', _this).addClass('active');
			});
			_input.focusout(function(){
				setTimeout(function(){
					jQuery('.wpStateLess-input-dropdown', _this).removeClass('active');
				}, 100);
			});

			_input.on( 'change keyup', function(event){
				event.stopPropagation();
				event.stopImmediatePropagation();
				var value = jQuery(this).val().replace(' ', '-').replace(/^-+|-+$/,'');
				var regex = /(^[a-zA-Z][\w-]{3,28}[\w]$)/;
				var match = regex.exec(value);
				if(!match || !match.length){
					_this.find('.error').show().html("Name can contain lowercase alphanumeric characters and hyphens. It must start with a letter. Trailing hyphens are prohibited.");
				}else{
					_new.parent().show();
					_this.find('.error').hide();
					_new.html( value.toLowerCase() );
					_id.val(value.toLowerCase());
					console.log(value.toLowerCase());
				}
				
			});

			_this.on( 'click', '.wpStateLess-existing li', function(){
				var id = jQuery(this).data('id');
				var name = jQuery(this).text();
				if(_this.find('.id').length > 0){
					_this.find('.id').val(id);
					_this.find('.name').val(name);
				}
				else{
					_this.find('.name').val(id);
				}
				_this.trigger('change');
				_new.parent().hide();
			});
			_this.data('comboboxLoaded', true);
		});
    };
 
}( jQuery ));