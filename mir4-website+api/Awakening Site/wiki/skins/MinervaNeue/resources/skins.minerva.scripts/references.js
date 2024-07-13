const drawers = require( './drawers.js' );

module.exports = function () {

	const mobile = require( 'mobile.startup' );
	const references = mobile.references;
	const currentPage = mobile.currentPage();
	const currentPageHTMLParser = mobile.currentPageHTMLParser();
	const ReferencesHtmlScraperGateway = mobile.references.ReferencesHtmlScraperGateway;
	const gateway = new ReferencesHtmlScraperGateway( new mw.Api() );

	/**
	 * Event handler to show reference when a reference link is clicked
	 *
	 * @ignore
	 * @param {jQuery.Event} ev Click event of the reference element
	 */
	function showReference( ev ) {
		const $dest = $( ev.currentTarget );
		let href = $dest.attr( 'href' );

		ev.preventDefault();

		// If necessary strip the URL portion of the href so we are left with the
		// fragment
		const i = href.indexOf( '#' );
		if ( i > 0 ) {
			href = href.slice( i );
		}

		references.showReference( href, currentPage, $dest.text(),
			currentPageHTMLParser, gateway, {
				onShow: function () {
					drawers.lockScroll();
				},
				onShowNestedReference: true,
				onBeforeHide: drawers.discardDrawer
			},
			function ( oldDrawer, newDrawer ) {
				oldDrawer.hide();
				drawers.displayDrawer( newDrawer, {} );
			}
		).then( function ( drawer ) {
			drawers.displayDrawer( drawer, {} );
		} );
	}

	/**
	 * Event handler to show reference when a reference link is clicked.
	 * Delegates to `showReference` once the references drawer is ready.
	 *
	 * @ignore
	 * @param {jQuery.Event} ev Click event of the reference element
	 */
	function onClickReference( ev ) {
		showReference( ev );
	}

	function init() {
		// Make references clickable and show a drawer when clicked on.
		$( document ).on( 'click', 'sup.reference a', onClickReference );
	}

	init();
};
