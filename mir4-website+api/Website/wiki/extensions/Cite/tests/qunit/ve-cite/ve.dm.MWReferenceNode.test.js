'use strict';

QUnit.module( 've.dm.MWReferenceNode (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'getGroup', function ( assert ) {
	const dataElement = { attributes: { refGroup: 'g' } };
	assert.deepEqual( ve.dm.MWReferenceNode.static.getGroup( dataElement ), 'g' );
} );

QUnit.test( 'cloneElement', function ( assert ) {
	const element = {
		attributes: { contentsUsed: true, mw: {}, originalMw: {} }
	};
	const store = { value: () => false };
	const clone = ve.dm.MWReferenceNode.static.cloneElement( element, store );
	assert.deepEqual( clone.attributes, {} );
	assert.true( isFinite( clone.originalDomElementsHash ) );
} );

QUnit.test( 'getHashObject', function ( assert ) {
	const dataElement = { type: 'T', attributes: { listGroup: 'L' } };
	assert.deepEqual( ve.dm.MWReferenceNode.static.getHashObject( dataElement ), dataElement );
	// FIXME: Shouldn't this behave different?
	assert.deepEqual( ve.dm.MWReferenceNode.static.getInstanceHashObject( dataElement ),
		dataElement );
} );

QUnit.test( 'describeChange', function ( assert ) {
	for ( const [ key, change, expected ] of [
		[ 'refGroup', { to: 'b' }, 'cite-ve-changedesc-ref-group-to,<ins>b</ins>' ],
		[ 'refGroup', { from: 'a' }, 'cite-ve-changedesc-ref-group-from,<del>a</del>' ],
		[ 'refGroup', { from: 'a', to: 'b' }, 'cite-ve-changedesc-ref-group-both,<del>a</del>,<ins>b</ins>' ],
		[ '', {}, undefined ]
	] ) {
		let msg = ve.dm.MWReferenceNode.static.describeChange( key, change );
		if ( Array.isArray( msg ) ) {
			msg = $( '<span>' ).append( msg ).html();
		}
		assert.strictEqual( msg, expected );
	}
} );
