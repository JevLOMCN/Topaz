/*!
 * VisualEditor DataModel Node tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.Node' );

/* Stubs */

ve.dm.NodeStub = function VeDmNodeStub() {
	// Parent constructor
	ve.dm.NodeStub.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.NodeStub, ve.dm.LeafNode );

ve.dm.NodeStub.static.name = 'stub';

ve.dm.NodeStub.static.matchTagNames = [];

ve.dm.NodeStub.static.resetAttributesForClone = function ( clonedElement ) {
	clonedElement.attributes.counter = 0;
};

ve.dm.nodeFactory.register( ve.dm.NodeStub );

// FakeCommentNode is never instantiated, so create
// one here to bypass code coverage warnings.
// eslint-disable-next-line no-new
new ve.dm.FakeCommentNode();

/* Tests */

QUnit.test( 'canHaveChildren', function ( assert ) {
	var node = new ve.dm.NodeStub();
	assert.strictEqual( node.canHaveChildren(), false );
} );

QUnit.test( 'canHaveChildrenNotContent', function ( assert ) {
	var node = new ve.dm.NodeStub();
	assert.strictEqual( node.canHaveChildrenNotContent(), false );
} );

QUnit.test( 'getLength', function ( assert ) {
	var node1 = new ve.dm.NodeStub(),
		node2 = new ve.dm.NodeStub();

	node2.setLength( 1234 );

	assert.strictEqual( node1.getLength(), 0 );
	assert.strictEqual( node2.getLength(), 1234 );
} );

QUnit.test( 'getOuterLength', function ( assert ) {
	var node1 = new ve.dm.NodeStub(),
		node2 = new ve.dm.NodeStub();

	node2.setLength( 1234 );

	assert.strictEqual( node1.getOuterLength(), 2 );
	assert.strictEqual( node2.getOuterLength(), 1236 );
} );

QUnit.test( 'setLength', function ( assert ) {
	var node = new ve.dm.NodeStub();
	node.setLength( 1234 );
	assert.strictEqual( node.getLength(), 1234 );
	assert.throws(
		function () {
			// Length cannot be negative
			node.setLength( -1 );
		},
		Error,
		'throws exception if length is negative'
	);
} );

QUnit.test( 'adjustLength', function ( assert ) {
	var node = new ve.dm.NodeStub();
	node.setLength( 1234 );
	node.adjustLength( 5678 );
	assert.strictEqual( node.getLength(), 6912 );
} );

QUnit.test( 'getAttribute', function ( assert ) {
	var node = new ve.dm.NodeStub( { type: 'stub', attributes: { a: 1, b: 2 } } );
	assert.strictEqual( node.getAttribute( 'a' ), 1 );
	assert.strictEqual( node.getAttribute( 'b' ), 2 );
} );

QUnit.test( 'setRoot', function ( assert ) {
	var node1 = new ve.dm.NodeStub(),
		node2 = new ve.dm.NodeStub();
	node1.setRoot( node2 );
	assert.strictEqual( node1.getRoot(), node2 );
} );

QUnit.test( 'attach', function ( assert ) {
	var node1 = new ve.dm.NodeStub(),
		node2 = new ve.dm.NodeStub();
	node1.attach( node2 );
	assert.strictEqual( node1.getParent(), node2 );
	assert.strictEqual( node1.getRoot(), null );
} );

QUnit.test( 'detach', function ( assert ) {
	var node1 = new ve.dm.NodeStub(),
		node2 = new ve.dm.NodeStub();
	node1.attach( node2 );
	node1.detach();
	assert.strictEqual( node1.getParent(), null );
	assert.strictEqual( node1.getRoot(), null );
} );

QUnit.test( 'canBeMergedWith', function ( assert ) {
	var node1 = new ve.dm.LeafNodeStub(),
		node2 = new ve.dm.BranchNodeStub( {}, [ node1 ] ),
		node3 = new ve.dm.BranchNodeStub( {}, [ node2 ] ),
		node4 = new ve.dm.LeafNodeStub(),
		node5 = new ve.dm.BranchNodeStub( {}, [ node4 ] ),
		heading1 = new ve.dm.HeadingNode( { type: 'heading', attributes: { level: 1 } } ),
		heading2 = new ve.dm.HeadingNode( { type: 'heading', attributes: { level: 2 } } );

	assert.strictEqual( node3.canBeMergedWith( node5 ), true, 'same level, same type' );
	assert.strictEqual( node2.canBeMergedWith( node5 ), false, 'different level, same type' );
	assert.strictEqual( node2.canBeMergedWith( node1 ), false, 'different level, different type' );
	assert.strictEqual( node2.canBeMergedWith( node4 ), false, 'same level, different type' );
	assert.strictEqual( heading1.canBeMergedWith( heading2 ), false, 'headings of different levels can\'t be merged' );
	assert.strictEqual( heading1.canBeMergedWith( heading1 ), true, 'headings of samve level can be merged' );
} );

QUnit.test( 'getClonedElement', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		cases = [
			{
				original: {
					type: 'foo'
				},
				clone: {
					type: 'foo'
				},
				msg: 'Simple element is cloned verbatim'
			},
			{
				original: {
					type: 'foo',
					attributes: {
						bar: 'baz'
					}
				},
				clone: {
					type: 'foo',
					attributes: {
						bar: 'baz'
					}
				},
				msg: 'Element with simple attributes is cloned verbatim'
			},
			{
				original: {
					type: 'foo',
					internal: {
						generated: 'wrapper',
						whitespace: [ undefined, ' ' ]
					}
				},
				clone: {
					type: 'foo',
					internal: {
						whitespace: [ undefined, ' ' ]
					}
				},
				msg: 'internal.generated property is removed from clone'
			},
			{
				original: {
					type: 'foo',
					internal: {
						generated: 'wrapper'
					}
				},
				clone: {
					type: 'foo',
					internal: {
						generated: 'wrapper'
					}
				},
				preserveGenerated: true,
				msg: 'internal.generated not removed if preserveGenerated set'
			},
			{
				original: {
					type: 'foo',
					internal: {
						generated: 'wrapper'
					}
				},
				clone: {
					type: 'foo'
				},
				msg: 'internal property is removed if it only contained .generated'
			},
			{
				original: {
					type: 'foo',
					attributes: {
						mode: 'bar',
						counter: 5
					}
				},
				clone: {
					type: 'foo',
					attributes: {
						mode: 'bar',
						counter: 5
					}
				},
				msg: 'attributes preserved in normal clone'
			},
			{
				original: {
					type: 'foo',
					attributes: {
						mode: 'bar',
						counter: 5
					}
				},
				clone: {
					type: 'foo',
					attributes: {
						mode: 'bar',
						counter: 0
					}
				},
				resetAttributes: true,
				msg: 'some attributes reset by resetAttributes'
			}
		];

	cases.forEach( function ( caseItem ) {
		var node = new ve.dm.NodeStub( caseItem.original );
		node.setDocument( doc );
		var clonedElement = node.getClonedElement( caseItem.preserveGenerated, caseItem.resetAttributes );
		assert.deepEqual( clonedElement, caseItem.clone, caseItem.msg );
	} );
} );
