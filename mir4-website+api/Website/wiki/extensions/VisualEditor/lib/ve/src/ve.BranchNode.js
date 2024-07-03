/*!
 * VisualEditor BranchNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Branch node mixin.
 *
 * Extenders are expected to inherit from ve.Node.
 *
 * Branch nodes are immutable, which is why there are no methods for adding or removing children.
 * DataModel classes will add this functionality, and other subclasses will implement behavior that
 * mimics changes made to DataModel nodes.
 *
 * @class
 * @abstract
 * @constructor
 * @param {ve.Node[]} children Array of children to add
 */
ve.BranchNode = function VeBranchNode( children ) {
	this.children = Array.isArray( children ) ? children : [];
};

/* Setup */

OO.initClass( ve.BranchNode );

/* Methods */

/**
 * Traverse a branch node depth-first.
 *
 * @param {Function} callback Callback to execute for each traversed node
 * @param {ve.Node} callback.node Node being traversed
 */
ve.BranchNode.prototype.traverse = function ( callback ) {
	var children = this.getChildren();

	for ( var i = 0, len = children.length; i < len; i++ ) {
		callback.call( this, children[ i ] );
		if ( children[ i ].hasChildren() ) {
			children[ i ].traverse( callback );
		}
	}
};

/**
 * Check if the node has children.
 *
 * @return {boolean} Whether the node has children
 */
ve.BranchNode.prototype.hasChildren = function () {
	return true;
};

/**
 * Get child nodes.
 *
 * @return {ve.Node[]} List of child nodes
 */
ve.BranchNode.prototype.getChildren = function () {
	return this.children;
};

/**
 * Get the index of a child node.
 *
 * @param {ve.dm.Node} node Child node to find index of
 * @return {number} Index of child node or -1 if node was not found
 */
ve.BranchNode.prototype.indexOf = function ( node ) {
	return this.children.indexOf( node );
};

/**
 * Set the root node.
 *
 * @see ve.Node#setRoot
 * @param {ve.BranchNode|null} root Node to use as root
 */
ve.BranchNode.prototype.setRoot = function ( root ) {
	var oldRoot = this.root;
	if ( root === oldRoot ) {
		// Nothing to do, don't recurse into all descendants
		return;
	}
	var i, len;
	if ( oldRoot ) {
		// Null the root, then recurse into children, then emit unroot.
		// That way, at emit time, all this node's ancestors and descendants have
		// null root.
		this.root = null;
		for ( i = 0, len = this.children.length; i < len; i++ ) {
			this.children[ i ].setRoot( null );
		}
		this.emit( 'unroot', oldRoot );
	}
	this.root = root;
	if ( root ) {
		// We've set the new root, so recurse into children, then emit root.
		// That way, at emit time, all this node's ancestors and descendants have
		// the new root.
		for ( i = 0, len = this.children.length; i < len; i++ ) {
			this.children[ i ].setRoot( root );
		}
		this.emit( 'root', root );
	}
};

/**
 * Set the document the node is a part of.
 *
 * @see ve.Node#setDocument
 * @param {ve.Document} doc Document this node is a part of
 */
ve.BranchNode.prototype.setDocument = function ( doc ) {
	var oldDoc = this.doc;
	if ( doc === this.doc ) {
		// Nothing to do, don't recurse into all descendants
		return;
	}
	var i, len;
	if ( oldDoc ) {
		// Null the doc, then recurse into children, then notify the doc.
		// That way, at notify time, all this node's ancestors and descendants have
		// null doc.
		this.doc = null;
		for ( i = 0, len = this.children.length; i < len; i++ ) {
			this.children[ i ].setDocument( null );
		}
		oldDoc.nodeDetached( this );
	}
	this.doc = doc;
	if ( doc ) {
		// We've set the new doc, so recurse into children, then notify the doc.
		// That way, at notify time, all this node's ancestors and descendants have
		// the new doc.
		for ( i = 0, len = this.children.length; i < len; i++ ) {
			this.children[ i ].setDocument( doc );
		}
		doc.nodeAttached( this );
	}
};

/**
 * Get a node from an offset.
 *
 * This method is pretty expensive. If you need to get different slices of the same content, get
 * the content first, then slice it up locally.
 *
 * @param {number} offset Offset get node for
 * @param {boolean} [shallow] Do not iterate into child nodes of child nodes
 * @return {ve.Node|null} Node at offset, or null if none was found
 * @throws {Error} If offset is out of bounds
 */
ve.BranchNode.prototype.getNodeFromOffset = function ( offset, shallow ) {
	var currentNode = this;
	if ( typeof offset !== 'number' ) {
		throw new Error( 'Offset must be a number' );
	}
	if ( offset === 0 ) {
		return currentNode;
	}
	if ( offset < 0 ) {
		throw new Error( 'Offset out of bounds' );
	}
	var nodeOffset = 0;
	// TODO a lot of logic is duplicated in selectNodes(), abstract that into a traverser or something
	SIBLINGS:
	while ( currentNode.children.length ) {
		for ( var i = 0, length = currentNode.children.length; i < length; i++ ) {
			var childNode = currentNode.children[ i ];
			if ( offset === nodeOffset ) {
				// The requested offset is right before childNode, so it's not
				// inside any of currentNode's children, but is inside currentNode
				return currentNode;
			}
			if ( childNode instanceof ve.ce.InternalListNode ) {
				break SIBLINGS;
			}
			var nodeLength = childNode.getOuterLength();
			if ( offset >= nodeOffset && offset < nodeOffset + nodeLength ) {
				if ( !shallow && childNode.hasChildren() && childNode.getChildren().length ) {
					// One of the children contains the node; increment to
					// enter the node, then iterate through children
					nodeOffset += 1;
					currentNode = childNode;
					continue SIBLINGS;
				} else {
					return childNode;
				}
			}
			nodeOffset += nodeLength;
		}
		if ( offset === nodeOffset ) {
			// The requested offset is right before currentNode.children[i], so it's
			// not inside any of currentNode's children, but is inside currentNode
			return currentNode;
		}
	}
	return null;
};
