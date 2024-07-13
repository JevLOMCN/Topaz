/*!
 * VisualEditor DataModel TreeModifier class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel tree modifier, following the algorithm in T162762.
 *
 * The top-level process() method applies a transaction to the document in two stages. First,
 * calculateTreeOperations() translates the linear transaction into tree operations; then,
 * applyTreeOperations() updates both the linear model and the document tree simultaneously.
 *
 * Each tree operation takes one of the following forms:
 *
 * { type: 'insertNode', isContent: {boolean}, at: {Path}, element: {OpenElementLinModItem} }
 * { type: 'removeNode', isContent: {boolean}, at: {Path}, element: {OpenElementLinModItem} }
 * { type: 'moveNode', isContent: {boolean}, from: {Path}, to: {Path} }
 * { type: 'insertText', isContent: true, at: {Path}, data: {TextLinModItem[]} }
 * { type: 'removeText', isContent: true, at: {Path}, data: {TextLinModItem[]} }
 * { type: 'moveText', isContent: true, from: {Path}, to: {Path}, length: {number} }
 *
 * Note that moveNode/moveText do not specify what content is being moved, and all the Node
 * operations always operate on a single node at a time.
 *
 * {Path} is number[] representing the tree path from the DocumentNode to the position, except
 * that within ContentBranchNodes, the offset is the linearized offset of the position.
 *
 * {OpenElementLinModItem} is the linear model value representing the node being inserted or
 * removed, like { type: 'paragraph' } .
 *
 * {TextLinModItem[]} is the linear model values representing the text, like
 * [ 'y', 'o', 'u', ' ', [ 'm', [ 'he4e7c54e2204d10b ] ], [ 'e' 'he4e7c54e2204d10b ] ] .
 *
 * The isContent flag is true if the operation is taking place inside a ContentBranchNode
 * (so it is always true for text).
 *
 * NOTE: Instances of this class are not recyclable: you can only call .process( tx ) on them once.
 *
 * @class
 * @constructor
 */
ve.dm.TreeModifier = function VeDmTreeModifier() {
	// XXX we only need these two for dump() in tests
	this.document = null;
	this.insertions = null;

	/**
	 * @property {Array} data Live document linear data
	 */
	this.data = null;

	/**
	 * @property {ve.dm.Node[]} deletions Array (acting as set) of removed nodes
	 */
	this.deletions = null;

	/**
	 * @property {ve.dm.TreeCursor} remover Tree cursor for removals
	 */
	this.remover = null;

	/**
	 * @property {ve.dm.TreeCursor} inserter Tree cursor for insertions
	 */
	this.inserter = null;

	/**
	 * @property {Object[]} treeOps Array of tree ops being built
	 */
	this.treeOps = null;

	/**
	 * @property {number[]} insertedNodes Nodes to be inserted at context
	 */
	this.insertedNodes = null;

	/**
	 * @property {number[]} insertedPositions Position within nodes to be inserted
	 */
	this.insertedPositions = null;

	/**
	 * @property {Object} adjustmentTree Sparse tree, paths as indexes
	 * @property {number} adjustmentTree.inserted Child items inserted at this position
	 * @property {number} adjustmentTree.removed Child items removed at this position
	 * @property {Object} adjustmentTree.i Subtree at position i
	 */
	this.adjustmentTree = null;
};

OO.initClass( ve.dm.TreeModifier );

// Static methods

/**
 * Apply tree operations to document tree and linear data simultaneously
 *
 * @param {boolean} isReversed Whether the transaction is an undo
 * @param {ve.dm.Document} document The document to modify
 * @param {Object[]} treeOps The tree operations
 */
ve.dm.TreeModifier.static.applyTreeOperations = function ( isReversed, document, treeOps ) {
	for ( var i = 0, iLen = treeOps.length; i < iLen; i++ ) {
		this.applyTreeOperation( isReversed, document, treeOps[ i ] );
	}
};

/**
 * Throw an exception if two pieces of linear data are not equal
 *
 * @param {Array} actual Document linear data to test
 * @param {Array} expected Expected linear data to test against
 */
ve.dm.TreeModifier.static.checkEqualData = function ( actual, expected ) {
	function replacer( name, value ) {
		// TODO: replace this check with data equality class method checks
		if (
			name === 'generated' ||
			name === 'changesSinceLoad' ||
			name === 'originalDomElementsHash' ||
			name === 'originalMw' ||
			name === 'originalVariantInfo' ||
			name === 'mw' ||
			name === 'contentsUsed'
		) {
			return undefined;
		}
		// Drop .internal that would become empty after the replacements above
		if ( name === 'internal' && JSON.stringify( value, replacer ) === '{}' ) {
			return undefined;
		}
		return value;
	}

	var jActual = JSON.stringify( actual, replacer );
	var jExpected = JSON.stringify( expected, replacer );

	if ( jActual !== jExpected ) {
		throw new Error( 'Expected ' + jExpected + ' but got ' + jActual );
	}
};

/**
 * Apply a tree operation to document tree and linear data simultaneously
 *
 * @param {boolean} isReversed Whether the transaction is an undo
 * @param {ve.dm.Document} document The document to modify
 * @param {Object} treeOp The tree operation
 */
