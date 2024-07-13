/*!
 * VisualEditor BranchNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.BranchNode' );

/* Stubs */

ve.LabelledNodeStub = function VeLabelledNodeStub( label ) {
	// Parent constructor
	ve.LabelledNodeStub.super.call( this );
	// Mixin constructor
	OO.EventEmitter.call( this );
	this.label = label;
};

OO.inheritClass( ve.LabelledNodeStub, ve.Node );
OO.mixinClass( ve.LabelledNodeStub, OO.EventEmitter );

ve.BranchNodeStub = function VeBranchNodeStub( label, children ) {
	// Parent constructor
	ve.BranchNodeStub.super.call( this, label );
	// Mixin constructor
	ve.BranchNode.call( this, children );
};

OO.inheritClass( ve.BranchNodeStub, ve.LabelledNodeStub );
OO.mixinClass( ve.BranchNodeStub, ve.BranchNode );

/* Tests */

QUnit.test( 'getChildren', function ( assert ) {
	var node1 = new ve.BranchNodeStub( 'node1' ),
		node2 = new ve.BranchNodeStub( 'node2', [ node1 ] );
	assert.deepEqual( node1.getChildren(), [] );
	assert.deepEqual( node2.getChildren(), [ node1 ] );
} );

QUnit.test( 'indexOf', function ( assert ) {
	var node1 = new ve.BranchNodeStub( 'node1' ),
		node2 = new ve.BranchNodeStub( 'node2' ),
		node3 = new ve.BranchNodeStub( 'node3' ),
		node4 = new ve.BranchNodeStub( 'node4', [ node1, node2, node3 ] );
	assert.strictEqual( node4.indexOf( null ), -1 );
	assert.strictEqual( node4.indexOf( node1 ), 0 );
	assert.strictEqual( node4.indexOf( node2 ), 1 );
	assert.strictEqual( node4.indexOf( node3 ), 2 );
} );

QUnit.test( 'setDocument', function ( assert ) {
	var log = [],
		doc = new ve.Document( new ve.BranchNodeStub( 'root' ) ),
		list = new ve.BranchNodeStub( 'list', [
			new ve.BranchNodeStub( 'listItem1', [
				new ve.LabelledNodeStub( 'para1' )
			] ),
			new ve.BranchNodeStub( 'listItem2', [
				new ve.LabelledNodeStub( 'para2' )
			] )
		] ),
		attachEvents = [ '+para1', '+listItem1', '+para2', '+listItem2', '+list' ],
		detachEvents = [ '-para1', '-listItem1', '-para2', '-listItem2', '-list' ];

	doc.connect( doc, {
		nodeAttached: function ( node ) {
			log.push( '+' + node.label );
		},
		nodeDetached: function ( node ) {
			log.push( '-' + node.label );
		}
	} );
	list.attach( doc.getDocumentNode() );
	assert.deepEqual( log, attachEvents, 'Attach' );
	log.length = 0;
	list.detach();
	assert.deepEqual( log, detachEvents, 'Detach' );
	list.attach( doc.getDocumentNode() );
	log.length = 0;
	list.setDocument( new ve.Document( new ve.BranchNodeStub( 'root2' ) ) );
	assert.deepEqual( log, detachEvents, 'Change to another doc' );
	log.length = 0;
	list.setDocument( doc );
	assert.deepEqual( log, attachEvents, 'Change to this doc' );
} );
