/*!
 * VisualEditor ContentEditable Document class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable document.
 *
 * @class
 * @extends ve.Document
 *
 * @constructor
 * @param {ve.dm.Document} model Model to observe
 * @param {ve.ce.Surface} surface Surface document is part of
 */
ve.ce.Document = function VeCeDocument( model, surface ) {
	// Parent constructor
	ve.ce.Document.super.call( this, new ve.ce.DocumentNode( model.getDocumentNode(), surface ) );

	this.lang = null;
	this.dir = null;

	this.setLang( model.getLang() );
	this.setDir( model.getDir() );

	// Properties
	this.model = model;
};

/* Inheritance */

OO.inheritClass( ve.ce.Document, ve.Document );

/* Events */

/**
 * Language or direction changed
 *
 * @event langChange
 */

/* Methods */

/**
 * Set the document view language
 *
 * @param {string} lang Language code
 */
ve.ce.Document.prototype.setLang = function ( lang ) {
	this.getDocumentNode().$element.prop( 'lang', lang );
	this.lang = lang;
	this.emit( 'langChange' );
};

/**
 * Set the document view directionality
 *
 * @param {string} dir Directionality (ltr/rtl)
 */
ve.ce.Document.prototype.setDir = function ( dir ) {
	this.getDocumentNode().$element.prop( 'dir', dir );
	this.dir = dir;
	this.emit( 'langChange' );
};

/**
 * Get the document view language
 *
 * @return {string} Language code
 */
ve.ce.Document.prototype.getLang = function () {
	return this.lang;
};

/**
 * Get the document view directionality
 *
 * @return {string} Directionality (ltr/rtl)
 */
ve.ce.Document.prototype.getDir = function () {
	return this.dir;
};

/**
 * Get a slug at an offset.
 *
 * @param {number} offset Offset to get slug at
 * @return {HTMLElement} Slug at offset
 */
ve.ce.Document.prototype.getSlugAtOffset = function ( offset ) {
	var node = this.getBranchNodeFromOffset( offset );
	return node ? node.getSlugAtOffset( offset ) : null;
};

/**
 * Calculate the DOM position corresponding to a DM offset
 *
 * If there are multiple DOM locations, heuristically pick the best one for cursor placement
 *
 * @private
 * @param {number} offset Linear model offset
 * @return {Object} position
 * @return {Node} return.node position node
 * @return {number} return.offset position offset within the node
 * @throws {Error} Offset could not be translated to a DOM element and offset
 */
