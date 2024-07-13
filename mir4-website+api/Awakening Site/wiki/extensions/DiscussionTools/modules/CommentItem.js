var ThreadItem = require( './ThreadItem.js' ),
	moment = require( './lib/moment-timezone/moment-timezone-with-data-1970-2030.js' );

/**
 * A comment item
 *
 * @class CommentItem
 * @extends ThreadItem
 * @constructor
 * @param {number} level
 * @param {Object} range
 * @param {Object[]} [signatureRanges] Objects describing the extent of signatures (plus
 *  timestamps) for this comment. There is always at least one signature, but there may be
 *  multiple. The author and timestamp of the comment is determined from the first signature.
 * @param {Object[]} [timestampRanges] Objects describing the extent of timestamps within
 *  the above signatures.
 * @param {moment} [timestamp] Timestamp (Moment object)
 * @param {string} [author] Comment author's username
 * @param {string|null} [displayName] Comment author's display name
 */
function CommentItem( level, range, signatureRanges, timestampRanges, timestamp, author, displayName ) {
	// Parent constructor
	CommentItem.super.call( this, 'comment', level, range );

	this.signatureRanges = signatureRanges || [];
	this.timestampRanges = timestampRanges || [];
	this.timestamp = timestamp || null;
	this.author = author || null;
	this.displayName = displayName || null;

	/**
	 * @member {ThreadItem} Parent thread item
	 */
	this.parent = null;
}

OO.inheritClass( CommentItem, ThreadItem );

/**
 * Get the comment timestamp in the format used in IDs and names.
 *
 * Depending on the date of the comment, this may use one of two formats:
 *
 *  - For dates prior to 'DiscussionToolsTimestampFormatSwitchTime' (by default 2022-07-12):
 *    Uses ISO 8601 date. Almost DateTimeInterface::RFC3339_EXTENDED, but ending with 'Z' instead
 *    of '+00:00', like Date#toISOString in JavaScript.
 *
 *  - For dates on or after 'DiscussionToolsTimestampFormatSwitchTime' (by default 2022-07-12):
 *    Uses MediaWiki timestamp (TS_MW in MediaWiki PHP code).
 *
 * @return {string} Comment timestamp in standard format
 */
CommentItem.prototype.getTimestampString = function () {
	var dtConfig = require( './config.json' );
	var switchTime = moment.utc( dtConfig.switchTime );
	if ( this.timestamp < switchTime ) {
		return this.timestamp.utc().toISOString();
	} else {
		// Switch to English locale to avoid number formatting
		return this.timestamp.utc().locale( 'en' ).format( 'YYYYMMDDHHmmss' );
	}
};

/**
 * @return {HeadingItem} Closest ancestor which is a HeadingItem
 */
CommentItem.prototype.getHeading = function () {
	var parent = this;
	while ( parent && parent.type !== 'heading' ) {
		parent = parent.parent;
	}
	return parent;
};

/**
 * @return {HeadingItem|null} Closest heading that can be used for topic subscriptions
 */
CommentItem.prototype.getSubscribableHeading = function () {
	var heading = this.getHeading();
	while ( heading && heading.type === 'heading' && !heading.isSubscribable() ) {
		heading = heading.parent;
	}
	return ( heading && heading.type === 'heading' ) ? heading : null;
};

// TODO: Implement getBodyRange/getBodyHTML/getBodyText/getMentions if required

module.exports = CommentItem;