ve.dm.TreeModifier.static.applyTreeOperation = function ( isReversed, document, treeOp ) {
	var removedNodes = [],
		addedNodes = [],
		changedBranchNodes = [];

	function splice( parentNode ) {
		var removed = parentNode.splice.apply( parentNode, Array.prototype.slice.call( arguments, 1 ) );
		ve.batchPush( removedNodes, removed );
		ve.batchPush( addedNodes, Array.prototype.slice.call( arguments, 3 ) );
		return removed;
	}

	function ensureText( position ) {
		var pre, post, newNode,
			node = position.node,
			offset = position.offset;
		if ( node.type === 'text' ) {
			return position;
		}
		pre = node.children[ offset - 1 ];
		post = node.children[ offset ];
		if ( post && post.type === 'text' ) {
			// Prefer post to pre, because we might want to remove text. (We shouldn't
			// really have two adjacent text nodes, though)
			return { node: post, offset: 0 };
		}
		if ( pre && pre.type === 'text' ) {
			return { node: pre, offset: pre.length };
		}
		// There are no adjacent text nodes; insert one
		if ( !node.hasChildren() ) {
			throw new Error( 'Cannot add a child to ' + node.type + ' node' );
		}
		newNode = new ve.dm.TextNode( 0 );
		splice( node, offset, 0, newNode );
		return { node: newNode, offset: 0 };
	}

	function ensureNotText( position ) {
		var parentNode, parentOffset, length, newNode,
			node = position.node,
			offset = position.offset;
		if ( node.type !== 'text' ) {
			return position;
		}
		parentNode = node.parent;
		parentOffset = node.parent.children.indexOf( node );
		if ( offset === 0 ) {
			// Position before the text node
			return { node: parentNode, offset: parentOffset };
		}
		if ( offset === node.length ) {
			return { node: parentNode, offset: parentOffset + 1 };
		}
		// Else we must split the text node
		length = node.length - offset;
		node.adjustLength( -length );
		newNode = new ve.dm.TextNode( length );
		splice( parentNode, parentOffset + 1, 0, newNode );
		return { node: parentNode, offset: parentOffset + 1 };
	}

	function findContentPosition( node, contentOffset ) {
		var i, offset, childLength, child;

		if ( contentOffset === 0 ) {
			return { node: node, offset: 0 };
		}
		// Find the child that will take us up to or past the contentOffset
		offset = 0;
		for ( i = 0; ; i++ ) {
			child = node.children[ i ];
			if ( !child ) {
				throw new Error( 'Node does not reach offset' );
			}
			childLength = child.getOuterLength();
			offset += childLength;
			if ( offset >= contentOffset ) {
				break;
			}
		}
		if ( offset === contentOffset ) {
			return { node: node, offset: i + 1 };
		}
		return { node: child, offset: contentOffset - offset + childLength };
	}

	function prepareSplice( pathAndOffset, isContent, wantText ) {
		var i, iLen, position,
			path = pathAndOffset.slice( 0, -1 ),
			offset = pathAndOffset[ pathAndOffset.length - 1 ],
			node = document.documentNode;

		// Find node
		for ( i = 0, iLen = path.length; i < iLen; i++ ) {
			node = node.children[ path[ i ] ];
		}
		if ( isContent ) {
			// Determine position from (linearized) content offset
			if ( wantText ) {
				position = ensureText( findContentPosition( node, offset ) );
			} else {
				position = ensureNotText( findContentPosition( node, offset ) );
			}
		} else {
			position = { node: node, offset: offset };
		}
		// Get linear offset
		if ( position.node.type === 'text' || position.offset === 0 ) {
			position.linearOffset = position.node.getRange().start + position.offset;
		} else {
			position.linearOffset = position.node.children[ position.offset - 1 ].getOuterRange().end;
		}
		return position;
	}

	// Increment the change counter on the closest containing branch node at this offset
	// (This is used when converting to/from HTML, to decide whether loaded metadata offsets
	// need round tripping)
	function markBranchNodeChanged( offset ) {
		var item, newItem,
			adj = isReversed ? -1 : 1,
			i = offset - 1;

		while ( i >= 0 ) {
			item = document.data.getData( i-- );
			if ( !(
				ve.dm.LinearData.static.isOpenElementData( item ) &&
				ve.dm.nodeFactory.lookup(
					ve.dm.LinearData.static.getType( item )
				).prototype instanceof ve.dm.BranchNode
			) ) {
				continue;
			}
			if ( item.internal && item.internal.changesSinceLoad !== undefined ) {
				// Guard against marking the same node twice
				if ( changedBranchNodes.indexOf( item ) === -1 ) {
					newItem = ve.copy( item );
					changedBranchNodes.push( newItem );
					newItem.internal.changesSinceLoad += adj;
					document.data.splice( i + 1, 1, ve.deepFreeze( newItem ) );
				}
			}
			// This is a branch node boundary, so go no further
			break;
		}
	}

	function spliceLinear( offset, remove, insert ) {
		var content = ve.batchSplice( document.data, offset, remove, insert ? ve.deepFreeze( insert, true ) : [] );
		markBranchNodeChanged( offset );
		return content;
	}

	// Removes empty text node, or joins consecutive text nodes, at offset
	function healTextNodes( node, offset ) {
		var pre = node.children[ offset - 1 ],
			post = node.children[ offset ];

		if ( post && post.type === 'text' && post.length === 0 ) {
			// Remove empty text node
			splice( node, offset, 1 );
			post = node.children[ offset ];
		}
		if ( pre && post && pre.type === 'text' && post.type === 'text' ) {
			pre.adjustLength( post.length );
			splice( node, offset, 1 );
		}
	}

	var isTextOp = treeOp.type.slice( -4 ) === 'Text';
	var f = treeOp.from && prepareSplice( treeOp.from, treeOp.isContent, isTextOp );
	var t = treeOp.to && prepareSplice( treeOp.to, treeOp.isContent, isTextOp );
	// eslint-disable-next-line es-x/no-array-string-prototype-at
	var a = treeOp.at && prepareSplice( treeOp.at, treeOp.isContent, isTextOp );

	// Always adjust linear data before tree, to ensure consistency when node events
	// are emitted.
	var data;
	var adjustment;
	switch ( treeOp.type ) {
		case 'removeNode':
			// The node should have no contents, so its outer length should be 2
			data = spliceLinear( a.linearOffset, 2 );
			this.checkEqualData( data, [ treeOp.element, { type: '/' + treeOp.element.type } ] );
			splice( a.node, a.offset, 1 );
			healTextNodes( a.node, a.offset );
			break;
		case 'insertNode':
			spliceLinear( a.linearOffset, 0, [ treeOp.element, { type: '/' + treeOp.element.type } ] );
			var nodeToInsert = ve.dm.nodeFactory.createFromElement( treeOp.element );
			if ( nodeToInsert instanceof ve.dm.BranchNode ) {
				nodeToInsert.setupBlockSlugs();
			}
			splice( a.node, a.offset, 0, nodeToInsert );
			break;
		case 'moveNode':
			data = spliceLinear( f.linearOffset, f.node.children[ f.offset ].getOuterLength() );
			// No need to use local splice function as we know the node is going
			// to be re-inserted immediately.
			var movedNode = f.node.splice( f.offset, 1 )[ 0 ];
			adjustment = t.linearOffset > f.linearOffset ? data.length : 0;
			spliceLinear( t.linearOffset - adjustment, 0, data );
			t.node.splice( t.offset, 0, movedNode );
			break;
		case 'removeText':
			data = spliceLinear( a.linearOffset, treeOp.data.length );
			this.checkEqualData( data, treeOp.data );
			a.node.adjustLength( -treeOp.data.length );
			healTextNodes( a.node.parent, a.node.parent.children.indexOf( a.node ) );
			break;
		case 'insertText':
			spliceLinear( a.linearOffset, 0, treeOp.data );
			a.node.adjustLength( treeOp.data.length );
			break;
		case 'moveText':
			data = spliceLinear( f.linearOffset, treeOp.length );
			f.node.adjustLength( -treeOp.length );
			healTextNodes( f.node.parent, f.node.parent.children.indexOf( f.node ) );
			adjustment = t.linearOffset > f.linearOffset ? data.length : 0;
			spliceLinear( t.linearOffset - adjustment, 0, data );
			t.node.adjustLength( treeOp.length );
			break;
		default:
			throw new Error( 'Unknown tree op type: ' + treeOp.type );
	}

	if ( addedNodes.length || removedNodes.length ) {
		document.updateNodesByType( addedNodes, removedNodes );
	}
};

