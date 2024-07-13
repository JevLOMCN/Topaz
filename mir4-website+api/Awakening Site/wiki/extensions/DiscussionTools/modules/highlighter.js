var
	lastHighlightedPublishedComment = null,
	featuresEnabled = mw.config.get( 'wgDiscussionToolsFeaturesEnabled' ) || {},
	CommentItem = require( './CommentItem.js' ),
	HeadingItem = require( './HeadingItem.js' ),
	utils = require( './utils.js' );

/**
 * Draw a semi-transparent rectangle on the page to highlight the given thread item.
 *
 * @class
 * @param {ThreadItem|ThreadItem[]} items Thread item(s) to highlight
 */
function Highlight( items ) {
	var highlightNodes = [];
	var ranges = [];

	this.topmostElement = null;

	items = Array.isArray( items ) ? items : [ items ];

	this.rootNode = items[ 0 ] ? items[ 0 ].rootNode : null;

	items.forEach( function ( item ) {
		var $highlight = $( '<div>' ).addClass( 'ext-discussiontools-init-highlight' );

		// We insert the highlight in the DOM near the thead item, so that it remains positioned correctly
		// when it shifts (e.g. collapsing the table of contents), and disappears when it is hidden (e.g.
		// opening visual editor).
		var range = item.getRange();
		// Support: Firefox
		// The highlight node must be inserted after the start marker node (data-mw-comment-start), not
		// before, otherwise Node#getBoundingClientRect() returns wrong results.
		range.insertNode( $highlight[ 0 ] );

		// If the item is a top-level comment wrapped in a frame, highlight outside that frame
		if ( item.level === 1 ) {
			var coveredSiblings = utils.getFullyCoveredSiblings( item, item.rootNode );
			if ( coveredSiblings ) {
				range.setStartBefore( coveredSiblings[ 0 ] );
				range.setEndAfter( coveredSiblings[ coveredSiblings.length - 1 ] );
			}
		}

		// If the item is a heading, highlight the full extent of it (not only the text)
		if ( item instanceof HeadingItem && !item.placeholderHeading ) {
			range.selectNode(
				$highlight.closest( '.mw-heading' )[ 0 ] ||
				$highlight.closest( 'h1, h2, h3, h4, h5, h6' )[ 0 ]
			);
		}

		ranges.push( range );
		highlightNodes.push( $highlight[ 0 ] );
	} );

	this.ranges = ranges;
	this.$element = $( highlightNodes );

	// Events
	this.updateDebounced = OO.ui.debounce( this.update.bind( this ), 500 );
	window.addEventListener( 'resize', this.updateDebounced );

	if ( OO.ui.isMobile() ) {
		// In MobileFrontend, ensure the section we are highlighting within is expanded. This is
		// often the case as we add the hash fragment to our notification URLs, but not when we highlight
		// comments across multiple threads.
		// HACK: Ideally MF would expose Toggler as a public API, but for now just find the correct DOM
		// node and trigger a fake click.
		this.$element.parents( '.collapsible-block' ).prev( '.collapsible-heading:not( .open-block )' ).trigger( 'click' );
	}

	this.update();
}

OO.initClass( Highlight );

/**
 * Update position of highlights, e.g. after window resize
 */
