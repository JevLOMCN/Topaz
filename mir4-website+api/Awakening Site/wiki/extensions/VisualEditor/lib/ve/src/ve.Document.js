/*!
 * VisualEditor Document class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Generic document.
 *
 * @class
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {ve.BranchNode} documentNode Document node
 */
ve.Document = function VeDocument( documentNode ) {
	// Mixin constructors
	OO.EventEmitter.call( this );

	// Properties
	this.documentNode = documentNode;
	this.documentNode.setDocument( this );
};

/* Inheritance */

OO.mixinClass( ve.Document, OO.EventEmitter );

/* Events */

/**
 * A node has been attached with ve.Node#setDocument . Its descendants are guaranteed
 * to be attached too (and the event is emitted for descendants first, and for siblings
 * in their order in the children list)
 *
 * @event nodeAttached
 * @param {ve.Node} node The node that has been attached
 */

/**
 * A node has been detached with ve.Node#setDocument . Its descendants are guaranteed
 * to be detached too (and the event is emitted for descendants first, and for siblings
 * in their order in the children list)
 *
 * @event nodeDetached
 * @param {ve.Node} node The node that has been detached
 */

/* Methods */

/**
 * Get the root of the document's node tree.
 *
 * @return {ve.BranchNode} Root of node tree
 */
ve.Document.prototype.getDocumentNode = function () {
	return this.documentNode;
};

/**
 * Get a node at an offset.
 *
 * @param {number} offset Offset to get node at
 * @return {ve.Node|null} Node at offset
 */
ve.Document.prototype.getBranchNodeFromOffset = function ( offset ) {
	var node = this.getDocumentNode().getNodeFromOffset( offset );
	if ( node && !node.hasChildren() ) {
		node = node.getParent();
	}
	return node;
};

/**
 * Gets a list of nodes and the ranges within them that a selection of the document covers.
 *
 * @param {ve.Range} range Range within document to select nodes
 * @param {string} [mode='leaves'] Type of selection to perform:
 *
 * - `leaves`: Return all leaf nodes in the given range (descends all the way down)
 * - `branches`': Return all branch nodes in the given range
 * - `covered`: Do not descend into nodes that are entirely covered by the range. The result
 *   is similar to that of 'leaves' except that if a node is entirely covered, its
 *   children aren't returned separately.
 * - `siblings`: Return a set of adjacent siblings covered by the range (descends as long as the
 *   range is in a single node)
 * @return {Array} List of objects describing nodes in the selection and the ranges therein:
 *
 * - `node`: Reference to a ve.Node
 * - `range`: ve.Range, missing if the entire node is covered
 * - `index`: Index of the node in its parent, missing if node has no parent
 * - `indexInNode`: If range is a zero-length range between two children of node,
 *   this is set to the index of the child following range (or to
 *   `node.children.length + 1` if range is between the last child and
 *   the end). If range is a zero-length range inside an empty non-content branch node, this is 0.
 *   Missing in all other cases.
 * - `nodeRange`: Range covering the inside of the entire node, not including wrapper
 * - `nodeOuterRange`: Range covering the entire node, including wrapper
 * - `parentOuterRange`: Outer range of node's parent. Missing if there is no parent
 *   or if indexInNode is set.
 *
 * @throws {Error} Invalid mode
 * @throws {Error} Invalid start offset
 * @throws {Error} Invalid end offset
 * @throws {Error} Failed to select any nodes
 */
ve.Document.prototype.selectNodes = function ( range, mode ) {
	var doc = this.getDocumentNode(),
		retval = [],
		start = range.start,
		end = range.end,
		stack = [ {
			// Node we are currently stepping through
			// Note each iteration visits a child of node, not node itself
			node: doc,
			// Index of the child in node we're visiting
			index: 0,
			// First offset inside node
			startOffset: 0
		} ],
		currentFrame = stack[ 0 ],
		startFound = false;

	mode = mode || 'leaves';
	if ( mode !== 'leaves' && mode !== 'branches' && mode !== 'covered' && mode !== 'siblings' ) {
		throw new Error( 'Invalid mode: ' + mode );
	}

	if ( start < 0 || start > doc.getLength() ) {
		throw new Error( 'Invalid start offset: ' + start );
	}
	if ( end < 0 || end > doc.getLength() ) {
		throw new Error( 'Invalid end offset: ' + end );
	}

	if ( !doc.children || doc.children.length === 0 ) {
		// Document has no children. This is weird
		var nodeRange = new ve.Range( 0, doc.getLength() );
		return [ {
			node: doc,
			range: new ve.Range( start, end ),
			index: 0,
			nodeRange: nodeRange,
			nodeOuterRange: nodeRange
		} ];
	}
	var left = doc.children[ 0 ].isWrapped() ? 1 : 0;

	do {
		var node = currentFrame.node.children[ currentFrame.index ];
		var prevNode = currentFrame.node.children[ currentFrame.index - 1 ];
		var nextNode = currentFrame.node.children[ currentFrame.index + 1 ];
		var right = left + node.getLength();
		// Is the start inside node?
		var startInside = start >= left && start <= right;
		// Is the end inside node?
		var endInside = end >= left && end <= right;
		// Does the node have wrapping elements around it
		var isWrapped = node.isWrapped();
		// Is there an unwrapped node right before this node?
		var isPrevUnwrapped = prevNode ? !prevNode.isWrapped() : false;
		// Is there an unwrapped node right after this node?
		var isNextUnwrapped = nextNode ? !nextNode.isWrapped() : false;
		// Is this node an empty non-content branch node?
		var isEmptyBranch = ( node.getLength() === 0 || node.shouldIgnoreChildren() ) &&
			!node.isContent() && !node.canContainContent();
		// Is the start between prevNode's closing and node or between the parent's opening and node?
		var startBetween = ( isWrapped ? start === left - 1 : start === left ) && !isPrevUnwrapped;
		// Is the end between node and nextNode's opening or between node and the parent's closing?
		var endBetween = ( isWrapped ? end === right + 1 : end === right ) && !isNextUnwrapped;
		var parentRange = new ve.Range(
			currentFrame.startOffset,
			currentFrame.startOffset + currentFrame.node.getLength()
		);

		var parentFrame;
		if ( isWrapped && end === left - 1 && currentFrame.index === 0 ) {
			// The selection ends here with an empty range at the beginning of the node
			// TODO duplicated code
			isWrapped = currentFrame.node.isWrapped();
			retval.push( {
				node: currentFrame.node,
				indexInNode: 0,
				range: new ve.Range( end, end ),
				nodeRange: parentRange,
				nodeOuterRange: new ve.Range(
					parentRange.start - isWrapped, parentRange.end + isWrapped
				)
			} );
			parentFrame = stack[ stack.length - 2 ];
			if ( parentFrame ) {
				retval[ retval.length - 1 ].index = parentFrame.index;
			}
			return retval;
		}

		if ( start === end && ( startBetween || endBetween ) && isWrapped ) {
			// Empty range in the parent, outside of any child
			isWrapped = currentFrame.node.isWrapped();
			retval = [ {
				node: currentFrame.node,
				indexInNode: currentFrame.index + ( endBetween ? 1 : 0 ),
				range: new ve.Range( start, end ),
				nodeRange: parentRange,
				nodeOuterRange: new ve.Range(
					parentRange.start - isWrapped, parentRange.end + isWrapped
				)
			} ];
			parentFrame = stack[ stack.length - 2 ];
			if ( parentFrame ) {
				retval[ 0 ].index = parentFrame.index;
			}
			return retval;
		} else if ( startBetween ) {
			// start is between the previous sibling and node
			// so the selection covers all or part of node

			// Descend if
			// - we are in leaves mode, OR
			// - we are in covered mode and the end is inside node OR
			// - we are in branches mode and node is a branch (can have grandchildren)
			// AND
			// the node is non-empty and doesn't handle its own children
			if ( ( mode === 'leaves' ||
					( mode === 'covered' && endInside ) ||
					( mode === 'branches' && node.canHaveChildrenNotContent() ) ) &&
				node.children && node.children.length && !node.shouldIgnoreChildren()
			) {
				// Descend into node
				currentFrame = {
					node: node,
					index: 0,
					startOffset: left
				};
				stack.push( currentFrame );
				startFound = true;
				// If the first child of node has an opening, skip over it
				if ( node.children[ 0 ].isWrapped() ) {
					left++;
				}
				continue;
			} else if ( !endInside ) {
				// All of node is covered
				retval.push( {
					node: node,
					// No 'range' because the entire node is covered
					index: currentFrame.index,
					nodeRange: new ve.Range( left, right ),
					nodeOuterRange: new ve.Range( left - isWrapped, right + isWrapped ),
					parentOuterRange: new ve.Range(
						parentRange.start - currentFrame.node.isWrapped(),
						parentRange.end + currentFrame.node.isWrapped()
					)
				} );
				startFound = true;
			} else {
				// Part of node is covered
				return [ {
					node: node,
					range: new ve.Range( start, end ),
					index: currentFrame.index,
					nodeRange: new ve.Range( left, right ),
					nodeOuterRange: new ve.Range( left - isWrapped, right + isWrapped ),
					parentOuterRange: new ve.Range(
						parentRange.start - currentFrame.node.isWrapped(),
						parentRange.end + currentFrame.node.isWrapped()
					)
				} ];
			}
		} else if ( startInside && endInside ) {
			if ( node.children && node.children.length &&
				( mode !== 'branches' || node.canHaveChildrenNotContent() ) ) {
				// Descend into node
				currentFrame = {
					node: node,
					index: 0,
					startOffset: left
				};
				stack.push( currentFrame );
				// If the first child of node has an opening, skip over it
				if ( node.children[ 0 ].isWrapped() ) {
					left++;
				}
				continue;
			} else {
				// node is a leaf node and the range is entirely inside it
				retval = [ {
					node: node,
					range: new ve.Range( start, end ),
					index: currentFrame.index,
					nodeRange: new ve.Range( left, right ),
					nodeOuterRange: new ve.Range( left - isWrapped, right + isWrapped ),
					parentOuterRange: new ve.Range(
						parentRange.start - currentFrame.node.isWrapped(),
						parentRange.end + currentFrame.node.isWrapped()
					)
				} ];
				if ( isEmptyBranch ) {
					retval[ 0 ].indexInNode = 0;
				}
				return retval;
			}
		} else if ( startInside ) {
			if ( ( mode === 'leaves' ||
					mode === 'covered' ||
					( mode === 'branches' && node.canHaveChildrenNotContent() ) ) &&
				node.children && node.children.length
			) {
				// node is a branch node and the start is inside it
				// Descend into it
				currentFrame = {
					node: node,
					index: 0,
					startOffset: left
				};
				stack.push( currentFrame );
				// If the first child of node has an opening, skip over it
				if ( node.children[ 0 ].isWrapped() ) {
					left++;
				}
				continue;
			} else {
				// node is a leaf node and the start is inside it
				// Add to retval and keep going
				retval.push( {
					node: node,
					range: new ve.Range( start, right ),
					index: currentFrame.index,
					nodeRange: new ve.Range( left, right ),
					nodeOuterRange: new ve.Range( left - isWrapped, right + isWrapped ),
					parentOuterRange: new ve.Range(
						parentRange.start - currentFrame.node.isWrapped(),
						parentRange.end + currentFrame.node.isWrapped()
					)
				} );
				startFound = true;
			}
		} else if ( endBetween ) {
			// end is between node and the next sibling
			// start is not inside node, so the selection covers
			// all of node, then ends

			if (
				( mode === 'leaves' || ( mode === 'branches' && node.canHaveChildrenNotContent() ) ) &&
				node.children && node.children.length
			) {
				// Descend into node
				currentFrame = {
					node: node,
					index: 0,
					startOffset: left
				};
				stack.push( currentFrame );
				// If the first child of node has an opening, skip over it
				if ( node.children[ 0 ].isWrapped() ) {
					left++;
				}
				continue;
			} else {
				// All of node is covered
				retval.push( {
					node: node,
					// No 'range' because the entire node is covered
					index: currentFrame.index,
					nodeRange: new ve.Range( left, right ),
					nodeOuterRange: new ve.Range( left - isWrapped, right + isWrapped ),
					parentOuterRange: new ve.Range(
						parentRange.start - currentFrame.node.isWrapped(),
						parentRange.end + currentFrame.node.isWrapped()
					)
				} );
				return retval;
			}
		} else if ( endInside ) {
			if ( ( mode === 'leaves' ||
					mode === 'covered' ||
					( mode === 'branches' && node.canHaveChildrenNotContent() ) ) &&
				node.children && node.children.length
			) {
				// node is a branch node and the end is inside it
				// Descend into it
				currentFrame = {
					node: node,
					index: 0,
					startOffset: left
				};
				stack.push( currentFrame );
				// If the first child of node has an opening, skip over it
				if ( node.children[ 0 ].isWrapped() ) {
					left++;
				}
				continue;
			} else {
				// node is a leaf node and the end is inside it
				// Add to retval and return
				retval.push( {
					node: node,
					range: new ve.Range( left, end ),
					index: currentFrame.index,
					nodeRange: new ve.Range( left, right ),
					nodeOuterRange: new ve.Range( left - isWrapped, right + isWrapped ),
					parentOuterRange: new ve.Range(
						parentRange.start - currentFrame.node.isWrapped(),
						parentRange.end + currentFrame.node.isWrapped()
					)
				} );
				return retval;
			}
		} else if ( startFound && end > right ) {
			// Neither the start nor the end is inside node, but we found the start earlier,
			// so node must be between the start and the end
			// Add the entire node, so no range property

			if (
				( mode === 'leaves' || ( mode === 'branches' && node.canHaveChildrenNotContent() ) ) &&
				node.children && node.children.length
			) {
				// Descend into node
				currentFrame = {
					node: node,
					index: 0,
					startOffset: left
				};
				stack.push( currentFrame );
				// If the first child of node has an opening, skip over it
				if ( node.children[ 0 ].isWrapped() ) {
					left++;
				}
				continue;
			} else {
				// All of node is covered
				retval.push( {
					node: node,
					// No 'range' because the entire node is covered
					index: currentFrame.index,
					nodeRange: new ve.Range( left, right ),
					nodeOuterRange: new ve.Range( left - isWrapped, right + isWrapped ),
					parentOuterRange: new ve.Range(
						parentRange.start - currentFrame.node.isWrapped(),
						parentRange.end + currentFrame.node.isWrapped()
					)
				} );
			}
		}

		// Move to the next node
		if ( nextNode ) {
			// The next node exists
			// Advance the index; the start of the next iteration will essentially
			// do node = nextNode;
			currentFrame.index++;
			// Advance to the first offset inside nextNode
			left = right +
				// Skip over node's closing, if present
				( node.isWrapped() ? 1 : 0 ) +
				// Skip over nextNode's opening, if present
				( nextNode.isWrapped() ? 1 : 0 );
		} else {
			// There is no next node, move up the stack until there is one
			left = right +
				// Skip over node's closing, if present
				( node.isWrapped() ? 1 : 0 );
			while ( !nextNode ) {
				// Check if the start is right past the end of this node, at the end of
				// the parent
				if ( node.isWrapped() && start === left ) {
					// TODO duplicated code
					parentRange = new ve.Range( currentFrame.startOffset,
						currentFrame.startOffset + currentFrame.node.getLength()
					);
					isWrapped = currentFrame.node.isWrapped();
					retval = [ {
						node: currentFrame.node,
						indexInNode: currentFrame.index + 1,
						range: new ve.Range( left, left ),
						nodeRange: parentRange,
						nodeOuterRange: new ve.Range(
							parentRange.start - isWrapped, parentRange.end + isWrapped
						)
					} ];
					parentFrame = stack[ stack.length - 2 ];
					if ( parentFrame ) {
						retval[ 0 ].index = parentFrame.index;
					}
				}

				// Move up the stack
				stack.pop();
				if ( stack.length === 0 ) {
					// This shouldn't be possible
					return retval;
				}
				currentFrame = stack[ stack.length - 1 ];
				currentFrame.index++;
				nextNode = currentFrame.node.children[ currentFrame.index ];
				// Skip over the parent node's closing
				// (this is present for sure, because the parent has children)
				left++;
			}

			// Skip over nextNode's opening if present
			if ( nextNode.isWrapped() ) {
				left++;
			}
		}
	} while ( end >= left - 1 );
	if ( retval.length === 0 ) {
		throw new Error( 'Failed to select any nodes' );
	}
	return retval;
};

