/*!
 * VisualEditor DataModel DefinitionListNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel definition list node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.DefinitionListNode = function VeDmDefinitionListNode() {
	// Parent constructor
	ve.dm.DefinitionListNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.DefinitionListNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.DefinitionListNode.static.name = 'definitionList';

ve.dm.DefinitionListNode.static.childNodeTypes = [ 'definitionListItem' ];

ve.dm.DefinitionListNode.static.matchTagNames = [ 'dl' ];

ve.dm.DefinitionListNode.static.isDiffedAsList = true;

// Nodes which are diffed as a list must have the same description logic as each other
ve.dm.DefinitionListNode.static.describeChanges = function () {
	return ve.dm.ListNode.static.describeChanges.apply( this, arguments );
};

ve.dm.DefinitionListNode.static.describeChange = function () {
	return ve.dm.ListNode.static.describeChange.apply( this, arguments );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.DefinitionListNode );
