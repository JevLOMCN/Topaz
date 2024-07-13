QUnit.module( 'ext.echo.dm - PaginationModel' );

QUnit.test.each( 'Constructing the model', {
	'Empty config': {
		config: {},
		expected: {}
	},
	'Overriding defaults': {
		config: {
			pageNext: 'continueValNext|123',
			itemsPerPage: 10
		},
		expected: {
			getNextPageContinue: 'continueValNext|123',
			hasNextPage: true,
			getItemsPerPage: 10,
			getCurrentPageItemCount: 10
		}
	}
}, function ( assert, data ) {
	var defaultValues = {
		getPageContinue: undefined,
		getCurrPageIndex: 0,
		getPrevPageContinue: '',
		getCurrPageContinue: '',
		getNextPageContinue: '',
		hasPrevPage: false,
		hasNextPage: false,
		getCurrentPageItemCount: 25,
		getItemsPerPage: 25
	};
	var expected = $.extend( true, {}, defaultValues, data.expected );

	var model = new mw.echo.dm.PaginationModel( data.config );

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

QUnit.test( 'Emitting update event', function ( assert ) {
	var results = [];
	var model = new mw.echo.dm.PaginationModel();

	// Listen to update event
	model.on( 'update', function () {
		results.push( [
			model.getCurrPageIndex(),
			model.hasNextPage()
		] );
	} );

	// Trigger events

	// Set up initial pages (first page is 0)
	model.setPageContinue( 1, 'page2|2' ); // [ [ 0, true ] ]
	model.setPageContinue( 2, 'page3|3' ); // [ [ 0, true ], [ 0, true ] ]
	model.setPageContinue( 3, 'page4|4' ); // [ [ 0, true ], [ 0, true ], [ 0, true ] ]

	model.forwards(); // [ [ 0, true ], [ 0, true ], [ 0, true ], [ 1, true ] ]
	model.forwards(); // [ [ 0, true ], [ 0, true ], [ 0, true ], [ 1, true ], [ 2, true ] ]
	model.forwards(); // [ [ 0, true ], [ 0, true ], [ 0, true ], [ 1, true ], [ 2, true ], [ 3, false ] ]
	model.backwards(); // [ [ 0, true ], [ 0, true ], [ 0, true ], [ 1, true ], [ 2, true ], [ 3, false ], [ 2, true ] ]
	model.setCurrentPageItemCount(); // [ [ 0, true ], [ 0, true ], [ 0, true ], [ 1, true ], [ 2, true ], [ 3, false ], [ 2, true ], [ 2, true ] ]
	model.reset(); // [ [ 0, true ], [ 0, true ], [ 0, true ], [ 1, true ], [ 2, true ], [ 3, false ], [ 2, true ], [ 2, true ], [ 0, false ] ]

	assert.deepEqual(
		// Actual
		results,
		// Expected:
		[ [ 0, true ], [ 0, true ], [ 0, true ], [ 1, true ], [ 2, true ], [ 3, false ], [ 2, true ], [ 2, true ], [ 0, false ] ],
		// Message
		'Update events emitted'
	);
} );
