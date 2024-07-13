/*!
 * VisualEditor DataModel InternalItemNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel internal item node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.InternalItemNode = function VeDmInternalItemNode() {
	// Parent constructor
	ve.dm.InternalItemNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.InternalItemNode, ve.dm.BranchNode );

/* Static members */

ve.dm.InternalItemNode.static.name = 'internalItem';

ve.dm.InternalItemNode.static.matchTagNames = [];

ve.dm.InternalItemNode.static.ignoreChildren = true;

ve.dm.InternalItemNode.static.isInternal = true;

ve.dm.InternalItemNode.static.isDeletable = false;

ve.dm.InternalItemNode.static.parentNodeTypes = [ 'internalList' ];

/* Methods */

/**
 * @inheritdoc
 */
ve.dm.InternalItemNode.static.describeChanges = function () {
	return [];
};

/**
 * @inheritdoc
 */
ve.dm.InternalItemNode.static.getHashObject = function ( dataElement ) {
	return { type: dataElement.type };
};

ve.dm.InternalItemNode.prototype.getAttributes = function () {
	// InternalItem node has an originalHtml attribute which confuses the differ
	// TODO: Move this to a new model method like getAttributesForDiff
	return {};
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.InternalItemNode );
