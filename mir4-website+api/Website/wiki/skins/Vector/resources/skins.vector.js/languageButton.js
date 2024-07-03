/**
 * Copies interwiki links to main menu
 *
 * Temporary solution to T287206, can be removed when the new ULS built in Vue.js
 * has been released and contains this
 */
function addInterwikiLinkToMainMenu() {
	const editLink = /** @type {HTMLElement|null} */ (
		document.querySelector( '#p-lang-btn .wbc-editpage' )
	);

	if ( !editLink ) {
		return;
	}
	const title = editLink.getAttribute( 'title' ) || '';

	const addInterlanguageLink = mw.util.addPortletLink(
		'p-tb',
		editLink.getAttribute( 'href' ) || '#',
		// Original text is "Edit links".
		// Since its taken out of context the title is more descriptive.
		title,
		'wbc-editpage',
		title
	);

	if ( addInterlanguageLink ) {
		addInterlanguageLink.addEventListener( 'click', function ( /** @type {Event} */ e ) {
			e.preventDefault();
			// redirect to the detached and original edit link
			editLink.click();
		} );
	}
}

/**
 * Checks if ULS is disabled, and makes sure the language dropdown continues
 * to work if it is.
 */
function checkIfULSDisabled() {
	const langModuleState = mw.loader.getState( 'ext.uls.interface' );
	if ( langModuleState === null || langModuleState === 'registered' ) {
		document.documentElement.classList.add( 'vector-uls-disabled' );
	}
}

/**
 * Initialize the language button.
 */
module.exports = function () {
	checkIfULSDisabled();
	addInterwikiLinkToMainMenu();
};
