/*!
 * VisualEditor DataModel BranchNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel branch node.
 *
 * Branch nodes can have branch or leaf nodes as children.
 *
 * @abstract
 * @extends ve.dm.Node
 * @mixins ve.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children] Child nodes to attach
 */
ve.dm.BranchNode = function VeDmBranchNode( element, children ) {
	// Mixin constructor
	ve.BranchNode.call( this );

	// Parent constructor
	ve.dm.BranchNode.super.call( this, element );

	// Properties
	this.slugPositions = {};

	// TODO: children is only ever used in tests
	if ( Array.isArray( children ) && children.length ) {
		this.splice.apply( this, [ 0, 0 ].concat( children ) );
	}
};

/**
 * @event splice
 * @see #method-splice
 * @param {number} index
 * @param {number} howmany
 * @param {ve.dm.BranchNode} [childModel]
 */

/**
 * @event update
 */

/* Inheritance */

OO.inheritClass( ve.dm.BranchNode, ve.dm.Node );

OO.mixinClass( ve.dm.BranchNode, ve.BranchNode );

/* Methods */

/**
 * Add a child node to the end of the list.
 *
 * @param {ve.dm.BranchNode} childModel Item to add
 * @return {number} New number of children
 * @fires splice
 * @fires update
 */
ve.dm.BranchNode.prototype.push = function ( childModel ) {
	this.splice( this.children.length, 0, childModel );
	return this.children.length;
};

/**
 * Remove a child node from the end of the list.
 *
 * @return {ve.dm.BranchNode} Removed childModel
 * @fires splice
 * @fires update
 */
ve.dm.BranchNode.prototype.pop = function () {
	if ( this.children.length ) {
		var childModel = this.children[ this.children.length - 1 ];
		this.splice( this.children.length - 1, 1 );
		return childModel;
	}
};

/**
 * Add a child node to the beginning of the list.
 *
 * @param {ve.dm.BranchNode} childModel Item to add
 * @return {number} New number of children
 * @fires splice
 * @fires update
 */
ve.dm.BranchNode.prototype.unshift = function ( childModel ) {
	this.splice( 0, 0, childModel );
	return this.children.length;
};

/**
 * Remove a child node from the beginning of the list.
 *
 * @return {ve.dm.BranchNode} Removed childModel
 * @fires splice
 * @fires update
 */
ve.dm.BranchNode.prototype.shift = function () {
	if ( this.children.length ) {
		var childModel = this.children[ 0 ];
		this.splice( 0, 1 );
		return childModel;
	}
};

/**
 * Add and/or remove child nodes at an offset.
 *
 * @param {number} index Index to remove and or insert nodes at
 * @param {number} howmany Number of nodes to remove
 * @param {...ve.dm.BranchNode} [nodes] Variadic list of nodes to insert
 * @fires splice
 * @return {ve.dm.BranchNode[]} Removed nodes
 */
ve.dm.BranchNode.prototype.splice = function () {
	var args = Array.prototype.slice.call( arguments ),
		diff = 0;

	var i, length;
	var removals = this.children.splice.apply( this.children, args );
	for ( i = 0, length = removals.length; i < length; i++ ) {
		removals[ i ].detach();
		diff -= removals[ i ].getOuterLength();
	}

	if ( args.length >= 3 ) {
		length = args.length;
		for ( i = 2; i < length; i++ ) {
			args[ i ].attach( this );
			diff += args[ i ].getOuterLength();
		}
	}

	this.adjustLength( diff, true );
	this.setupBlockSlugs();
	this.emit.apply( this, [ 'splice' ].concat( args ) );

	return removals;
};

/**
 * Setup a sparse array of booleans indicating where to place slugs
 *
 * TODO: The function name is misleading: in ContentBranchNodes it sets up inline slugs
 */
ve.dm.BranchNode.prototype.setupBlockSlugs = function () {
	var isBlock = this.canHaveChildrenNotContent();

	this.slugPositions = {};

	if ( isBlock && !this.isAllowedChildNodeType( 'paragraph' ) ) {
		// Don't put slugs in nodes which can't contain paragraphs
		return;
	}

	// Consider every position between two child nodes, before first child and after last child.
	// Skip over metadata children. Add slugs in appropriate places.

	// Support: Firefox
	// Note that this inserts a slug at position 0 if this content branch has no items or only
	// internal items, keeping the node from becoming invisible/unfocusable. In Firefox, backspace
	// after Ctrl+A leaves the document completely empty, so this ensures DocumentNode gets a slug.

	var len = this.children.length;
	var i = -1; // from -1 to len-1
	var j = 0; // from 0 to len
	while ( i < len ) {
		// If the next node is a meta item, find the first non-meta node after it, and consider that
		// one instead when deciding to insert a slug. Meta nodes themselves don't have slugs.
		while ( j < len && this.children[ j ].isMetaData() ) {
			j++;
		}

		// Can have slug at the beginning, or after every node which allows it (except internal nodes)
		var canHaveSlugAfter = i === -1 || ( this.children[ i ].canHaveSlugAfter() &&
			!this.children[ i ].isInternal() );
		// Can have slug at the end, or before every node which allows it
		var canHaveSlugBefore = j === len || this.children[ j ].canHaveSlugBefore();

		if ( canHaveSlugAfter && canHaveSlugBefore ) {
			var suppressSlugTypeAfter = this.children[ j ] && this.children[ j ].suppressSlugType();
			var suppressSlugTypeBefore = this.children[ i ] && this.children[ i ].suppressSlugType();
			// Slugs are suppressed if they have the same string type, e.g. for adjacent floated images
			if ( !( typeof suppressSlugTypeAfter === 'string' && suppressSlugTypeAfter === suppressSlugTypeBefore ) ) {
				this.slugPositions[ j ] = true;
			}
		}

		i = j;
		j++;
	}
};

/**
 * Check in the branch node has a slug at a particular offset
 *
 * @param {number} offset Offset to check for a slug at
 * @return {boolean} There is a slug at the offset
 */
ve.dm.BranchNode.prototype.hasSlugAtOffset = function ( offset ) {
	var startOffset = this.getOffset() + ( this.isWrapped() ? 1 : 0 );

	if ( offset === startOffset ) {
		return !!this.slugPositions[ 0 ];
	}
	for ( var i = 0; i < this.children.length; i++ ) {
		startOffset += this.children[ i ].getOuterLength();
		if ( offset === startOffset ) {
			return !!this.slugPositions[ i + 1 ];
		}
	}
	return false;
};
