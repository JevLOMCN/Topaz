const watchstar = require( 'mediawiki.page.watch.ajax' ).watchstar;

( function () {

	const WATCHED_ICON_CLASS = 'minerva-icon--unStar-progressive';
	const TEMP_WATCHED_ICON_CLASS = 'minerva-icon--halfStar-progressive';
	const UNWATCHED_ICON_CLASS = 'minerva-icon--star-base20';

	/**
	 * Tweaks the global watchstar handler in core to use the correct classes for Minerva.
	 *
	 * @param {jQuery} $icon
	 */
	function init( $icon ) {
		const $watchlink = $icon.find( 'a' );
		watchstar( $watchlink, mw.config.get( 'wgRelevantPageName' ), toggleClasses );
	}

	/**
	 * @param {jQuery} $link
	 * @param {boolean} isWatched
	 * @param {string} expiry
	 */
	function toggleClasses( $link, isWatched, expiry ) {
		const $icon = $link.find( '.minerva-icon' );
		$icon.removeClass( [ WATCHED_ICON_CLASS, UNWATCHED_ICON_CLASS, TEMP_WATCHED_ICON_CLASS ] )
			.addClass( function () {
				let classes = UNWATCHED_ICON_CLASS;
				if ( isWatched ) {
					if ( expiry !== null && expiry !== undefined && expiry !== 'infinity' ) {
						classes = TEMP_WATCHED_ICON_CLASS;
					} else {
						classes = WATCHED_ICON_CLASS;
					}
				}
				return classes;
			} );
	}

	module.exports = {
		init: init,
		test: {
			toggleClasses,
			TEMP_WATCHED_ICON_CLASS,
			WATCHED_ICON_CLASS,
			UNWATCHED_ICON_CLASS
		}
	};

}() );
