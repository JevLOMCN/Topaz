/*!
 * VisualEditor DataModel DeleteAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel delete annotation.
 *
 * Represents `<del>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.DeleteAnnotation = function VeDmDeleteAnnotation() {
	// Parent constructor
	ve.dm.DeleteAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.DeleteAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.DeleteAnnotation.static.name = 'textStyle/delete';

ve.dm.DeleteAnnotation.static.matchTagNames = [ 'del' ];

// TODO: Move this to a DiffDeleteAnnotation sub-class
ve.dm.DeleteAnnotation.static.trimWhitespace = false;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.DeleteAnnotation );
