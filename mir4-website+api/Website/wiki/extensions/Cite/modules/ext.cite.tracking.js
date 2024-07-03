'use strict';

/**
 * @file Temporary tracking to evaluate the impact of Reference Previews on
 * users' interaction with references.
 *
 * @see https://phabricator.wikimedia.org/T214493
 * @see https://phabricator.wikimedia.org/T231529
 * @see https://phabricator.wikimedia.org/T353798
 * @see https://meta.wikimedia.org/wiki/Schema:ReferencePreviewsBaseline
 * @see https://meta.wikimedia.org/wiki/Schema:ReferencePreviewsCite
 */

const CITE_BASELINE_LOGGING_SCHEMA = 'ext.cite.baseline';
// Same as in the Popups extension
// FIXME: Could be an extension wide constant when Reference Previews is merged into this code base
const REFERENCE_PREVIEWS_LOGGING_SCHEMA = 'event.ReferencePreviewsPopups';

// EventLogging may not be installed
mw.loader.using( 'ext.eventLogging' ).then( function () {
	$( function () {
		if ( !navigator.sendBeacon ||
			!mw.config.get( 'wgIsArticle' )
		) {
			return;
		}

		// FIXME: This might be obsolete when the code moves to the this extension
		mw.trackSubscribe( REFERENCE_PREVIEWS_LOGGING_SCHEMA, function ( type, data ) {
			if ( data.action.indexOf( 'anonymous' ) !== -1 ) {
				mw.config.set( 'wgPopupsReferencePreviewsVisible', data.action === 'anonymousEnabled' );
			}
		} );

		// eslint-disable-next-line no-jquery/no-global-selector
		$( '#mw-content-text' ).on(
			'click',
			// Footnote links, references block in VisualEditor, and reference content links.
			'.reference a[ href*="#" ], .mw-reference-text a, .reference-text a',
			function () {
				const isInReferenceBlock = $( this ).parents( '.references' ).length > 0;
				mw.eventLog.dispatch( CITE_BASELINE_LOGGING_SCHEMA, {
					action: ( isInReferenceBlock ?
						'clickedReferenceContentLink' :
						'clickedFootnote' ),
					// FIXME: This might be obsolete when the code moves to the this extension and
					//  we get state directly.
					// eslint-disable-next-line camelcase
					with_ref_previews: mw.config.get( 'wgPopupsReferencePreviewsVisible' )
				} );
			}
		);
	} );
} );
