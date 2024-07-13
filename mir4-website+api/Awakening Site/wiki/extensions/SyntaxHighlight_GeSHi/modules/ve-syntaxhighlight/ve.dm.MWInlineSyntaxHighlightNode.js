/*!
 * VisualEditor DataModel MWInlineSyntaxHighlightNode class.
 *
 * @copyright VisualEditor Team and others
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki inline syntax highlight node.
 *
 * @class
 *
 * @constructor
 */
ve.dm.MWInlineSyntaxHighlightNode = function VeDmMWInlineSyntaxHighlightNode() {
	// Parent method
	ve.dm.MWInlineExtensionNode.super.apply( this, arguments );

	// Mixin method
	ve.dm.MWSyntaxHighlightNode.call( this );
};

OO.inheritClass( ve.dm.MWInlineSyntaxHighlightNode, ve.dm.MWInlineExtensionNode );

OO.mixinClass( ve.dm.MWInlineSyntaxHighlightNode, ve.dm.MWSyntaxHighlightNode );

ve.dm.MWInlineSyntaxHighlightNode.static.name = 'mwInlineSyntaxHighlight';

ve.dm.MWInlineSyntaxHighlightNode.static.tagName = 'code';

ve.dm.MWInlineSyntaxHighlightNode.static.isContent = true;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWInlineSyntaxHighlightNode );
