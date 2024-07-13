'use strict';

QUnit.module( 've.dm.MWReferencesListNode (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'isEditable', function ( assert ) {
	let model = new ve.dm.MWReferencesListNode();
	assert.true( model.isEditable() );

	model = new ve.dm.MWReferencesListNode( { attributes: { templateGenerated: true } } );
	assert.false( model.isEditable() );
} );

QUnit.test( 'matchFunction', function ( assert ) {
	const el = document.createElement( 'div' );
	assert.false( ve.dm.MWReferencesListNode.static.matchFunction( el ) );
} );

QUnit.test( 'describeChange', function ( assert ) {
	for ( const [ key, change, expected ] of [
		[ 'refGroup', { to: 'b' }, 'cite-ve-changedesc-reflist-group-to,<ins>b</ins>' ],
		[ 'refGroup', { from: 'a' }, 'cite-ve-changedesc-reflist-group-from,<del>a</del>' ],
		[ 'refGroup', { from: 'a', to: 'b' }, 'cite-ve-changedesc-reflist-group-both,<del>a</del>,<ins>b</ins>' ],
		[ 'isResponsive', { from: 'a' }, 'cite-ve-changedesc-reflist-responsive-unset' ],
		[ 'isResponsive', {}, 'cite-ve-changedesc-reflist-responsive-set' ],
		[ 'originalMw', {}, null ],
		[ '', {}, null ]
	] ) {
		let msg = ve.dm.MWReferencesListNode.static.describeChange( key, change );
		if ( Array.isArray( msg ) ) {
			msg = $( '<span>' ).append( msg ).html();
		}
		assert.strictEqual( msg, expected );
	}
} );

QUnit.test( 'getHashObject', function ( assert ) {
	const dataElement = {
		type: 'T',
		attributes: {
			refGroup: 'R',
			listGroup: 'L',
			isResponsive: true,
			templateGenerated: true
		}
	};
	assert.deepEqual( ve.dm.MWReferencesListNode.static.getHashObject( dataElement ), dataElement );
} );
