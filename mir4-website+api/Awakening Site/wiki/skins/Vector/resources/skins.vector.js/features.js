/** @interface MwApi */

let /** @type {MwApi} */ api;
const debounce = require( /** @type {string} */ ( 'mediawiki.util' ) ).debounce;

/**
 * Saves preference to user preferences and/or cookies.
 *
 * @param {string} feature
 * @param {boolean} enabled
 */
function save( feature, enabled ) {
	if ( !mw.user.isNamed() ) {
		switch ( feature ) {
			case 'toc-pinned':
			case 'limited-width':
				// Save the setting under the new system
				// @ts-ignore https://github.com/wikimedia/typescript-types/pull/44
				mw.user.clientPrefs.set( `vector-feature-${ feature }`, enabled ? '1' : '0' );
				break;
			default:
				// not a supported anonymous preference
				break;
		}
	} else {
		debounce( function () {
			api = api || new mw.Api();
			api.saveOption( 'vector-' + feature, enabled ? 1 : 0 );
		}, 500 )();
	}
}

/**
 * @param {string} name feature name
 * @param {boolean} [override] option to force enabled or disabled state.
 * @param {boolean} [isLegacy] should we search for legacy classes
 *   FIXME: this is for supporting cached HTML,
 *   this should be removed 1-4 weeks after the patch has been in production.
 * @return {boolean} The new feature state (false=disabled, true=enabled).
 * @throws {Error} if unknown feature toggled.
 */
function toggleDocClasses( name, override, isLegacy ) {
	const suffixEnabled = isLegacy ? 'enabled' : 'clientpref-1';
	const suffixDisabled = isLegacy ? 'disabled' : 'clientpref-0';
	const featureClassEnabled = `vector-feature-${ name }-${ suffixEnabled }`,
		classList = document.documentElement.classList,
		featureClassDisabled = `vector-feature-${ name }-${ suffixDisabled }`,
		// If neither of the classes can be found it is a legacy feature
		isLegacyFeature = !classList.contains( featureClassDisabled ) &&
			!classList.contains( featureClassEnabled );

	// Check in legacy mode.
	if ( isLegacyFeature && !isLegacy ) {
		// try again using the legacy classes
		return toggleDocClasses( name, override, true );
	} else if ( classList.contains( featureClassDisabled ) || override === true ) {
		classList.remove( featureClassDisabled );
		classList.add( featureClassEnabled );
		return true;
	} else if ( classList.contains( featureClassEnabled ) || override === false ) {
		classList.add( featureClassDisabled );
		classList.remove( featureClassEnabled );
		return false;
	} else {
		throw new Error( `Attempt to toggle unknown feature: ${ name }` );
	}
}

/**
 * @param {string} name
 * @throws {Error} if unknown feature toggled.
 */
function toggle( name ) {
	const featureState = toggleDocClasses( name );
	save( name, featureState );
}

/**
 * Checks if the feature is enabled.
 *
 * @param {string} name
 * @return {boolean}
 */
function isEnabled( name ) {
	return document.documentElement.classList.contains( getClass( name, true ) ) ||
		document.documentElement.classList.contains( getClass( name, true, true ) );
}

/**
 * Get name of feature class.
 *
 * @param {string} name
 * @param {boolean} featureEnabled
 * @param {boolean} [isLegacy] FIXME: this is for supporting cached HTML,
 *   but also features using the old feature class structure.
 * @return {string}
 */
function getClass( name, featureEnabled, isLegacy ) {
	if ( featureEnabled ) {
		const suffix = isLegacy ? 'enabled' : 'clientpref-1';
		return `vector-feature-${ name }-${ suffix }`;
	} else {
		const suffix = isLegacy ? 'disabled' : 'clientpref-0';
		return `vector-feature-${ name }-${ suffix }`;
	}
}

module.exports = { getClass, isEnabled, toggle, toggleDocClasses };
