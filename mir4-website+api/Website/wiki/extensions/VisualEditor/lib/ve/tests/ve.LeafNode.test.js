/*!
 * VisualEditor LeafNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.LeafNode' );

/* Stubs */

ve.LeafNodeStub = function VeLeafNodeStub() {
	// Parent constructor
	ve.LeafNode.call( this );
};

OO.inheritClass( ve.LeafNodeStub, ve.LeafNode );

ve.LeafNodeStub.static.name = 'leaf-stub';

/* Tests */
