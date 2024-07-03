/*!
 * VisualEditor DataModel MWAlienInlineExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki alien inline extension node.
 *
 * @class
 * @abstract
 * @extends ve.dm.MWInlineExtensionNode
 * @mixins ve.dm.MWAlienExtensionNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWAlienInlineExtensionNode = function VeDmMWAlienInlineExtensionNode() {
	// Parent constructor
	ve.dm.MWAlienInlineExtensionNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.MWAlienExtensionNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWAlienInlineExtensionNode, ve.dm.MWInlineExtensionNode );

OO.mixinClass( ve.dm.MWAlienInlineExtensionNode, ve.dm.MWAlienExtensionNode );

/* Static members */

ve.dm.MWAlienInlineExtensionNode.static.name = 'mwAlienInlineExtension';

ve.dm.MWAlienInlineExtensionNode.static.isContent = true;

ve.dm.MWAlienInlineExtensionNode.static.tagName = 'span';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWAlienInlineExtensionNode );
