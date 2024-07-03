QUnit.module( 'ext.echo.dm - BundleNotificationItem' );

QUnit.test( 'Constructing the model', function ( assert ) {
	var bundledItems = [
		new mw.echo.dm.NotificationItem( 0, { read: false, seen: false, timestamp: '201601010000' } ),
		new mw.echo.dm.NotificationItem( 1, { read: false, seen: false, timestamp: '201601010100' } ),
		new mw.echo.dm.NotificationItem( 2, { read: false, seen: true, timestamp: '201601010200' } ),
		new mw.echo.dm.NotificationItem( 3, { read: false, seen: true, timestamp: '201601010300' } ),
		new mw.echo.dm.NotificationItem( 4, { read: false, seen: true, timestamp: '201601010400' } )
	];
	var bundle = new mw.echo.dm.BundleNotificationItem(
		100,
		bundledItems,
		{ modelName: 'foo' }
	);

	assert.strictEqual(
		bundle.getCount(),
		5,
		'Bundled items added to internal list'
	);

	assert.strictEqual(
		bundle.getName(),
		'foo',
		'Bundle name stored'
	);

	assert.deepEqual(
		bundle.getAllIds(),
		[ 4, 3, 2, 1, 0 ],
		'All ids present'
	);

	assert.strictEqual(
		bundle.isRead(),
		false,
		'Bundle with all unread items is unread'
	);

	assert.strictEqual(
		bundle.hasUnseen(),
		true,
		'Bundle has unseen items'
	);

	var findItems = bundle.findByIds( [ 1, 4 ] ).map( function ( item ) {
		return item.getId();
	} );
	assert.deepEqual(
		findItems,
		[ 4, 1 ],
		'findByIds fetches correct items in the default sorting order'
	);
} );

QUnit.test( 'Managing a list of items', function ( assert ) {
	var bundledItems = [
		new mw.echo.dm.NotificationItem( 0, { read: false, seen: false, timestamp: '201601010000' } ),
		new mw.echo.dm.NotificationItem( 1, { read: false, seen: false, timestamp: '201601010100' } ),
		new mw.echo.dm.NotificationItem( 2, { read: false, seen: true, timestamp: '201601010200' } ),
		new mw.echo.dm.NotificationItem( 3, { read: false, seen: true, timestamp: '201601010300' } ),
		new mw.echo.dm.NotificationItem( 4, { read: false, seen: true, timestamp: '201601010400' } )
	];
	var bundle = new mw.echo.dm.BundleNotificationItem(
		100,
		bundledItems,
		{
			name: 'foo'
		}
	);

	assert.strictEqual(
		bundle.hasUnseen(),
		true,
		'Bundle has unseen'
	);

	// Mark all items as seen
	bundledItems.forEach( function ( item ) {
		item.toggleSeen( true );
	} );

	assert.strictEqual(
		bundle.hasUnseen(),
		false,
		'Bundle does not have unseen after all items marked as seen'
	);

	assert.strictEqual(
		bundle.isRead(),
		false,
		'Bundle is unread'
	);
	// Mark one item as read
	bundledItems[ 0 ].toggleRead( true );
	assert.strictEqual(
		bundle.isRead(),
		false,
		'Bundle is still unread if it has some unread items'
	);

	// Mark all items as read
	bundledItems.forEach( function ( item ) {
		item.toggleRead( true );
	} );
	assert.strictEqual(
		bundle.isRead(),
		true,
		'Bundle is marked as read if all items are read'
	);
} );