Highlight.prototype.update = function () {
	var highlight = this;
	this.$element.css( {
		'margin-top': '',
		'margin-left': '',
		'margin-right': '',
		width: '',
		height: ''
	} );
	var rootRect = this.rootNode.getBoundingClientRect();
	this.topmostElement = null;
	var topmostTop = Infinity;
	this.ranges.forEach( function ( range, i ) {
		var $element = highlight.$element.eq( i );
		var baseRect = $element[ 0 ].getBoundingClientRect();
		var rect = RangeFix.getBoundingClientRect( range );
		// rect may be null if the range is in a detached or hidden node
		if ( rect ) {
			// Draw the highlight over the full width of the page, except for very short comments
			// (less than 1/3 of the available width).
			//
			// This lets the far edges of almost all comments align nicely (T309444#7968858),
			// while still accurately highlighting comments in narrow floating boxes, such as
			// image captions or {{archive top}}.
			//
			// It seems difficult to distinguish the floating boxes from comments that are just
			// very short or very deeply indented, and this seems to work well enough in practice.
			var useFullWidth = rect.width > rootRect.width / 3;

			var headingTopAdj = 0;
			if (
				featuresEnabled.visualenhancements &&
				$element.closest( '.ext-discussiontools-init-section' ).length
			) {
				// Shift the highlight a little to avoid drawing over the separator border at the top,
				// and to cover the gap to the first comment at the bottom.
				headingTopAdj = 10;
			}

			var top = rect.top - baseRect.top;
			var width = rect.width;
			var height = rect.height;
			var left, right;
			if ( $element.css( 'direction' ) === 'ltr' ) {
				left = rect.left - baseRect.left;
				if ( useFullWidth ) {
					width = rootRect.width - ( rect.left - rootRect.left );
				}
			} else {
				right = ( baseRect.left + baseRect.width ) - ( rect.left + rect.width );
				if ( useFullWidth ) {
					width = rootRect.width - ( ( rootRect.left + rootRect.width ) - ( rect.left + rect.width ) );
				}
			}
			var padding = 5;
			$element.css( {
				'margin-top': top - padding + headingTopAdj,
				'margin-left': left !== undefined ? left - padding : '',
				'margin-right': right !== undefined ? right - padding : '',
				width: width + ( padding * 2 ),
				height: height + ( padding * 2 )
			} );

			if ( rect.top < topmostTop ) {
				highlight.topmostElement = $element[ 0 ];
				topmostTop = rect.top;
			}
		}
	} );
};

/**
 * Scroll the topmost highlight into view
 */
Highlight.prototype.scrollIntoView = function () {
	if ( this.topmostElement ) {
		this.topmostElement.scrollIntoView();
	}
};

/**
 * Destroy the highlight
 */
Highlight.prototype.destroy = function () {
	this.$element.remove();
	window.removeEventListener( 'resize', this.updateDebounced );
};

var highlightedTarget = null;
/**
 * Highlight the thread item(s) on the page associated with the URL hash or query string
 *
 * @param {ThreadItemSet} threadItemSet
 * @param {boolean} [noScroll] Don't scroll to the topmost highlight, e.g. on popstate
 * @return {Object} Object containing 'requested' IDs, 'requestedSince' ID, and successfully 'highligted' IDs
 */
