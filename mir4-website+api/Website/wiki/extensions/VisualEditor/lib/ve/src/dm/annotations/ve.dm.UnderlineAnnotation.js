/*!
 * VisualEditor DataModel UnderlineAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel underline annotation.
 *
 * Represents `<u>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.UnderlineAnnotation = function VeDmUnderlineAnnotation() {
	// Parent constructor
	ve.dm.UnderlineAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.UnderlineAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.UnderlineAnnotation.static.name = 'textStyle/underline';

ve.dm.UnderlineAnnotation.static.matchTagNames = [ 'u' ];

ve.dm.UnderlineAnnotation.static.inferFromView = true;

ve.dm.UnderlineAnnotation.static.description = OO.ui.deferMsg( 'visualeditor-annotationbutton-underline-tooltip' );

/* Registration */

ve.dm.modelRegistry.register( ve.dm.UnderlineAnnotation );
