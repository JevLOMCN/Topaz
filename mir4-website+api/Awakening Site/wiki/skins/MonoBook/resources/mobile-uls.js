/* eslint-disable no-jquery/no-global-selector */
module.exports = function () {
	mw.loader.using( [ 'ext.uls.interface' ] ).then( function () {
		var mobileMediaQuery = window.matchMedia( 'screen and (max-width: 550px)' ),
			$ULSTrigger = $( '#pt-uls' ),
			ULSMoved = false;

		function moveULS() {
			if ( $ULSTrigger.length ) {
				if ( !ULSMoved && mobileMediaQuery.matches ) {
					$ULSTrigger.insertBefore( $( '#pt-preferences' ) );

					ULSMoved = true;
				} else if ( ULSMoved && !mobileMediaQuery.matches ) {
					$ULSTrigger.prepend( $( '#p-preferences' ) );

					ULSMoved = false;
				}
			}
		}

		$( window ).on( 'resize', moveULS );
		moveULS();
	} );
};
