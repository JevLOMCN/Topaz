QUnit.module( 've.ui.MWAddParameterPage', ve.test.utils.newMwEnvironment() );

QUnit.test( 'Input event handlers', ( assert ) => {
	const transclusion = new ve.dm.MWTransclusionModel(),
		template = new ve.dm.MWTemplateModel( transclusion, {} ),
		parameter = new ve.dm.MWParameterModel( template ),
		page = new ve.ui.MWAddParameterPage( parameter );

	page.togglePlaceholder( true );

	page.paramInputField.setValue( ' ' );
	assert.strictEqual( page.saveButton.isDisabled(), true, 'cannot click' );
	page.onParameterNameSubmitted();
	assert.deepEqual( template.getParameters(), {}, 'empty input is ignored' );
	assert.strictEqual( page.paramInputField.getValue(), ' ', 'bad input is not cleared' );

	page.paramInputField.setValue( ' p1 ' );
	assert.strictEqual( page.saveButton.isDisabled(), false, 'can click' );
	page.onParameterNameSubmitted();
	assert.true( template.hasParameter( 'p1' ), 'input is trimmed and parameter added' );
	assert.strictEqual( page.paramInputField.getValue(), '', 'accepted input is cleared' );

	template.getParameter( 'p1' ).setValue( 'not empty' );
	page.paramInputField.setValue( 'p1' );
	assert.strictEqual( page.saveButton.isDisabled(), true, 'cannot click' );
	page.onParameterNameSubmitted();
	assert.strictEqual( template.getParameter( 'p1' ).getValue(), 'not empty',
		'existing parameter is not replaced' );

	template.getSpec().setTemplateData( { params: { documented: {} } } );
	page.paramInputField.setValue( 'documented' );
	page.onParameterNameSubmitted();
	assert.false( template.hasParameter( 'documented' ), 'documented parameter is not added' );
	assert.strictEqual( page.paramInputField.getValue(), 'documented', 'bad input is not cleared' );
} );

[
	[ '', 0 ],
	[ 'a', 0 ],
	[ 'a=b', '(visualeditor-dialog-transclusion-add-param-error-forbidden-char: =)' ],
	[ 'x|a=b', '(visualeditor-dialog-transclusion-add-param-error-forbidden-char: |)' ],
	[ 'used', '(visualeditor-dialog-transclusion-add-param-error-exists-selected: used, used)' ],
	[ 'unused', '(visualeditor-dialog-transclusion-add-param-error-exists-unselected: unused, unused)' ],
	[ 'usedAlias', '(visualeditor-dialog-transclusion-add-param-error-alias: usedAlias, xLabel)' ],
	[ 'unusedAlias', '(visualeditor-dialog-transclusion-add-param-error-alias: unusedAlias, y)' ],
	[ 'usedAliasNoLabel', '(visualeditor-dialog-transclusion-add-param-error-alias: usedAliasNoLabel, usedAliasNoLabel)' ],
	[ 'usedDeprecated', '(visualeditor-dialog-transclusion-add-param-error-exists-selected: usedDeprecated, usedDeprecated)' ],
	[ 'unusedDeprecated', '(visualeditor-dialog-transclusion-add-param-error-deprecated: unusedDeprecated, unusedDeprecated)' ]
].forEach( ( [ input, expected ] ) =>
	QUnit.test( 'getValidationErrors: ' + input, ( assert ) => {
		const data = {
			target: {},
			params: {
				used: {},
				usedAlias: {},
				usedAliasNoLabel: {},
				usedDeprecated: {}
			}
		};

		const transclusion = new ve.dm.MWTransclusionModel(),
			template = ve.dm.MWTemplateModel.newFromData( transclusion, data ),
			parameter = new ve.dm.MWParameterModel( template ),
			page = new ve.ui.MWAddParameterPage( parameter );

		template.getSpec().setTemplateData( { params: {
				used: {},
				unused: {},
				x: { aliases: [ 'usedAlias' ], label: 'xLabel' },
				y: { aliases: [ 'unusedAlias' ] },
				z: { aliases: [ 'usedAliasNoLabel' ] },
				usedDeprecated: { deprecated: true },
				unusedDeprecated: { deprecated: true }
			} } );
		template.addParameter( parameter );

		const errors = page.getValidationErrors( input );
		assert.strictEqual( errors.length, expected ? 1 : 0 );
		if ( expected ) {
			assert.strictEqual( errors[ 0 ].text(), expected );
		}
	} )
);
