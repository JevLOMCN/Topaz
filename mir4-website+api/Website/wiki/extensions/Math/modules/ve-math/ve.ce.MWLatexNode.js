/*!
 * VisualEditor ContentEditable MWLatexNode class.
 *
 * An abstract class that has most of the common functionality
 * for the different tags in the Math extension.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * ContentEditable MediaWiki abstract LaTeX node: <math>, <chem>, etc.
 *
 * @abstract
 * @class
 * @extends ve.ce.MWInlineExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWLatexNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWLatexNode = function VeCeMWLatexNode() {
	// Parent constructor
	ve.ce.MWLatexNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWLatexNode, ve.ce.MWInlineExtensionNode );

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.MWLatexNode.prototype.onSetup = function () {
	// Parent method
	ve.ce.MWLatexNode.super.prototype.onSetup.call( this );

	// DOM changes
	this.$element.addClass( 've-ce-mwLatexNode' );
};

/**
 * @inheritdoc ve.ce.GeneratedContentNode
 */
ve.ce.MWLatexNode.prototype.validateGeneratedContents = function ( $element ) {
	return !( $element.find( '.error' ).addBack( '.error' ).length );
};
