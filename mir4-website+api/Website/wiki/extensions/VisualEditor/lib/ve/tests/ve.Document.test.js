/*!
 * VisualEditor Document tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.Document' );

/* Stubs */

ve.DocumentStub = function VeDocumentStub() {
	// Parent constructor
	ve.DocumentStub.super.apply( this, arguments );
};

OO.inheritClass( ve.DocumentStub, ve.Document );

/* Tests */

QUnit.test( 'getDocumentNode', function ( assert ) {
	var node = new ve.NodeStub(),
		doc = new ve.DocumentStub( node );
	assert.strictEqual( doc.getDocumentNode(), node );
	assert.strictEqual( node.getDocument(), doc );
} );
