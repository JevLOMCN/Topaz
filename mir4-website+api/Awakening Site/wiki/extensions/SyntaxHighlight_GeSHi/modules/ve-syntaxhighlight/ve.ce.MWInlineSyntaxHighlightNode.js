/*!
 * VisualEditor ContentEditable MWInlineSyntaxHighlightNode class.
 *
 * @copyright VisualEditor Team and others
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki inline syntax highlight node.
 *
 * @class
 * @abstract
 *
 * @constructor
 */
ve.ce.MWInlineSyntaxHighlightNode = function VeCeMWInlineSyntaxHighlightNode() {
	// Parent method
	ve.ce.MWInlineExtensionNode.super.apply( this, arguments );

	// Mixin method
	ve.ce.MWSyntaxHighlightNode.call( this );
};

OO.inheritClass( ve.ce.MWInlineSyntaxHighlightNode, ve.ce.MWInlineExtensionNode );

OO.mixinClass( ve.ce.MWInlineSyntaxHighlightNode, ve.ce.MWSyntaxHighlightNode );

ve.ce.MWInlineSyntaxHighlightNode.static.name = 'mwInlineSyntaxHighlight';

ve.ce.MWInlineSyntaxHighlightNode.static.primaryCommandName = 'syntaxhighlightInspector';

ve.ce.MWInlineSyntaxHighlightNode.static.getDescription = function ( model ) {
	return ve.getProp( model.getAttribute( 'mw' ), 'attrs', 'lang' );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWInlineSyntaxHighlightNode );
