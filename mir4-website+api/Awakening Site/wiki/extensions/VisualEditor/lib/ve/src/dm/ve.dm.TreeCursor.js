/*!
 * VisualEditor DataModel TreeCursor class
 *
 * @copyright See AUTHORS.txt
 */

// TODO identify a core of "trusted" code that is guaranteed to detect tree invalidation.
// Probably needs: removeNode insertNode removeText insertText

/**
 * DataModel TreeCursor - a tree walker that tracks the path to the current position.
 *
 * @class
 *
 * @constructor
 * @param {ve.dm.Node} root A document node or a branch root within which to walk
 * @param {ve.dm.Node[]} liveIgnoreNodes Live array of nodes to ignore (cross without counting)
 * @param {number} [linearOffset] The first linear model offset inside root; default 0
 */
ve.dm.TreeCursor = function VeDmTreeCursor( root, liveIgnoreNodes, linearOffset ) {
	this.root = root;
	this.liveIgnoreNodes = liveIgnoreNodes;
	this.path = [];
	this.offset = 0;
	this.nodes = [ root ];
	this.node = root;
	this.lastStep = null;
	if ( linearOffset === undefined ) {
		linearOffset = 0;
	}
	this.linearOffset = linearOffset;
};

/* Inheritance */

OO.initClass( ve.dm.TreeCursor );

/* Methods */

/**
 * Skip past ignored nodes and text node boundaries
 *
 * @param {number} [tooShort] Only step into text nodes longer than this
 */
ve.dm.TreeCursor.prototype.normalizeCursor = function ( tooShort ) {
	if ( !this.node ) {
		return;
	}
	if ( tooShort === undefined ) {
		tooShort = -1;
	}

	// If at the end of a text node, step out
	if ( this.node.type === 'text' && this.offset === this.node.length ) {
		this.nodes.pop();
		this.node = this.nodes[ this.nodes.length - 1 ];
		this.offset = this.path.pop() + 1;
	}
	this.crossIgnoredNodes();

	var item;
	// If at the start of long enough text node, step in
	if (
		this.node.hasChildren() &&
		( item = this.node.children[ this.offset ] ) &&
		item.type === 'text' &&
		item.length > tooShort
	) {
		this.node = item;
		this.nodes.push( item );
		this.path.push( this.offset );
		this.offset = 0;
	}
};

/**
 * Cross any immediately following nodes that are in liveIgnoreNodes
 */
ve.dm.TreeCursor.prototype.crossIgnoredNodes = function () {
	var parent, nextSibling;
	if (
		this.node &&
		this.node.type === 'text' &&
		this.offset === this.node.length &&
		( parent = this.nodes[ this.nodes.length - 2 ] ) &&
		( nextSibling = parent.children[ this.path[ this.path.length - 1 ] + 1 ] ) &&
		this.liveIgnoreNodes.indexOf( nextSibling ) !== -1
	) {
		// At the end of a text node and the next node is ignored
		this.stepOut();
	}
	var len = ( this.node && this.node.hasChildren() && this.node.children.length ) || 0;
	var item;
	while (
		this.offset < len &&
		( item = this.node.children[ this.offset ] ) &&
		this.liveIgnoreNodes.indexOf( item ) !== -1
	) {
		this.offset++;
		this.linearOffset += item.getOuterLength();
	}
};

/**
 * Validate this.linearOffset against this.node and this.offset
 *
 * TODO: Improve the unit tests, then move this method to the unit testing framework
 *
 * @throws {Error} If this.linearOffset does not match this.node and this.offset
 */
ve.dm.TreeCursor.prototype.checkLinearOffset = function () {
	var expected = this.node.getOffset();
	if ( this.node.type === 'text' ) {
		expected += this.offset;
	} else {
		if ( this.node !== this.root ) {
			// Add 1 for the open tag
			expected += 1;
		}
		if ( this.node.hasChildren() ) {
			// Add the outer length of each crossed child
			this.node.children.slice( 0, this.offset ).forEach( function ( child ) {
				expected += child.getOuterLength();
			} );
		}
	}
	if ( expected !== this.linearOffset ) {
		throw new Error( 'Linear offset does not match tree position' );
	}
};

/**
 * Take a single step in the walk, consuming no more than a given linear model length
 *
 * A "single step" means either stepping across text content, or stepping over a node, or
 * stepping into/out of a non-text node. (Steps into/out of text nodes happen transparently)
 *
 * See https://phabricator.wikimedia.org/T162762 for the algorithm
 *
 * @param {number} maxLength Maximum linear model length to step over (integer >= 1)
 * @return {Object|null} The type of step taken, or null if there are no more steps
 * @return {string} return.type open|close|cross|crosstext
 * @return {number} return.length Linear length of the step (integer >= 1)
 * @return {number[]} return.path Offset path from the root to the node containing the stepped item
 * @return {ve.dm.Node|null} return.node The node containing the stepped item
 * @return {number} return.offset The offset of the stepped item within its parent
 * @return {number} [return.offsetLength] Number of characters 'crosstext' passed
 * @return {ve.dm.Node} [return.item] The node stepped into/out of/across (absent for 'crosstext')
 */
ve.dm.TreeCursor.prototype.stepAtMost = function ( maxLength ) {
	if ( !this.node ) {
		this.lastStep = null;
		return null;
	}
	// On very large pages this is a performance bottleneck, so only run in tests
	if ( ve.test ) {
		this.checkLinearOffset();
	}
	this.normalizeCursor( maxLength );
	var length, step;
	if ( this.node.type === 'text' ) {
		// We cannot be the end, because we just normalized
		length = Math.min( maxLength, this.node.length - this.offset );
		step = {
			type: 'crosstext',
			length: length,
			path: this.path.slice(),
			node: this.node,
			offset: this.offset,
			offsetLength: length
		};
		this.offset += step.length;
		this.lastStep = step;
		this.linearOffset += length;
		return step;
	}
	// Else not a text node
	var childLength = this.node.hasChildren() ? this.node.children.length : 0;
	if ( this.offset > childLength ) {
		throw new Error( 'Offset ' + this.offset + ' > childLength ' + childLength );
	}
	if ( this.offset === childLength ) {
		return this.stepOut();
	}
	// Else there are unpassed child nodes
	var item = this.node.children[ this.offset ];
	if ( item.getOuterLength() > maxLength ) {
		return this.stepIn();
	}
	// Else step across this item
	length = item.getOuterLength();
	step = {
		type: 'cross',
		length: length,
		path: this.path.slice(),
		node: this.node,
		offset: this.offset,
		item: item
	};
	this.offset++;
	this.lastStep = step;
	this.linearOffset += length;
	return step;
};

/**
 * Step into the next node
 *
 * @return {Object} The step
 */
ve.dm.TreeCursor.prototype.stepIn = function () {
	if (
		this.node.type === 'text' ||
		!this.node.hasChildren() ||
		this.offset >= this.node.children.length
	) {
		throw new Error( 'No node to step into' );
	}
	var item = this.node.children[ this.offset ];
	var length = item.type === 'text' ? 0 : 1;
	var step = {
		type: 'open',
		length: length,
		path: this.path.slice(),
		node: this.node,
		offset: this.offset,
		item: item
	};
	this.path.push( this.offset );
	this.nodes.push( item );
	this.node = item;
	this.offset = 0;
	this.lastStep = step;
	this.linearOffset += length;
	return step;
};

/**
 * Step out of the current node (skipping past any uncrossed children or text within)
 *
 * @return {Object|null} The step
 */
ve.dm.TreeCursor.prototype.stepOut = function () {
	var treeCursor = this,
		priorOffset = this.offset;
	var item = this.nodes.pop();
	this.node = this.nodes[ this.nodes.length - 1 ];
	this.offset = this.path.pop();
	if ( this.node === undefined ) {
		// Stepped out of the root
		this.lastStep = null;
		return null;
	}
	if ( item.type === 'text' ) {
		this.linearOffset += item.getLength() - priorOffset;
	} else {
		if ( item.hasChildren() ) {
			// Increase linearOffset by the length of each child
			item.children.slice( priorOffset ).forEach( function ( child ) {
				treeCursor.linearOffset += child.getOuterLength();
			} );
		}
		// Increase linearOffset for the close tag
		this.linearOffset++;
	}
	var step = {
		type: 'close',
		length: item.type === 'text' ? 0 : 1,
		path: this.path.slice(),
		node: this.node,
		offset: this.offset,
		item: item
	};
	this.offset++;
	this.lastStep = step;
	return step;
};