/**
 * The top level method: modify document tree according to transaction
 *
 * @param {ve.dm.Document} document The document
 * @param {ve.dm.Transaction} transaction The transaction
 */
ve.dm.TreeModifier.prototype.process = function ( document, transaction ) {
	this.setup( document );
	this.calculateTreeOperations( transaction );
	// Prior rollback logic removed: treeOps is now guaranteed to work
	this.constructor.static.applyTreeOperations( transaction.isReversed, document, this.treeOps );
};

/**
 * Setup state variables
 *
 * @param {ve.dm.Document} document The document to be processed
 */
ve.dm.TreeModifier.prototype.setup = function ( document ) {
	// XXX we only need these two properties for dump() in tests
	this.document = document;
	this.insertions = [];

	// Initialize state
	this.data = document.data;
	this.deletions = [];
	this.remover = new ve.dm.TreeCursor( document.getDocumentNode(), [] );
	this.inserter = new ve.dm.TreeCursor( document.getDocumentNode(), this.deletions );
	this.treeOps = [];
	this.insertedNodes = [];
	this.insertedPositions = [];
	this.adjustmentTree = { offsetsUsed: [] };
};

/**
 * Transform linear operations into tree operations
 *
 * @param {ve.dm.Transaction} transaction The transaction
 */
ve.dm.TreeModifier.prototype.calculateTreeOperations = function ( transaction ) {
	var linearOps = transaction.operations;
	for ( var i = 0, iLen = linearOps.length; i < iLen; i++ ) {
		this.processLinearOperation( linearOps[ i ] );
	}
	this.processImplicitFinalRetain();
};

/**
 * Translate a linear operation into tree operations
 *
 * #processImplicitFinalRetain should be called once all operations have been processed
 *
 * @param {Object} linearOp The linear operation
 */
ve.dm.TreeModifier.prototype.processLinearOperation = function ( linearOp ) {
	if ( linearOp.type === 'retain' ) {
		var retainLength = linearOp.length;
		while ( retainLength > 0 ) {
			retainLength -= this.processRetain( retainLength );
		}
	} else if ( linearOp.type === 'replace' ) {
		var i, iLen, item, data;
		for ( i = 0, iLen = linearOp.remove.length; i < iLen; i++ ) {
			item = linearOp.remove[ i ];
			if ( item.type ) {
				this.processRemove( item );
				continue;
			}
			// Group the whole current run of text, and process it
			data = [ item ];
			while ( ++i < iLen && !linearOp.remove[ i ].type ) {
				item = linearOp.remove[ i ];
				data.push( item );
			}
			i--;
			this.processRemove( data );
		}
		for ( i = 0, iLen = linearOp.insert.length; i < iLen; i++ ) {
			item = linearOp.insert[ i ];
			if ( item.type ) {
				this.processInsert( item );
				continue;
			}
			// Group the whole current run of text, and process it
			data = [ item ];
			while ( ++i < iLen && !linearOp.insert[ i ].type ) {
				item = linearOp.insert[ i ];
				data.push( item );
			}
			i--;
			this.processInsert( data );
		}
	}
	// Else the linear operation type must be 'attribute': do nothing
};

/**
 * Retain to the end of the content
 */
ve.dm.TreeModifier.prototype.processImplicitFinalRetain = function () {
	// Pretend there is an implicit retain to the end of the document
	// TODO: fix our tests so this is unnecessary, then check for exhaustion instead
	while ( true ) {
		var node = this.remover.node;
		if ( !node || (
			node === this.remover.root &&
			this.remover.offset === node.children.length
		) ) {
			return;
		}
		var retainLength;
		if ( node.type === 'text' ) {
			// Retain all remaining text; if there is no remaining text then
			// retain a single offset.
			retainLength = Math.max( 1, node.length - this.remover.offset );
		} else if ( !node.hasChildren() ) {
			retainLength = 1;
		} else {
			var item = node.children[ this.remover.offset ];
			retainLength = item ? item.getOuterLength() : 1;
		}
		this.processRetain( retainLength );
	}
};

/**
 * Test whether both pointers point to the same location
 *
 * @return {boolean} True if the paths and offsets are identical
 */
ve.dm.TreeModifier.prototype.cursorsMatch = function () {
	if ( this.insertedPositions.length > 0 ) {
		return false;
	}
	var rawRemoverPosition = this.getRawRemoverPosition( {
		path: this.remover.path,
		offset: this.remover.offset,
		node: this.remover.node
	} );
	var rawInserterPosition = this.getRawInserterPosition();
	var adjustedRemoverPosition = this.adjustRemoverPosition( rawRemoverPosition );
	var adjustedInserterPosition = this.adjustInserterPosition( rawInserterPosition );

	// Optimization: adjustedRemoverPosition and adjustedInserterPosition are very
	// often arrays of length 1. This simple check is much faster than a full
	// JSON.stringify comparison.
	if (
		adjustedRemoverPosition.length === 1 && adjustedInserterPosition.length === 1 &&
		adjustedRemoverPosition[ 0 ] === adjustedInserterPosition[ 0 ]
	) {
		return true;
	}

	return JSON.stringify( adjustedRemoverPosition ) === JSON.stringify( adjustedInserterPosition );
};

/**
 * Process the retention of content passed by one step of the remover
 *
 * If the inserter and remover are at the same place, just skip content.
 *
 * Else the remover *feeds* the inserter:
 * - The inserter plans insertions in the (imagined, hypothetical) document
 * - But the only move it can make in the (immutable) real document is to step out of nodes
 * - It cannot skip past nodes (other than skipping remaining siblings when stepping out)
 * - If the remover steps into a node, the inserter creates a node of the same type
 * - If the remover crosses content, it is moved to the inserter
 * - If the remover steps out of a node, the inserter steps out of its node
 *
 * @param {number} maxLength The maximum amount of content to retain
 * @return {number} The amount of content retained
 */
