/*!
 * VisualEditor DataModel TableCaptionNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel table caption node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.TableCaptionNode = function VeDmTableCaptionNode() {
	// Parent constructor
	ve.dm.TableCaptionNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.TableCaptionNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.TableCaptionNode.static.name = 'tableCaption';

ve.dm.TableCaptionNode.static.parentNodeTypes = [ 'table' ];

ve.dm.TableCaptionNode.static.matchTagNames = [ 'caption' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.TableCaptionNode );
