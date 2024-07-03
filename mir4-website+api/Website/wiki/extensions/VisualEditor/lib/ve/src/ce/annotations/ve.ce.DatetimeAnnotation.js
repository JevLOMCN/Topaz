/*!
 * VisualEditor ContentEditable DatetimeAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable datetime annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.DatetimeAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.DatetimeAnnotation = function VeCeDatetimeAnnotation() {
	// Parent constructor
	ve.ce.DatetimeAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-datetimeAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.DatetimeAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.DatetimeAnnotation.static.name = 'textStyle/datetime';

ve.ce.DatetimeAnnotation.static.tagName = 'time';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.DatetimeAnnotation );
