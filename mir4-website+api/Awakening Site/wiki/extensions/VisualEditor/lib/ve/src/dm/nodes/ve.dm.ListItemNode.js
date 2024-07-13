/*!
 * VisualEditor DataModel ListItemNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel list item node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.ListItemNode = function VeDmListItemNode() {
	// Parent constructor
	ve.dm.ListItemNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.ListItemNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.ListItemNode.static.name = 'listItem';

ve.dm.ListItemNode.static.parentNodeTypes = [ 'list' ];

ve.dm.ListItemNode.static.matchTagNames = [ 'li' ];

// Nodes which are diffed as a list must have the same description logic as each other
ve.dm.ListItemNode.static.describeChanges = function () {
	return ve.dm.ListNode.static.describeChanges.apply( this, arguments );
};

ve.dm.ListItemNode.static.describeChange = function () {
	return ve.dm.ListNode.static.describeChange.apply( this, arguments );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.ListItemNode );
