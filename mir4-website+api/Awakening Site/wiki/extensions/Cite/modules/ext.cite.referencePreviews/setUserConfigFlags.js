/**
 * @module setUserConfigFlags
 */

/**
 * Same as in includes/PopupsContext.php
 */
const REF_TOOLTIPS_ENABLED = 2,
	REFERENCE_PREVIEWS_ENABLED = 4;

/**
 * Decodes the bitmask that represents preferences to the related config options.
 *
 * @param {mw.Map} config
 */
module.exports = function setUserConfigFlags( config ) {
	const popupsFlags = parseInt( config.get( 'wgPopupsFlags' ), 10 );

	/* eslint-disable no-bitwise */
	config.set(
		'wgPopupsConflictsWithRefTooltipsGadget',
		!!( popupsFlags & REF_TOOLTIPS_ENABLED )
	);
	config.set(
		'wgPopupsReferencePreviews',
		!!( popupsFlags & REFERENCE_PREVIEWS_ENABLED )
	);
	/* eslint-enable no-bitwise */
};
