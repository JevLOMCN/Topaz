/*!
 * VisualEditor DataModel MWLatexNode class.
 *
 * An abstract class that has most of the common functionality
 * for the different tags in the Math extension.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * DataModel MediaWiki abstract LaTeX node: <math>, <chem>, etc.
 *
 * @abstract
 * @class
 * @extends ve.dm.MWInlineExtensionNode
 *
 * @constructor
 * @param {Object} [element]
 */
ve.dm.MWLatexNode = function VeDmMWLatexNode() {
	// Parent constructor
	ve.dm.MWLatexNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWLatexNode, ve.dm.MWInlineExtensionNode );

/* Static members */

ve.dm.MWLatexNode.static.tagName = 'img';

/* Static Methods */

/**
 * @inheritdoc ve.dm.GeneratedContentNode
 */
ve.dm.MWLatexNode.static.getHashObjectForRendering = function ( dataElement ) {
	// Parent method
	const hashObject = ve.dm.MWLatexNode.super.static.getHashObjectForRendering.call( this, dataElement );

	// The id does not affect the rendering.
	if ( hashObject.mw.attrs ) {
		delete hashObject.mw.attrs.id;
	}
	return hashObject;
};
