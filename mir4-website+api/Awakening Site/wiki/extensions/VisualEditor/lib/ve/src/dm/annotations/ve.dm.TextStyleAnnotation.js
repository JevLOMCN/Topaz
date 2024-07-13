/*!
 * VisualEditor DataModel TextStyleAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel text style annotation.
 *
 * @class
 * @abstract
 * @extends ve.dm.Annotation
 * @constructor
 * @param {Object} element
 */
ve.dm.TextStyleAnnotation = function VeDmTextStyleAnnotation() {
	// Parent constructor
	ve.dm.TextStyleAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.TextStyleAnnotation, ve.dm.Annotation );

/* Static Properties */

ve.dm.TextStyleAnnotation.static.name = 'textStyle';

ve.dm.TextStyleAnnotation.static.matchTagNames = [];

ve.dm.TextStyleAnnotation.static.toDataElement = function ( domElements, converter ) {
	var nodeName = converter.isFromClipboard() ? this.matchTagNames[ 0 ] : domElements[ 0 ].nodeName.toLowerCase();
	return {
		type: this.name,
		attributes: {
			nodeName: nodeName
		}
	};
};

ve.dm.TextStyleAnnotation.static.toDomElements = function ( dataElement, doc ) {
	var nodeName = ve.getProp( dataElement, 'attributes', 'nodeName' );

	return [ doc.createElement( nodeName || this.matchTagNames[ 0 ] ) ];
};

ve.dm.TextStyleAnnotation.static.description = null;

/* Methods */

/**
 * @return {Object}
 */
ve.dm.TextStyleAnnotation.prototype.getComparableObject = function () {
	return { type: this.getType() };
};

ve.dm.TextStyleAnnotation.prototype.describeAdded = function () {
	if ( this.constructor.static.description ) {
		return [ ve.msg( 'visualeditor-changedesc-textstyle-added', OO.ui.resolveMsg( this.constructor.static.description ) ) ];
	}
	return [];
};

ve.dm.TextStyleAnnotation.prototype.describeRemoved = function () {
	if ( this.constructor.static.description ) {
		return [ ve.msg( 'visualeditor-changedesc-textstyle-removed', OO.ui.resolveMsg( this.constructor.static.description ) ) ];
	}
	return [];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.TextStyleAnnotation );
