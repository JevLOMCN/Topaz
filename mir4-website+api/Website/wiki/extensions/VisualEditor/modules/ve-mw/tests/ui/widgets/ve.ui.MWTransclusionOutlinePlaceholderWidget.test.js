QUnit.module( 've.ui.MWTransclusionOutlinePlaceholderWidget' );

QUnit.test( 'Constructor', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		placeholder = new ve.dm.MWTemplatePlaceholderModel( transclusion );
	let widget = new ve.ui.MWTransclusionOutlinePlaceholderWidget( placeholder );

	assert.strictEqual( widget.getData(), 'part_0' );
	assert.strictEqual(
		widget.$element.find( '.ve-ui-mwTransclusionOutlineButtonWidget' ).text(),
		'visualeditor-dialog-transclusion-add-template',
		'Outline item says "Add template" by default'
	);

	// Bypass the asynchronous .addPart() and the API request it does
	transclusion.parts.push( placeholder );
	widget = new ve.ui.MWTransclusionOutlinePlaceholderWidget( placeholder );

	assert.strictEqual(
		widget.$element.find( '.ve-ui-mwTransclusionOutlineButtonWidget' ).text(),
		'visualeditor-dialog-transclusion-template-search',
		'Outline item says "Template search" when this placeholder is the only part'
	);
} );
