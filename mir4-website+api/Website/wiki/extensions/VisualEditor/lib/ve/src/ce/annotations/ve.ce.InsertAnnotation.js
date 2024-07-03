/*!
 * VisualEditor ContentEditable InsertAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable insert annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.InsertAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.InsertAnnotation = function VeCeInsertAnnotation() {
	// Parent constructor
	ve.ce.InsertAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-insertAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.InsertAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.InsertAnnotation.static.name = 'textStyle/insert';

ve.ce.InsertAnnotation.static.tagName = 'ins';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.InsertAnnotation );
