( function () {
	'use strict';

	function invertSelections() {
		var form = document.getElementById( 'choose_pages' ),
			numElements = form.elements.length,
			i,
			curElement;

		for ( i = 0; i < numElements; i++ ) {
			curElement = form.elements[ i ];

			if ( curElement.type === 'checkbox' && curElement.id !== 'create-redirect' &&
				curElement.id !== 'watch-pages' && curElement.id !== 'botEdit' ) {
				curElement.checked = !curElement.checked;
			}
		}
	}

	$( function () {
		var $checkboxes = $( '#powersearch input[id^="mw-search-ns"]' );

		$( '.ext-replacetext-invert' ).on( 'click', invertSelections );

		// Attach handler for check all/none buttons
		$( '#mw-search-toggleall' ).on( 'click', function () {
			$checkboxes.prop( 'checked', true );
		} );
		$( '#mw-search-togglenone' ).on( 'click', function () {
			$checkboxes.prop( 'checked', false );
		} );
	} );
}() );
