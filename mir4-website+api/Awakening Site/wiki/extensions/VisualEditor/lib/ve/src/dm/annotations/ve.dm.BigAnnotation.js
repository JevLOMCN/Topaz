/*!
 * VisualEditor DataModel BigAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel big annotation.
 *
 * Represents `<big>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.BigAnnotation = function VeDmBigAnnotation() {
	// Parent constructor
	ve.dm.BigAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.BigAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.BigAnnotation.static.name = 'textStyle/big';

ve.dm.BigAnnotation.static.matchTagNames = [ 'big' ];

ve.dm.BigAnnotation.static.removes = [ 'textStyle/small' ];

ve.dm.BigAnnotation.static.description = OO.ui.deferMsg( 'visualeditor-annotationbutton-big-tooltip' );

/* Registration */

ve.dm.modelRegistry.register( ve.dm.BigAnnotation );
