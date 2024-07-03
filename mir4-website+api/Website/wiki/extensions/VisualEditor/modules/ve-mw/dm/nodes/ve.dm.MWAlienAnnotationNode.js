/*!
 * VisualEditor DataModel MWAlienAnnotationNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki alien annotation node.
 *
 * @class
 * @abstract
 * @extends ve.dm.MWAnnotationNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWAlienAnnotationNode = function VeDmMWAlienAnnotationNode() {
	// Parent constructor
	ve.dm.MWAlienAnnotationNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWAlienAnnotationNode, ve.dm.MWAnnotationNode );

/* Static members */

ve.dm.MWAlienAnnotationNode.static.name = 'mwAlienAnnotation';

ve.dm.MWAlienAnnotationNode.static.matchRdfaTypes = [
	/^mw:Annotation\//
];

/* Methods */

ve.dm.MWAlienAnnotationNode.static.toDataElement = function ( domElements ) {
	// 'Parent' method
	var element = ve.dm.MWAlienAnnotationNode.super.static.toDataElement.call( this, domElements );

	element.type = 'mwAlienAnnotation';
	return element;
};

ve.dm.MWAlienAnnotationNode.prototype.getWikitextTag = function () {
	var type = this.getAttribute( 'type' );
	if ( type.indexOf( '/End', type.length - 4 ) !== -1 ) {
		return '</UNKNOWN>';
	}
	return '<UNKNOWN>';
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWAlienAnnotationNode );
