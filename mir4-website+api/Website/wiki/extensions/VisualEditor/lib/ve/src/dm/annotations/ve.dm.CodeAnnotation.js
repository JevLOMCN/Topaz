/*!
 * VisualEditor DataModel CodeAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel code annotation.
 *
 * Represents `<code>` and `<tt>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.CodeAnnotation = function VeDmCodeAnnotation() {
	// Parent constructor
	ve.dm.CodeAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.CodeAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.CodeAnnotation.static.name = 'textStyle/code';

ve.dm.CodeAnnotation.static.matchTagNames = [ 'code', 'tt' ];

ve.dm.CodeAnnotation.static.description = OO.ui.deferMsg( 'visualeditor-annotationbutton-code-tooltip' );

/* Registration */

ve.dm.modelRegistry.register( ve.dm.CodeAnnotation );
