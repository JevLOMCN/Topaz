/*!
 * VisualEditor DataModel MWEntityNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki entity node.
 *
 * @class
 * @extends ve.dm.LeafNode
 * @constructor
 *
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWEntityNode = function VeDmMWEntityNode() {
	// Parent constructor
	ve.dm.MWEntityNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWEntityNode, ve.dm.LeafNode );

/* Static Properties */

ve.dm.MWEntityNode.static.name = 'mwEntity';

ve.dm.MWEntityNode.static.isContent = true;

ve.dm.MWEntityNode.static.matchTagNames = [ 'span' ];

ve.dm.MWEntityNode.static.matchRdfaTypes = [ 'mw:Entity', 'mw:DisplaySpace' ];

ve.dm.MWEntityNode.static.toDataElement = function ( domElements ) {
	var dataElement = {
		type: this.name,
		attributes: {
			character: domElements[ 0 ].textContent
		}
	};
	if ( domElements[ 0 ].getAttribute( 'typeof' ).indexOf( 'mw:DisplaySpace' ) !== -1 ) {
		dataElement.attributes.displaySpace = true;
	}
	return dataElement;
};

ve.dm.MWEntityNode.static.toDomElements = function ( dataElement, doc ) {
	var domElement = doc.createElement( 'span' ),
		textNode = doc.createTextNode( dataElement.attributes.character );
	domElement.setAttribute( 'typeof',
		dataElement.attributes.displaySpace ? 'mw:DisplaySpace' : 'mw:Entity' );
	domElement.appendChild( textNode );
	return [ domElement ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWEntityNode );
