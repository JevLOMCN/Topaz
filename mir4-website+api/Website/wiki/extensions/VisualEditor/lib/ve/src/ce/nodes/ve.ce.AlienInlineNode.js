/*!
 * VisualEditor ContentEditable AlienInlineNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable alien inline node.
 *
 * @class
 * @extends ve.ce.AlienNode
 *
 * @constructor
 * @param {ve.dm.AlienInlineNode} model
 * @param {Object} [config]
 */
ve.ce.AlienInlineNode = function VeCeAlienInlineNode() {
	// Parent constructor
	ve.ce.AlienInlineNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.AlienInlineNode, ve.ce.AlienNode );

/* Static Properties */

ve.ce.AlienInlineNode.static.name = 'alienInline';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.AlienInlineNode );
