/*!
 * VisualEditor ContentEditable MWAlienInlineExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki alien inline extension node.
 *
 * @class
 * @abstract
 * @extends ve.ce.MWInlineExtensionNode
 * @mixins ve.ce.MWAlienExtensionNode
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ce.MWAlienInlineExtensionNode = function VeCeMWAlienInlineExtensionNode( config ) {
	// Parent constructor
	ve.ce.MWAlienInlineExtensionNode.super.apply( this, arguments );

	// Mixin constructors
	ve.ce.MWAlienExtensionNode.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWAlienInlineExtensionNode, ve.ce.MWInlineExtensionNode );

OO.mixinClass( ve.ce.MWAlienInlineExtensionNode, ve.ce.MWAlienExtensionNode );

/* Static members */

ve.ce.MWAlienInlineExtensionNode.static.name = 'mwAlienInlineExtension';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWAlienInlineExtensionNode );
