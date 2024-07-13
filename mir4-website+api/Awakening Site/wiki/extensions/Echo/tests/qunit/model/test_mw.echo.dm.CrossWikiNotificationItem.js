QUnit.module( 'ext.echo.dm - CrossWikiNotificationItem' );

QUnit.test.each( 'Constructing the model', {
	'Default values': {
		params: {
			id: -1,
			config: {}
		},
		expected: {}
	},
	'Overriding model name': {
		params: {
			id: -1,
			config: { modelName: 'foo' }
		},
		expected: {
			getModelName: 'foo'
		}
	},
	'Overriding model count': {
		params: {
			id: -1,
			config: { count: 10 }
		},
		expected: {
			getCount: 10
		}
	}
}, function ( assert, data ) {
	var defaults = {
		getModelName: 'xwiki',
		getSourceNames: [],
		getCount: 0,
		hasUnseen: false,
		getItems: [],
		isEmpty: true
	};
	var expected = $.extend( true, {}, defaults, data.expected );

	var model = new mw.echo.dm.CrossWikiNotificationItem(
		data.params.id,
		data.params.config
	);

	for ( var method in defaults ) {
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

QUnit.test( 'Managing notification lists', function ( assert ) {
	var model = new mw.echo.dm.CrossWikiNotificationItem( 1 );
	var groupDefinitions = [
		{
			name: 'foo',
			sourceData: {
				title: 'Foo Wiki',
				base: 'http://foo.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 0, { source: 'foo', read: false, seen: false, timestamp: '201601010100' } ),
				new mw.echo.dm.NotificationItem( 1, { source: 'foo', read: false, seen: false, timestamp: '201601010200' } ),
				new mw.echo.dm.NotificationItem( 2, { source: 'foo', read: false, seen: false, timestamp: '201601010300' } )
			]
		},
		{
			name: 'bar',
			sourceData: {
				title: 'Bar Wiki',
				base: 'http://bar.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 3, { source: 'bar', read: false, seen: false, timestamp: '201601020000' } ),
				new mw.echo.dm.NotificationItem( 4, { source: 'bar', read: false, seen: false, timestamp: '201601020100' } ),
				new mw.echo.dm.NotificationItem( 5, { source: 'bar', read: false, seen: false, timestamp: '201601020200' } ),
				new mw.echo.dm.NotificationItem( 6, { source: 'bar', read: false, seen: false, timestamp: '201601020300' } )
			]
		},
		{
			name: 'baz',
			sourceData: {
				title: 'Baz Wiki',
				base: 'http://baz.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 7, { source: 'baz', timestamp: '201601050100' } )
			]
		}
	];

	// Add groups to model
	groupDefinitions.forEach( function ( def ) {
		model.getList().addGroup(
			def.name,
			def.sourceData,
			def.items
		);
	} );

	assert.deepEqual(
		model.getSourceNames(),
		[ 'baz', 'bar', 'foo' ],
		'Model source names exist in order'
	);
	assert.strictEqual(
		model.hasUnseen(),
		true,
		'hasUnseen is true if there are unseen items in any group'
	);

	// Mark all items as seen except one
	groupDefinitions.forEach( function ( def ) {
		def.items.forEach( function ( item ) {
			item.toggleSeen( true );
		} );
	} );
	groupDefinitions[ 0 ].items[ 0 ].toggleSeen( false );
	assert.strictEqual(
		model.hasUnseen(),
		true,
		'hasUnseen is true even if only one item in one group is unseen'
	);

	groupDefinitions[ 0 ].items[ 0 ].toggleSeen( true );
	assert.strictEqual(
		model.hasUnseen(),
		false,
		'hasUnseen is false if there are no unseen items in any of the groups'
	);

	// Discard group
	model.getList().removeGroup( 'foo' );
	assert.deepEqual(
		model.getSourceNames(),
		[ 'baz', 'bar' ],
		'Group discarded successfully'
	);
} );

QUnit.test( 'Update seen state', function ( assert ) {
	var model = new mw.echo.dm.CrossWikiNotificationItem( 1 );
	var groupDefinitions = [
		{
			name: 'foo',
			sourceData: {
				title: 'Foo Wiki',
				base: 'http://foo.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 0, { source: 'foo', read: false, seen: false, timestamp: '201601010100' } ),
				new mw.echo.dm.NotificationItem( 1, { source: 'foo', read: false, seen: false, timestamp: '201601010200' } ),
				new mw.echo.dm.NotificationItem( 2, { source: 'foo', read: false, seen: false, timestamp: '201601010300' } )
			]
		},
		{
			name: 'bar',
			sourceData: {
				title: 'Bar Wiki',
				base: 'http://bar.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 3, { source: 'bar', read: false, seen: false, timestamp: '201601020000' } ),
				new mw.echo.dm.NotificationItem( 4, { source: 'bar', read: false, seen: false, timestamp: '201601020100' } ),
				new mw.echo.dm.NotificationItem( 5, { source: 'bar', read: false, seen: false, timestamp: '201601020200' } ),
				new mw.echo.dm.NotificationItem( 6, { source: 'bar', read: false, seen: false, timestamp: '201601020300' } )
			]
		},
		{
			name: 'baz',
			sourceData: {
				title: 'Baz Wiki',
				base: 'http://baz.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 7, { source: 'baz', timestamp: '201601050100' } )
			]
		}
	];

	// Count all actual items
	var numAllItems = groupDefinitions.reduce( function ( prev, curr ) {
		return prev + curr.items.length;
	}, 0 );

	// Add groups to model
	for ( var i = 0; i < groupDefinitions.length; i++ ) {
		model.getList().addGroup(
			groupDefinitions[ i ].name,
			groupDefinitions[ i ].sourceData,
			groupDefinitions[ i ].items
		);
	}

	var numUnseenItems = model.getItems().filter( function ( item ) {
		return !item.isSeen();
	} ).length;
	assert.strictEqual(
		numUnseenItems,
		numAllItems,
		'Starting state: all items are unseen'
	);

	// Update seen time to be bigger than 'foo' but smaller than the other groups
	model.updateSeenState( '201601010400' );

	numUnseenItems = model.getItems().filter( function ( item ) {
		return !item.isSeen();
	} ).length;
	assert.strictEqual(
		numUnseenItems,
		numAllItems - groupDefinitions[ 0 ].items.length,
		'Only some items are seen'
	);

	// Update seen time to be bigger than all
	model.updateSeenState( '201701010000' );

	numUnseenItems = model.getItems().filter( function ( item ) {
		return !item.isSeen();
	} ).length;
	assert.strictEqual(
		numUnseenItems,
		0,
		'All items are seen'
	);
} );

QUnit.test( 'Emit discard event', function ( assert ) {
	var results = [];
	var model = new mw.echo.dm.CrossWikiNotificationItem( -1 );
	var groupDefinitions = [
		{
			name: 'foo',
			sourceData: {
				title: 'Foo Wiki',
				base: 'http://foo.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 0, { source: 'foo', read: false, seen: false, timestamp: '201601010100' } ),
				new mw.echo.dm.NotificationItem( 1, { source: 'foo', read: false, seen: false, timestamp: '201601010200' } ),
				new mw.echo.dm.NotificationItem( 2, { source: 'foo', read: false, seen: false, timestamp: '201601010300' } )
			]
		},
		{
			name: 'bar',
			sourceData: {
				title: 'Bar Wiki',
				base: 'http://bar.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 3, { source: 'bar', read: false, seen: false, timestamp: '201601020000' } ),
				new mw.echo.dm.NotificationItem( 4, { source: 'bar', read: false, seen: false, timestamp: '201601020100' } ),
				new mw.echo.dm.NotificationItem( 5, { source: 'bar', read: false, seen: false, timestamp: '201601020200' } ),
				new mw.echo.dm.NotificationItem( 6, { source: 'bar', read: false, seen: false, timestamp: '201601020300' } )
			]
		},
		{
			name: 'baz',
			sourceData: {
				title: 'Baz Wiki',
				base: 'http://baz.wiki.sample/$1'
			},
			items: [
				new mw.echo.dm.NotificationItem( 7, { source: 'baz', timestamp: '201601050100' } )
			]
		}
	];

	// Add groups to model
	for ( var i = 0; i < groupDefinitions.length; i++ ) {
		model.getList().addGroup(
			groupDefinitions[ i ].name,
			groupDefinitions[ i ].sourceData,
			groupDefinitions[ i ].items
		);
	}

	// Listen to event
	model.on( 'discard', function ( name ) {
		results.push( name );
	} );

	// Trigger
	model.getList().removeGroup( 'foo' ); // [ 'foo' ]
	// Empty a list
	model.getList().getGroupByName( 'baz' ).discardItems( groupDefinitions[ 2 ].items ); // [ 'foo', 'baz' ]

	assert.deepEqual(
		results,
		[ 'foo', 'baz' ],
		'Discard event emitted'
	);
} );
