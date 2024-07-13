/*!
 * VisualEditor ContentEditable namespace.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Namespace for all VisualEditor ContentEditable classes, static methods and static properties.
 *
 * @class
 * @singleton
 */
ve.ce = {
	// nodeFactory: Initialized in ve.ce.NodeFactory.js
};

/* Static Properties */

/**
 * Data URI for minimal GIF image. This is the smallest technically-valid 1x1px transparent GIF it's possible to create.
 */
ve.ce.minImgDataUri = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

/* Static Methods */

/**
 * Gets the plain text of a DOM element (that is a node canContainContent === true)
 *
 * In the returned string only the contents of text nodes are included, and the contents of
 * non-editable elements are excluded (but replaced with the appropriate number of snowman
 * characters so the offsets match up with the linear model).
 *
 * @param {HTMLElement} element DOM element to get text of
 * @return {string} Plain text of DOM element
 */
ve.ce.getDomText = function ( element ) {
	// Inspired by jQuery.text / Sizzle.getText
	var nodeType = element.nodeType,
		text = '';

	if (
		nodeType === Node.ELEMENT_NODE ||
		nodeType === Node.DOCUMENT_NODE ||
		nodeType === Node.DOCUMENT_FRAGMENT_NODE
	) {
		if ( element.classList.contains( 've-ce-branchNode-blockSlug' ) ) {
			// Block slugs are not represented in the model at all, but they do
			// contain a single nbsp/FEFF character in the DOM, so make sure
			// that character isn't counted
			return '';
		} else if ( element.classList.contains( 've-ce-cursorHolder' ) ) {
			// Cursor holders do not exist in the model
			return '';
		} else if ( element.classList.contains( 've-ce-leafNode' ) ) {
			// For leaf nodes, don't return the content, but return
			// the right number of placeholder characters so the offsets match up.
			var viewNode = $( element ).data( 'view' );
			// Only return snowmen for the first element in a sibling group: otherwise
			// we'll double-count this node
			if ( viewNode && element === viewNode.$element[ 0 ] ) {
				// \u2603 is the snowman character: ☃
				return new Array( viewNode.getOuterLength() + 1 ).join( '\u2603' );
			}
			// Second or subsequent sibling, don't double-count
			return '';
		} else {
			// Traverse its children
			for ( element = element.firstChild; element; element = element.nextSibling ) {
				text += ve.ce.getDomText( element );
			}
		}
	} else if ( nodeType === Node.TEXT_NODE ) {
		return element.data;
	}
	return text;
};

/**
 * Gets a hash of a DOM element's structure.
 *
 * In the returned string text nodes are represented as "#" and elements are represented as "<type>"
 * and "</type>" where "type" is their element name. This effectively generates an HTML
 * serialization without any attributes or text contents. This can be used to observe structural
 * changes.
 *
 * @param {HTMLElement} element DOM element to get hash of
 * @return {string} Hash of DOM element
 */
