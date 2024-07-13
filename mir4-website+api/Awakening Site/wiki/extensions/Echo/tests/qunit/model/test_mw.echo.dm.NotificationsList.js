QUnit.module( 'ext.echo.dm - NotificationsList' );

QUnit.test.each( 'Constructing the model', {
	'Empty config': {
		config: {},
		expected: {}
	},
	'Prefilled data': {
		config: {
			title: 'Some title',
			name: 'local_demo',
			source: 'hewiki',
			sourceURL: 'http://he.wiki.local.wmftest.net:8080/wiki/$1',
			timestamp: '20160916171300'
		},
		expected: {
			getTitle: 'Some title',
			getName: 'local_demo',
			getSource: 'hewiki',
			getSourceURL: 'http://he.wiki.local.wmftest.net:8080/wiki/$1',
			getTimestamp: '20160916171300',
			isForeign: true
		}
	}
}, function ( assert, data ) {
	var defaultValues = {
		getAllItemIds: [],
		getAllItemIdsByType: [],
		getTitle: '',
		getName: 'local',
		getSource: 'local',
		getSourceURL: '',
		getTimestamp: 0,
		getCount: 0,
		hasUnseen: false,
		isForeign: false
	};
	var expected = $.extend( true, {}, defaultValues, data.expected );
	var model = new mw.echo.dm.NotificationsList( data.config );

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

QUnit.test( 'Handling notification items', function ( assert ) {
	var model = new mw.echo.dm.NotificationsList( { timestamp: '200101010000' } );
	var items = [
		new mw.echo.dm.NotificationItem( 0, { type: 'alert', timestamp: '201609190000', read: false, seen: false } ),
		new mw.echo.dm.NotificationItem( 1, { type: 'message', timestamp: '201609190100', read: false, seen: true } ),
		new mw.echo.dm.NotificationItem( 2, { type: 'alert', timestamp: '201609190200', read: true, seen: true } ),
		new mw.echo.dm.NotificationItem( 3, { type: 'message', timestamp: '201609190300', read: true, seen: true } ),
		new mw.echo.dm.NotificationItem( 4, { type: 'alert', timestamp: '201609190400', read: true, seen: true } ),
		new mw.echo.dm.NotificationItem( 5, { type: 'message', timestamp: '201609190500', read: true, seen: false } )
	];

	assert.strictEqual(
		model.getCount(),
		0,
		'Model list starts empty'
	);
	assert.strictEqual(
		model.getTimestamp(),
		'200101010000',
		'Model timestamp is its default'
	);

	model.setItems( items );
	assert.strictEqual(
		model.getCount(),
		6,
		'Item list setup'
	);
	assert.strictEqual(
		model.getTimestamp(),
		'201609190100',
		'Model timestamp is the latest unread item\'s timestamp'
	);
	assert.deepEqual(
		model.getAllItemIds(),
		[ 1, 0, 5, 4, 3, 2 ],
		'getAllItemIds (sorted)'
	);
	assert.deepEqual(
		[
			model.getAllItemIdsByType( 'alert' ),
			model.getAllItemIdsByType( 'message' )
		],
		[
			[ 0, 4, 2 ],
			[ 1, 5, 3 ]
		],
		'getAllItemIdsByType (sorted)'
	);
	assert.deepEqual(
		model.findByIds( [ 1, 2 ] ),
		[ items[ 1 ], items[ 2 ] ],
		'findByIds'
	);

	// Change item state (trigger resort)
	items[ 1 ].toggleRead( true );
	items[ 3 ].toggleRead( false );
	items[ 5 ].toggleSeen( true ); // Will not affect sorting order of the item
	assert.deepEqual(
		model.getAllItemIds(),
		[ 3, 0, 5, 4, 2, 1 ],
		'getAllItemIds (re-sorted)'
	);

	// Discard items
	model.discardItems( [ items[ 5 ], items[ 2 ] ] );

	assert.deepEqual(
		model.getAllItemIds(),
		[ 3, 0, 4, 1 ],
		'getAllItemIds (discarded items)'
	);
	assert.deepEqual(
		[
			model.getAllItemIdsByType( 'alert' ),
			model.getAllItemIdsByType( 'message' )
		],
		[
			[ 0, 4 ],
			[ 3, 1 ]
		],
		'getAllItemIdsByType (discarded items)'
	);

} );

QUnit.test( 'Intercepting events', function ( assert ) {
	var model = new mw.echo.dm.NotificationsList();
	var result = [];
	var items = [
		new mw.echo.dm.NotificationItem( 0, { timestamp: '201609190000', read: false, seen: false } ),
		new mw.echo.dm.NotificationItem( 1, { timestamp: '201609190100', read: false, seen: true } ),
		new mw.echo.dm.NotificationItem( 2, { timestamp: '201609190200', read: true, seen: true } ),
		new mw.echo.dm.NotificationItem( 3, { timestamp: '201609190300', read: true, seen: true } ),
		new mw.echo.dm.NotificationItem( 4, { timestamp: '201609190400', read: true, seen: true } ),
		new mw.echo.dm.NotificationItem( 5, { timestamp: '201609190500', read: true, seen: true } )
	];

	// Listen to events
	model
		.on( 'update', function ( itms ) {
			result.push( 'update:' + itms.length );
		} )
		.on( 'discard', function ( item ) {
			result.push( 'discard:' + item.getId() );
		} )
		.on( 'itemUpdate', function ( item ) {
			result.push( 'itemUpdate:' + item.getId() );
		} );

	// Set up and trigger events
	model
		.setItems( items ); // [ 'update:6' ]
	model.discardItems( items[ items.length - 1 ] ); // [ 'update:6', 'discard:5' ]
	items[ 0 ].toggleSeen( true ); // [ 'update:6', 'discard:5', 'itemUpdate:0' ]
	items[ 1 ].toggleRead( true ); // [ 'update:6', 'discard:5', 'itemUpdate:0', 'itemUpdate:1' ]

	assert.deepEqual(
		// Actual
		result,
		// Expected:
		[ 'update:6', 'discard:5', 'itemUpdate:0', 'itemUpdate:1' ],
		// Message
		'Events emitted correctly'
	);
} );
