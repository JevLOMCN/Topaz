/*!
 * VisualEditor DataModel VariableAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel variable annotation.
 *
 * Represents `<var>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.VariableAnnotation = function VeDmVariableAnnotation() {
	// Parent constructor
	ve.dm.VariableAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.VariableAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.VariableAnnotation.static.name = 'textStyle/variable';

ve.dm.VariableAnnotation.static.matchTagNames = [ 'var' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.VariableAnnotation );
