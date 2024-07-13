/*!
 * VisualEditor InternalItemNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable internal item node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.InternalItemNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.InternalItemNode = function VeCeInternalItemNode() {
	// Parent constructor
	ve.ce.InternalItemNode.super.apply( this, arguments );

	this.$element.addClass( 've-ce-internalItemNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.InternalItemNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.InternalItemNode.static.name = 'internalItem';

ve.ce.InternalItemNode.static.tagName = 'span';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.InternalItemNode );
