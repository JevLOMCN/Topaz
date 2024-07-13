/*!
 * VisualEditor ContentEditable LeafNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.LeafNode' );

/* Stubs */

ve.ce.LeafNodeStub = function VeCeLeafNodeStub() {
	// Parent constructor
	ve.ce.LeafNodeStub.super.apply( this, arguments );
};

OO.inheritClass( ve.ce.LeafNodeStub, ve.ce.LeafNode );

ve.ce.LeafNodeStub.static.name = 'leaf-stub';

ve.ce.nodeFactory.register( ve.ce.LeafNodeStub );

/* Tests */

QUnit.test( 'splitOnEnter', function ( assert ) {
	var node = new ve.ce.LeafNodeStub( new ve.dm.LeafNodeStub() );
	assert.strictEqual( node.splitOnEnter(), false );
} );

QUnit.test( 'canHaveChildren', function ( assert ) {
	var node = new ve.ce.LeafNodeStub( new ve.dm.LeafNodeStub() );
	assert.strictEqual( node.canHaveChildren(), false );
} );

QUnit.test( 'canHaveChildrenNotContent', function ( assert ) {
	var node = new ve.ce.LeafNodeStub( new ve.dm.LeafNodeStub() );
	assert.strictEqual( node.canHaveChildrenNotContent(), false );
} );
