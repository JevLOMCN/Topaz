/*!
 * VisualEditor DataModel AnnotationFactory class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel annotation factory.
 *
 * @class
 * @extends ve.dm.ModelFactory
 * @constructor
 */
ve.dm.AnnotationFactory = function VeDmAnnotationFactory() {
	// Parent constructor
	ve.dm.AnnotationFactory.super.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.AnnotationFactory, ve.dm.ModelFactory );

/* Initialization */

ve.dm.annotationFactory = new ve.dm.AnnotationFactory();
