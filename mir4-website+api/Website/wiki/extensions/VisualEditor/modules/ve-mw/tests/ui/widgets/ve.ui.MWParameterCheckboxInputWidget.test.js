QUnit.module( 've.ui.MWParameterCheckboxInputWidget' );

QUnit.test( 'Constructor passes config to parent', ( assert ) => {
	const widget = new ve.ui.MWParameterCheckboxInputWidget( { selected: true } );

	assert.strictEqual( widget.getValue(), '1' );
	assert.strictEqual( widget.isSelected(), true );
} );

[
	[ '1', true, '"1"' ],
	[ '0', false, '"0"' ],
	[ '', false, 'empty string' ],
	[ '2', false, 'unexpected string' ],
	[ true, false, 'unexpected type' ]
].forEach( ( [ value, expected, message ] ) =>
	QUnit.test( `setValue( ${ message } )`, ( assert ) => {
		const widget = new ve.ui.MWParameterCheckboxInputWidget();
		widget.setValue( value );

		assert.strictEqual( widget.isSelected(), expected );
		assert.strictEqual( widget.getValue(), expected ? '1' : '0' );
	} )
);

[
	true,
	false
].forEach( ( value ) =>
	QUnit.test( `setSelected( ${ value } )`, ( assert ) => {
		const widget = new ve.ui.MWParameterCheckboxInputWidget();
		widget.setSelected( value );

		assert.strictEqual( widget.isSelected(), value );
		assert.strictEqual( widget.getValue(), value ? '1' : '0' );
	} )
);
