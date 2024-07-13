/*!
 * VisualEditor DataModel SectionNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel section node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.SectionNode = function VeDmSectionNode() {
	// Parent constructor
	ve.dm.SectionNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.SectionNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.SectionNode.static.name = 'section';

ve.dm.SectionNode.static.isUnwrappable = false;

ve.dm.SectionNode.static.defaultAttributes = {
	style: 'section'
};

ve.dm.SectionNode.static.matchTagNames = [ 'header', 'section', 'footer' ];

ve.dm.SectionNode.static.toDataElement = function ( domElements ) {
	return { type: this.name, attributes: { style: domElements[ 0 ].nodeName.toLowerCase() } };
};

ve.dm.SectionNode.static.toDomElements = function ( dataElement, doc ) {
	var style = dataElement.attributes && dataElement.attributes.style || 'section';
	return [ doc.createElement( style ) ];
};

/* Methods */

ve.dm.SectionNode.prototype.canHaveSlugBefore = function () {
	return false;
};

ve.dm.SectionNode.prototype.canHaveSlugAfter = function () {
	return false;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.SectionNode );
