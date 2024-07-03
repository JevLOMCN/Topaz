/*!
 * VisualEditor DataModel MWHeadingNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki heading node.
 *
 * @class
 * @extends ve.dm.HeadingNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWHeadingNode = function VeDmMWHeadingNode() {
	// Parent constructor
	ve.dm.MWHeadingNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWHeadingNode, ve.dm.HeadingNode );

/* Static Properties */

ve.dm.MWHeadingNode.static.name = 'mwHeading';

// Headings in wikitext only work in some contexts, they're impossible e.g. in list items
ve.dm.MWHeadingNode.static.suggestedParentNodeTypes = [ 'document', 'tableCell', 'div', 'section' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWHeadingNode );
