QUnit.module( 've.ui.MWTransclusionOutlineTemplateWidget' );

QUnit.test( 'Constructor', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		template = ve.dm.MWTemplateModel.newFromData( transclusion, {
			target: { wt: 'Example' },
			params: {
				1: {},
				2: {},
				3: {},
				4: {},
				// Representation of a parameter placeholder in the model, should be skipped
				'': {}
			}
		} );
	template.getSpec().setTemplateData( { params: {
		// A deprecated parameter must be shown as long as it's used
		4: { deprecated: true },
		// An unused deprecated parameter should never be shown
		5: { deprecated: true },
		6: {}
	} } );
	const widget = new ve.ui.MWTransclusionOutlineTemplateWidget( template ),
		// We need to skip .getItems() for this test because it makes a copy
		parameters = widget.parameterList.items;

	assert.strictEqual( widget.getData(), 'part_0' );
	assert.strictEqual(
		widget.$element.find( '.ve-ui-mwTransclusionOutlineButtonWidget .oo-ui-buttonElement-button' ).text(),
		'Example'
	);
	assert.true( widget.searchWidget.isVisible() );
	assert.true( widget.toggleUnusedWidget.isVisible() );
	assert.false( widget.infoWidget.isVisible() );
	// Note that documented parameters go first
	assert.deepEqual( parameters.map( ( item ) => item.data ), [ '4', '1', '2', '3' ] );

	widget.toggleUnusedWidget.emit( 'click' );

	assert.deepEqual( parameters.map( ( item ) => item.data ), [ '4', '6', '1', '2', '3' ] );

	widget.searchWidget.setValue( 'can not find anything' );

	assert.false( widget.toggleUnusedWidget.isVisible() );
	assert.true( widget.infoWidget.isVisible() );
	assert.strictEqual( parameters.filter( ( p ) => p.isVisible() ).length, 0 );
} );

QUnit.test( 'findCanonicalPosition()', ( assert ) => {
	function assertOrder( w, expected ) {
		assert.deepEqual( w.parameterList.items.map( ( item ) => item.data ), expected );
	}

	const transclusion = new ve.dm.MWTransclusionModel(),
		template = ve.dm.MWTemplateModel.newFromData( transclusion, {
			target: { wt: '' },
			params: { b: {}, e: {} }
		} );
	// Skip .addPart(), the TemplateData API, specCache and everything related
	transclusion.parts.push( template );
	template.getSpec().setTemplateData( {
		params: {
			e: { deprecated: true },
			h: { deprecated: true },
			g: {}
		},
		paramOrder: [ 'g', 'h', 'e' ]
	} );
	const widget = new ve.ui.MWTransclusionOutlineTemplateWidget( template );

	// Expected order on construction time is:
	// - Documented params in paramOrder (g, h, e), excluding unused deprected params (- h)
	// - Undocumented params currently used in the template (+ b)
	assertOrder( widget, [ 'g', 'e', 'b' ] );

	let insertAt = widget.findCanonicalPosition( 'h' );
	// Most minimal mock instead of an actual ve.ui.MWTransclusionOutlineParameterWidget
	widget.parameterList.addItems( [ new OO.ui.Widget( { data: 'h' } ) ], insertAt );
	// Deprecated param appears at its canonical position via paramOrder
	assert.strictEqual( insertAt, 1 );
	assertOrder( widget, [ 'g', 'h', 'e', 'b' ] );

	const newParam = new ve.dm.MWParameterModel( template, 'a1' );
	// This fires an "add" event the widget listens to, i.e. this covers onAddParameter() as well
	template.addParameter( newParam );
	assertOrder( widget, [ 'g', 'h', 'e', 'a1', 'b' ] );
	// Removing the param doesn't remove it's checkbox item from the widget
	template.removeParameter( newParam );
	assertOrder( widget, [ 'g', 'h', 'e', 'a1', 'b' ] );

	// This is effectively the same as above: teach the spec a new param without adding it to the
	// template. This doesn't fire events, which allows us to test the private method in isolation.
	template.getSpec().seenParameterNames.a2 = true;
	insertAt = widget.findCanonicalPosition( 'a2' );
	// Most minimal mock instead of an actual ve.ui.MWTransclusionOutlineParameterWidget
	widget.parameterList.addItems( [ new OO.ui.Widget( { data: 'a2' } ) ], insertAt );
	assert.strictEqual( insertAt, 4 );
	assertOrder( widget, [ 'g', 'h', 'e', 'a1', 'a2', 'b' ] );
} );

QUnit.test( 'filterParameters() when it cannot find anything', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		template = ve.dm.MWTemplateModel.newFromData( transclusion, {
			target: { wt: '' },
			params: { 1: {}, 2: {}, 3: {}, 4: {} }
		} ),
		widget = new ve.ui.MWTransclusionOutlineTemplateWidget( template );
	let eventsFired = 0;
	widget.connect( this, {
		filterParametersById: ( visibility ) => {
			assert.deepEqual( visibility, {
				'part_0/1': false,
				'part_0/2': false,
				'part_0/3': false,
				'part_0/4': false
			} );
			eventsFired++;
		}
	} );
	widget.filterParameters( 'b' );
	assert.true( widget.infoWidget.isVisible() );
	assert.strictEqual( eventsFired, 1 );
} );

QUnit.test( 'filterParameters() considers everything from the spec', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		template = ve.dm.MWTemplateModel.newFromData( transclusion, {
			target: { wt: '' },
			params: { a: {}, b: {}, c: {}, d: {}, e: {} }
		} ),
		widget = new ve.ui.MWTransclusionOutlineTemplateWidget( template );

	template.getSpec().setTemplateData( {
		params: {
			c: { label: 'Contains a' },
			d: { description: 'Contains a' },
			e: { aliases: [ 'Contains a' ] },
			f: { label: 'Also contains a, but is not used in the template' }
		}
	} );

	let eventsFired = 0;
	widget.connect( this, {
		filterParametersById: ( visibility ) => {
			assert.deepEqual( visibility, {
				'part_0/a': true,
				'part_0/b': false,
				'part_0/c': true,
				'part_0/d': true,
				'part_0/e': true
			} );
			eventsFired++;
		}
	} );

	assert.true( widget.searchWidget.isVisible() );
	widget.filterParameters( ' A ' );
	assert.false( widget.infoWidget.isVisible() );
	assert.strictEqual( eventsFired, 1 );
} );