ve.ce.Document.prototype.getNodeAndOffset = function ( offset ) {
	var countedNodes = [];

	// 1. Step with ve.adjacentDomPosition( …, { stop: function () { return true; } } )
	// until we hit a position at the correct offset (which is guaranteed to be the first
	// such position in document order).
	// 2. Use ve.adjacentDomPosition( …, { stop: … } ) once to return all
	// subsequent positions at the same offset.
	// 3. Look at the possible positions and pick as follows:
	//   - If there is a unicorn, return just inside it
	//   - Else if there is a nail, return just outside it
	//   - Else if there is a text node, return an offset in it
	//   - Else return the first matching offset
	//
	// Offsets of DOM nodes are counted to match their model equivalents.
	//
	// TODO: take the following into account:
	// Unfortunately, there is no way to avoid slugless block nodes with no DM length: an
	// IME can remove all the text from a node at a time when it is unsafe to fixup the node
	// contents. In this case, a maximally deep element gives better bounding rectangle
	// coordinates than any of its containers.

	if ( !this.model.getDocumentRange().containsRange( new ve.Range( offset ) ) ) {
		throw new Error( 'Offset is out of bounds' );
	}
	var branchNode = this.getBranchNodeFromOffset( offset );
	var count = branchNode.getOffset() + ( branchNode.isWrapped() ? 1 : 0 );

	var node;
	if ( !( branchNode instanceof ve.ce.ContentBranchNode ) ) {
		// The cursor does not lie in a ContentBranchNode, so we can determine
		// everything from the DM tree
		var i, ceChild;
		for ( i = 0; ; i++ ) {
			ceChild = branchNode.children[ i ];
			if ( count === offset ) {
				break;
			}
			if ( !ceChild ) {
				throw new Error( 'Offset lies beyond branchNode' );
			}
			count += ceChild.getOuterLength();
			if ( count > offset ) {
				if ( ceChild.getOuterLength() !== 2 ) {
					throw new Error( 'Offset lies inside child of strange size' );
				}
				node = ceChild.$element[ 0 ];
				if ( node ) {
					return { node: node, offset: 0 };
				}
				// Else ceChild has no DOM representation; step forwards
				break;
			}
		}
		// Offset lies directly in branchNode, just before ceChild
		node = branchNode.$element[ 0 ];
		while ( ceChild && !ceChild.$element[ 0 ] ) {
			// Node does not have a DOM representation; move forwards past it
			i++;
			ceChild = branchNode.children[ i ];
		}
		if ( !ceChild || !ceChild.$element[ 0 ] ) {
			// Offset lies just at the end of branchNode
			return { node: node, offset: node.childNodes.length };
		}
		return {
			node: node,
			offset: Array.prototype.indexOf.call(
				node.childNodes,
				ceChild.$element[ 0 ]
			)
		};
	}

	// Else the cursor lies in a ContentBranchNode, so we must traverse the DOM, keeping
	// count of the corresponding DM position until it reaches offset.
	var position = { node: branchNode.$element[ 0 ], offset: 0 };

	function noDescend() {
		return this.classList.contains( 've-ce-branchNode-blockSlug' ) ||
			ve.rejectsCursor( this );
	}

	while ( true ) {
		if ( count === offset ) {
			break;
		}
		position = ve.adjacentDomPosition(
			position,
			1,
			{
				noDescend: noDescend,
				stop: function () {
					return true;
				}
			}
		);
		var step = position.steps[ 0 ];
		node = step.node;
		if ( node.nodeType === Node.TEXT_NODE ) {
			if ( step.type === 'leave' ) {
				// Skip without incrementing
				continue;
			}
			// else the code below always breaks or skips over the text node;
			// therefore it is guaranteed that step.type === 'enter' (we just
			// stepped in)
			// TODO: what about zero-length text nodes?
			if ( offset <= count + node.data.length ) {
				// Match the appropriate offset in the text node
				position = { node: node, offset: offset - count };
				break;
			} else {
				// Skip over the text node
				count += node.data.length;
				position = { node: node, offset: node.data.length };
				continue;
			}
		} // else it is an element node (TODO: handle comment etc)

		if ( !(
			node.classList.contains( 've-ce-branchNode' ) ||
			node.classList.contains( 've-ce-leafNode' )
		) ) {
			// Nodes like b, inline slug, browser-generated br that doesn't have
			// class ve-ce-leafNode: continue walk without incrementing
			continue;
		}

		if ( step.type === 'leave' ) {
			// Below we'll guarantee that .ve-ce-branchNode/.ve-ce-leafNode elements
			// are only entered if their open/close tags take up a model offset, so
			// we can increment unconditionally here
			count++;
			continue;
		} // else step.type === 'enter' || step.type === 'cross'

		var model = $.data( node, 'view' ).model;

		if ( countedNodes.indexOf( model ) !== -1 ) {
			// This DM node is rendered as multiple DOM elements, and we have already
			// counted it as part of an earlier element. Skip past without incrementing
			position = { node: node.parentNode, offset: ve.parentIndex( node ) + 1 };
			continue;
		}
		countedNodes.push( model );
		if ( offset >= count + model.getOuterLength() ) {
			// Offset doesn't lie inside the node. Skip past and count length
			// skip past the whole node
			position = { node: node.parentNode, offset: ve.parentIndex( node ) + 1 };
			count += model.getOuterLength();
		} else if ( step.type === 'cross' ) {
			if ( offset === count + 1 ) {
				// The offset lies inside the crossed node
				position = { node: node, offset: 0 };
				break;
			}
			count += 2;
		} else {
			count += 1;
		}
	}
	// Now "position" is the first DOM position (in document order) at the correct
	// model offset.

	// If the position is exactly after the first of multiple view nodes sharing a model,
	// then jump to the position exactly after the final such view node.
	var prevNode = position.node.childNodes[ position.offset - 1 ];
	if ( prevNode && prevNode.nodeType === Node.ELEMENT_NODE && (
		prevNode.classList.contains( 've-ce-branchNode' ) ||
		prevNode.classList.contains( 've-ce-leafNode' )
	) ) {
		var $viewNodes = $.data( prevNode, 'view' ).$element;
		if ( $viewNodes.length > 1 ) {
			position.node = $viewNodes.get( -1 ).parentNode;
			position.offset = 1 + ve.parentIndex( $viewNodes.get( -1 ) );
		}
	}

	// Find all subsequent DOM positions at the same model offset
	var found = {};
	function stop( s ) {
		var m;
		if ( s.node.nodeType === Node.TEXT_NODE ) {
			return s.type === 'internal';
		}

		if (
			s.node.classList.contains( 've-ce-branchNode' ) ||
			s.node.classList.contains( 've-ce-leafNode' )
		) {
			m = $.data( s.node, 'view' ).model;
			if ( countedNodes.indexOf( m ) !== -1 ) {
				return false;
			}
			countedNodes.push( m );
			return true;
		}
		return false;
	}
	var steps = ve.adjacentDomPosition( position, 1, { stop: stop, noDescend: noDescend } ).steps;
	steps.slice( 0, -1 ).forEach( function ( s ) {
		// Step type cannot be "internal", else the offset would have incremented
		var hasClass = function ( className ) {
			return s.node.nodeType === Node.ELEMENT_NODE &&
				s.node.classList.contains( className );
		};
		found.preUnicorn = found.preUnicorn || ( hasClass( 've-ce-pre-unicorn' ) && s );
		found.postUnicorn = found.postUnicorn || ( hasClass( 've-ce-post-unicorn' ) && s );
		found.preOpenNail = found.preOpenNail || ( hasClass( 've-ce-nail-pre-open' ) && s );
		found.postOpenNail = found.postOpenNail || ( hasClass( 've-ce-nail-post-open' ) && s );
		found.preCloseNail = found.preCloseNail || ( hasClass( 've-ce-nail-pre-close' ) && s );
		found.postCloseNail = found.postCloseNail || ( hasClass( 've-ce-nail-post-close' ) && s );
		found.focusableNode = found.focusableNode || ( hasClass( 've-ce-focusableNode' ) && s );
		found.text = found.text || ( s.node.nodeType === Node.TEXT_NODE && s );
	} );

	// If there is a unicorn, it should be a unique pre/post-Unicorn pair containing text or
	// nothing return the position just inside.
	if ( found.preUnicorn ) {
		return ve.ce.nextCursorOffset( found.preUnicorn.node );
	}
	if ( found.postUnicorn ) {
		return ve.ce.previousCursorOffset( found.postUnicorn.node );
	}

	if ( found.preOpenNail ) {
		// This will also cover the case where there is a post-open nail, as there will
		// be no offset difference between them
		return ve.ce.previousCursorOffset( found.preOpenNail.node );
	}
	if ( found.postCloseNail ) {
		// This will also cover the case where there is a pre-close nail, as there will
		// be no offset difference between them
		return ve.ce.nextCursorOffset( found.postCloseNail.node );
	}
	if ( found.text ) {
		if ( position.node.nodeType === Node.TEXT_NODE ) {
			return position;
		}
		// We must either have entered or left the text node
		return { node: found.text.node, offset: 0 };
	}
	return position;
};

/**
 * Get the block directionality of some range
 *
 * Uses the computed CSS direction value of the current node
 *
 * @param {ve.Range} range
 * @return {string} 'rtl', 'ltr'
 */
ve.ce.Document.prototype.getDirectionalityFromRange = function ( range ) {
	var selectedNodes = this.selectNodes( range, 'covered' );

	var effectiveNode;
	if ( selectedNodes.length > 1 ) {
		// Selection of multiple nodes
		// Get the common parent node
		effectiveNode = this.selectNodes( range, 'siblings' )[ 0 ].node.getParent();
	} else {
		// Selection of a single node
		effectiveNode = selectedNodes[ 0 ].node;

		while ( effectiveNode.isContent() ) {
			// This means that we're in a leaf node, like TextNode
			// those don't read the directionality properly, we will
			// have to climb up the parentage chain until we find a
			// wrapping node like paragraph or list item, etc.
			effectiveNode = effectiveNode.parent;
		}
	}

	return effectiveNode.$element.css( 'direction' );
};
