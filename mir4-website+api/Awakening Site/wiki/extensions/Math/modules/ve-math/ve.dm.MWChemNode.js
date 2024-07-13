/*!
 * VisualEditor DataModel MWChemNode class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * DataModel MediaWiki chem node.
 *
 * @class
 * @extends ve.dm.MWLatexNode
 *
 * @constructor
 * @param {Object} [element]
 */
ve.dm.MWChemNode = function VeDmMWChemNode() {
	// Parent constructor
	ve.dm.MWChemNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWChemNode, ve.dm.MWLatexNode );

/* Static members */

ve.dm.MWChemNode.static.name = 'mwChem';

ve.dm.MWChemNode.static.extensionName = 'chem';

ve.dm.MWChemNode.static.getMatchRdfaTypes = function () {
	return [
		'mw:Extension/chem',
		'mw:Extension/ce' // Deprecated, kept for backwards compatibility
	];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWChemNode );
