{
	const toggleCirrusSearchLookup = ( enabled ) =>
		mw.config.set( 'wgVisualEditorConfig', ve.extendObject( {}, mw.config.get( 'wgVisualEditorConfig' ), {
			cirrusSearchLookup: enabled !== false
		} ) );

	const makeFakeApi = () => {
		return {
			defaults: { parameters: {} },
			get: () => {
				return {
					abort: { bind: () => {} },
					then: () => {}
				};
			}
		};
	};

	QUnit.module( 've.ui.MWTemplateTitleInputWidget', ve.test.utils.newMwEnvironment( {
		// Config will be reset by newMwEnvironment's teardown
		beforeEach: toggleCirrusSearchLookup
	} ) );

	QUnit.test( 'default prefixsearch', ( assert ) => {
		toggleCirrusSearchLookup( false );

		const widget = new ve.ui.MWTemplateTitleInputWidget(),
			query = 'a',
			apiParams = widget.getApiParams( query );

		assert.deepEqual( apiParams, {
			action: 'query',
			generator: 'prefixsearch',
			gpslimit: 10,
			gpsnamespace: 10,
			gpssearch: 'a',
			ppprop: 'disambiguation',
			prop: [ 'info', 'pageprops' ],
			redirects: true
		} );
	} );

	QUnit.test( 'CirrusSearch: all API parameters', ( assert ) => {
		const widget = new ve.ui.MWTemplateTitleInputWidget(),
			query = 'a',
			apiParams = widget.getApiParams( query );

		assert.deepEqual( apiParams, {
			action: 'query',
			generator: 'search',
			gsrlimit: 10,
			gsrnamespace: 10,
			gsrprop: 'redirecttitle',
			gsrsearch: 'a*',
			ppprop: 'disambiguation',
			prop: [ 'info', 'pageprops' ],
			redirects: true
		} );
	} );

	QUnit.test( 'CirrusSearch: showRedirectTargets disabled', ( assert ) => {
		const widget = new ve.ui.MWTemplateTitleInputWidget( { showRedirectTargets: false } ),
			apiParams = widget.getApiParams();

		assert.false( 'gsrprop' in apiParams );
	} );

	QUnit.test( 'CirrusSearch: prefixsearch behavior', ( assert ) => {
		const widget = new ve.ui.MWTemplateTitleInputWidget();

		[
			{
				query: 'a',
				expected: 'a*'
			},
			{
				query: 'a ',
				expected: 'a '
			},
			{
				query: 'ü',
				expected: 'ü*'
			},
			{
				query: '3',
				expected: '3*'
			},
			{
				query: '!!',
				expected: '!!'
			},
			{
				query: 'Foo:',
				expected: 'Foo:'
			},
			{
				query: 'Foo:Bar',
				expected: 'Foo:Bar*'
			},
			{
				query: 'foo_',
				expected: 'foo_'
			},
			{
				query: 'foo-',
				expected: 'foo-'
			},
			{
				query: 'foo+',
				expected: 'foo+'
			},
			{
				query: 'foo/',
				expected: 'foo/'
			},
			{
				query: 'foo~',
				expected: 'foo~'
			},
			{
				query: 'foo*',
				expected: 'foo*'
			},
			{
				query: '(foo)',
				expected: '(foo)'
			},
			{
				query: '[foo]',
				expected: '[foo]'
			},
			{
				query: '{foo}',
				expected: '{foo}'
			},
			{
				query: '"foo"',
				expected: '"foo"'
			},
			{
				query: 'foß',
				expected: 'foß*'
			},
			{
				query: '中文字',
				expected: '中文字*'
			},
			{
				query: 'zhōngwénzì',
				expected: 'zhōngwénzì*'
			}
		].forEach( ( data ) => {
			const apiParams = widget.getApiParams( data.query );

			assert.strictEqual(
				apiParams.gsrsearch,
				data.expected,
				'Searching for ' + data.query
			);
		} );
	} );

	QUnit.test( 'CirrusSearch with prefixsearch fallback', function ( assert ) {
		const done = assert.async(),
			api = makeFakeApi();
		this.sandbox.stub( api, 'get' )
			.onFirstCall().returns( ve.createDeferred()
				.resolve( { query: {
					pages: [
						{ pageid: 101, title: 'B' },
						{ pageid: 102, title: 'A' },
						// Documentation subpage, expected to be stripped
						{ pageid: 103, title: 'A/(templatedata-doc-subpage)' }
					],
					redirects: [
						// Alternative source for indexes, expected to be copied to the pages array
						{ from: '', to: 'B', index: 1 },
						{ from: '', to: 'A', index: 0 }
					]
				} } )
				.promise( { abort: function () {} } )
			)
			.onSecondCall().returns( ve.createDeferred()
				.resolve( { query: { pages: [
					// Duplicate found by CirrusSearch (above) and prefixsearch
					{ pageid: 102, title: 'A', index: 2 },
					// New prefixsearch matches, expected to be prepended, in order of relevance
					{ pageid: 201, title: 'D', index: 1 },
					{ pageid: 202, title: 'C', index: 0 }
				] } } )
			)
			.onThirdCall().returns( ve.createDeferred()
				.resolve( /* we can skip the templatedata request for this test */ )
			);

		const widget = new ve.ui.MWTemplateTitleInputWidget( { api, showDescriptions: true } );
		widget.setValue( 'something' );
		widget.getLookupRequest()
			.done( ( response ) => {
				assert.deepEqual( response.query.pages, [
					{ pageid: 202, title: 'C', index: -10 },
					{ pageid: 201, title: 'D', index: -9 },
					{ pageid: 102, title: 'A', index: 0 },
					{ pageid: 101, title: 'B', index: 1 }
				] );
			} )
			.always( () => done() );
	} );

	QUnit.test( 'CirrusSearch: redirect is forwarded to the TitleOptionWidget', ( assert ) => {
		const widget = new ve.ui.MWTemplateTitleInputWidget(),
			originalData = { redirecttitle: 'Template:From' },
			data = widget.getOptionWidgetData( 'Template:To', { originalData } );

		assert.strictEqual( data.redirecttitle, 'Template:From' );
	} );

	QUnit.test( 'CirrusSearch: redirect appears in the description', ( assert ) => {
		const widget = new ve.ui.MWTemplateTitleInputWidget();

		let option = widget.createOptionWidget( { redirecttitle: 'Template:From' } );
		assert.strictEqual(
			option.$element.find( '.ve-ui-mwTemplateTitleInputWidget-redirectedfrom' ).text(),
			'(redirectedfrom: From)'
		);

		widget.relative = false;
		option = widget.createOptionWidget( { redirecttitle: 'Template:From' } );
		assert.strictEqual(
			option.$element.find( '.ve-ui-mwTemplateTitleInputWidget-redirectedfrom' ).text(),
			'(redirectedfrom: Template:From)'
		);
	} );
}
