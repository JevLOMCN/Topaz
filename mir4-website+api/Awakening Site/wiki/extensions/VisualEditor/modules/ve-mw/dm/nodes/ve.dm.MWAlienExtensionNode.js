/*!
 * VisualEditor DataModel MWAlienExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki alien extension node.
 *
 * @class
 * @abstract
 *
 * @constructor
 */
ve.dm.MWAlienExtensionNode = function VeDmMWAlienExtensionNode() {};

/* Inheritance */

OO.initClass( ve.dm.MWAlienExtensionNode );

/* Static members */

ve.dm.MWAlienExtensionNode.static.getMatchRdfaTypes = function () {
	return [
		/^mw:Extension/
	];
};

// Similar to transclusions, extension encapsulation nodes can be of various types
ve.dm.MWAlienExtensionNode.static.allowedRdfaTypes = null;

ve.dm.MWAlienExtensionNode.static.toDataElement = function ( domElements, converter ) {
	// 'Parent' method
	var element = ve.dm.MWExtensionNode.static.toDataElement.call( this, domElements, converter ),
		isInline = this.isHybridInline( domElements, converter );

	element.type = isInline ? 'mwAlienInlineExtension' : 'mwAlienBlockExtension';
	return element;
};

/**
 * @inheritdoc ve.dm.MWExtensionNode
 */
ve.dm.MWAlienExtensionNode.static.getExtensionName = function ( dataElement ) {
	return dataElement.attributes.mw.name;
};
