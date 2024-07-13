/*!
 * VisualEditor ContentEditable UserInputAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable user input annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.UserInputAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.UserInputAnnotation = function VeCeUserInputAnnotation() {
	// Parent constructor
	ve.ce.UserInputAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-userInputAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.UserInputAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.UserInputAnnotation.static.name = 'textStyle/userInput';

ve.ce.UserInputAnnotation.static.tagName = 'kbd';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.UserInputAnnotation );
