const SEARCH_CLASS = 'search-enabled';

module.exports = function () {
	// eslint-disable-next-line no-jquery/no-global-selector
	$( '#searchIcon' ).on( 'click', function () {
		// eslint-disable-next-line no-jquery/no-global-selector
		const $input = $( '#searchInput' );
		const $body = $( document.body );

		// eslint-disable-next-line no-jquery/no-sizzle
		if ( !$input.is( ':visible' ) ) {
			$body.addClass( SEARCH_CLASS );
			$input.trigger( 'focus' )
				.one( 'blur', function () {
					$body.removeClass( SEARCH_CLASS );
				} );
			return false;
		}
	} );
};
