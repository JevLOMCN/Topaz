QUnit.module( 've.ui.MWTransclusionOutlineWidget' );

QUnit.test( 'Constructor', ( assert ) => {
	const widget = new ve.ui.MWTransclusionOutlineWidget();

	// eslint-disable-next-line no-jquery/no-class-state
	assert.true( widget.$element.hasClass( 've-ui-mwTransclusionOutlineWidget' ) );
	assert.deepEqual( widget.partWidgets, {} );
} );

QUnit.test( 'Supports all ve.dm.MWTransclusionPartModel subclasses', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		widget = new ve.ui.MWTransclusionOutlineWidget();

	widget.onReplacePart( null, new ve.dm.MWTemplateModel( transclusion, { wt: '' } ) );
	widget.onReplacePart( null, new ve.dm.MWTemplatePlaceholderModel( transclusion ) );
	widget.onReplacePart( null, new ve.dm.MWTransclusionContentModel( transclusion ) );

	assert.true( widget.partWidgets.part_0 instanceof ve.ui.MWTransclusionOutlineTemplateWidget );
	assert.true( widget.partWidgets.part_1 instanceof ve.ui.MWTransclusionOutlinePlaceholderWidget );
	assert.true( widget.partWidgets.part_2 instanceof ve.ui.MWTransclusionOutlineWikitextWidget );
} );

QUnit.test( 'Basic functionality', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		part0 = new ve.dm.MWTransclusionContentModel( transclusion ),
		part1 = new ve.dm.MWTransclusionContentModel( transclusion ),
		widget = new ve.ui.MWTransclusionOutlineWidget();

	widget.onReplacePart();
	assert.deepEqual( widget.partWidgets, {} );

	widget.onReplacePart( null, part0 );
	widget.onReplacePart( null, part1 );
	assert.deepEqual( Object.keys( widget.partWidgets ), [ 'part_0', 'part_1' ] );

	widget.onReplacePart( part0 );
	assert.deepEqual( Object.keys( widget.partWidgets ), [ 'part_1' ] );

	widget.clear();
	assert.deepEqual( widget.partWidgets, {} );
} );

QUnit.test( 'Adding and moving parts to specific positions', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		part0 = new ve.dm.MWTransclusionContentModel( transclusion ),
		part1 = new ve.dm.MWTransclusionContentModel( transclusion ),
		part2 = new ve.dm.MWTransclusionContentModel( transclusion ),
		widget = new ve.ui.MWTransclusionOutlineWidget();

	// This adds the parts at an invalid position, at the start, and in the middle
	widget.onReplacePart( null, part0, 666 );
	widget.onReplacePart( null, part1, 0 );
	widget.onReplacePart( null, part2, 1 );

	// Note this is just a map and doesn't reflect the order in the UI
	assert.deepEqual( Object.keys( widget.partWidgets ), [ 'part_0', 'part_1', 'part_2' ] );

	const $items = widget.$element.children();
	assert.true( $items.eq( 0 ).is( widget.partWidgets.part_1.$element ) );
	assert.true( $items.eq( 1 ).is( widget.partWidgets.part_2.$element ) );
	assert.true( $items.eq( 2 ).is( widget.partWidgets.part_0.$element ) );
} );

[
	[ null, null ],
	[ '', null ],
	[ 'part_0', null ],
	[ 'part_0/', '' ],
	[ 'part_0/foo', 'foo' ],
	[ 'part_1/foo', null ],
	[ 'part_0/foo/bar', 'foo/bar' ]
].forEach( ( [ pageName, expected ] ) =>
	QUnit.test( 'setSelectionByPageName: ' + pageName, ( assert ) => {
		const transclusion = new ve.dm.MWTransclusionModel(),
			template = new ve.dm.MWTemplateModel( transclusion, { wt: '' } ),
			partWidget = new ve.ui.MWTransclusionOutlineTemplateWidget( template ),
			widget = new ve.ui.MWTransclusionOutlineWidget();

		// eslint-disable-next-line camelcase
		widget.partWidgets.part_0 = partWidget;

		let actual = null;
		partWidget.setParameter = ( paramName ) => {
			actual = paramName;
		};

		widget.setSelectionByPageName( pageName );
		assert.strictEqual( actual, expected );
	} )
);
