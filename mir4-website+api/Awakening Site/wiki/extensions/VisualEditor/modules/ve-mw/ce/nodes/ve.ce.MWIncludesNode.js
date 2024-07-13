/*!
 * VisualEditor ContentEditable MWIncludesNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MW node for noinclude, includeonly and onlyinclude tags.
 *
 * @class
 * @extends ve.ce.AlienInlineNode
 * @constructor
 * @param {ve.dm.MWIncludesNode} model
 * @param {Object} [config]
 */
ve.ce.MWIncludesNode = function VeCeMWIncludesNode() {
	// Parent constructor
	ve.ce.MWIncludesNode.super.apply( this, arguments );

	// DOM changes
	this.$element
		.addClass( 've-ce-mwIncludesNode' )
		.text( this.model.getWikitextTag() );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWIncludesNode, ve.ce.AlienInlineNode );

/* Static Properties */

ve.ce.MWIncludesNode.static.name = 'mwIncludes';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWIncludesNode );
