const { TYPE_REFERENCE } = require( './constants.js' );

/**
 * @module isReferencePreviewsEnabled
 */

/**
 * Given the global state of the application, creates a function that gets
 * whether or not the user should have Reference Previews enabled.
 *
 * @param {mw.user} user The `mw.user` singleton instance
 * @param {Function} isPreviewTypeEnabled check whether preview has been disabled or enabled.
 * @param {mw.Map} config
 *
 * @return {boolean|null} Null when there is no way the popup type can be enabled at run-time.
 */
function isReferencePreviewsEnabled( user, isPreviewTypeEnabled, config ) {
	// TODO: This and the final `mw.user.options` check are currently redundant. Only this here
	// should be removed when the wgPopupsReferencePreviews feature flag is not needed any more.
	if ( !config.get( 'wgPopupsReferencePreviews' ) ) {
		return null;
	}

	// T265872: Unavailable when in conflict with (one of the) reference tooltips gadgets.
	if ( config.get( 'wgPopupsConflictsWithRefTooltipsGadget' ) ||
		config.get( 'wgPopupsConflictsWithNavPopupGadget' ) ||
		// T243822: Temporarily disabled in the mobile skin
		config.get( 'skin' ) === 'minerva'
	) {
		return null;
	}

	if ( user.isAnon() ) {
		return isPreviewTypeEnabled( TYPE_REFERENCE );
	}

	// Registered users never can enable popup types at run-time.
	return user.options.get( 'popups-reference-previews' ) === '1' ? true : null;
}

module.exports = isReferencePreviewsEnabled;
