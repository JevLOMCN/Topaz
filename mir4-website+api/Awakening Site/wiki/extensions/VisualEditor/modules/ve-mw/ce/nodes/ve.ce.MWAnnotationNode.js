/*!
 * VisualEditor ContentEditable MWAnnotationNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MW node for annotation tags.
 *
 * @class
 * @extends ve.ce.AlienInlineNode
 * @constructor
 * @param {ve.dm.MWAnnotationNode} model
 * @param {Object} [config]
 */
ve.ce.MWAnnotationNode = function VeCeMWAnnotationNode() {
	// Parent constructor
	ve.ce.MWAnnotationNode.super.apply( this, arguments );

	// DOM changes
	this.$element
		.addClass( 've-ce-mwAnnotationNode' )
		.text( this.model.getWikitextTag() );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWAnnotationNode, ve.ce.AlienInlineNode );