ve.dm.TreeModifier.prototype.processRetain = function ( maxLength ) {
	var remover = this.remover,
		inserter = this.inserter;

	if ( this.insertedPositions.length === 0 ) {
		this.inserter.crossIgnoredNodes();
	}
	var removerStep, inserterStep;
	if ( this.cursorsMatch() ) {
		// Pointers are in the same location, so advance them together.
		// This is the only way both pointers can ever enter the same node;
		// in any other case, a node entered at an 'open' tag by one pointer
		// is marked as either as a deletion or a creation, so the other
		// pointer will not follow.
		removerStep = remover.stepAtMost( maxLength );
		inserterStep = inserter.stepAtMost( maxLength );
		if ( !removerStep ) {
			throw new Error( 'Remover past end' );
		}
		if ( !inserterStep ) {
			throw new Error( 'Inserter past end' );
		}
		if ( !this.cursorsMatch() ) {
			throw new Error( 'Remover and inserter unexpectedly diverged' );
		}
		return removerStep.length;
	}
	// Else pointers are not in the same location (in fact they cannot lie in the
	// same node)
	removerStep = remover.stepAtMost( maxLength );
	switch ( removerStep.type ) {
		case 'crosstext':
			this.pushMoveTextOp( removerStep );
			if ( this.insertedPositions.length ) {
				this.insertedPositions[ this.insertedPositions.length - 1 ] += removerStep.length;
			}
			break;
		case 'cross':
			if ( removerStep.item.type === 'text' ) {
				this.pushMoveTextOp( removerStep );
				if ( this.insertedPositions.length ) {
					this.insertedPositions[ this.insertedPositions.length - 1 ] += removerStep.item.length;
				}
			} else {
				this.deletions.push( removerStep.item );
				this.pushMoveNodeOp( removerStep );
				if ( this.insertedPositions.length ) {
					this.insertedPositions[ this.insertedPositions.length - 1 ] +=
						this.isInsertionContent() ? 2 : 1;
				}
			}
			break;
		case 'open':
			this.deletions.push( removerStep.item );
			// Clone last open and step in
			var element = removerStep.item.getClonedElement( true );
			this.pushInsertNodeOp( element );
			this.insertedNodes.push( element );
			// This 0 position is invalid if element is content (because the offset should be
			// linearized), but it will get popped immediately
			this.insertedPositions.push( 0 );
			break;
		case 'close':
			if ( this.insertedPositions.length ) {
				this.insertedNodes.pop();
				this.insertedPositions.pop();
				if ( this.insertedPositions.length ) {
					this.insertedPositions[ this.insertedPositions.length - 1 ] +=
						this.isInsertionContent() ? 2 : 1;
				}
			} else {
				if ( inserter.node.type === 'text' ) {
					inserter.stepOut();
				}
				inserterStep = inserter.stepOut();
				if ( inserterStep.item.type !== removerStep.item.type ) {
					throw new Error( 'Expected ' + removerStep.item.type + ', not ' +
		inserterStep.item.type );
				}
			}
			this.pushRemoveLastIfInDeletions();
			break;
	}
	return removerStep.length;
};

/**
 * Process the removal of some items
 *
 * @param {Object|Array} itemOrData An open tag, a close tag, or an array of text items
 */
ve.dm.TreeModifier.prototype.processRemove = function ( itemOrData ) {
	var cursorsMatch = this.cursorsMatch(),
		length = itemOrData.length || 1,
		step = this.remover.stepAtMost( length );

	if ( cursorsMatch && ( step.type === 'cross' || step.type === 'crosstext' ) ) {
		this.inserter.stepAtMost( length );
	}

	if ( step.type === 'crosstext' ) {
		this.pushRemoveTextOp( step );
	} else if ( step.type === 'cross' ) {
		this.pushRemoveLast();
	} else if ( step.type === 'open' ) {
		this.deletions.push( step.item );
	} else if ( step.type === 'close' ) {
		this.pushRemoveLastIfInDeletions();
	}
};

/**
 * Process the insertion an open tag, a close tag, or an array of text items
 *
 * @param {Object|Array} itemOrData An open tag, a close tag, or an array of text items
 */
ve.dm.TreeModifier.prototype.processInsert = function ( itemOrData ) {
	var inserter = this.inserter;

	var item, type, data;
	if ( itemOrData.type ) {
		item = itemOrData;
		type = item.type.charAt( 0 ) === '/' ? 'close' : 'open';
	} else {
		data = itemOrData;
		type = 'crosstext';
	}

	if ( type === 'open' ) {
		if ( inserter.node.type === 'text' ) {
			// Step past the end of the text node, even if that skips past some
			// content (in which case the remover logically must later cross that
			// content in either processRemove or processRetain).
			inserter.stepOut();
		}
		var element = ve.copy( item );
		this.pushInsertNodeOp( element );
		this.insertedNodes.push( element );
		// This 0 position is invalid if element is content (because the offset should be
		// linearized), but it will get popped immediately
		this.insertedPositions.push( 0 );
	} else if ( type === 'crosstext' ) {
		this.pushInsertTextOp( ve.copy( data ) );
		if ( this.insertedPositions.length ) {
			this.insertedPositions[ this.insertedPositions.length - 1 ] += data.length;
		}
	} else if ( type === 'close' ) {
		if ( this.insertedPositions.length ) {
			var insertedNode = this.insertedNodes.pop();
			if ( insertedNode.type !== item.type.slice( 1 ) ) {
				throw new Error( 'Expected closing for ' + insertedNode.type +
					' but got closing for ' + item.type.slice( 1 ) );
			}
			this.insertedPositions.pop();
			if ( this.insertedPositions.length ) {
				this.insertedPositions[ this.insertedPositions.length - 1 ] +=
					this.isInsertionContent() ? 2 : 1;
			}
		} else {
			// Step past the next close tag, even if that skips past some content (in which
			// case the remover logically must later cross that content in either
			// processRemove or processRetain).
			if ( inserter.node.type === 'text' ) {
				inserter.stepOut();
			}
			var step = inserter.stepOut();
			if ( step.item.type !== item.type.slice( 1 ) ) {
				throw new Error( 'Expected closing for ' + step.item.type +
					' but got closing for ' + item.type.slice( 1 ) );
			}
		}
	}
};

