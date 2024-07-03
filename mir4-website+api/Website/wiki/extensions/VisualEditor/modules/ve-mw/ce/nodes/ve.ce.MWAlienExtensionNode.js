/*!
 * VisualEditor ContentEditable MWAlienExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki alien extension node.
 *
 * @class
 * @abstract
 *
 * @constructor
 */
ve.ce.MWAlienExtensionNode = function VeCeMWAlienExtensionNode() {
};

/* Inheritance */

OO.initClass( ve.ce.MWAlienExtensionNode );

/* Static members */

ve.ce.MWAlienExtensionNode.static.primaryCommandName = 'alienExtension';

ve.ce.MWAlienExtensionNode.static.iconWhenInvisible = 'markup';

ve.ce.MWAlienExtensionNode.static.rendersEmpty = true;

/* Methods */

/* Static methods */

/**
 * @inheritdoc ve.ce.MWExtensionNode
 */
ve.ce.MWAlienExtensionNode.static.getDescription = function ( model ) {
	return model.getExtensionName();
};
