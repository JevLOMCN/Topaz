/*!
 * VisualEditor DataModel MWAlienBlockExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki alien block extension node.
 *
 * @class
 * @abstract
 * @extends ve.dm.MWBlockExtensionNode
 * @mixins ve.dm.MWAlienExtensionNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWAlienBlockExtensionNode = function VeDmMWAlienBlockExtensionNode() {
	// Parent constructor
	ve.dm.MWAlienBlockExtensionNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.MWAlienExtensionNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWAlienBlockExtensionNode, ve.dm.MWBlockExtensionNode );

OO.mixinClass( ve.dm.MWAlienBlockExtensionNode, ve.dm.MWAlienExtensionNode );

/* Static members */

ve.dm.MWAlienBlockExtensionNode.static.name = 'mwAlienBlockExtension';

ve.dm.MWAlienBlockExtensionNode.static.tagName = 'div';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWAlienBlockExtensionNode );
