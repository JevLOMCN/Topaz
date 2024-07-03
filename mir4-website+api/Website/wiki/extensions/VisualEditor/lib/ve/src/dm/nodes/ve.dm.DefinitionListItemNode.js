/*!
 * VisualEditor DataModel DefinitionListItemNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel definition list item node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.DefinitionListItemNode = function VeDmDefinitionListItemNode() {
	// Parent constructor
	ve.dm.DefinitionListItemNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.DefinitionListItemNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.DefinitionListItemNode.static.name = 'definitionListItem';

ve.dm.DefinitionListItemNode.static.parentNodeTypes = [ 'definitionList' ];

ve.dm.DefinitionListItemNode.static.defaultAttributes = {
	style: 'term'
};

ve.dm.DefinitionListItemNode.static.matchTagNames = [ 'dt', 'dd' ];

ve.dm.DefinitionListItemNode.static.toDataElement = function ( domElements ) {
	var style = domElements[ 0 ].nodeName.toLowerCase() === 'dt' ? 'term' : 'definition';
	return { type: this.name, attributes: { style: style } };
};

ve.dm.DefinitionListItemNode.static.toDomElements = function ( dataElement, doc ) {
	var tag = dataElement.attributes && dataElement.attributes.style === 'term' ? 'dt' : 'dd';
	return [ doc.createElement( tag ) ];
};

// Nodes which are diffed as a list must have the same description logic as each other
ve.dm.DefinitionListItemNode.static.describeChanges = function () {
	return ve.dm.ListNode.static.describeChanges.apply( this, arguments );
};

ve.dm.DefinitionListItemNode.static.describeChange = function () {
	return ve.dm.ListNode.static.describeChange.apply( this, arguments );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.DefinitionListItemNode );