/**
 * Get groups of sibling nodes covered by the given range.
 *
 * @param {ve.Range} range
 * @return {Array} Array of objects. Each object has the following keys:
 *
 *  - nodes: Array of sibling nodes covered by a part of range
 *  - parent: Parent of all of these nodes
 *  - grandparent: parent's parent
 */
ve.Document.prototype.getCoveredSiblingGroups = function ( range ) {
	var leaves = this.selectNodes( range, 'leaves' ),
		groups = [],
		lastEndOffset = 0;
	for ( var i = 0; i < leaves.length; i++ ) {
		if ( leaves[ i ].nodeOuterRange.end <= lastEndOffset ) {
			// This range is contained within a range we've already processed
			continue;
		}
		var node = leaves[ i ].node;
		// Traverse up to a content branch from content elements
		if ( node.isContent() ) {
			node = node.getParent();
		}
		var parentNode = node.getParent();
		if ( !parentNode ) {
			break;
		}
		// Group this with its covered siblings
		groups.push( {
			parent: parentNode,
			grandparent: parentNode.getParent(),
			nodes: []
		} );
		// Seek forward to the last covered sibling
		var siblingNode = node;
		do {
			// Add this to its sibling's group
			groups[ groups.length - 1 ].nodes.push( siblingNode );
			i++;
			if ( leaves[ i ] === undefined ) {
				break;
			}
			// Traverse up to a content branch from content elements
			siblingNode = leaves[ i ].node;
			if ( siblingNode.isContent() ) {
				siblingNode = siblingNode.getParent();
			}
		} while ( siblingNode.getParent() === parentNode );
		i--;
		lastEndOffset = parentNode.getOuterRange().end;
	}
	return groups;
};

/**
 * Test whether a range lies within a single leaf node.
 *
 * @param {ve.Range} range The range to test
 * @return {boolean} Whether the range lies within a single node
 */
ve.Document.prototype.rangeInsideOneLeafNode = function ( range ) {
	var selected = this.selectNodes( range, 'leaves' );
	return selected.length === 1 && selected[ 0 ].nodeRange.containsRange( range ) && selected[ 0 ].indexInNode === undefined;
};

/**
 * Callback when a node is attached with ve.Node#setDocument
 *
 * The node and all its children are guaranteed to be attached
 *
 * @param {ve.Node} node The node attached
 */
ve.Document.prototype.nodeAttached = function ( node ) {
	this.emit( 'nodeAttached', node );
};

/**
 * Callback when a node is attached with ve.Node#setDocument
 *
 * The node and all its children are guaranteed to be attached
 *
 * @param {ve.Node} node The node detached
 */
ve.Document.prototype.nodeDetached = function ( node ) {
	this.emit( 'nodeDetached', node );
};
