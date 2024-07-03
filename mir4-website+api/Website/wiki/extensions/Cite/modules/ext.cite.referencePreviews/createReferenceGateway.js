/**
 * @module gateway/reference
 */

const { TYPE_REFERENCE } = require( './constants.js' );

/**
 * @return {Gateway}
 */
module.exports = function createReferenceGateway() {

	/**
	 * @param {string} id
	 * @return {HTMLElement}
	 */
	function scrapeReferenceText( id ) {
		const idSelector = `#${ CSS.escape( id ) }`;

		/**
		 * Same alternative selectors with and without mw-… as in the RESTbased endpoint.
		 *
		 * @see https://phabricator.wikimedia.org/diffusion/GMOA/browse/master/lib/transformations/references/structureReferenceListContent.js$138
		 */
		return document.querySelector( `${ idSelector } .mw-reference-text, ${ idSelector } .reference-text` );
	}

	/**
	 * Attempts to find a single reference type identifier, limited to a list of known types.
	 * - When a `class="…"` attribute mentions multiple known types, the last one is used, following
	 *   CSS semantics.
	 * - When there are multiple <cite> tags, the first with a known type is used.
	 *
	 * @param {HTMLElement} referenceText
	 * @return {string|null}
	 */
	function scrapeReferenceType( referenceText ) {
		const KNOWN_TYPES = [ 'book', 'journal', 'news', 'note', 'web' ];
		let type = null;
		const citeTags = referenceText.querySelectorAll( 'cite[class]' );
		Array.prototype.forEach.call( citeTags, ( element ) => {
			// don't need to keep scanning if one is found.
			if ( type ) {
				return;
			}
			const classNames = element.className.split( /\s+/ );
			for ( let i = classNames.length; i--; ) {
				if ( KNOWN_TYPES.indexOf( classNames[ i ] ) !== -1 ) {
					type = classNames[ i ];
					return false;
				}
			}
		} );
		return type;
	}

	/**
	 * @param {mw.Title} title
	 * @param {HTMLAnchorElement} el
	 * @return {Promise<ext.popups.PreviewModel>}
	 */
	function fetchPreviewForTitle( title, el ) {
		// Need to encode the fragment again as mw.Title returns it as decoded text
		const id = title.getFragment().replace( / /g, '_' ),
			referenceNode = scrapeReferenceText( id );

		if ( !referenceNode ||
			// Skip references that don't contain anything but whitespace, e.g. a single &nbsp;
			( !referenceNode.textContent.trim() && !referenceNode.children.length )
		) {
			return Promise.reject(
				// Required to set `showNullPreview` to false and not open an error popup
				{ textStatus: 'abort', textContext: 'Footnote not found or empty', xhr: { readyState: 0 } }
			);
		}

		const model = {
			url: `#${ id }`,
			extract: referenceNode.innerHTML,
			type: TYPE_REFERENCE,
			referenceType: scrapeReferenceType( referenceNode ),
			// Note: Even the top-most HTMLHtmlElement is guaranteed to have a parent.
			sourceElementId: el.parentNode.id
		};

		// Make promise abortable.
		const promise = Promise.resolve( model );
		promise.abort = () => {};
		return promise;
	}

	return {
		fetchPreviewForTitle
	};
};
