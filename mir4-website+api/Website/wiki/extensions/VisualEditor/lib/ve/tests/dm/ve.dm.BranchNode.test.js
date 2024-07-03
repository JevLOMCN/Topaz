/*!
 * VisualEditor DataModel BranchNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.BranchNode' );

/* Stubs */

ve.dm.BranchNodeStub = function VeDmBranchNodeStub() {
	// Parent constructor
	ve.dm.BranchNodeStub.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.BranchNodeStub, ve.dm.BranchNode );

ve.dm.BranchNodeStub.static.name = 'branch-stub';

ve.dm.BranchNodeStub.static.matchTagNames = [];

// Throw in nodeAttached so BranchNodeStub better passes duck type scrutiny as a Document
// (e.g. for setDocument test below)
ve.dm.BranchNodeStub.prototype.nodeAttached = ve.Document.prototype.nodeAttached;

// Throw in nodeDetached so BranchNodeStub better passes duck type scrutiny as a Document
// (e.g. for setDocument test below)
ve.dm.BranchNodeStub.prototype.nodeDetached = ve.Document.prototype.nodeDetached;

ve.dm.nodeFactory.register( ve.dm.BranchNodeStub );

/* Tests */

QUnit.test( 'canHaveChildren', function ( assert ) {
	var node = new ve.dm.BranchNodeStub();
	assert.strictEqual( node.canHaveChildren(), true );
} );

QUnit.test( 'canHaveChildrenNotContent', function ( assert ) {
	var node = new ve.dm.BranchNodeStub();
	assert.strictEqual( node.canHaveChildrenNotContent(), true );
} );

QUnit.test( 'setRoot', function ( assert ) {
	var node1 = new ve.dm.BranchNodeStub(),
		node2 = new ve.dm.BranchNodeStub( {}, [ node1 ] ),
		node3 = new ve.dm.BranchNodeStub( {}, [ node2 ] ),
		node4 = new ve.dm.BranchNodeStub();
	node3.setRoot( node4 );
	assert.strictEqual( node3.getRoot(), node4 );
	assert.strictEqual( node2.getRoot(), node4 );
	assert.strictEqual( node1.getRoot(), node4 );
} );

QUnit.test( 'setDocument', function ( assert ) {
	var node1 = new ve.dm.BranchNodeStub(),
		node2 = new ve.dm.BranchNodeStub( {}, [ node1 ] ),
		node3 = new ve.dm.BranchNodeStub( {}, [ node2 ] ),
		node4 = new ve.dm.BranchNodeStub();
	node3.setDocument( node4 );
	assert.strictEqual( node3.getDocument(), node4 );
	assert.strictEqual( node2.getDocument(), node4 );
	assert.strictEqual( node1.getDocument(), node4 );
} );

QUnit.test( 'push', function ( assert ) {
	var node1 = new ve.dm.BranchNodeStub(),
		node2 = new ve.dm.BranchNodeStub(),
		node3 = new ve.dm.BranchNodeStub( {}, [ node1 ] );
	node3.on( 'splice', function () {
		// Will be called 1 time
		assert.true( true, 'splice was emitted' );
	} );
	assert.strictEqual( node3.push( node2 ), 2 );
	assert.deepEqual( node3.getChildren(), [ node1, node2 ] );
} );

QUnit.test( 'pop', function ( assert ) {
	var node1 = new ve.dm.BranchNodeStub(),
		node2 = new ve.dm.BranchNodeStub(),
		node3 = new ve.dm.BranchNodeStub( {}, [ node1, node2 ] );
	node3.on( 'splice', function () {
		// Will be called 1 time
		assert.true( true, 'splice was emitted' );
	} );
	assert.strictEqual( node3.pop(), node2 );
	assert.deepEqual( node3.getChildren(), [ node1 ] );
} );

QUnit.test( 'unshift', function ( assert ) {
	var node1 = new ve.dm.BranchNodeStub(),
		node2 = new ve.dm.BranchNodeStub(),
		node3 = new ve.dm.BranchNodeStub( {}, [ node1 ] );
	node3.on( 'splice', function () {
		// Will be called 1 time
		assert.true( true, 'splice was emitted' );
	} );
	assert.strictEqual( node3.unshift( node2 ), 2 );
	assert.deepEqual( node3.getChildren(), [ node2, node1 ] );
} );

QUnit.test( 'shift', function ( assert ) {
	var node1 = new ve.dm.BranchNodeStub(),
		node2 = new ve.dm.BranchNodeStub(),
		node3 = new ve.dm.BranchNodeStub( {}, [ node1, node2 ] );
	node3.on( 'splice', function () {
		// Will be called 1 time
		assert.true( true, 'splice was emitted' );
	} );
	assert.strictEqual( node3.shift(), node1 );
	assert.deepEqual( node3.getChildren(), [ node2 ] );
} );

QUnit.test( 'splice', function ( assert ) {
	var node1 = new ve.dm.BranchNodeStub(),
		node2 = new ve.dm.BranchNodeStub(),
		node3 = new ve.dm.BranchNodeStub(),
		node4 = new ve.dm.BranchNodeStub( {}, [ node1, node2 ] );
	node4.on( 'splice', function () {
		// Will be called 3 times
		assert.true( true, 'splice was emitted' );
	} );
	// Insert branch
	assert.deepEqual( node4.splice( 1, 0, node3 ), [] );
	assert.deepEqual( node4.getChildren(), [ node1, node3, node2 ] );
	// Remove branch
	assert.deepEqual( node4.splice( 1, 1 ), [ node3 ] );
	assert.deepEqual( node4.getChildren(), [ node1, node2 ] );
	// Remove branch and insert branch
	assert.deepEqual( node4.splice( 1, 1, node3 ), [ node2 ] );
	assert.deepEqual( node4.getChildren(), [ node1, node3 ] );
} );
