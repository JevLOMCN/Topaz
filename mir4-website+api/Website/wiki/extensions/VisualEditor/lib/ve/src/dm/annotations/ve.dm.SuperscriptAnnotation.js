/*!
 * VisualEditor DataModel SuperscriptAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel superscript annotation.
 *
 * Represents `<sup>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.SuperscriptAnnotation = function VeDmSuperscriptAnnotation() {
	// Parent constructor
	ve.dm.SuperscriptAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.SuperscriptAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.SuperscriptAnnotation.static.name = 'textStyle/superscript';

ve.dm.SuperscriptAnnotation.static.matchTagNames = [ 'sup' ];

ve.dm.SuperscriptAnnotation.static.removes = [ 'textStyle/subscript' ];

ve.dm.SuperscriptAnnotation.static.description = OO.ui.deferMsg( 'visualeditor-annotationbutton-superscript-tooltip' );

/* Registration */

ve.dm.modelRegistry.register( ve.dm.SuperscriptAnnotation );
