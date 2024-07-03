/*!
 * VisualEditor initialization support checker.
 *
 * @copyright See AUTHORS.txt
 */

( function () {
	/**
	 * Check whether the environment has the needed features to load VisualEditor.
	 * This considers every ES5 feature, support for contentEditable itself, those
	 * specific DOM features we use, and SVG support for the user interface. As we
	 * use this to check for feature compatibility this file must be ES3-parsable.
	 *
	 * @method VisualEditorSupportCheck
	 * @member global
	 * @return {boolean} True if the environment should support VisualEditor.
	 */
	window.VisualEditorSupportCheck = function () {
		return (
			/* ES6 */
			( function () {
				try {
					// eslint-disable-next-line no-new, no-new-func
					new Function( '(a = 0) => a' );
					return true;
				} catch ( e ) {
					return false;
				}
			}() ) &&

			/* ES6 RegExp.prototype.flags */
			/./g.flags === 'g' &&

			// TODO: Most of the below checks can probably be removed as they are supported in all ES6 browsers.

			/* contentEditable */
			!!( 'contentEditable' in document.createElement( 'div' ) ) &&

			/* createElementNS */
			!!document.createElementNS &&

			/* DOMParser */
			( function () {
				var doc;
				try {
					doc = new DOMParser().parseFromString( '<body></body>', 'text/html' );
				} catch ( e ) {}
				return doc instanceof HTMLDocument;
			}() ) &&

			/* classList */
			!!(
				( 'classList' in document.createElement( '_' ) ) ||
				( 'classList' in document.createElementNS( 'http://www.w3.org/2000/svg ', 'g' ) )
			) &&

			/* SVG */
			!!( 'createSVGRect' in document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' ) )
		);
	};
}() );
