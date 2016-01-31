/**
 *
 *
 */

jQuery(document).ready(function($){
	$('.key_type .radio').on('click', function(){
		$this = $(this);
		$siblings = $this.siblings();
		$this.parent().siblings().find('.field').hide();
		$siblings.filter('.field').show().focus();
	});
	$('.key_type label').on('click', function(){
		$this = $(this);
		$siblings = $this.siblings();
		$this.parent().siblings().find('.field').hide();
		$siblings.filter('.field').show();
		$siblings.filter('.radio').prop('checked', 'checked');
	});
	$('.key_type .radio:checked').parent().siblings().find('.field').hide();
});