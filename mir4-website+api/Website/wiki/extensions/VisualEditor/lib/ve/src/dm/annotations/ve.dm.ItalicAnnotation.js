/*!
 * VisualEditor DataModel ItalicAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel italic annotation.
 *
 * Represents `<i>` and `<em>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.ItalicAnnotation = function VeDmItalicAnnotation() {
	// Parent constructor
	ve.dm.ItalicAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.ItalicAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.ItalicAnnotation.static.name = 'textStyle/italic';

ve.dm.ItalicAnnotation.static.matchTagNames = [ 'i', 'em' ];

ve.dm.ItalicAnnotation.static.inferFromView = true;

ve.dm.ItalicAnnotation.static.description = OO.ui.deferMsg( 'visualeditor-annotationbutton-italic-tooltip' );

/* Registration */

ve.dm.modelRegistry.register( ve.dm.ItalicAnnotation );
