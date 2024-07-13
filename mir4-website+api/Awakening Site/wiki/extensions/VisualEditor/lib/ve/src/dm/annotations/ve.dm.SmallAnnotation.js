/*!
 * VisualEditor DataModel SmallAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel small annotation.
 *
 * Represents `<small>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.SmallAnnotation = function VeDmSmallAnnotation() {
	// Parent constructor
	ve.dm.SmallAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.SmallAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.SmallAnnotation.static.name = 'textStyle/small';

ve.dm.SmallAnnotation.static.matchTagNames = [ 'small' ];

ve.dm.SmallAnnotation.static.removes = [ 'textStyle/big' ];

ve.dm.SmallAnnotation.static.description = OO.ui.deferMsg( 'visualeditor-annotationbutton-small-tooltip' );

/* Registration */

ve.dm.modelRegistry.register( ve.dm.SmallAnnotation );
