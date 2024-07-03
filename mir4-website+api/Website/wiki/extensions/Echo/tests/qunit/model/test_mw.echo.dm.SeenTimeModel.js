QUnit.module( 'ext.echo.dm - SeenTimeModel' );

QUnit.test( '.getTypes()', function ( assert ) {
	var model = new mw.echo.dm.SeenTimeModel();

	assert.deepEqual(
		model.getTypes(),
		[ 'alert', 'message' ],
		'Default model has both types'
	);
} );

QUnit.test( '.setSeenTime() reflected', function ( assert ) {
	var model;

	model = new mw.echo.dm.SeenTimeModel();
	model.setSeenTime( '20160101010000' );

	assert.deepEqual(
		model.getSeenTime(),
		'20160101010000',
		'Model sets seen time for both types'
	);

	model = new mw.echo.dm.SeenTimeModel( { types: 'alert' } );
	model.setSeenTime( '20160101010001' );

	assert.deepEqual(
		model.getSeenTime(),
		'20160101010001',
		'Alerts seen time model returns correct time'
	);
} );

QUnit.test( '.setSeenTime() events', function ( assert ) {
	var results = [];
	var model = new mw.echo.dm.SeenTimeModel();

	// Attach a listener
	model.on( 'update', function ( time ) {
		results.push( time );
	} );

	// Trigger events
	model.setSeenTime( '1' ); // [ '1' ]
	model.setSeenTime( '2' ); // [ '1', '2' ]
	model.setSeenTime( '2' ); // (no change, no event) [ '1', '2' ]

	assert.deepEqual(
		results,
		[ '1', '2' ],
		'events emitted'
	);
} );
