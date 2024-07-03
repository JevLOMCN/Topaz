var utils = require( 'ext.discussionTools.init' ).utils;

module.exports = {};

/**
 * Override mw.config with the given data. Used for testing different languages etc.
 * (Automatically restored after every test by QUnit.newMwEnvironment.)
 *
 * @param {Object} config
 */
module.exports.overrideMwConfig = function ( config ) {
	$.extend(
		mw.config.values,
		config
	);
};

/**
 * Return the node that is expected to contain thread items.
 *
 * @param {Document} doc
 * @return {Element}
 */
module.exports.getThreadContainer = function ( doc ) {
	// In tests created from Parsoid output, comments are contained directly in <body>.
	// In tests created from old parser output, comments are contained in <div class="mw-parser-output">.
	var body = doc.body;
	var wrapper = body.querySelector( 'div.mw-parser-output' );
	return wrapper || body;
};

/**
 * Get the offset path from ancestor to offset in descendant
 *
 * @param {Node} ancestor The ancestor node
 * @param {Node} node The descendant node
 * @param {number} nodeOffset The offset in the descendant node
 * @return {string} The offset path
 */
function getOffsetPath( ancestor, node, nodeOffset ) {
	var path = [ nodeOffset ];
	while ( node !== ancestor ) {
		if ( node.parentNode === null ) {
			// eslint-disable-next-line no-console
			console.log( node, 'is not a descendant of', ancestor );
			throw new Error( 'Not a descendant' );
		}
		path.unshift( utils.childIndexOf( node ) );
		node = node.parentNode;
	}
	return path.join( '/' );
}

function getPathsFromRange( root, range ) {
	return [
		getOffsetPath( root, range.startContainer, range.startOffset ),
		getOffsetPath( root, range.endContainer, range.endOffset )
	];
}

/**
 * Massage comment data to make it serializable as JSON.
 *
 * @param {CommentItem} parent Comment item; modified in-place
 * @param {Node} root Ancestor node of all comments
 */
module.exports.serializeComments = function ( parent, root ) {
	if ( !parent.range.startContainer ) {
		// Already done as part of a different thread
		return;
	}

	// Can't serialize circular structures to JSON
	delete parent.parent;

	// Can't serialize the DOM nodes involved in the range,
	// instead use their offsets within their parent nodes
	parent.range = getPathsFromRange( root, parent.range );
	if ( parent.signatureRanges ) {
		parent.signatureRanges = parent.signatureRanges.map( function ( range ) {
			return getPathsFromRange( root, range );
		} );
	}
	if ( parent.timestampRanges ) {
		parent.timestampRanges = parent.timestampRanges.map( function ( range ) {
			return getPathsFromRange( root, range );
		} );
	}
	if ( parent.timestamp ) {
		parent.timestamp = parent.getTimestampString();
	}
	if ( !parent.displayName ) {
		delete parent.displayName;
	}

	// Unimportant
	delete parent.rootNode;

	// Ignore generated properties
	delete parent.authors;
	delete parent.commentCount;
	delete parent.oldestReply;
	delete parent.latestReply;

	parent.replies.forEach( function ( comment ) {
		module.exports.serializeComments( comment, root );
	} );
};
