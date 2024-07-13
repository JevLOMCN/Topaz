/*!
 * VisualEditor ContentEditable MWAlienBlockExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki alien block extension node.
 *
 * @class
 * @abstract
 * @extends ve.ce.MWBlockExtensionNode
 * @mixins ve.ce.MWAlienExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWAlienBlockExtensionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWAlienBlockExtensionNode = function VeCeMWAlienBlockExtensionNode() {
	// Parent constructor
	ve.ce.MWAlienBlockExtensionNode.super.apply( this, arguments );

	// Mixin constructors
	ve.ce.MWAlienExtensionNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWAlienBlockExtensionNode, ve.ce.MWBlockExtensionNode );

OO.mixinClass( ve.ce.MWAlienBlockExtensionNode, ve.ce.MWAlienExtensionNode );

/* Static members */

ve.ce.MWAlienBlockExtensionNode.static.name = 'mwAlienBlockExtension';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWAlienBlockExtensionNode );
