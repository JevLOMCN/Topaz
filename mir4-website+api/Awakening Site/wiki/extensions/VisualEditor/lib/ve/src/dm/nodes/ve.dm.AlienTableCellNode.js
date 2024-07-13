/*!
 * VisualEditor DataModel AlienTableCellNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel alien table cell node.
 *
 * @class
 * @extends ve.dm.AlienNode
 * @mixins ve.dm.TableCellableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.AlienTableCellNode = function VeDmAlienTableCellNode() {
	// Parent constructor
	ve.dm.AlienTableCellNode.super.apply( this, arguments );

	// Mixin constructor
	ve.dm.TableCellableNode.call( this );
};

OO.inheritClass( ve.dm.AlienTableCellNode, ve.dm.AlienNode );

OO.mixinClass( ve.dm.AlienTableCellNode, ve.dm.TableCellableNode );

ve.dm.AlienTableCellNode.static.name = 'alienTableCell';

ve.dm.AlienTableCellNode.static.matchFunction = null;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.AlienTableCellNode );