function highlightTargetComment( threadItemSet, noScroll ) {
	if ( highlightedTarget ) {
		highlightedTarget.destroy();
		highlightedTarget = null;
	}

	var targetElement = mw.util.getTargetFromFragment();

	if ( targetElement && targetElement.hasAttribute( 'data-mw-comment-start' ) ) {
		var threadItemId = targetElement.getAttribute( 'id' );
		var threadItem = threadItemSet.findCommentById( targetElement.getAttribute( 'id' ) );
		if ( threadItem ) {
			highlightedTarget = new Highlight( threadItem );
			highlightedTarget.$element.addClass( 'ext-discussiontools-init-targetcomment' );
			highlightedTarget.$element.addClass( 'ext-discussiontools-init-highlight-fadein' );
		}
		return {
			requested: [ threadItemId ],
			highlighted: [ threadItemId ]
		};
	}

	// Single thread item not highlighted yet
	if ( location.hash.match( /^#(c|h)-/ ) ) {
		return {
			requested: [ location.hash.slice( 1 ) ],
			highlighted: []
		};
	}

	var url = new URL( location.href );
	return highlightNewComments(
		threadItemSet,
		noScroll,
		url.searchParams.get( 'dtnewcomments' ) && url.searchParams.get( 'dtnewcomments' ).split( '|' ),
		{
			newCommentsSinceId: url.searchParams.get( 'dtnewcommentssince' ),
			inThread: url.searchParams.get( 'dtinthread' ),
			sinceThread: url.searchParams.get( 'dtsincethread' )
		}
	);
}

/**
 * Highlight a just-published comment/topic
 *
 * These highlights show for a short period of time then tear themselves down.
 *
 * @param {ThreadItemSet} threadItemSet Thread item set
 * @param {string} threadItemId Thread item ID (NEW_TOPIC_COMMENT_ID for the a new topic)
 */
function highlightPublishedComment( threadItemSet, threadItemId ) {
	var highlightComments = [];

	if ( threadItemId === utils.NEW_TOPIC_COMMENT_ID ) {
		// Highlight the last comment on the page
		var lastComment = threadItemSet.threadItems[ threadItemSet.threadItems.length - 1 ];
		lastHighlightedPublishedComment = lastComment;
		highlightComments.push( lastComment );

		// If it's the only comment under its heading, highlight the heading too.
		// (It might not be if the new discussion topic was posted without a title: T272666.)
		if (
			lastComment.parent &&
			lastComment.parent.type === 'heading' &&
			lastComment.parent.replies.length === 1
		) {
			highlightComments.push( lastComment.parent );
			lastHighlightedPublishedComment = lastComment.parent;
			// Change URL to point to this section, like the old section=new wikitext editor does.
			// This also expands collapsed sections on mobile (T301840).
			var sectionTitle = lastHighlightedPublishedComment.getLinkableTitle();
			var urlFragment = mw.util.escapeIdForLink( sectionTitle );
			// Navigate to fragment without scrolling
			location.hash = '#' + urlFragment + '-DoesNotExist-DiscussionToolsHack';
			history.replaceState( null, '', '#' + urlFragment );
		}
	} else {
		// Find the comment we replied to, then highlight the last reply
		var repliedToComment = threadItemSet.threadItemsById[ threadItemId ];
		highlightComments.push( repliedToComment.replies[ repliedToComment.replies.length - 1 ] );
		lastHighlightedPublishedComment = highlightComments[ 0 ];
	}

	// We may have changed the location hash on mobile, so wait for that to cause
	// the section to expand before drawing the highlight.
	setTimeout( function () {
		var highlight = new Highlight( highlightComments );
		highlight.$element.addClass( 'ext-discussiontools-init-publishedcomment' );

		// Show a highlight with the same timing as the post-edit message (mediawiki.action.view.postEdit):
		// show for 3000ms, fade out for 250ms (animation duration is defined in CSS).
		OO.ui.Element.static.scrollIntoView(
			highlight.topmostElement,
			{
				padding: {
					// Add padding to avoid overlapping the post-edit notification (above on desktop, below on mobile)
					top: OO.ui.isMobile() ? 10 : 60,
					bottom: OO.ui.isMobile() ? 85 : 10
				},
				// Specify scrollContainer for compatibility with MobileFrontend.
				// Apparently it makes `<dd>` elements scrollable and OOUI tried to scroll them instead of body.
				scrollContainer: OO.ui.Element.static.getRootScrollableElement( highlight.topmostElement )
			}
		).then( function () {
			highlight.$element.addClass( 'ext-discussiontools-init-highlight-fadein' );
			setTimeout( function () {
				highlight.$element.addClass( 'ext-discussiontools-init-highlight-fadeout' );
				setTimeout( function () {
					// Remove the node when no longer needed, because it's using CSS 'mix-blend-mode', which
					// affects the text rendering of the whole page, disabling subpixel antialiasing on Windows
					highlight.destroy();
				}, 250 );
			}, 3000 );
		} );
	} );
}

/**
 * Highlight the new comments on the page associated with the query string
 *
 * @param {ThreadItemSet} threadItemSet
 * @param {boolean} [noScroll] Don't scroll to the topmost highlighted comment, e.g. on popstate
 * @param {string[]} [newCommentIds] A list of comment IDs to highlight
 * @param {Object} [options] Extra options
 * @param {string} [options.newCommentsSinceId] Highlight all comments after the comment with this ID
 * @param {boolean} [options.inThread] When using newCommentsSinceId, only highlight comments in the same thread
 * @param {boolean} [options.sinceThread] When using newCommentsSinceId, only highlight comments in threads
 *  created since that comment was posted.
 * @return {Object} Object containing 'requested' comment IDs, 'requestedSince' ID, and successfully 'highligted' IDs
 */
function highlightNewComments( threadItemSet, noScroll, newCommentIds, options ) {
	if ( highlightedTarget ) {
		highlightedTarget.destroy();
		highlightedTarget = null;
	}

	newCommentIds = newCommentIds || [];
	options = options || {};

	if ( options.newCommentsSinceId ) {
		var newCommentsSince = threadItemSet.findCommentById( options.newCommentsSinceId );
		if ( newCommentsSince && newCommentsSince instanceof CommentItem ) {
			var sinceTimestamp = newCommentsSince.timestamp;
			var threadItems;
			if ( options.inThread ) {
				var heading = newCommentsSince.getSubscribableHeading() || newCommentsSince.getHeading();
				threadItems = heading.getThreadItemsBelow();
			} else {
				threadItems = threadItemSet.getCommentItems();
			}
			threadItems.forEach( function ( threadItem ) {
				if (
					threadItem instanceof CommentItem &&
					threadItem.timestamp >= sinceTimestamp
				) {
					if ( options.sinceThread ) {
						// Check that we are in a thread that is newer than `sinceTimestamp`.
						// Thread age is determined by looking at getOldestReply.
						var itemHeading = threadItem.getSubscribableHeading() || threadItem.getHeading();
						var oldestReply = itemHeading.getOldestReply();
						if ( !( oldestReply && oldestReply.timestamp >= sinceTimestamp ) ) {
							return;
						}
					}
					newCommentIds.push( threadItem.id );
				}
			} );
		}
	}

	var comments;
	if ( newCommentIds.length ) {
		comments = newCommentIds.map( function ( id ) {
			return threadItemSet.findCommentById( id );
		} ).filter( function ( cmt ) {
			return !!cmt;
		} );
		if ( comments.length ) {
			highlightedTarget = new Highlight( comments );
			highlightedTarget.$element.addClass( 'ext-discussiontools-init-targetcomment' );
			highlightedTarget.$element.addClass( 'ext-discussiontools-init-highlight-fadein' );

			if ( !noScroll ) {
				highlightedTarget.scrollIntoView();
			}
		}
	}
	return {
		requested: newCommentIds,
		requestedSince: options.newCommentsSinceId,
		highlighted: comments || []
	};
}

/**
 * Clear the highlighting of the comment in the URL hash
 *
 * @param {ThreadItemSet} threadItemSet
 */
function clearHighlightTargetComment( threadItemSet ) {
	var url = new URL( location.href );

	var targetElement = mw.util.getTargetFromFragment();

	if ( targetElement && targetElement.hasAttribute( 'data-mw-comment-start' ) ) {
		// Clear the hash from the URL, triggering the 'hashchange' event and updating the :target
		// selector (so that our code to clear our highlight works), but without scrolling anywhere.
		// This is tricky because:
		// * Using history.pushState() does not trigger 'hashchange' or update the :target selector.
		//   https://developer.mozilla.org/en-US/docs/Web/API/History/pushState#description
		//   https://github.com/whatwg/html/issues/639
		// * Using location.hash does, but it also scrolls to the target, which is the top of the
		//   page for the empty hash.
		// Instead, we first use location.hash to navigate to a *different* hash (whose target
		// doesn't exist on the page, hopefully), and then use history.pushState() to clear it.
		location.hash += '-DoesNotExist-DiscussionToolsHack';
		url.hash = '';
		history.replaceState( null, '', url );
	} else if (
		url.searchParams.has( 'dtnewcomments' ) ||
		url.searchParams.has( 'dtnewcommentssince' )
	) {
		url.searchParams.delete( 'dtnewcomments' );
		url.searchParams.delete( 'dtnewcommentssince' );
		url.searchParams.delete( 'dtinthread' );
		url.searchParams.delete( 'dtsincethread' );
		history.pushState( null, '', url );
		highlightTargetComment( threadItemSet );
	} else if ( highlightedTarget ) {
		// Highlights were applied without changing the URL, e.g. when showing
		// new comments while drafting. Just clear the highlights.
		highlightedTarget.destroy();
		highlightedTarget = null;
	}
}

/**
 * Get the last highlighted just-published comment, if any
 *
 * Used to show an auto-subscription popup to first-time users
 *
 * @return {ThreadItem|null}
 */
function getLastHighlightedPublishedComment() {
	return lastHighlightedPublishedComment;
}

module.exports = {
	highlightTargetComment: highlightTargetComment,
	highlightPublishedComment: highlightPublishedComment,
	highlightNewComments: highlightNewComments,
	clearHighlightTargetComment: clearHighlightTargetComment,
	getLastHighlightedPublishedComment: getLastHighlightedPublishedComment
};
