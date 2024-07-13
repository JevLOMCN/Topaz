/* global moment */

var utils = require( './utils.js' );

/**
 * A thread item, either a heading or a comment
 *
 * @class ThreadItem
 * @constructor
 * @param {string} type `heading` or `comment`
 * @param {number} level Indentation level
 * @param {Object} range Object describing the extent of the comment, including the
 *  signature and timestamp. It has the same properties as a Range object: `startContainer`,
 *  `startOffset`, `endContainer`, `endOffset` (we don't use a real Range because they change
 *  magically when the DOM structure changes).
 */
function ThreadItem( type, level, range ) {
	this.type = type;
	this.level = level;
	this.range = range;

	/**
	 * @member {string} Name for this comment, intended to be used to
	 *  find this comment in other revisions of the same page
	 */
	this.name = null;
	/**
	 * @member {string} Unique ID (within the page) for this comment
	 */
	this.id = null;
	/**
	 * @member {ThreadItem[]} Replies to this thread item
	 */
	this.replies = [];

	/**
	 * @member {string[]} Warnings
	 */
	this.warnings = [];

	this.rootNode = null;

	this.authors = null;
	this.commentCount = null;
	this.oldestReply = null;
	this.latestReply = null;
}

OO.initClass( ThreadItem );

/**
 * Create a new ThreadItem from a JSON serialization
 *
 * @param {string|Object} json JSON serialization or hash object
 * @param {HTMLElement} rootNode
 * @return {ThreadItem}
 * @throws {Error} Unknown ThreadItem type
 */
ThreadItem.static.newFromJSON = function ( json, rootNode ) {
	// The page can be served from the HTTP cache (Varnish), and the JSON may be generated
	// by an older version of our PHP code. Code below must be able to handle that.
	// See ThreadItem::jsonSerialize() in PHP.

	var hash = typeof json === 'string' ? JSON.parse( json ) : json;

	var item;
	switch ( hash.type ) {
		case 'comment':
			// Late require to avoid circular dependency
			var CommentItem = require( './CommentItem.js' );
			item = new CommentItem(
				hash.level,
				hash.range,
				hash.signatureRanges,
				hash.timestampRanges,
				moment.utc( hash.timestamp, [
					// See CommentItem#getTimestampString for notes about the two formats.
					'YYYYMMDDHHmmss',
					moment.ISO_8601
				], true ),
				hash.author,
				hash.displayName
			);
			break;
		case 'heading':
			var HeadingItem = require( './HeadingItem.js' );
			// Cached HTML may still have the placeholder heading constant in it.
			// This code can be removed a few weeks after being deployed.
			if ( hash.headingLevel === 99 ) {
				hash.headingLevel = null;
			}
			item = new HeadingItem(
				hash.range,
				hash.headingLevel
			);
			break;
		default:
			throw new Error( 'Unknown ThreadItem type ' + hash.name );
	}
	item.name = hash.name;
	item.id = hash.id;

	item.rootNode = rootNode;

	var idEscaped = $.escapeSelector( item.id );
	var startMarker = document.getElementById( item.id );
	var endMarker = document.querySelector( '[data-mw-comment-end="' + idEscaped + '"]' );

	item.range = {
		// Start range after startMarker, because it produces funny results from getBoundingClientRect
		startContainer: startMarker.parentNode,
		startOffset: utils.childIndexOf( startMarker ) + 1,
		// End range inside endMarker, because modifier crashes if endContainer is a <p>/<dd>/<li> node
		endContainer: endMarker,
		endOffset: 0
	};

	return item;
};

/**
 * Calculate summary metadata for a thread.
 */
ThreadItem.prototype.calculateThreadSummary = function () {
	if ( this.authors ) {
		return;
	}
	var authors = {};
	var commentCount = 0;
	var oldestReply = null;
	var latestReply = null;
	function threadScan( comment ) {
		if ( comment.type === 'comment' ) {
			authors[ comment.author ] = authors[ comment.author ] || {
				username: comment.author,
				displayNames: []
			};
			if (
				comment.displayName &&
				authors[ comment.author ].displayNames.indexOf( comment.displayName ) === -1
			) {
				authors[ comment.author ].displayNames.push( comment.displayName );
			}

			if (
				!oldestReply ||
				( comment.timestamp < oldestReply.timestamp )
			) {
				oldestReply = comment;
			}
			if (
				!latestReply ||
				( latestReply.timestamp < comment.timestamp )
			) {
				latestReply = comment;
			}
			commentCount++;
		}
		comment.replies.forEach( threadScan );
	}
	this.replies.forEach( threadScan );

	this.authors = Object.keys( authors ).sort().map( function ( author ) {
		return authors[ author ];
	} );
	this.commentCount = commentCount;
	this.oldestReply = oldestReply;
	this.latestReply = latestReply;
};

/**
 * Get the list of authors in the comment tree below this thread item.
 *
 * Usually called on a HeadingItem to find all authors in a thread.
 *
 * @return {Object[]} Authors, with `username` and `displayNames` (list of display names) properties.
 */
ThreadItem.prototype.getAuthorsBelow = function () {
	this.calculateThreadSummary();
	return this.authors;
};

/**
 * Get the number of comment items in the tree below this thread item.
 *
 * @return {number}
 */
ThreadItem.prototype.getCommentCount = function () {
	this.calculateThreadSummary();
	return this.commentCount;
};

/**
 * Get the latest reply in the tree below this thread item, null if there are no replies
 *
 * @return {CommentItem|null}
 */
ThreadItem.prototype.getLatestReply = function () {
	this.calculateThreadSummary();
	return this.latestReply;
};

/**
 * Get the oldest reply in the tree below this thread item, null if there are no replies
 *
 * @return {CommentItem|null}
 */
ThreadItem.prototype.getOldestReply = function () {
	this.calculateThreadSummary();
	return this.oldestReply;
};

/**
 * Get the list of thread items in the comment tree below this thread item.
 *
 * @return {ThreadItem[]} Thread items
 */
ThreadItem.prototype.getThreadItemsBelow = function () {
	var threadItems = [];
	function getReplies( comment ) {
		threadItems.push( comment );
		comment.replies.forEach( getReplies );
	}

	this.replies.forEach( getReplies );

	return threadItems;
};

/**
 * Get the range of the entire thread item
 *
 * @return {Range}
 */
ThreadItem.prototype.getRange = function () {
	var doc = this.range.startContainer.ownerDocument;
	var nativeRange = doc.createRange();
	nativeRange.setStart( this.range.startContainer, this.range.startOffset );
	nativeRange.setEnd( this.range.endContainer, this.range.endOffset );
	return nativeRange;
};

// Deprecated alias
ThreadItem.prototype.getNativeRange = ThreadItem.prototype.getRange;

// TODO: Implement getHTML/getText if required

module.exports = ThreadItem;
