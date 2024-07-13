/*!
 * VisualEditor ContentEditable DefinitionListNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable definition list node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.DefinitionListNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.DefinitionListNode = function VeCeDefinitionListNode() {
	// Parent constructor
	ve.ce.DefinitionListNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.DefinitionListNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.DefinitionListNode.static.name = 'definitionList';

ve.ce.DefinitionListNode.static.tagName = 'dl';

ve.ce.DefinitionListNode.static.removeEmptyLastChildOnEnter = true;

/* Registration */

ve.ce.nodeFactory.register( ve.ce.DefinitionListNode );
