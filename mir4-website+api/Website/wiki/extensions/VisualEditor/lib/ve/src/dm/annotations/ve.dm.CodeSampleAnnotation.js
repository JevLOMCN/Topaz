/*!
 * VisualEditor DataModel CodeSampleAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel code sample annotation.
 *
 * Represents `<samp>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.CodeSampleAnnotation = function VeDmCodeSampleAnnotation() {
	// Parent constructor
	ve.dm.CodeSampleAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.CodeSampleAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.CodeSampleAnnotation.static.name = 'textStyle/codeSample';

ve.dm.CodeSampleAnnotation.static.matchTagNames = [ 'samp' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.CodeSampleAnnotation );
