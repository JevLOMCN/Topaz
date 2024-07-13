QUnit.module( 'ext.echo.dm - NotificationGroupsList' );

QUnit.test( 'Constructing the model', function ( assert ) {
	var model = new mw.echo.dm.NotificationGroupsList();

	assert.strictEqual(
		model.getTimestamp(),
		0,
		'Empty group has timestamp 0'
	);
} );

QUnit.test( 'Managing lists', function ( assert ) {
	var model = new mw.echo.dm.NotificationGroupsList();
	var groupDefinitions = [
		{
			name: 'foo',
			sourceData: {
				title: 'Foo Wiki',
				base: 'http://foo.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 0 ),
				new mw.echo.dm.NotificationItem( 1 ),
				new mw.echo.dm.NotificationItem( 2 )
			]
		},
		{
			name: 'bar',
			sourceData: {
				title: 'Bar Wiki',
				base: 'http://bar.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 3 ),
				new mw.echo.dm.NotificationItem( 4 ),
				new mw.echo.dm.NotificationItem( 5 ),
				new mw.echo.dm.NotificationItem( 6 )
			]
		},
		{
			name: 'baz',
			sourceData: {
				title: 'Baz Wiki',
				base: 'http://baz.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 7 )
			]
		}
	];

	groupDefinitions.forEach( function ( def, i ) {
		model.addGroup(
			def.name,
			def.sourceData,
			def.items
		);

		assert.strictEqual(
			model.getItemCount(),
			i + 1,
			'Group number increases after addGroup ("' + def.name + '")'
		);

		var result = model.getGroupByName( def.name );
		assert.strictEqual(
			result.getName(),
			def.name,
			'Group exists after addGroup ("' + def.name + '")'
		);
	} );

	// Remove group
	model.removeGroup( groupDefinitions[ 0 ].name );

	assert.strictEqual(
		model.getItemCount(),
		groupDefinitions.length - 1,
		'Group number decreased after removeGroup'
	);
	assert.strictEqual(
		model.getGroupByName( groupDefinitions[ 0 ] ),
		null,
		'Removed group is no longer in the list'
	);

	// Removing the last item from a group should remove the group
	var group = model.getGroupByName( 'baz' );
	group.discardItems( groupDefinitions[ 2 ].items );
	assert.strictEqual(
		model.getGroupByName( 'baz' ),
		null,
		'Empty group is no longer in the list'
	);
} );

QUnit.test( 'Emitting discard event', function ( assert ) {
	var results = [];
	var model = new mw.echo.dm.NotificationGroupsList();
	var groups = {
		first: [
			new mw.echo.dm.NotificationItem( 0 ),
			new mw.echo.dm.NotificationItem( 1 ),
			new mw.echo.dm.NotificationItem( 2 )
		],
		second: [
			new mw.echo.dm.NotificationItem( 3 ),
			new mw.echo.dm.NotificationItem( 4 ),
			new mw.echo.dm.NotificationItem( 5 )
		],
		third: [
			new mw.echo.dm.NotificationItem( 6 ),
			new mw.echo.dm.NotificationItem( 7 )
		],
		fourth: [
			new mw.echo.dm.NotificationItem( 8 ),
			new mw.echo.dm.NotificationItem( 9 )
		]
	};

	// Listen to the event
	model
		.on( 'discard', function ( g ) {
			results.push( g.getName() );
		} );

	// Fill the list
	for ( var group in groups ) {
		model.addGroup( group, {}, groups[ group ] );
	}

	// Trigger events
	model.removeGroup( 'first' ); // [ 'first' ]
	model.removeGroup( 'fourth' ); // [ 'first', 'fourth' ]
	// Group doesn't exist, no change
	model.removeGroup( 'first' ); // [ 'first', 'fourth' ]
	// Discard of an item in a group (no event on the list model)
	model.getGroupByName( 'third' ).discardItems( groups.third[ 0 ] ); // [ 'first', 'fourth' ]
	// Discard of the last item in a group (trigger discard event on the list model)
	model.getGroupByName( 'third' ).discardItems( groups.third[ 1 ] ); // [ 'first', 'fourth', 'third' ]

	assert.deepEqual(
		// Actual
		results,
		// Expected:
		[ 'first', 'fourth', 'third' ],
		// Message
		'Discard events emitted'
	);
} );
