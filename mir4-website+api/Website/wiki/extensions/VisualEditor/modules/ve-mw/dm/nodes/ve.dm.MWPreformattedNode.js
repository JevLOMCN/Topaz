/*!
 * VisualEditor DataModel MWPreformattedNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki preformatted node.
 *
 * @class
 * @extends ve.dm.PreformattedNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWPreformattedNode = function VeDmMWPreformattedNode() {
	// Parent constructor
	ve.dm.MWPreformattedNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWPreformattedNode, ve.dm.PreformattedNode );

/* Static Properties */

ve.dm.MWPreformattedNode.static.name = 'mwPreformatted';

// Indent-pre in wikitext only works in some contexts, it's impossible e.g. in list items
ve.dm.MWPreformattedNode.static.suggestedParentNodeTypes = [ 'document', 'tableCell', 'div', 'section' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWPreformattedNode );
