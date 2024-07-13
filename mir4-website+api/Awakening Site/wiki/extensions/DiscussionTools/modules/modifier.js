'use strict';
/* global $:off */

var
	utils = require( './utils.js' );

/**
 * Remove extra linebreaks from a wikitext string
 *
 * @param {string} wikitext Wikitext
 * @return {string}
 */
function sanitizeWikitextLinebreaks( wikitext ) {
	return utils.htmlTrim( wikitext )
		.replace( /\r/g, '\n' )
		.replace( /\n+/g, '\n' );
}

/**
 * Given a comment and a reply link, add the reply link to its document's DOM tree, at the end of
 * the comment.
 *
 * @param {CommentItem} comment Comment item
 * @param {HTMLElement} linkNode Reply link
 */
function addReplyLink( comment, linkNode ) {
	var target = comment.range.endContainer;

	// Insert the link before trailing whitespace.
	// In the MediaWiki parser output, <ul>/<dl> nodes are preceded by a newline. Normally it isn't
	// visible on the page. But if we insert an inline element (the reply link) after it, it becomes
	// meaningful and gets rendered, which results in additional spacing before some reply links.
	// Split the text node, so that we can insert the link before the trailing whitespace.
	if ( target.nodeType === Node.TEXT_NODE ) {
		target.splitText( target.textContent.match( /\s*$/ ).index );
	}

	target.parentNode.insertBefore( linkNode, target.nextSibling );
}

/**
 * Given a comment, add a list item to its document's DOM tree, inside of which a reply to said
 * comment can be added.
 *
 * The DOM tree is suitably rearranged to ensure correct indentation level of the reply (wrapper
 * nodes are added, and other nodes may be moved around).
 *
 * @param {CommentItem} comment Comment item
 * @param {string} replyIndentation Reply indentation syntax to use, one of:
 *   - 'invisible' (use `<dl><dd>` tags to output `:` in wikitext)
 *   - 'bullet' (use `<ul><li>` tags to output `*` in wikitext)
 * @return {HTMLElement}
 */
function addListItem( comment, replyIndentation ) {
	var listTypeMap = {
		li: 'ul',
		dd: 'dl'
	};

	// 1. Start at given comment
	// 2. Skip past all comments with level greater than the given
	//    (or in other words, all replies, and replies to replies, and so on)
	// 3. Add comment with level of the given comment plus 1

	var curComment = comment;
	while ( curComment.replies.length ) {
		curComment = curComment.replies[ curComment.replies.length - 1 ];
	}

	// Tag names for lists and items we're going to insert
	var itemType;
	if ( replyIndentation === 'invisible' ) {
		itemType = 'dd';
	} else if ( replyIndentation === 'bullet' ) {
		itemType = 'li';
	} else {
		throw new Error( "Invalid reply indentation syntax '" + replyIndentation + "'" );
	}
	var listType = listTypeMap[ itemType ];

	var desiredLevel = comment.level + 1;
	var target = curComment.range.endContainer;

	// target is a text node or an inline element at the end of a "paragraph" (not necessarily paragraph node).
	// First, we need to find a block-level parent that we can mess with.
	// If we can't find a surrounding list item or paragraph (e.g. maybe we're inside a table cell
	// or something), take the parent node and hope for the best.
	var parent = utils.closestElement( target, [ 'li', 'dd', 'p' ] ) || target.parentNode;
	while ( target.parentNode !== parent ) {
		target = target.parentNode;
	}
	// parent is a list item or paragraph (hopefully)
	// target is an inline node within it

	// If the comment is fully covered by some wrapper element, insert replies outside that wrapper.
	// This will often just be a paragraph node (<p>), but it can be a <div> or <table> that serves
	// as some kind of a fancy frame, which are often used for barnstars and announcements.
	var excludedWrapper = utils.closestElement( target, [ 'section' ] ) ||
		curComment.rootNode;
	var covered = utils.getFullyCoveredSiblings( curComment, excludedWrapper );
	if ( curComment.level === 1 && covered ) {
		target = covered[ covered.length - 1 ];
		parent = target.parentNode;
	}

	// If the comment is in a transclusion, insert replies after the transclusion. (T313100)
	// This method should never be called in cases where that would be a bad idea.
	var transclusionNode = utils.getTranscludedFromElement( target );
	if ( transclusionNode ) {
		while (
			transclusionNode.nextSibling &&
			transclusionNode.nextSibling.nodeType === Node.ELEMENT_NODE &&
			transclusionNode.nextSibling.getAttribute( 'about' ) === transclusionNode.getAttribute( 'about' )
		) {
			transclusionNode = transclusionNode.nextSibling;
		}
		target = transclusionNode;
		parent = target.parentNode;
	}

	// If we can't insert a list directly inside this element, insert after it.
	// The covered wrapper check above handles most cases, but we still need this sometimes, such as:
	// * If the comment starts in the middle of a list, then ends with an unindented p/pre, the
	//   wrapper check doesn't adjust the parent
	// * If the comment consists of multiple list items (starting with a <dt>, so that the comment is
	//   considered to be unindented, that is level === 1), but not all of them, the wrapper check
	//   adjusts the parent to be the list, and the rest of the algorithm doesn't handle that well
	if (
		parent.tagName.toLowerCase() === 'p' ||
		parent.tagName.toLowerCase() === 'pre' ||
		parent.tagName.toLowerCase() === 'ul' ||
		parent.tagName.toLowerCase() === 'dl'
	) {
		parent = parent.parentNode;
		target = target.parentNode;
	}

	// HACK: Skip past our own reply buttons
	if ( target.nextSibling && target.nextSibling.nodeType === Node.ELEMENT_NODE && target.nextSibling.classList.contains( 'ext-discussiontools-init-replylink-buttons' ) ) {
		target = target.nextSibling;
	}

	// Instead of just using curComment.level, consider indentation of lists within the
	// comment (T252702)
	var curLevel = utils.getIndentLevel( target, curComment.rootNode ) + 1;

	var item, list;
	if ( desiredLevel === 1 ) {
		// Special handling for top-level comments
		item = target.ownerDocument.createElement( 'div' );
		item.discussionToolsModified = 'new';
		parent.insertBefore( item, target.nextSibling );
		// TODO: We should not insert a <div>, instead we need a function that returns parent and target,
		// so that we can insert nodes in this place in other code

	} else if ( curLevel < desiredLevel ) {
		// Insert more lists after the target to increase nesting.

		// Parsoid puts HTML comments (and other "rendering-transparent nodes", e.g. category links)
		// which appear at the end of the line in wikitext outside the paragraph,
		// but we usually shouldn't insert replies between the paragraph and such comments. (T257651)
		// Skip over comments and whitespace, but only update target when skipping past comments.
		var pointer = target;
		while (
			pointer.nextSibling && (
				utils.isRenderingTransparentNode( pointer.nextSibling ) ||
				(
					pointer.nextSibling.nodeType === Node.TEXT_NODE &&
					utils.htmlTrim( pointer.nextSibling.textContent ) === '' &&
					// If at least two lines of whitespace are detected, the following HTML
					// comments are not considered to be part of the reply (T264026, T301214)
					!/\n[^\n]*\n/.test( pointer.nextSibling.textContent )
				)
			)
		) {
			pointer = pointer.nextSibling;
			if ( utils.isRenderingTransparentNode( pointer ) ) {
				target = pointer;
			}
		}

		// Insert required number of wrappers
		while ( curLevel < desiredLevel ) {
			list = target.ownerDocument.createElement( listType );
			list.discussionToolsModified = 'new';
			item = target.ownerDocument.createElement( itemType );
			item.discussionToolsModified = 'new';

			parent.insertBefore( list, target.nextSibling );
			list.appendChild( item );

			target = item;
			parent = list;
			curLevel++;
		}
	} else {
		// Split the ancestor nodes after the target to decrease nesting.

		var newNode;
		do {
			if ( !target || !parent ) {
				throw new Error( 'Can not decrease nesting any more' );
			}

			// If target is the last child of its parent, no need to split it
			if ( target.nextSibling ) {
				// Create new identical node after the parent
				newNode = parent.cloneNode( false );
				parent.discussionToolsModified = 'split';
				parent.parentNode.insertBefore( newNode, parent.nextSibling );

				// Move nodes following target to the new node
				while ( target.nextSibling ) {
					newNode.appendChild( target.nextSibling );
				}
			}

			target = parent;
			parent = parent.parentNode;

			// Decrease nesting level if we escaped outside of a list
			if ( listTypeMap[ target.tagName.toLowerCase() ] ) {
				curLevel--;
			}
		} while ( curLevel >= desiredLevel );

		// parent is now a list, target is a list item
		if ( itemType === target.tagName.toLowerCase() ) {
			item = target.ownerDocument.createElement( itemType );
			item.discussionToolsModified = 'new';
			parent.insertBefore( item, target.nextSibling );

		} else {
			// This is the wrong type of list, split it one more time

			// If target is the last child of its parent, no need to split it
			if ( target.nextSibling ) {
				// Create new identical node after the parent
				newNode = parent.cloneNode( false );
				parent.discussionToolsModified = 'split';
				parent.parentNode.insertBefore( newNode, parent.nextSibling );

				// Move nodes following target to the new node
				while ( target.nextSibling ) {
					newNode.appendChild( target.nextSibling );
				}
			}

			target = parent;
			parent = parent.parentNode;

			// Insert a list of the right type in the middle
			list = target.ownerDocument.createElement( listType );
			list.discussionToolsModified = 'new';
			item = target.ownerDocument.createElement( itemType );
			item.discussionToolsModified = 'new';

			parent.insertBefore( list, target.nextSibling );
			list.appendChild( item );
		}
	}

	return item;
}

