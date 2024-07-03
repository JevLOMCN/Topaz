/*!
 * VisualEditor ContentEditable SmallAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable small annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.SmallAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.SmallAnnotation = function VeCeSmallAnnotation() {
	// Parent constructor
	ve.ce.SmallAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-smallAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.SmallAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.SmallAnnotation.static.name = 'textStyle/small';

ve.ce.SmallAnnotation.static.tagName = 'small';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.SmallAnnotation );
