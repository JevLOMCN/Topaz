/*!
 * VisualEditor ContentEditable CheckListNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable list node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.CheckListNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.CheckListNode = function VeCeCheckListNode() {
	// Parent constructor
	ve.ce.CheckListNode.super.apply( this, arguments );

	this.$element.addClass( 've-ce-checkListNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.CheckListNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.CheckListNode.static.name = 'checkList';

ve.ce.CheckListNode.static.tagName = 'ul';

ve.ce.CheckListNode.static.removeEmptyLastChildOnEnter = true;

/* Registration */

ve.ce.nodeFactory.register( ve.ce.CheckListNode );