/**
 * Undo the effects of #addListItem, also removing or merging any affected parent nodes.
 *
 * @param {HTMLElement} node
 */
function removeAddedListItem( node ) {
	while ( node && node.discussionToolsModified ) {
		var nextNode;
		if ( node.discussionToolsModified === 'new' ) {
			nextNode = node.previousSibling || node.parentNode;

			// Remove this node
			delete node.discussionToolsModified;
			node.parentNode.removeChild( node );

		} else if ( node.discussionToolsModified === 'split' ) {
			// Children might be split too, if so, descend into them afterwards
			if ( node.lastChild && node.lastChild.discussionToolsModified === 'split' ) {
				node.discussionToolsModified = 'done';
				nextNode = node.lastChild;
			} else {
				delete node.discussionToolsModified;
				nextNode = node.parentNode;
			}
			// Merge the following sibling node back into this one
			while ( node.nextSibling.firstChild ) {
				node.appendChild( node.nextSibling.firstChild );
			}
			node.parentNode.removeChild( node.nextSibling );

		} else {
			nextNode = node.parentNode;
		}

		node = nextNode;
	}
}

/**
 * Unwrap a top level list, converting list item text to paragraphs
 *
 * Assumes that the list has a parent node.
 *
 * @param {Node} list DOM node, will be wrapped if it is a list element (dl/ol/ul)
 * @param {DocumentFragment|null} fragment Containing document fragment if list has no parent
 */
function unwrapList( list, fragment ) {
	var doc = list.ownerDocument,
		container = fragment || list.parentNode,
		referenceNode = list;

	if ( !(
		list.nodeType === Node.ELEMENT_NODE && (
			list.tagName.toLowerCase() === 'dl' ||
			list.tagName.toLowerCase() === 'ol' ||
			list.tagName.toLowerCase() === 'ul'
		)
	) ) {
		// Not a list, leave alone (e.g. auto-generated ref block)
		return;
	}

	// If the whole list is a template return it unmodified (T253150)
	if ( utils.getTranscludedFromElement( list ) ) {
		return;
	}

	var insertBefore;
	while ( list.firstChild ) {
		if ( list.firstChild.nodeType === Node.ELEMENT_NODE ) {
			// Move <dd> contents to <p>
			var p = doc.createElement( 'p' );
			while ( list.firstChild.firstChild ) {
				// If contents is a block element, place outside the paragraph
				// and start a new paragraph after
				if ( utils.isBlockElement( list.firstChild.firstChild ) ) {
					if ( p.firstChild ) {
						insertBefore = referenceNode.nextSibling;
						referenceNode = p;
						container.insertBefore( p, insertBefore );
					}
					insertBefore = referenceNode.nextSibling;
					referenceNode = list.firstChild.firstChild;
					container.insertBefore( list.firstChild.firstChild, insertBefore );
					p = doc.createElement( 'p' );
				} else {
					p.appendChild( list.firstChild.firstChild );
				}
			}
			if ( p.firstChild ) {
				insertBefore = referenceNode.nextSibling;
				referenceNode = p;
				container.insertBefore( p, insertBefore );
			}
			list.removeChild( list.firstChild );
		} else {
			// Text node / comment node, probably empty
			insertBefore = referenceNode.nextSibling;
			referenceNode = list.firstChild;
			container.insertBefore( list.firstChild, insertBefore );
		}
	}
	container.removeChild( list );
}

/**
 * Add another list item after the given one.
 *
 * @param {HTMLElement} previousItem
 * @return {HTMLElement}
 */
function addSiblingListItem( previousItem ) {
	var listItem = previousItem.ownerDocument.createElement( previousItem.tagName );
	previousItem.parentNode.insertBefore( listItem, previousItem.nextSibling );
	return listItem;
}

module.exports = {
	addReplyLink: addReplyLink,
	addListItem: addListItem,
	removeAddedListItem: removeAddedListItem,
	addSiblingListItem: addSiblingListItem,
	unwrapList: unwrapList,
	sanitizeWikitextLinebreaks: sanitizeWikitextLinebreaks
};
