( function ( window, document, $ ) {
	'use strict';

	function switchTab() {
		$( '.rwmb-tab-nav' ).on( 'click', 'a', e => {
			e.preventDefault();
			showTab( e.target, e.target.parentNode.dataset.panel );
		} );
	}

	function showTab( el, tab ) {
		var $wrapper = $( el ).closest( '.rwmb-tabs' ),
			$tabs = $wrapper.find( '.rwmb-tab-nav > li' ),
			$panels = $wrapper.find( '.rwmb-tab-panel' );

		$tabs.removeClass( 'rwmb-tab-active' ).filter( '[data-panel="' + tab + '"]' ).addClass( 'rwmb-tab-active' );
		$panels.hide().filter( '.rwmb-tab-panel-' + tab ).show();

		// Refresh maps, make sure they're fully loaded, when it's in hidden div (tab).
		$( window ).trigger( 'rwmb_map_refresh' );
	}

	// Set active tab based on visible pane to better works with Meta Box Conditional Logic.
	function tweakForConditionalLogic() {
		if ( $( '.rwmb-tab-active' ).is( 'visible' ) ) {
			return;
		}

		// Find the active pane.
		var activePane = $( '.rwmb-tab-panel[style*="block"]' ).index();
		if ( activePane >= 0 ) {
			$( '.rwmb-tab-nav li' ).removeClass( 'rwmb-tab-active' ).eq( activePane ).addClass( 'rwmb-tab-active' );
		}
	}

	function showValidateErrorFields() {
		var inputSelectors = 'input[class*="rwmb-error"], textarea[class*="rwmb-error"], select[class*="rwmb-error"], button[class*="rwmb-error"]';
		$( document ).on( 'after_validate', 'form', e => {
			var $input = $( e.target ).find( inputSelectors );
			showTab( $input, $input.closest( '.rwmb-tab-panel' ).data( 'panel' ) );
		} );
	}

	$( function() {
		switchTab();
		tweakForConditionalLogic();
		showValidateErrorFields();

		$( '.rwmb-tab-active a' ).trigger( 'click' );

		// Remove wrapper. Use Meta Box's seamless style.
		$( '.rwmb-tabs-no-wrapper' ).closest( '.postbox' ).removeClass( 'rwmb-default' ).addClass( 'rwmb-seamless' );
	} );
} )( window, document, jQuery );
