/*!
 * VisualEditor DataModel MWBlockSyntaxHighlightNode class.
 *
 * @copyright VisualEditor Team and others
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki block syntax highlight node.
 *
 * @class
 *
 * @constructor
 */
ve.dm.MWBlockSyntaxHighlightNode = function VeDmMWBlockSyntaxHighlightNode() {
	// Parent method
	ve.dm.MWBlockExtensionNode.super.apply( this, arguments );

	// Mixin method
	ve.dm.MWSyntaxHighlightNode.call( this );
};

OO.inheritClass( ve.dm.MWBlockSyntaxHighlightNode, ve.dm.MWBlockExtensionNode );

OO.mixinClass( ve.dm.MWBlockSyntaxHighlightNode, ve.dm.MWSyntaxHighlightNode );

ve.dm.MWBlockSyntaxHighlightNode.static.name = 'mwBlockSyntaxHighlight';

ve.dm.MWBlockSyntaxHighlightNode.static.tagName = 'div';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWBlockSyntaxHighlightNode );
