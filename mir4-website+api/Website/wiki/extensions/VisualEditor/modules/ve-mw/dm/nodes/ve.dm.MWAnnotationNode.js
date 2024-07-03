/*!
 * VisualEditor DataModel MWAnnotationNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MW node for mw:Annotation tags.
 *
 * @class
 * @extends ve.dm.AlienInlineNode
 * @constructor
 * @param {Object} element Reference to element in linear model
 */
ve.dm.MWAnnotationNode = function VeDmMWAnnotationNode() {
	// Parent constructor
	ve.dm.MWAnnotationNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWAnnotationNode, ve.dm.AlienInlineNode );

/* Static Properties */

ve.dm.MWAnnotationNode.static.name = 'mwAnnotation';

ve.dm.MWAnnotationNode.static.preserveHtmlAttributes = true;

/* Static Methods */

/**
 * @inheritdoc
 */
ve.dm.MWAnnotationNode.static.toDataElement = function ( domElements ) {
	var dataElement,
		mwDataJSON = domElements[ 0 ].getAttribute( 'data-mw' ),
		type = domElements[ 0 ].getAttribute( 'typeof' );

	dataElement = {
		type: 'mwAnnotation',
		attributes: {
			type: type
		}
	};

	if ( mwDataJSON !== null ) {
		dataElement.attributes.mw = JSON.parse( mwDataJSON );
	}

	return dataElement;
};

ve.dm.MWAnnotationNode.static.toDomElements = function ( dataElement, doc ) {
	var el;

	el = doc.createElement( 'meta' );
	el.setAttribute( 'typeof', dataElement.attributes.type );
	if ( dataElement.attributes.mw ) {
		el.setAttribute( 'data-mw', JSON.stringify( dataElement.attributes.mw ) );
	}

	return [ el ];
};
