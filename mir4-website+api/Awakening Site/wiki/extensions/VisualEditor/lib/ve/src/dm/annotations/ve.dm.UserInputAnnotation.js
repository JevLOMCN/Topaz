/*!
 * VisualEditor DataModel UserInputAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel user input annotation.
 *
 * Represents `<kbd>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.UserInputAnnotation = function VeDmUserInputAnnotation() {
	// Parent constructor
	ve.dm.UserInputAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.UserInputAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.UserInputAnnotation.static.name = 'textStyle/userInput';

ve.dm.UserInputAnnotation.static.matchTagNames = [ 'kbd' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.UserInputAnnotation );
