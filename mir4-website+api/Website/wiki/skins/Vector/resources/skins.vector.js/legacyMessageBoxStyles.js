/**
 * Checks if the page is using legacy message box styles
 */
module.exports = function () {
	mw.requestIdleCallback( () => {
		if ( document.querySelectorAll( '.mw-message-box:not(.cdx-message)' ).length ) {
			mw.log.warn( `[T360668] A message box on this page is not using standard markup.
See https://doc.wikimedia.org/codex/latest/components/demos/message.html#markup-structure for more information.` );
		}
	} );
};
