QUnit.module( 've.ui.MWParameterPage', ve.test.utils.newMwEnvironment );

[
	[ undefined, '', ve.ui.MWLazyMultilineTextInputWidget ],
	[ 'content', '', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'line', '', ve.ui.MWLazyMultilineTextInputWidget ],
	[ 'line', '\n', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'number', '', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'boolean', '', ve.ui.MWLazyMultilineTextInputWidget ],
	[ 'boolean', '0', ve.ui.MWParameterCheckboxInputWidget ],
	[ 'boolean', '1', ve.ui.MWParameterCheckboxInputWidget ],
	[ 'boolean', '2', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'string', '', ve.ui.MWLazyMultilineTextInputWidget ],
	[ 'date', '', ve.ui.MWLazyMultilineTextInputWidget ],
	[ 'unbalanced-wikitext', '', ve.ui.MWLazyMultilineTextInputWidget ],
	[ 'unknown', '', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'url', '', OO.ui.TextInputWidget ],
	[ 'url', 'http://example.com', OO.ui.TextInputWidget ],
	[ 'url', 'BadUrl', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'wiki-page-name', '', mw.widgets.TitleInputWidget ],
	[ 'wiki-page-name', 'GoodTitle', mw.widgets.TitleInputWidget ],
	[ 'wiki-page-name', '[[BadTitle]]', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'wiki-user-name', '', mw.widgets.UserInputWidget ],
	[ 'wiki-user-name', 'GoodTitle', mw.widgets.UserInputWidget ],
	[ 'wiki-user-name', '[[BadTitle]]', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'wiki-file-name', '', mw.widgets.TitleInputWidget ],
	[ 'wiki-file-name', 'GoodTitle', mw.widgets.TitleInputWidget ],
	[ 'wiki-file-name', '[[BadTitle]]', ve.ui.MWLazyMultilineTextInputWidget ],

	[ 'wiki-template-name', '', mw.widgets.TitleInputWidget ],
	[ 'wiki-template-name', 'GoodTitle', mw.widgets.TitleInputWidget ],
	[ 'wiki-template-name', '[[BadTitle]]', ve.ui.MWLazyMultilineTextInputWidget ]
].forEach( ( [ type, value, expected ] ) =>
	QUnit.test( `createValueInput: ${ type }, ${ value }`, ( assert ) => {
		const transclusion = new ve.dm.MWTransclusionModel(),
			template = new ve.dm.MWTemplateModel( transclusion, {} ),
			parameter = new ve.dm.MWParameterModel( template, 'p', value );

		template.getSpec().setTemplateData( { params: { p: { type } } } );

		const page = new ve.ui.MWParameterPage( parameter ),
			input = page.createValueInput();

		assert.strictEqual( input.constructor.name, expected.prototype.constructor.name );
	} )
);

[
	[ undefined, OO.ui.ComboBoxInputWidget ],
	[ 'content', OO.ui.ComboBoxInputWidget ],
	[ 'line', OO.ui.ComboBoxInputWidget ],
	[ 'number', OO.ui.ComboBoxInputWidget ],
	[ 'boolean', ve.ui.MWLazyMultilineTextInputWidget ],
	[ 'string', OO.ui.ComboBoxInputWidget ],
	[ 'date', ve.ui.MWLazyMultilineTextInputWidget ],
	[ 'unbalanced-wikitext', OO.ui.ComboBoxInputWidget ],
	[ 'unknown', OO.ui.ComboBoxInputWidget ],
	[ 'url', OO.ui.TextInputWidget ],
	[ 'wiki-page-name', mw.widgets.TitleInputWidget ],
	[ 'wiki-user-name', mw.widgets.UserInputWidget ],
	[ 'wiki-file-name', mw.widgets.TitleInputWidget ],
	[ 'wiki-template-name', mw.widgets.TitleInputWidget ]
].forEach( ( [ type, expected ] ) =>
	QUnit.test( `suggestedvalues: ${ type }`, ( assert ) => {
		const transclusion = new ve.dm.MWTransclusionModel(),
			template = new ve.dm.MWTemplateModel( transclusion, {} ),
			parameter = new ve.dm.MWParameterModel( template, 'p', '' );

		template.getSpec().setTemplateData( { params: { p: {
			type,
			suggestedvalues: [ 'example' ]
		} } } );

		const page = new ve.ui.MWParameterPage( parameter ),
			input = page.createValueInput();

		assert.strictEqual( input.constructor.name, expected.prototype.constructor.name );
		if ( input instanceof OO.ui.ComboBoxInputWidget ) {
			assert.strictEqual( input.getMenu().getItemCount(), 1 );
			assert.strictEqual( input.getMenu().items[ 0 ].getData(), 'example' );
		}
	} )
);

[
	[
		'', '', false,
		'empty'
	],
	[
		'some value', '', true,
		'not empty'
	],
	[
		'', 'some default', true,
		'empty is meaningful because it is different from the default'
	],
	[
		'some value', 'some default', true,
		'value is different from the default'
	],
	[
		'same', 'same', true,
		'the default is probably not meaningful, but we can not be sure'
	],
	[
		' ', '', true,
		'whitespace is probably not meaningful, but we do not want to make this decision here'
	],
	[
		'', ' ', true,
		'same for the default'
	]
].forEach( ( [ value, defaultValue, expected, message ] ) =>
	QUnit.test( 'containsSomeValue: ' + message, ( assert ) => {
		const transclusion = new ve.dm.MWTransclusionModel(),
			template = new ve.dm.MWTemplateModel( transclusion, {} ),
			parameter = new ve.dm.MWParameterModel( template, 'p', value );

		template.getSpec().setTemplateData( { params: { p: { default: defaultValue } } } );

		const page = new ve.ui.MWParameterPage( parameter );

		assert.strictEqual( page.containsSomeValue(), expected );
	} )
);
