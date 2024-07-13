/*!
 * VisualEditor DataModel Document store tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.DocumentStore' );

QUnit.test( 'Create', function ( assert ) {
	var done = assert.async(),
		fakeMongo = new ve.dm.FakeMongo(),
		documentStore = new ve.dm.DocumentStore( fakeMongo, 'test', fakeMongo );

	documentStore.connect().then( function () {
		return documentStore.dropDatabase();
	} ).then( function () {
		return documentStore.load( 'Foo' );
	} ).then( function ( change ) {
		assert.deepEqual(
			change.toJSON(),
			{ start: 0, transactions: [] },
			'Load new empty document'
		);
		return documentStore.onNewChange( 'Foo', ve.dm.Change.static.deserialize( {
			start: 0,
			transactions: [ [ [ '', 'W' ] ], 'o', 'rld' ]
		}, true ) );
	} ).then( function () {
		return documentStore.onNewChange( 'Foo', ve.dm.Change.static.deserialize( {
			start: 3,
			transactions: [ [ [ '', 'H' ], 5 ], 'e', 'l', 'l', 'o', ' ' ]
		}, true ) );
	} ).then( function () {
		return documentStore.load( 'Bar' );
	} ).then( function ( change ) {
		assert.deepEqual(
			change.toJSON(),
			{ start: 0, transactions: [] },
			'Different document is empty'
		);
		return documentStore.onNewChange( 'Bar', ve.dm.Change.static.deserialize( {
			start: 44,
			transactions: [ [ [ '', 'X' ] ] ]
		}, true ) ).then( function () {
			assert.true( false, 'Throw on unmached start' );
		} ).catch( function () {
			assert.true( true, 'Throw on unmatched start' );
		} );
	} ).then( function () {
		return documentStore.load( 'Foo' );
	} ).then( function ( change ) {
		assert.deepEqual(
			change.toJSON(),
			{ start: 0, transactions: [ [ [ '', 'W' ] ], 'o', 'rld', [ [ '', 'H' ], 5 ], 'e', 'l', 'l', 'o', ' ' ] },
			'Transactions were saved'
		);
		return documentStore.dropDatabase();
	} ).then( function () {
		return documentStore.load( 'Foo' );
	} ).then( function ( change ) {
		assert.deepEqual(
			change.toJSON(),
			{ start: 0, transactions: [] },
			'Reload new empty document after dropDatabase'
		);
	} ).then( function () {
		return documentStore.onClose();
	} ).then( function () {
		assert.deepEqual(
			fakeMongo.log,
			[
				{ type: 'DocumentStore#connected', dbName: 'test' },
				{ type: 'DocumentStore#dropDatabase', dbName: 'test' },
				{ type: 'DocumentStore#loaded', docName: 'Foo', length: 0 },
				{ type: 'DocumentStore#onNewChange', docName: 'Foo', start: 0, length: 3 },
				{ type: 'DocumentStore#onNewChange', docName: 'Foo', start: 3, length: 6 },
				{ type: 'DocumentStore#loaded', docName: 'Bar', length: 0 },
				{ type: 'DocumentStore#loaded', docName: 'Foo', length: 9 },
				{ type: 'DocumentStore#dropDatabase', dbName: 'test' },
				{ type: 'DocumentStore#loaded', docName: 'Foo', length: 0 },
				{ type: 'DocumentStore#onClose' }
			],
			'Log is correct'
		);
	} ).catch( function ( error ) {
		assert.true( false, 'Test failure: ' + error );
	} ).then( () => done() );
} );
