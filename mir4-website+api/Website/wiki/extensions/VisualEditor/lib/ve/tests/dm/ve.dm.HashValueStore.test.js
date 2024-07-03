/*!
 * VisualEditor DataModel HashValueStore tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.HashValueStore' );

/* Tests */

QUnit.test( 'hash(es)', function ( assert ) {
	var object1 = { a: 1, b: 2 },
		object1Hash = 'h608de49a4600dbb5',
		object2 = { c: 3, d: 4 },
		object2Hash = 'hdf3d2cbd332be4da',
		customHash = 'hb05df789ce115b75',
		store = new ve.dm.HashValueStore();

	var hash = store.hash( object1 );
	assert.strictEqual( hash, object1Hash, 'First object stores in hash' );
	hash = store.hash( object1 );
	assert.strictEqual( hash, object1Hash, 'First object re-stores in hash' );
	hash = store.hash( object2 );
	assert.strictEqual( hash, object2Hash, 'Second object stores in 1' );
	assert.deepEqual( store.value( object1Hash ), object1, 'first object has finds first object' );

	hash = store.hash( object2, 'custom hash string' );
	assert.strictEqual( hash, customHash, 'Second object with custom hash stores in custom hash' );
	hash = store.hash( object1, 'custom hash string' );
	assert.strictEqual( hash, customHash, 'Using the same custom hash with a different object returns custom hash again' );
	assert.deepEqual( store.value( customHash ), object2, 'Second object was not overwritten' );

	store = new ve.dm.HashValueStore();

	var values = store.hashAll( [ object1, object2 ] );
	assert.deepEqual( values, [ object1Hash, object2Hash ], 'Store two objects in 0,1' );

	store = new ve.dm.HashValueStore();

	hash = store.hash( 'String to store' );
	assert.strictEqual( store.value( hash ), 'String to store', 'Strings are stored as strings, not objects' );

	hash = store.hash( [ 'array', 1, 2, 3 ] );
	assert.deepEqual( store.value( hash ), [ 'array', 1, 2, 3 ], 'Arrays are stored as arrays, not objects' );

} );

QUnit.test( 'value(s)', function ( assert ) {
	var object1 = { a: 1, b: 2 },
		object1Hash = 'h608de49a4600dbb5',
		object2 = { c: 3, d: 4 },
		object2Hash = 'hdf3d2cbd332be4da',
		store = new ve.dm.HashValueStore();

	store.hash( object1 );
	store.hash( object2 );
	assert.deepEqual( store.value( object1Hash ), object1, 'First hash gets first stored object' );
	assert.deepEqual( store.value( object2Hash ), object2, 'Second hash gets second stored object' );
	assert.strictEqual( store.value( 'h0missing' ), undefined, 'Value of missing hash is undefined' );
	assert.deepEqual( store.values( [ object2Hash, object1Hash ] ), [ object2, object1 ], 'Values [secondHash, firstHash] are second and first object' );
	object1.a = 3;
	assert.deepEqual( store.value( object1Hash ), { a: 1, b: 2 }, 'Value 0 is still first stored object after original has been modified' );
} );

QUnit.test( 'slice', function ( assert ) {
	var values = [ 'foo', 'bar', 'baz', 'qux', 'quux' ],
		store = new ve.dm.HashValueStore();

	store.hashAll( values );
	var sliced = store.slice( 2, 4 );
	assert.deepEqual( sliced.values( sliced.hashes ), values.slice( 2, 4 ), 'Slice' );
	sliced = store.slice( 3 );
	assert.deepEqual( sliced.values( sliced.hashes ), values.slice( 3 ), 'Slice to end' );
	sliced = store.slice();
	assert.deepEqual( sliced.values( sliced.hashes ), values, 'Slice all' );
} );
