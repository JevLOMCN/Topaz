QUnit.module( 'ext.echo.dm - SourcePagesModel', function () {
	// Mock partial API response we feed into the model
	var sources = {
		local: {
			pages: [
				{
					ns: 2,
					title: 'User:Admin',
					unprefixed: 'Admin',
					pages: [
						'User:Admin',
						'User talk:Admin',
						null
					],
					count: 24
				},
				{
					ns: 2,
					title: 'User:RandomUser',
					unprefixed: 'RandomUser',
					pages: [
						'User:RandomUser',
						'User talk:RandomUser'
					],
					count: 6
				},
				{
					ns: 0,
					title: 'Moai',
					unprefixed: 'Moai',
					pages: [
						'Moai',
						'Talk:Moai'
					],
					count: 3
				}
			],
			totalCount: 33,
			source: {
				title: 'LocalWiki',
				url: 'http://dev.wiki.local.wmftest.net:8080/w/api.php',
				base: 'http://dev.wiki.local.wmftest.net:8080/wiki/$1'
			}
		},
		hewiki: {
			pages: [
				{
					ns: 2,
					title: 'Foo',
					unprefixed: 'Foo',
					pages: [
						'Foo',
						'Talk:Foo',
						null
					],
					count: 10
				},
				{
					ns: 0,
					title: 'User:Bar',
					unprefixed: 'Bar',
					pages: [
						'User:Bar',
						'User talk:Bar'
					],
					count: 5
				}
			],
			totalCount: 15,
			source: {
				title: 'Hebrew Wikipedia',
				url: 'http://he.wiki.local.wmftest.net:8080/w/api.php',
				base: 'http://he.wiki.local.wmftest.net:8080/wiki/$1'
			}
		}
	};

	QUnit.test( 'Creating source-page map', function ( assert ) {
		var model = new mw.echo.dm.SourcePagesModel();

		model.setAllSources( sources );

		assert.strictEqual(
			model.getCurrentSource(),
			'local',
			'Default source is local'
		);
		assert.strictEqual(
			model.getCurrentPage(),
			null,
			'Default page is null'
		);
		assert.deepEqual(
			model.getSourcesArray(),
			[ 'local', 'hewiki' ],
			'Source array includes all sources'
		);
		assert.strictEqual(
			model.getSourceTitle( 'hewiki' ),
			'Hebrew Wikipedia',
			'Source title'
		);
		assert.strictEqual(
			model.getSourceTotalCount( 'hewiki' ),
			15,
			'Source total count'
		);
		assert.deepEqual(
			model.getSourcePages( 'local' ),
			{
				Moai: {
					count: 3,
					ns: 0,
					pages: [
						'Moai',
						'Talk:Moai'
					],
					title: 'Moai',
					unprefixed: 'Moai'
				},
				'User:Admin': {
					count: 24,
					ns: 2,
					pages: [
						'User:Admin',
						'User talk:Admin',
						null
					],
					title: 'User:Admin',
					unprefixed: 'Admin'
				},
				'User:RandomUser': {
					count: 6,
					ns: 2,
					pages: [
						'User:RandomUser',
						'User talk:RandomUser'
					],
					title: 'User:RandomUser',
					unprefixed: 'RandomUser'
				}
			},
			'Outputting source pages'
		);
		assert.deepEqual(
			model.getGroupedPagesForTitle( 'local', 'User:Admin' ),
			[
				'User:Admin',
				'User talk:Admin',
				null
			],
			'Grouped pages per title'
		);

		// Change source
		model.setCurrentSourcePage( 'hewiki', 'User:Bar' );

		assert.strictEqual(
			model.getCurrentSource(),
			'hewiki',
			'Source changed successfully'
		);
		assert.strictEqual(
			model.getCurrentPage(),
			'User:Bar',
			'Page changed successfully'
		);

	} );
} );
