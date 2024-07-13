( function () {
	const
		checkboxHack = require( ( 'mediawiki.page.ready' ) ).checkboxHack,
		CHECKBOX_HACK_CONTAINER_SELECTOR = '.toggle-list',
		CHECKBOX_HACK_CHECKBOX_SELECTOR = '.toggle-list__checkbox',
		CHECKBOX_HACK_BUTTON_SELECTOR = '.toggle-list__toggle',
		CHECKBOX_HACK_TARGET_SELECTOR = '.toggle-list__list';

	/**
	 * Automatically dismiss the list when clicking or focusing elsewhere and update the
	 * aria-expanded attribute based on list visibility.
	 *
	 * @param {Window} window
	 * @param {HTMLElement} component
	 */
	function bind( window, component ) {
		const
			checkbox = /** @type {HTMLInputElement} */ (
				component.querySelector( CHECKBOX_HACK_CHECKBOX_SELECTOR )
			),
			button = component.querySelector( CHECKBOX_HACK_BUTTON_SELECTOR ),
			target = component.querySelector( CHECKBOX_HACK_TARGET_SELECTOR ).parentNode;

		if ( !( checkbox && button && target ) ) {
			return;
		}
		checkboxHack.bind( window, checkbox, button, target );
	}

	module.exports = Object.freeze( {
		selector: CHECKBOX_HACK_CONTAINER_SELECTOR,
		bind
	} );
}() );
