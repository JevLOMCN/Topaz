/*!
 * VisualEditor ContentEditable AnnotationFactory tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.AnnotationFactory' );

/* Stubs */

ve.ce.AnnotationFactoryAnnotationStub = function VeCeAnnotationFactoryNodeStub() {};

OO.inheritClass( ve.ce.AnnotationFactoryAnnotationStub, ve.ce.Annotation );

ve.ce.AnnotationFactoryAnnotationStub.static.name = 'annotation-factory-annotation-stub';

ve.ce.AnnotationFactoryAnnotationStub.static.canBeActive = true;

ve.ce.AnnotationFactoryAnnotationStub.static.getDescription = function () {
	return 'description';
};

ve.dm.AnnotationFactoryAnnotationStub = function VeDmAnnotationFactoryNodeStub() {};

OO.inheritClass( ve.dm.AnnotationFactoryAnnotationStub, ve.dm.Annotation );

ve.dm.AnnotationFactoryAnnotationStub.static.name = 'annotation-factory-annotation-stub';

/* Tests */

QUnit.test( 'canAnnotationBeActive/getDescription', function ( assert ) {
	var factory = new ve.ce.AnnotationFactory();

	assert.throws(
		function () {
			factory.canAnnotationBeActive( 'annotation-factory-annotation-stub' );
		},
		Error,
		'throws an exception when calling canAnnotationBeActive on an unregistered type'
	);

	assert.throws(
		function () {
			factory.getDescription( new ve.dm.AnnotationFactoryAnnotationStub() );
		},
		Error,
		'throws an exception when calling getDescription on an unregistered type'
	);

	factory.register( ve.ce.AnnotationFactoryAnnotationStub );

	assert.strictEqual(
		factory.canAnnotationBeActive( 'annotation-factory-annotation-stub' ),
		true,
		'canAnnotationBeActive'
	);

	assert.strictEqual(
		factory.getDescription( new ve.dm.AnnotationFactoryAnnotationStub() ),
		'description',
		'getDescription'
	);
} );

QUnit.test( 'initialization', function ( assert ) {
	assert.true( ve.ce.annotationFactory instanceof ve.ce.AnnotationFactory, 'factory is initialized at ve.ce.annotationFactory' );
} );