/**
 * Push into treeOps the removal of the last remover step, which must be 'cross' or 'close'
 */
ve.dm.TreeModifier.prototype.pushRemoveLast = function () {
	var step = this.remover.lastStep;
	if ( step.item.type === 'text' ) {
		this.pushRemoveTextOp( step );
	} else {
		this.pushRemoveNodeOp( step );
	}
};

/**
 * Push into treeOps the removal of the last remover step, if that item marked as deleted
 */
ve.dm.TreeModifier.prototype.pushRemoveLastIfInDeletions = function () {
	var i = this.deletions.indexOf( this.remover.lastStep.item );
	if ( i !== -1 ) {
		this.pushRemoveLast();
	}
};

/**
 * Push into treeOps the node insertion at the current inserter position
 *
 * @param {ve.dm.Node} element The element to insert (inserted into treeOps without copying)
 */
ve.dm.TreeModifier.prototype.pushInsertNodeOp = function ( element ) {
	var isContent = this.isInsertionContent(),
		rawInserterPosition = this.getRawInserterPosition();

	this.checkCanInsertNodeType( element.type );

	this.treeOps.push( {
		type: 'insertNode',
		isContent: isContent,
		at: this.adjustInserterPosition( rawInserterPosition ),
		element: element
	} );
	if ( this.insertedPositions.length === 0 ) {
		this.modifyAdjustmentTree( rawInserterPosition, isContent ? 2 : 1, false );
	}
};

/**
 * Push into treeOps the text insertion at the current inserter position
 *
 * @param {Array} data The text data to insert
 */
ve.dm.TreeModifier.prototype.pushInsertTextOp = function ( data ) {
	var rawInserterPosition = this.getRawInserterPosition();

	this.checkCanInsertText();

	this.treeOps.push( {
		type: 'insertText',
		isContent: true,
		at: this.adjustInserterPosition( rawInserterPosition ),
		data: data
	} );
	if ( this.insertedPositions.length === 0 ) {
		this.modifyAdjustmentTree( rawInserterPosition, data.length, false );
	}
};

/**
 * Push into treeOps a move of a node to the current inserter position
 *
 * @param {Object} removerStep The remover step over the node; see ve.dm.TreeCursor#stepAtMost
 */
ve.dm.TreeModifier.prototype.pushMoveNodeOp = function ( removerStep ) {
	var rawRemoverPosition = this.getRawRemoverPosition( removerStep ),
		rawInserterPosition = this.getRawInserterPosition(),
		isContent = this.doesTypeTakeContent( removerStep.node.type );

	this.checkCanInsertNodeType( removerStep.item.type );

	this.treeOps.push( {
		type: 'moveNode',
		isContent: isContent,
		from: this.adjustRemoverPosition( rawRemoverPosition ),
		to: this.adjustInserterPosition( rawInserterPosition )
	} );
	this.modifyAdjustmentTree( rawRemoverPosition, isContent ? -2 : -1, true );
	if ( this.insertedPositions.length === 0 ) {
		this.modifyAdjustmentTree( rawInserterPosition, isContent ? 2 : 1, false );
	}
};

/**
 * Push into treeOps a move of some text to the current inserter position
 *
 * @param {Object} removerStep The remover step over the text; see ve.dm.TreeCursor#stepAtMost
 */
ve.dm.TreeModifier.prototype.pushMoveTextOp = function ( removerStep ) {
	var length = removerStep.type === 'crosstext' ?
			removerStep.length :
			removerStep.item.getLength(),
		rawRemoverPosition = this.getRawRemoverPosition( removerStep ),
		rawInserterPosition = this.getRawInserterPosition();

	this.checkCanInsertText();

	this.treeOps.push( {
		type: 'moveText',
		isContent: true,
		from: this.adjustRemoverPosition( rawRemoverPosition ),
		to: this.adjustInserterPosition( rawInserterPosition ),
		length: length
	} );
	this.modifyAdjustmentTree( rawRemoverPosition, -length, false );
	if ( this.insertedPositions.length === 0 ) {
		this.modifyAdjustmentTree( rawInserterPosition, length, false );
	}
};

/**
 * Push into treeOps a removal of a node
 *
 * @param {Object} removerStep The remover step over the node; see ve.dm.TreeCursor#stepAtMost
 */
ve.dm.TreeModifier.prototype.pushRemoveNodeOp = function ( removerStep ) {
	var rawRemoverPosition = this.getRawRemoverPosition( removerStep ),
		isContent = this.doesTypeTakeContent( removerStep.node.type );
	this.treeOps.push( {
		type: 'removeNode',
		isContent: isContent,
		at: this.adjustRemoverPosition( rawRemoverPosition ),
		element: removerStep.item.getClonedElement( true )
	} );
	this.modifyAdjustmentTree( rawRemoverPosition, isContent ? -2 : -1, true );
};

/**
 * Push into treeOps a removal of some text
 *
 * @param {Object} removerStep The remover step over the text; see ve.dm.TreeCursor#stepAtMost
 */
