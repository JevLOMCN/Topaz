QUnit.module( 've.ui.MWTransclusionOutlinePartWidget' );

QUnit.test( 'Constructor', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		part = new ve.dm.MWTransclusionPartModel( transclusion ),
		widget = new ve.ui.MWTransclusionOutlinePartWidget( part, {
			label: 'Example',
			ariaDescriptionUnselected: 'Help when unselected',
			ariaDescriptionSelected: 'Help when selected',
			ariaDescriptionSelectedSingle: 'Help when selected and single'
		} ),
		$ariaElement = widget.$element.find( '[aria-describedby]' );

	assert.strictEqual( widget.getData(), 'part_0' );
	assert.strictEqual(
		widget.$element.find( '.ve-ui-mwTransclusionOutlineButtonWidget .oo-ui-buttonElement-button' ).text(),
		'Example'
	);
	assert.strictEqual( widget.isSelected(), false );
	let $ariaDescription = widget.$element.find( '#' + $ariaElement.attr( 'aria-describedby' ) );
	assert.strictEqual( $ariaDescription.text(), 'Help when unselected' );

	widget.setSelected( true );

	assert.strictEqual( widget.isSelected(), true );
	$ariaDescription = widget.$element.find( '#' + $ariaElement.attr( 'aria-describedby' ) );
	assert.strictEqual( $ariaDescription.text(), 'Help when selected' );
} );
