/*!
 * VisualEditor DataModel NodeFactory tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.NodeFactory' );

/* Stubs */

ve.dm.NodeFactoryNodeStub = function VeDmNodeFactoryNodeStub() {};

OO.inheritClass( ve.dm.NodeFactoryNodeStub, ve.dm.LeafNode );

ve.dm.NodeFactoryNodeStub.static.name = 'node-factory-node-stub';

ve.dm.NodeFactoryNodeStub.static.isContent = true;

ve.dm.NodeFactoryNodeStub.static.matchTagNames = [];

ve.dm.NodeFactoryNodeStub.static.disallowedAnnotationTypes = [ 'link', 'textStyle/italic' ];

/* Tests */

QUnit.test( 'getChildNodeTypes', function ( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws(
		function () {
			factory.getChildNodeTypes( 'node-factory-node-stub', 23, { bar: 'baz' } );
		},
		Error,
		'throws an exception when getting allowed child nodes of a node of an unregistered type'
	);
	factory.register( ve.dm.NodeFactoryNodeStub );
	assert.deepEqual(
		factory.getChildNodeTypes( 'node-factory-node-stub' ),
		[],
		'gets child type rules for registered nodes'
	);
} );

QUnit.test( 'getParentNodeTypes', function ( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws(
		function () {
			factory.getParentNodeTypes( 'node-factory-node-stub', 23, { bar: 'baz' } );
		},
		Error,
		'throws an exception when getting allowed parent nodes of a node of an unregistered type'
	);
	factory.register( ve.dm.NodeFactoryNodeStub );
	assert.strictEqual(
		factory.getParentNodeTypes( 'node-factory-node-stub' ),
		null,
		'gets parent type rules for registered nodes'
	);
} );

QUnit.test( 'canNodeHaveChildren', function ( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws(
		function () {
			factory.canNodeHaveChildren( 'node-factory-node-stub', 23, { bar: 'baz' } );
		},
		Error,
		'throws an exception when checking if a node of an unregistered type can have children'
	);
	factory.register( ve.dm.NodeFactoryNodeStub );
	assert.strictEqual(
		factory.canNodeHaveChildren( 'node-factory-node-stub' ),
		false,
		'gets child rules for registered nodes'
	);
} );

QUnit.test( 'canNodeTakeAnnotation', function ( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws(
		function () {
			factory.canNodeTakeAnnotation( 'node-factory-node-stub', 23, { bar: 'baz' } );
		},
		Error,
		'throws an exception when checking if a node of an unregistered type can have children'
	);
	factory.register( ve.dm.NodeFactoryNodeStub );
	assert.strictEqual(
		factory.canNodeTakeAnnotation( 'node-factory-node-stub', new ve.dm.LinkAnnotation() ),
		false,
		'can\'t take link annotation'
	);
	assert.strictEqual(
		factory.canNodeTakeAnnotation( 'node-factory-node-stub', new ve.dm.ItalicAnnotation() ),
		false,
		'can\'t take italic annotation'
	);
	assert.strictEqual(
		factory.canNodeTakeAnnotation( 'node-factory-node-stub', new ve.dm.BoldAnnotation() ),
		true,
		'can take bold annotation'
	);
} );

QUnit.test( 'canNodeHaveChildrenNotContent', function ( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws(
		function () {
			factory.canNodeHaveChildrenNotContent( 'node-factory-node-stub', 23, { bar: 'baz' } );
		},
		Error,
		'throws an exception when checking if a node of an unregistered type can have grandchildren'
	);
	factory.register( ve.dm.NodeFactoryNodeStub );
	assert.strictEqual(
		factory.canNodeHaveChildrenNotContent( 'node-factory-node-stub' ),
		false,
		'gets grandchild rules for registered nodes'
	);
} );

QUnit.test( 'initialization', function ( assert ) {
	assert.true( ve.dm.nodeFactory instanceof ve.dm.NodeFactory, 'factory is initialized at ve.dm.nodeFactory' );
} );

// TODO: getDataElement
// TODO: getSuggestedParentNodeTypes
// TODO: isNodeWrapped
// TODO: canNodeContainContent
// TODO: isNodeContent
// TODO: doesNodeHaveSignificantWhitespace
// TODO: doesNodeHandleOwnChildren
// TODO: shouldIgnoreChildren
// TODO: isNodeInternal
