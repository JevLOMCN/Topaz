/*!
 * VisualEditor DataModel MWMathNode class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * DataModel MediaWiki math node.
 *
 * @class
 * @extends ve.dm.MWLatexNode
 *
 * @constructor
 * @param {Object} [element]
 */
ve.dm.MWMathNode = function VeDmMWMathNode() {
	// Parent constructor
	ve.dm.MWMathNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWMathNode, ve.dm.MWLatexNode );

/* Static members */

ve.dm.MWMathNode.static.name = 'mwMath';

ve.dm.MWMathNode.static.extensionName = 'math';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWMathNode );
