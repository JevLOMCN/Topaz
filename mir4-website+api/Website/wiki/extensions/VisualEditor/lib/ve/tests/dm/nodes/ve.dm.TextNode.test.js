/*!
 * VisualEditor DataModel TextNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.TextNode' );

/* Tests */

QUnit.test( 'getOuterLength', function ( assert ) {
	var node1 = new ve.dm.TextNode(),
		node2 = new ve.dm.TextNode();

	node2.setLength( 1234 );

	assert.strictEqual( node1.getOuterLength(), 0 );
	assert.strictEqual( node2.getOuterLength(), 1234 );
} );