ve.dm.TreeModifier.prototype.pushRemoveTextOp = function ( removerStep ) {
	var rawRemoverPosition = this.getRawRemoverPosition( removerStep );
	var start, end;
	if ( removerStep.type === 'crosstext' ) {
		start = removerStep.node.getRange().start + removerStep.offset;
		end = start + removerStep.length;
	} else {
		start = removerStep.item.getRange().start;
		end = removerStep.item.getRange().end;
	}
	this.treeOps.push( {
		type: 'removeText',
		isContent: true,
		at: this.adjustRemoverPosition( rawRemoverPosition ),
		data: ve.copy( this.data.slice( start, end ) )
	} );
	this.modifyAdjustmentTree( rawRemoverPosition, start - end, false );
};

/**
 * Return adjustment tree node at a given offset path, inserting it if necessary
 *
 * @param {number[]} position Offset path in the adjustment tree
 * @return {Object} The adjustment tree node
 */
ve.dm.TreeModifier.prototype.findOrCreateAdjustmentNode = function ( position ) {
	var adjustmentNode = this.adjustmentTree;
	for ( var i = 0, len = position.length; i < len; i++ ) {
		var offset = position[ i ];
		if ( !adjustmentNode[ offset ] ) {
			adjustmentNode[ offset ] = { offsetsUsed: [] };
			adjustmentNode.offsetsUsed.push( offset );
		}
		adjustmentNode = adjustmentNode[ offset ];
	}
	return adjustmentNode;
};

/**
 * Modify the adjustment in the adjustment tree at a given offset path
 *
 * @param {number[]} rawPosition Unadjusted offset path
 * @param {number} diff Adjustment at rawPosition
 * @param {boolean} deleteDescendants If true, delete all adjustments at paths descending from here
 */
ve.dm.TreeModifier.prototype.modifyAdjustmentTree = function ( rawPosition, diff, deleteDescendants ) {
	var adjustmentNode = this.findOrCreateAdjustmentNode( rawPosition );
	if ( diff > 0 ) {
		adjustmentNode.inserted = ( adjustmentNode.inserted || 0 ) + diff;
	} else {
		adjustmentNode.removed = ( adjustmentNode.removed || 0 ) - diff;
	}
	if ( deleteDescendants ) {
		adjustmentNode.offsetsUsed.forEach( function ( i ) {
			delete adjustmentNode[ i ];
		} );
		adjustmentNode.offsetsUsed = [];
	}
};

/**
 * Get the raw position of a node stepped over by the remover
 *
 * @param {Object} step Remover step; see ve.dm.TreeCursor#stepAtMost
 * @return {number[]} The pathAndOffset, with offsets inside a ContentBranchNode linearized
 */
ve.dm.TreeModifier.prototype.getRawRemoverPosition = function ( step ) {
	return this.getRawPosition( step.path, step.offset, step.node );
};

/**
 * Get the raw position at the inserter
 *
 * @return {number[]} The pathAndOffset, with offsets inside a ContentBranchNode linearized
 */
ve.dm.TreeModifier.prototype.getRawInserterPosition = function () {
	return this.getRawPosition( this.inserter.path, this.inserter.offset, this.inserter.node );
};

/**
 * Get the adjusted position of a raw remover position
 *
 * @param {number[]} rawPosition The raw remover position
 * @return {number[]} Adjusted pathAndOffset, with offsets inside a ContentBranchNode linearized
 */
ve.dm.TreeModifier.prototype.adjustRemoverPosition = function ( rawPosition ) {
	return this.getAdjustedPosition( rawPosition, false );
};

/**
 * Get the adjusted position of a raw inserter position, XXX
 *
 * @param {number[]} rawPosition The raw inserter position (must be its current position)
 * @return {number[]} Adjusted pathAndOffset, with offsets inside a ContentBranchNode linearized, and including current position within nodes to be inserted, if any
 */
ve.dm.TreeModifier.prototype.adjustInserterPosition = function ( rawPosition ) {
	// getAdjustedPosition returns a brand new array, so we can safely modify
	// it with batchPush, which is much faster than concat (which creates a new array)
	var positions = this.getAdjustedPosition( rawPosition, true );
	ve.batchPush( positions, this.insertedPositions );
	return positions;
};

/**
 * Get the raw position of a node, with offsets inside a ContentBranchNode linearized
 *
 * @param {number[]} path Path to a node
 * @param {number} offset Offset within the node
 * @param {ve.dm.Node} node The node
 * @return {number[]} The pathAndOffset, with offsets inside a ContentBranchNode linearized
 */
ve.dm.TreeModifier.prototype.getRawPosition = function ( path, offset, node ) {
	var i, linearizedOffset;
	// No need to check node.parent.hasChildren() below as node.parent
	// is a parent so must have children.
	if ( node.parent && node.parent.canContainContent() ) {
		var numNodesBefore = path[ path.length - 1 ];
		linearizedOffset = offset;
		for ( i = 0; i < numNodesBefore; i++ ) {
			linearizedOffset += node.parent.children[ i ].getOuterLength();
		}
		path = path.slice( 0, -1 );
	} else if ( node.canContainContent() && node.hasChildren() ) {
		linearizedOffset = 0;
		for ( i = 0; i < offset; i++ ) {
			linearizedOffset += node.children[ i ].getOuterLength();
		}
	} else {
		linearizedOffset = offset;
	}
	if ( !path.length ) {
		// Optimization, path is often empty
		return [ linearizedOffset ];
	}
	path = path.slice();
	// slice+push is faster than concat
	path.push( linearizedOffset );
	return path;
};

