QUnit.module( 'ext.echo.dm - NotificationItem', function ( hooks ) {
	var fakeData = {
		type: 'alert',
		read: true,
		seen: true,
		timestamp: '2016-09-14T23:21:56Z',
		content: {
			header: 'Your edit on <strong>Moai</strong> was reverted.',
			compactHeader: 'Your edit on <strong>Moai</strong> was reverted.',
			body: 'undo'
		},
		iconType: 'revert',
		primaryUrl: 'http://dev.wiki.local.wmftest.net:8080/w/index.php?title=Moai&oldid=prev&diff=1978&markasread=2126',
		secondaryUrls: [
			{
				url: 'http://dev.wiki.local.wmftest.net:8080/wiki/User:RandomUser',
				label: 'RandomUser',
				icon: 'userAvatar'
			},
			{
				url: 'http://dev.wiki.local.wmftest.net:8080/wiki/Talk:Moai',
				label: 'Moai',
				tooltip: 'Talk:Moai',
				icon: 'speechBubbles'
			}
		]
	};
	var now = 1234567890000;
	var nowFormatted = '2009-02-13T23:31:30Z';

	hooks.beforeEach( function () {
		this.sandbox.useFakeTimers( now );
	} );

	QUnit.test.each( 'Constructing items', {
		'Empty data': {
			params: { id: 0, config: {} },
			methods: 'all',
			expected: { getId: 0, getAllIds: [ 0 ] }
		},
		'Fake data': {
			params: { id: 999, config: fakeData },
			methods: 'all',
			expected: {
				getId: 999,
				getAllIds: [ 999 ],
				getType: 'alert',
				isRead: true,
				isSeen: true,
				getTimestamp: '2016-09-14T23:21:56Z',
				getContentHeader: 'Your edit on <strong>Moai</strong> was reverted.',
				getContentBody: 'undo',
				getIconType: 'revert',
				getPrimaryUrl: 'http://dev.wiki.local.wmftest.net:8080/w/index.php?title=Moai&oldid=prev&diff=1978&markasread=2126',
				getSecondaryUrls: [
					{
						url: 'http://dev.wiki.local.wmftest.net:8080/wiki/User:RandomUser',
						label: 'RandomUser',
						icon: 'userAvatar'
					},
					{
						url: 'http://dev.wiki.local.wmftest.net:8080/wiki/Talk:Moai',
						label: 'Moai',
						tooltip: 'Talk:Moai',
						icon: 'speechBubbles'
					}
				]
			}
		}
	}, function ( assert, data ) {
		var defaultValues = {
			getId: undefined,
			getContentHeader: '',
			getContentBody: '',
			getCategory: '',
			getType: 'message',
			isRead: false,
			isSeen: false,
			isForeign: false,
			isBundled: false,
			getTimestamp: nowFormatted,
			getPrimaryUrl: undefined,
			getIconURL: undefined,
			getIconType: undefined,
			getSecondaryUrls: [],
			getModelName: 'local',
			getAllIds: []
		};
		var expected = $.extend( true, {}, defaultValues, data.expected );

		var itemModel = new mw.echo.dm.NotificationItem(
			data.params.id,
			data.params.config
		);

		var methods = ( data.methods === 'all' ? Object.keys( expected ) : data.methods );
		methods.forEach( function ( method ) {
			assert.deepEqual(
				// Run the method
				itemModel[ method ](),
				// Expected result
				expected[ method ],
				// Message
				method
			);
		} );
	} );

	QUnit.test( 'Emitting update event', function ( assert ) {
		var results = [];
		var itemModel = new mw.echo.dm.NotificationItem( 0, $.extend( true, {}, fakeData, { seen: false, read: false } ) );

		// Listen to update event
		itemModel.on( 'update', function () {
			results.push( [
				itemModel.isRead(),
				itemModel.isSeen()
			] );
		} );

		// Trigger events
		itemModel.toggleSeen( true ); // [ [ false, true ] ]
		itemModel.toggleSeen( true ); // [ [ false, true ] ] ( No change, event was not emitted )
		itemModel.toggleRead( true ); // [ [ false, true ], [ true, true ] ]
		itemModel.toggleRead( true ); // [ [ false, true ], [ true, true ] ] ( No change, event was not emitted )
		itemModel.toggleRead( false ); // [ [ false, true ], [ true, true ], [ false, true ] ]
		itemModel.toggleSeen( false ); // [ [ false, true ], [ true, true ], [ false, true ], [ false, false ] ]
		itemModel.toggleRead( true ); // [ [ false, true ], [ true, true ], [ false, true ], [ false, false ], [ true, false ] ]

		assert.deepEqual(
			results,
			// Expected:
			[ [ false, true ], [ true, true ], [ false, true ], [ false, false ], [ true, false ] ],
			'Read and seen changes produced "update" events'
		);
	} );

} );
