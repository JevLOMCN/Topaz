/*!
 * VisualEditor DataModel DatetimeAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel datetime annotation.
 *
 * Represents `<time>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.DatetimeAnnotation = function VeDmDatetimeAnnotation() {
	// Parent constructor
	ve.dm.DatetimeAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.DatetimeAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.DatetimeAnnotation.static.name = 'textStyle/datetime';

ve.dm.DatetimeAnnotation.static.matchTagNames = [ 'time' ];

ve.dm.DatetimeAnnotation.static.toDataElement = function ( domElements ) {
	// Parent method
	var dataElement = ve.dm.DatetimeAnnotation.super.static.toDataElement.apply( this, arguments );
	dataElement.attributes.datetime = domElements[ 0 ].getAttribute( 'datetime' );
	return dataElement;
};

ve.dm.DatetimeAnnotation.static.toDomElements = function ( dataElement, doc ) {
	var domElement = doc.createElement( 'time' );
	if ( dataElement.attributes.datetime ) {
		// If it's null, don't bother creating a blank attribute; <time> alone is valid
		domElement.setAttribute( 'datetime', dataElement.attributes.datetime );
	}
	return [ domElement ];
};

/* Methods */

/**
 * @inheritdoc
 */
ve.dm.DatetimeAnnotation.prototype.getComparableObject = function () {
	return {
		type: this.getType(),
		datetime: this.getAttribute( 'datetime' )
	};
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.DatetimeAnnotation );