/**
 * Get the adjusted position of a raw position
 *
 * @param {number[]} position The raw position
 * @param {boolean} isInserter True for an inserter position; false for a remover position
 * @return {number[]} Adjusted pathAndOffset, with offsets inside a ContentBranchNode linearized
 */
ve.dm.TreeModifier.prototype.getAdjustedPosition = function ( position, isInserter ) {
	var node = this.adjustmentTree;

	position = position.slice();
	// Adjust each offset in the path so inserted nodes are counted
	for ( var i = 0, iLen = position.length; i < iLen; i++ ) {
		var positionI = position[ i ];

		// The loop below is equivalent to:
		//
		// for ( j = 0, jLen = positionI + 1; j < jLen; j++ ) {
		//   childNode = node[ j ];
		//   if ( !childNode ) {
		//     continue;
		//   }
		//   ...
		// }
		//
		// However as `node` is very sparse, it is slow to iterate over
		// every position, so just iterate over the positions we have,
		// then check the loop conditions later. (T261634)
		// An offsetsUsed property is stored on every node instead of
		// using a for..in loop as a for..in loop has to re-calculate
		// the list of indexes to iterate over.

		var jLen = positionI + 1;
		var offsetsUsed = node.offsetsUsed;
		for ( var k = 0, kLen = offsetsUsed.length; k < kLen; k++ ) {
			var j = offsetsUsed[ k ];
			if ( j >= jLen ) {
				continue;
			}

			var childNode = node[ j ];

			var inserted = childNode.inserted || 0;
			var removed = childNode.removed || 0;

			if ( i < iLen - 1 || j < jLen - 1 ) {
				// This offset is strictly before position
				position[ i ] += inserted - removed;
			} else {
				// This offset is exactly at position
				// Don't adjust for removals
				position[ i ] += inserted;
				// Adjust for insertions, except if we are the inserter and the insertion is incomplete
				// (note if insertedNodes.length > 0, then also inserted > 0)
				if ( isInserter && this.insertedNodes.length > 0 ) {
					// Incomplete means we are inside a (non text) node
					position[ i ]--;
				}
			}
		}
		node = node[ positionI ];
		if ( !node ) {
			break;
		}
	}
	return position;
};

/**
 * Test whether a node type takes content
 *
 * @param {string} type The name of a ve.dm.Node type
 * @return {boolean} Whether that type takes content
 */
ve.dm.TreeModifier.prototype.doesTypeTakeContent = function ( type ) {
	return !!ve.dm.nodeFactory.canNodeContainContent( type );
};

/**
 * Test whether the inserter is at a content position
 *
 * @return {boolean} Whether the inserter is at a content position
 */
ve.dm.TreeModifier.prototype.isInsertionContent = function () {
	return this.doesTypeTakeContent( this.getTypeAtInserter() );
};

/**
 * Get the type of the node in which the inserter lies
 *
 * @return {string} The node type
 */
ve.dm.TreeModifier.prototype.getTypeAtInserter = function () {
	return this.insertedNodes.length > 0 ?
		this.insertedNodes[ this.insertedNodes.length - 1 ].type :
		this.inserter.node.type;
};

/**
 * Throw an error if the inserter is not in a content position
 *
 * @throws {Error} Cannot insert text into a foo node
 */
ve.dm.TreeModifier.prototype.checkCanInsertText = function () {
	var parentType = this.getTypeAtInserter();

	if ( parentType === 'text' ) {
		return;
	}
	if ( !ve.dm.nodeFactory.canNodeContainContent( parentType ) ) {
		throw new Error( 'Cannot insert text into a ' + parentType + ' node' );
	}
};

/**
 * Throw an error if the inserter node cannot take a child of a specified type
 *
 * @param {string} nodeType The node type
 * @throws {Error} Cannot add child
 */
ve.dm.TreeModifier.prototype.checkCanInsertNodeType = function ( nodeType ) {
	var parentType = this.getTypeAtInserter(),
		nodeClass = ve.dm.nodeFactory.lookup( nodeType ),
		parentClass = ve.dm.nodeFactory.lookup( parentType ),
		childNodeTypes = parentClass.static.childNodeTypes;

	if ( Array.isArray( childNodeTypes ) && childNodeTypes.length === 0 ) {
		throw new Error( 'Cannot add a child to ' + parentType + ' node' );
	}

	if ( nodeClass.static.isContent && !parentClass.static.canContainContent ) {
		throw new Error( 'Cannot add content node (' + nodeType + ') to a ' + parentType + ' node' );
	}

	if ( !nodeClass.static.isMetaData ) {
		if ( !nodeClass.static.isContent && parentClass.static.canContainContent ) {
			throw new Error( 'Cannot add structure node (' + nodeType + ') to a ' + parentType + ' node' );
		}

		if ( Array.isArray( childNodeTypes ) && childNodeTypes.indexOf( nodeType ) === -1 ) {
			throw new Error( 'Cannot add a ' + nodeType + ' node to ' + parentType + ' node' );
		}
	}
};

/* Initialization */

ve.dm.treeModifier = new ve.dm.TreeModifier();
