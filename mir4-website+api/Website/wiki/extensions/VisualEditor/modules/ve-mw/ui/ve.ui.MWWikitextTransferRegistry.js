/*!
 * VisualEditor MediaWiki WikitextTransferRegistry and registrations.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Heuristic patterns which attempts to discover wikitext, without
 * incurring too many false positives.
 *
 * Rules can be regular expressions or strings
 */
ve.ui.mwWikitextTransferRegistry = new OO.Registry();

ve.ui.mwWikitextTransferRegistry.register(
	'heading',
	// ==...== on a single line of max 80 characters
	/(^\s*(={2,6})[^=\r\n]{1,80}\2\s*$)/m
);

ve.ui.mwWikitextTransferRegistry.register(
	'internalLink',
	'[['
);

ve.init.platform.getInitializedPromise().done( function () {
	ve.ui.mwWikitextTransferRegistry.register(
		'externalLink',
		// [url label]
		new RegExp(
			'\\[' + ve.init.platform.getUnanchoredExternalLinkUrlProtocolsRegExp().source + '\\S+ [^\\]]+\\]',
			'i'
		)
	);
} );

ve.ui.mwWikitextTransferRegistry.register(
	'template',
	'{{'
);