ve.ce.getDomHash = function ( element ) {
	var nodeType = element.nodeType,
		nodeName = element.nodeName,
		hash = '';

	if ( nodeType === Node.TEXT_NODE || nodeType === Node.CDATA_SECTION_NODE ) {
		return '#';
	} else if ( nodeType === Node.ELEMENT_NODE || nodeType === Node.DOCUMENT_NODE ) {
		if ( !(
			element.classList.contains( 've-ce-branchNode-blockSlug' ) ||
			element.classList.contains( 've-ce-cursorHolder' ) ||
			element.classList.contains( 've-ce-nail' )
		) ) {
			hash += '<' + nodeName + '>';
			// Traverse its children
			for ( element = element.firstChild; element; element = element.nextSibling ) {
				hash += ve.ce.getDomHash( element );
			}
			hash += '</' + nodeName + '>';
		}
		// Merge adjacent text node representations
		hash = hash.replace( /##+/g, '#' );
	}
	return hash;
};

/**
 * Get the first cursor offset immediately after a node.
 *
 * @param {Node} node DOM node
 * @return {Object}
 * @return {Node} return.node
 * @return {number} return.offset
 */
ve.ce.nextCursorOffset = function ( node ) {
	var nextNode, offset;
	if ( node.nextSibling !== null && node.nextSibling.nodeType === Node.TEXT_NODE ) {
		nextNode = node.nextSibling;
		offset = 0;
	} else {
		nextNode = node.parentNode;
		offset = 1 + ve.parentIndex( node );
	}
	return { node: nextNode, offset: offset };
};

/**
 * Get the first cursor offset immediately before a node.
 *
 * @param {Node} node DOM node
 * @return {Object}
 * @return {Node} return.node
 * @return {number} return.offset
 */
ve.ce.previousCursorOffset = function ( node ) {
	var previousNode, offset;
	if ( node.previousSibling !== null && node.previousSibling.nodeType === Node.TEXT_NODE ) {
		previousNode = node.previousSibling;
		offset = previousNode.data.length;
	} else {
		previousNode = node.parentNode;
		offset = ve.parentIndex( node );
	}
	return { node: previousNode, offset: offset };
};

/**
 * Gets the linear offset from a given DOM node and offset within it.
 *
 * @param {HTMLElement} domNode DOM node
 * @param {number} domOffset DOM offset within the DOM node
 * @return {number} Linear model offset
 * @throws {Error} domOffset is out of bounds
 * @throws {Error} domNode has no ancestor with a .data( 'view' )
 * @throws {Error} domNode is not in document
 */
ve.ce.getOffset = function ( domNode, domOffset ) {
	if ( domNode.nodeType === Node.ELEMENT_NODE && domNode.classList.contains( 've-ce-unicorn' ) ) {
		if ( domOffset !== 0 ) {
			throw new Error( 'Non-zero offset in unicorn' );
		}
		return $( domNode ).data( 'modelOffset' );
	}

	/**
	 * Move to the previous "traversal node" in "traversal sequence".
	 *
	 * - A node is a "traversal node" if it is either a leaf node or a "view node"
	 * - A "view node" is one that has $( n ).data( 'view' ) instanceof ve.ce.Node
	 * - "Traversal sequence" is defined on every node (not just traversal nodes).
	 *   It is like document order, except that each parent node appears
	 *   in the sequence both immediately before and immediately after its child nodes.
	 *
	 * Important properties:
	 * - Non-traversal nodes don't have any width in DM (e.g. bold).
	 * - Certain traversal nodes also have no width (namely, those within an alienated node).
	 * - Both the start and end of a (non-alienated) parent traversal node has width
	 *   (which is one reason why traversal sequence is important).
	 * - In VE-normalized HTML, a text node cannot be a sibling of a non-leaf view node
	 *   (because all non-alienated text nodes are inside a ContentBranchNode).
	 * - Traversal-consecutive non-view nodes are either all alienated or all not alienated.
	 *
	 * @param {Node} n Node to traverse from
	 * @return {Node} Previous traversal node from n
	 * @throws {Error} domNode has no ancestor with a .data( 'view' )
	 */
	function traverse( n ) {
		while ( !n.previousSibling ) {
			n = n.parentNode;
			if ( !n ) {
				throw new Error( 'domNode has no ancestor with a .data( \'view\' )' );
			}
			if ( $( n ).data( 'view' ) instanceof ve.ce.Node ) {
				return n;
			}
		}
		n = n.previousSibling;
		if ( $( n ).data( 'view' ) instanceof ve.ce.Node ) {
			return n;
		}
		while ( n.lastChild ) {
			n = n.lastChild;
			if ( $( n ).data( 'view' ) instanceof ve.ce.Node ) {
				return n;
			}
		}
		return n;
	}

	// Validate domOffset
	var maxOffset;
	if ( domNode.nodeType === Node.ELEMENT_NODE ) {
		maxOffset = domNode.childNodes.length;
	} else {
		maxOffset = domNode.data.length;
	}
	if ( domOffset < 0 || domOffset > maxOffset ) {
		throw new Error( 'domOffset is out of bounds' );
	}

	var lengthSum = 0;
	var startNode, node, view;
	// Figure out what node to start traversing at (startNode)
	if ( domNode.nodeType === Node.ELEMENT_NODE ) {
		if ( domNode.childNodes.length === 0 ) {
			// domNode has no children, and the offset is inside of it
			// If domNode is a view node, return the offset inside of it
			// Otherwise, start traversing at domNode
			startNode = domNode;
			view = $( startNode ).data( 'view' );
			if ( view instanceof ve.ce.Node ) {
				return view.getOffset() + ( view.isWrapped() ? 1 : 0 );
			}
			node = startNode;
		} else if ( domOffset === domNode.childNodes.length ) {
			// Offset is at the end of domNode, after the last child. Set startNode to the
			// very rightmost descendant node of domNode (i.e. the last child of the last child
			// of the last child, etc.)
			// However, if the last child or any of the last children we encounter on the way
			// is a view node, return the offset after it. This will be the correct return value
			// because non-traversal nodes don't have a DM width.
			startNode = domNode.lastChild;

			view = $( startNode ).data( 'view' );
			if ( view instanceof ve.ce.Node ) {
				return view.getOffset() + view.getOuterLength();
			}
			while ( startNode.lastChild ) {
				startNode = startNode.lastChild;
				view = $( startNode ).data( 'view' );
				if ( view instanceof ve.ce.Node ) {
					return view.getOffset() + view.getOuterLength();
				}
			}
			node = startNode;
		} else {
			// Offset is right before childNodes[domOffset]. Set startNode to this node
			// (i.e. the node right after the offset), then traverse back once.
			startNode = domNode.childNodes[ domOffset ];
			node = traverse( startNode );
		}
	} else {
		// Text inside of a block slug doesn't count
		if ( !(
			domNode.parentNode.classList.contains( 've-ce-branchNode-blockSlug' ) ||
			domNode.parentNode.classList.contains( 've-ce-cursorHolder' )
		) ) {
			lengthSum += domOffset;
		}
		startNode = domNode;
		node = traverse( startNode );
	}

	// Walk the traversal nodes in reverse traversal sequence, until we find a view node.
	// Add the width of each text node we meet. (Non-text node non-view nodes can only be widthless).
	// Later, if it transpires that we're inside an alienated node, then we will throw away all the
	// text node lengths, because the alien's content has no DM width.
	while ( true ) {
		// First node that has a ve.ce.Node, stop
		// Note that annotations have a .data( 'view' ) too, but that's a ve.ce.Annotation,
		// not a ve.ce.Node
		view = $( node ).data( 'view' );
		if ( view instanceof ve.ce.Node ) {
			break;
		}

		// Text inside of a block slug doesn't count
		if (
			node.nodeType === Node.TEXT_NODE &&
			!node.parentNode.classList.contains( 've-ce-branchNode-blockSlug' ) &&
			!node.parentNode.classList.contains( 've-ce-cursorHolder' )
		) {
			lengthSum += node.data.length;
		}
		// else: non-text nodes that don't have a .data( 'view' ) don't exist in the DM
		node = traverse( node );
	}

	var offset = view.getOffset();

	if ( $.contains( node, startNode ) ) {
		// node is an ancestor of startNode
		if ( !view.getModel().isContent() ) {
			// Add 1 to take the opening into account
			offset += view.getModel().isWrapped() ? 1 : 0;
		}
		if ( view.getModel().canContainContent() ) {
			offset += lengthSum;
		}
		// else: we're inside an alienated node: throw away all the text node lengths,
		// because the alien's content has no DM width
	} else if ( view.parent ) {
		// node is not an ancestor of startNode
		// startNode comes after node, so add node's length
		offset += view.getOuterLength();
		if ( view.isContent() ) {
			// view is a leaf node inside of a CBN, so we started inside of a CBN
			// (otherwise we would have hit the CBN when entering it), so the text we summed up
			// needs to be counted.
			offset += lengthSum;
		}
	} else {
		throw new Error( 'Node is not in document' );
	}

	return offset;
};

/**
 * Gets the linear offset of a given slug
 *
 * @param {HTMLElement} element Slug DOM element
 * @return {number} Linear model offset
 * @throws {Error}
 */
ve.ce.getOffsetOfSlug = function ( element ) {
	var $element = $( element );
	var model;
	if ( $element.index() === 0 ) {
		model = $element.parent().data( 'view' ).getModel();
		return model.getOffset() + ( model.isWrapped() ? 1 : 0 );
	} else {
		// Don't pick up DOM nodes not from the view tree e.g. cursor holders (T202103)
		var $prev = $element.prevAll( '.ve-ce-leafNode,.ve-ce-branchNode' ).first();
		if ( $prev.length ) {
			model = $prev.data( 'view' ).getModel();
			return model.getOffset() + model.getOuterLength();
		}
		throw new Error( 'Incorrect slug location' );
	}
};

/**
 * Test whether the DOM position lies straight after annotation boundaries
 *
 * "Straight after" means that in document order, there are annotation open/close tags
 * immediately before the position, and there are none immediately after.
 *
 * This is important for cursors: the DM position is ambiguous with respect to annotation
 * boundaries, and the browser does not fully distinguish this position from the preceding
 * position immediately before the annotation boundaries (e.g. 'a|&lt;b&gt;c' and 'a&lt;b&gt;|c'),
 * but the two positions behave differently for insertions (in this case, whether the text
 * appears bolded or not).
 *
 * In Chromium, cursor focus normalizes to the earliest (in document order) of equivalent
 * positions, at least in reasonably-styled non-BIDI text. But in Firefox, the user can
 * cursor/click into either the earliest or the latest equivalent position: the cursor lands in
 * the closest (in document order) to the click location (for mouse actions) or cursor start
 * location (for cursoring).
 *
 * @param {Node} node Position node
 * @param {number} offset Position offset
 * @return {boolean} Whether this is the end-most of multiple cursor-equivalent positions
 */
ve.ce.isAfterAnnotationBoundary = function ( node, offset ) {
	if ( node.nodeType === Node.TEXT_NODE ) {
		if ( offset > 0 ) {
			return false;
		}
		offset = ve.parentIndex( node );
		node = node.parentNode;
	}
	if ( offset === 0 ) {
		return ve.dm.modelRegistry.isAnnotation( node );
	}

	var previousNode = node.childNodes[ offset - 1 ];
	if ( previousNode.nodeType === Node.ELEMENT_NODE && (
		previousNode.classList.contains( 've-ce-nail-post-close' ) ||
		previousNode.classList.contains( 've-ce-nail-post-open' )
	) ) {
		return true;
	}
	return ve.dm.modelRegistry.isAnnotation( previousNode );
};

/**
 * Check if keyboard shortcut modifier key is pressed.
 *
 * @param {jQuery.Event} e Key press event
 * @return {boolean} Modifier key is pressed
 */
ve.ce.isShortcutKey = function ( e ) {
	return !!( e.ctrlKey || e.metaKey );
};

/**
 * Find the DM range of a DOM selection
 *
 * @param {Object} selection DOM-selection-like object
 * @param {Node} selection.anchorNode
 * @param {number} selection.anchorOffset
 * @param {Node} selection.focusNode
 * @param {number} selection.focusOffset
 * @return {ve.Range|null} DM range, or null if nothing in the CE document is selected
 */
ve.ce.veRangeFromSelection = function ( selection ) {
	try {
		return new ve.Range(
			ve.ce.getOffset( selection.anchorNode, selection.anchorOffset ),
			ve.ce.getOffset( selection.focusNode, selection.focusOffset )
		);
	} catch ( e ) {
		return null;
	}
};

/**
 * Find the closest nailed annotation in which a node lies
 *
 * @param {Node|null} node The node to test
 * @return {Node|null} The closest nailed annotation within which the node lies (possibly the node itself)
 */
ve.ce.nailedAnnotationAt = function ( node ) {
	if ( node && node.nodeType === Node.TEXT_NODE ) {
		node = node.parentNode;
	}
	return $( node ).closest( '.ve-ce-nailedAnnotation' )[ 0 ];
};

/**
 * Check whether a given DOM element is an inline annotation
 *
 * @param {Node} element The element
 * @return {boolean} Whether the element is an inline annotation
 */
ve.ce.isAnnotationElement = function ( element ) {
	return !(
		ve.isBlockElement( element ) ||
		ve.isVoidElement( element ) ||
		element.classList.contains( 've-ce-branchNode-slug' )
	);
};
