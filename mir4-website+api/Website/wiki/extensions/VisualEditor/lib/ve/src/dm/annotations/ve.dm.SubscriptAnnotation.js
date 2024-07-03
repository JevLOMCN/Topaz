/*!
 * VisualEditor DataModel SubscriptAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel subscript annotation.
 *
 * Represents `<sub>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.SubscriptAnnotation = function VeDmSubscriptAnnotation() {
	// Parent constructor
	ve.dm.SubscriptAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.SubscriptAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.SubscriptAnnotation.static.name = 'textStyle/subscript';

ve.dm.SubscriptAnnotation.static.matchTagNames = [ 'sub' ];

ve.dm.SubscriptAnnotation.static.removes = [ 'textStyle/superscript' ];

ve.dm.SubscriptAnnotation.static.description = OO.ui.deferMsg( 'visualeditor-annotationbutton-subscript-tooltip' );

/* Registration */

ve.dm.modelRegistry.register( ve.dm.SubscriptAnnotation );
