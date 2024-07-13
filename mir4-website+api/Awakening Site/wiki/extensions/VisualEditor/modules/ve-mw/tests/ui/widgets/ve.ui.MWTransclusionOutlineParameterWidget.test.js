QUnit.module( 've.ui.MWTransclusionOutlineParameterWidget' );

QUnit.test( 'interprets param with no attributes', ( assert ) => {
	const widget = new ve.ui.MWTransclusionOutlineParameterWidget( {} );

	assert.false( widget.isSelected() );
	assert.false( widget.checkbox.isSelected() );
	assert.false( widget.checkbox.isDisabled() );
	assert.strictEqual( widget.checkbox.getTitle(), null );

	widget.setSelected( true );
	assert.true( widget.isSelected(), 'can select item' );
	assert.true( widget.checkbox.isSelected(), 'can select checkbox' );
} );

QUnit.test( 'interprets required param', ( assert ) => {
	const widget = new ve.ui.MWTransclusionOutlineParameterWidget( { required: true } );

	assert.true( widget.isSelected() );
	assert.true( widget.checkbox.isSelected() );
	assert.true( widget.checkbox.isDisabled() );
	assert.strictEqual(
		widget.checkbox.getTitle(),
		'visualeditor-dialog-transclusion-required-parameter'
	);

	widget.setSelected( false );
	assert.true( widget.isSelected(), 'cannot unselect required item' );
	assert.true( widget.checkbox.isSelected(), 'cannot unselect required checkbox' );
} );

QUnit.test( 'interprets selected param', ( assert ) => {
	const widget = new ve.ui.MWTransclusionOutlineParameterWidget( { selected: true } );

	assert.true( widget.isSelected() );
	assert.true( widget.checkbox.isSelected() );
	assert.false( widget.checkbox.isDisabled() );
	assert.strictEqual( widget.checkbox.getTitle(), null );

	widget.setSelected( false );
	assert.false( widget.isSelected(), 'can unselect item' );
	assert.false( widget.checkbox.isSelected(), 'can unselect checkbox' );
} );
