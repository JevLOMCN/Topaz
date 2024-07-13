QUnit.module( 'ext.echo.dm - FiltersModel' );

QUnit.test.each( 'Constructing the model', {
	'Empty config': {
		config: {},
		expected: {}
	},
	'Readstate: unread': {
		config: {
			readState: 'unread'
		},
		expected: {
			getReadState: 'unread'
		}
	},
	'Readstate: read': {
		config: {
			readState: 'read'
		},
		expected: {
			getReadState: 'read'
		}
	}
}, function ( assert, data ) {
	var defaultValues = {
		getReadState: 'all'
	};
	var expected = $.extend( true, {}, defaultValues, data.expected );

	var model = new mw.echo.dm.FiltersModel( data.config );

	for ( var method in expected ) {
		assert.deepEqual(
			// Run the method
			model[ method ](),
			// Expected value
			expected[ method ],
			// Message
			method
		);
	}
} );

QUnit.test( 'Changing filters', function ( assert ) {
	var model = new mw.echo.dm.FiltersModel();

	assert.strictEqual(
		model.getReadState(),
		'all',
		'Initial value: all'
	);

	model.setReadState( 'unread' );
	assert.strictEqual(
		model.getReadState(),
		'unread',
		'Changing state (unread)'
	);

	model.setReadState( 'read' );
	assert.strictEqual(
		model.getReadState(),
		'read',
		'Changing state (read)'
	);

	model.setReadState( 'foo' );
	assert.strictEqual(
		model.getReadState(),
		'read',
		'Ignoring invalid state (foo)'
	);
} );

QUnit.test( '.setReadState() events', function ( assert ) {
	var results = [];
	var model = new mw.echo.dm.FiltersModel();

	// Listen to update event
	model.on( 'update', function () {
		results.push( model.getReadState() );
	} );

	// Trigger events
	model.setReadState( 'read' ); // [ 'read' ]
	model.setReadState( 'unread' ); // [ 'read', 'unread' ]
	model.setReadState( 'unread' ); // (no change, no event) [ 'read', 'unread' ]
	model.setReadState( 'all' ); // [ 'read', 'unread', 'all' ]
	model.setReadState( 'foo' ); // (invalid value, no event) [ 'read', 'unread', 'all' ]
	model.setReadState( 'unread' ); // [ 'read', 'unread', 'all', 'unread' ]

	assert.deepEqual(
		// Actual
		results,
		// Expected:
		[ 'read', 'unread', 'all', 'unread' ],
		// Message
		'Update events emitted'
	);
} );
