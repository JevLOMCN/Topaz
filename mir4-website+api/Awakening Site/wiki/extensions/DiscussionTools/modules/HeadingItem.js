var ThreadItem = require( './ThreadItem.js' );
// Placeholder headings must have a level higher than real headings (1-6)
var PLACEHOLDER_HEADING_LEVEL = 99;

/**
 * A heading item
 *
 * @class HeadingItem
 * @extends ThreadItem
 * @constructor
 * @param {Object} range
 * @param {number|null} headingLevel Heading level (1-6). Use null for a placeholder heading.
 */
function HeadingItem( range, headingLevel ) {
	// Parent constructor
	HeadingItem.super.call( this, 'heading', 0, range );

	this.placeholderHeading = headingLevel === null;
	this.headingLevel = this.placeholderHeading ? PLACEHOLDER_HEADING_LEVEL : headingLevel;
}

OO.inheritClass( HeadingItem, ThreadItem );

HeadingItem.prototype.getLinkableTitle = function () {
	var title = '';
	// If this comment is in 0th section, there's no section title for the edit summary
	if ( !this.placeholderHeading ) {
		// <span class="mw-headline" …>, or <hN …> in Parsoid HTML
		var headline = this.range.startContainer;
		var id = headline.getAttribute( 'id' );
		if ( id ) {
			// Replace underscores with spaces to undo Sanitizer::escapeIdInternal().
			// This assumes that $wgFragmentMode is [ 'html5', 'legacy' ] or [ 'html5' ],
			// otherwise the escaped IDs are super garbled and can't be unescaped reliably.
			title = id.replace( /_/g, ' ' );
		}
		// else: Not a real section, probably just HTML markup in wikitext
	}
	return title;
};

/**
 * Check whether this heading can be used for topic subscriptions.
 *
 * @return {boolean}
 */
HeadingItem.prototype.isSubscribable = function () {
	return (
		// Placeholder headings have nothing to attach the button to.
		!this.placeholderHeading &&
		// We only allow subscribing to level 2 headings, because the user interface for sub-headings
		// would be difficult to present.
		this.headingLevel === 2 &&
		// Check if the name corresponds to a section that contain no comments (only sub-sections).
		// They can't be distinguished from each other, so disallow subscribing.
		this.name !== 'h-'
	);
};

module.exports = HeadingItem;
