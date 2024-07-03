/*!
 * VisualEditor ContentEditable MWChemNode class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * ContentEditable MediaWiki chem node.
 *
 * @class
 * @extends ve.ce.MWInlineExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWChemNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWChemNode = function VeCeMWChemNode() {
	// Parent constructor
	ve.ce.MWChemNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWChemNode, ve.ce.MWLatexNode );

/* Static Properties */

ve.ce.MWChemNode.static.name = 'mwChem';

ve.ce.MWChemNode.static.primaryCommandName = 'chemDialog';

ve.ce.MWChemNode.static.iconWhenInvisible = 'labFlask';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWChemNode );
