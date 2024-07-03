/*!
 * VisualEditor DataModel StrikethroughAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel strikethrough annotation.
 *
 * Represents `<s>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.StrikethroughAnnotation = function VeDmStrikethroughAnnotation() {
	// Parent constructor
	ve.dm.StrikethroughAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.StrikethroughAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.StrikethroughAnnotation.static.name = 'textStyle/strikethrough';

ve.dm.StrikethroughAnnotation.static.matchTagNames = [ 's', 'del' ];

ve.dm.StrikethroughAnnotation.static.description = OO.ui.deferMsg( 'visualeditor-annotationbutton-strikethrough-tooltip' );

/* Registration */

ve.dm.modelRegistry.register( ve.dm.StrikethroughAnnotation );
