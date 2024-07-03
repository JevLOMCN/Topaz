/**
 * TemplateData Generator GUI Unit Tests
 */
'use strict';

var finalJsonParams, finalJson,
	resultDescCurrLang, resultDescMockLang, resultDescBothLang, currLanguage, originalWikitext,
	DataModule = require( 'ext.templateDataGenerator.data' ),
	Model = DataModule.Model,
	SourceHandler = DataModule.SourceHandler;

QUnit.module( 'ext.templateData', QUnit.newMwEnvironment() );

resultDescCurrLang = {};
resultDescMockLang = {};
resultDescBothLang = {};
currLanguage = mw.config.get( 'wgContentLanguage' ) || 'en';
originalWikitext = 'Some text here that is not templatedata information.' +
	'<templatedata>' +
	'{' +
		'"description": {\n' +
			'"' + currLanguage + '": "Label unsigned comments in a conversation.",\n' +
			'"blah": "Template description in some blah language."\n' +
		'},' +
		'"params": {' +
			'"user": {' +
				'"label": "Username",' +
				'"type": "wiki-user-name",' +
				'"required": true,' +
				'"description": "User name of person who forgot to sign their comment.",' +
				'"aliases": ["1"]' +
			'},' +
			'"date": {' +
				'"label": "Date",' +
				'"description": {' +
					'"' + currLanguage + '": "Timestamp of when the comment was posted, in YYYY-MM-DD format."' +
				'},' +
				'"aliases": ["2"],' +
				'"autovalue": "{{subst:CURRENTMONTHNAME}}",' +
				'"suggested": true' +
			'},' +
			'"year": {' +
				'"label": "Year",' +
				'"type": "number"' +
			'},' +
			'"month": {' +
				'"label": "Month",' +
				'"inherits": "year"' +
			'},' +
			'"day": {' +
				'"label": "Day",' +
				'"inherits": "year"' +
			'},' +
			'"comment": {' +
				'"required": false' +
			'}' +
		'},' +
		'"sets": [' +
			'{' +
				'"label": "Date",' +
				'"params": ["year", "month", "day"]' +
			'}' +
		']' +
	'}' +
	'</templatedata>' +
	'Trailing text at the end.';

// Prepare description language objects
resultDescCurrLang[ currLanguage ] = 'Some string here in ' + currLanguage + ' language.';
resultDescMockLang.blah = 'Some string here in blah language.';
resultDescBothLang = $.extend( {}, resultDescCurrLang, resultDescMockLang );
finalJsonParams = {
	user: {
		label: 'Username',
		type: 'wiki-user-name',
		required: true,
		description: 'User name of person who forgot to sign their comment.',
		aliases: [ '1' ]
	},
	date: {
		label: 'Date',
		description: {
			// currLanguage goes here
		},
		aliases: [ '2' ],
		autovalue: '{{subst:CURRENTMONTHNAME}}',
		suggested: true,
		type: undefined
	},
	year: {
		label: 'Year',
		type: 'number'
	},
	month: {
		label: 'Month',
		inherits: 'year',
		type: undefined
	},
	comment: {
		required: false,
		type: 'wiki-page-name'
	},
	newParam1: {
		description: {
			blah: 'Some string here in blah language.'
		},
		required: true,
		type: undefined
	},
	newParam2: {
		description: undefined,
		type: undefined
	},
	newParam3: {
		description: 'Some string here in ' + currLanguage + ' language.',
		deprecated: 'This is deprecated.',
		type: undefined
	},
	newParam4: {
		description: {
			// currLanguage goes here
			blah: resultDescBothLang.blah
		},
		type: undefined
	},
	newParam5: {
		type: 'wiki-page-name'
	}
};
finalJsonParams.date.description[ currLanguage ] = 'Timestamp of when the comment was posted, in YYYY-MM-DD format.';
finalJsonParams.newParam1.description[ currLanguage ] = 'Some string here in ' + currLanguage + ' language.';
finalJsonParams.newParam4.description[ currLanguage ] = resultDescBothLang[ currLanguage ];

finalJson = {
	description: {
		blah: 'Template description in some blah language.'
	},
	params: finalJsonParams,
	sets: [
		{
			label: 'Date',
			params: [
				'year',
				'month',
				'day'
			]
		}
	],
	format: 'inline'
};
finalJson.description[ currLanguage ] = 'Label unsigned comments in a conversation.';

// Test validation tools
QUnit.test( 'Validation tools', function ( assert ) {
	var i, testVars,
		tests = {
			compare: [
				{
					obj1: null,
					obj2: undefined,
					result: false,
					msg: 'Compare: null vs undefined'
				},
				{
					obj1: 'string',
					obj2: undefined,
					result: false,
					msg: 'Compare: string vs undefined'
				},
				{
					obj1: undefined,
					obj2: undefined,
					result: true,
					msg: 'Compare: undefined vs undefined'
				},
				{
					obj1: null,
					obj2: null,
					result: true,
					msg: 'Compare: null vs null'
				},
				{
					obj1: 'A proper string.',
					obj2: 'A proper string.',
					result: true,
					msg: 'Compare: strings'
				},
				{
					obj1: true,
					obj2: true,
					result: true,
					msg: 'Compare: booleans'
				},
				{
					obj1: { 1: 'string', 2: 'another', 4: 'and another' },
					obj2: { 1: 'string', 2: 'another', 4: 'and another' },
					result: true,
					allowSubset: true,
					msg: 'Compare: plain object full equality'
				},
				{
					obj1: { 1: 'string', 2: 'another', 4: 'and another' },
					obj2: { 1: 'another', 2: 'and another', 4: 'string' },
					result: false,
					allowSubset: true,
					msg: 'Compare: plain object full inequality'
				},
				{
					obj1: { 1: 'string', 2: 'another', 4: 'and another' },
					obj2: { 4: 'and another' },
					result: true,
					allowSubset: true,
					msg: 'Compare: plain object subset equality'
				},
				{
					obj1: [ 'val1', 'val2', 'val3' ],
					obj2: [ 'val1', 'val2', 'val3' ],
					result: true,
					msg: 'Compare: arrays'
				},
				{
					obj1: [ 'val1', 'val2', 'val3' ],
					obj2: [ 'val1' ],
					result: true,
					allowSubset: true,
					msg: 'Compare: array subset: true'
				},
				{
					obj1: [ 'val1', 'val2', 'val3' ],
					obj2: [ 'val1' ],
					result: false,
					allowSubset: false,
					msg: 'Compare: array subset: false'
				},
				{
					obj1: {
						param1: {
							type: 'unknown',
							aliases: [ 'alias2', 'alias1', 'alias3' ],
							description: 'Some description',
							required: true,
							suggested: false
						},
						param2: {
							required: true
						}
					},
					obj2: {
						param1: {
							type: 'unknown',
							aliases: [ 'alias2', 'alias1', 'alias3' ],
							description: 'Some description',
							required: true,
							suggested: false
						},
						param2: {
							required: true
						}
					},
					result: true,
					allowSubset: true,
					msg: 'Compare: complex objects'
				},
				{
					obj1: {
						param1: {
							type: 'unknown',
							aliases: [ 'alias1', 'alias2', 'alias3' ],
							description: 'Some description',
							required: true,
							suggested: false
						},
						param2: {
							required: true
						}
					},
					obj2: {
						param1: {
							aliases: [ 'alias1', 'alias2', 'alias3' ],
							suggested: false
						}
					},
					result: true,
					allowSubset: true,
					msg: 'Compare: complex objects subset'
				}
			],
			splitAndTrimArray: [
				{
					string: 'str1 , str2 ',
					delim: ',',
					result: [ 'str1', 'str2' ],
					msg: 'splitAndTrimArray: split and trim'
				},
				{
					string: 'str1, str2, , , , str3',
					delim: ',',
					result: [ 'str1', 'str2', 'str3' ],
					msg: 'splitAndTrimArray: remove empty values'
				},
				{
					string: 'str1|str2|str3',
					delim: '|',
					result: [ 'str1', 'str2', 'str3' ],
					msg: 'splitAndTrimArray: different delimeter'
				}
			],
			arrayUnionWithoutEmpty: [
				{
					arrays: [ [ 'en', 'he', '' ], [ 'he', 'de', '' ], [ 'en', 'de' ] ],
					result: [ 'en', 'he', 'de' ],
					msg: 'arrayUnionWithoutEmpty: Remove duplications'
				},
				{
					arrays: [ [ 'en', '', '' ], [ 'he', '', '' ], [ 'de', '' ] ],
					result: [ 'en', 'he', 'de' ],
					msg: 'arrayUnionWithoutEmpty: Remove empty values'
				}
			],
			props: {
				all: [
					'name',
					'aliases',
					'label',
					'description',
					'example',
					'type',
					'suggestedvalues',
					'default',
					'autovalue',
					'status',
					'deprecated',
					'deprecatedValue',
					'required',
					'suggested'
				],
				language: [
					'label',
					'description',
					'example',
					'default'
				]
			}
		};

	// Compare
	for ( i = 0; i < tests.compare.length; i++ ) {
		testVars = tests.compare[ i ];
		assert.strictEqual(
			Model.static.compare( testVars.obj1, testVars.obj2, testVars.allowSubset ),
			testVars.result,
			testVars.msg
		);
	}

	// Split and trim
	for ( i = 0; i < tests.splitAndTrimArray.length; i++ ) {
		testVars = tests.splitAndTrimArray[ i ];
		assert.deepEqual(
			Model.static.splitAndTrimArray( testVars.string, testVars.delim ),
			testVars.result,
			testVars.msg
		);
	}

	// arrayUnionWithoutEmpty
	for ( i = 0; i < tests.arrayUnionWithoutEmpty.length; i++ ) {
		testVars = tests.arrayUnionWithoutEmpty[ i ];
		assert.deepEqual(
			Model.static.arrayUnionWithoutEmpty.apply( testVars, testVars.arrays ),
			testVars.result,
			testVars.msg
		);
	}

	// Props
	assert.deepEqual(
		Model.static.getAllProperties( false ),
		tests.props.all,
		'All properties'
	);
	assert.deepEqual(
		Model.static.getPropertiesWithLanguage(),
		tests.props.language,
		'Language properties'
	);
} );

// Test model load
QUnit.test( 'TemplateData model', function ( assert ) {
	var i, testVars,
		sourceHandler = new SourceHandler(),
		paramAddTest = [
			{
				key: 'newParam1',
				data: { required: true },
				result: { name: 'newParam1', required: true },
				description: '',
				msg: 'Adding a simple parameter.'
			},
			{
				key: 'newParam2',
				data: null,
				result: { name: 'newParam2' },
				description: '',
				msg: 'Adding new parameter without data.'
			},
			{
				key: 'newParam3',
				data: { description: 'Some string here in ' + currLanguage + ' language.' },
				result: { name: 'newParam3', description: resultDescCurrLang },
				description: 'Some string here in ' + currLanguage + ' language.',
				msg: 'Adding parameter with language prop: original without language.'
			},
			{
				key: 'newParam4',
				data: {
					description: resultDescBothLang
				},
				result: { name: 'newParam4', description: resultDescBothLang },
				description: 'Some string here in ' + currLanguage + ' language.',
				msg: 'Adding parameter with language prop: original with multiple languages.'
			},
			{
				key: 'newParam5',
				data: { type: 'string/wiki-page-name' },
				result: { name: 'newParam5', type: 'wiki-page-name' },
				description: '',
				msg: 'Adding parameter with obsolete type'
			}
		],
		paramChangeTest = [
			{
				key: 'newParam1',
				property: 'description',
				language: currLanguage,
				value: resultDescCurrLang[ currLanguage ],
				result: $.extend( {}, paramAddTest[ 0 ].result, {
					description: resultDescCurrLang
				} ),
				msg: 'Adding description in current language.'
			},
			{
				key: 'newParam1',
				property: 'description',
				language: 'blah',
				value: resultDescMockLang.blah,
				result: $.extend( {}, paramAddTest[ 0 ].result, {
					description: $.extend( {}, resultDescCurrLang, resultDescMockLang )
				} ),
				msg: 'Adding description in mock language.'
			},
			{
				key: 'comment',
				property: 'type',
				value: 'wiki-page-name',
				result: {
					name: 'comment',
					type: 'wiki-page-name',
					required: false
				},
				msg: 'Adding type to comment.'
			},
			{
				key: 'newParam2',
				property: 'description',
				language: 'blah',
				value: '',
				result: $.extend( {}, paramAddTest[ 1 ].result, {
					description: { blah: '' }
				} ),
				msg: 'Adding empty description in mock language.'
			},
			{
				key: 'newParam3',
				property: 'deprecated',
				value: true,
				result: $.extend( {}, paramAddTest[ 2 ].result, {
					deprecated: true
				} ),
				msg: 'Adding deprecated property (boolean).'
			},
			{
				key: 'newParam3',
				property: 'deprecatedValue',
				value: 'This is deprecated.',
				result: $.extend( {}, paramAddTest[ 2 ].result, {
					deprecated: true,
					deprecatedValue: 'This is deprecated.'
				} ),
				msg: 'Adding deprecated property (string).'
			}
		];

	return sourceHandler.buildModel( originalWikitext )
		.done( function ( model ) {
			// Check description
			assert.strictEqual(
				model.getTemplateDescription(),
				'Label unsigned comments in a conversation.',
				'Description in default language.'
			);
			assert.strictEqual(
				model.getTemplateDescription( 'blah' ),
				'Template description in some blah language.',
				'Description in mock language.'
			);

			// Check parameters
			assert.deepEqual(
				Object.keys( model.getParams() ),
				[ 'user', 'date', 'year', 'month', 'day', 'comment' ],
				'Parameters retention.'
			);

			for ( i = 0; i < paramAddTest.length; i++ ) {
				// Add parameter
				model.addParam( paramAddTest[ i ].key, paramAddTest[ i ].data );

				// Test new param data
				assert.deepEqual(
					model.getParamData( paramAddTest[ i ].key ),
					paramAddTest[ i ].result,
					paramAddTest[ i ].msg + ' (parameter data)'
				);

				// Check description in current language
				assert.strictEqual(
					model.getParamValue( paramAddTest[ i ].key, 'description', currLanguage ),
					paramAddTest[ i ].description,
					paramAddTest[ i ].msg + ' (description in current language)'
				);
			}

			// Change parameter properties
			for ( i = 0; i < paramChangeTest.length; i++ ) {
				testVars = paramChangeTest[ i ];
				model.setParamProperty( testVars.key, testVars.property, testVars.value, testVars.language );
				assert.deepEqual(
					model.getParamData( testVars.key ),
					paramChangeTest[ i ].result,
					paramChangeTest[ i ].msg
				);
			}

			// Delete parameter
			model.deleteParam( 'day' );

			// Format checks
			assert.deepEqual(
				model.getTemplateFormat(),
				null,
				'Initial template format (unspecified)'
			);
			[
				'\n{{_ |\n__ = __}}',
				'{{_|_=_\n}}\n',
				'{{_|_=_}}', // No newlines
				'\n{{  __  \n|\n  __  =  __\n  }}\n', // Max newlines/ws
				null,
				'block',
				'inline'
			].forEach( function ( f ) {
				model.setTemplateFormat( f );
				assert.deepEqual(
					model.getTemplateFormat(),
					f,
					'Set template format to ' + JSON.stringify( f )
				);
			} );

			// Ouput a final templatedata
			assert.deepEqual(
				model.outputTemplateData(),
				finalJson,
				'Final templatedata output'
			);

			// Move 'user' to offset 3 (in original array), i.e. after 'year'
			model.reorderParamOrderKey( 'user', 3 );
			assert.deepEqual(
				model.paramOrder,
				[ 'date', 'year', 'user', 'month', 'comment', 'newParam1', 'newParam2', 'newParam3', 'newParam4', 'newParam5' ],
				'Final templatedata output with paramOrder'
			);

			// Move 'month' to offset 2, i.e. after 'year'
			model.reorderParamOrderKey( 'month', 2 );
			assert.deepEqual(
				model.paramOrder,
				[ 'date', 'year', 'month', 'user', 'comment', 'newParam1', 'newParam2', 'newParam3', 'newParam4', 'newParam5' ],
				'Final templatedata output with paramOrder'
			);
		} );
} );

// Test model with maps in wikitext
QUnit.test( 'TemplateData sourceHandler with maps', function ( assert ) {
	var sourceHandler = new SourceHandler(),
		wikitextWithMaps = 'Some text here that is not templatedata information.' +
		'<templatedata>' +
		'{' +
			'"description": {\n' +
				'"en": "Template description in English."\n' +
			'},' +
			'"params": {' +
				'"user": {' +
					'"label": "Username",' +
					'"type": "wiki-user-name",' +
					'"required": true,' +
					'"description": "User name of person who forgot to sign their comment.",' +
					'"aliases": ["1"]' +
				'},' +
				'"date": {' +
					'"label": "Date",' +
					'"aliases": ["2"],' +
					'"autovalue": "{{subst:CURRENTMONTHNAME}}",' +
					'"suggested": true' +
				'},' +
				'"year": {' +
					'"label": "Year",' +
					'"type": "number"' +
				'},' +
				'"month": {' +
					'"label": "Month",' +
					'"inherits": "year"' +
				'},' +
				'"day": {' +
					'"label": "Day",' +
					'"inherits": "year"' +
				'},' +
				'"comment": {' +
					'"required": false' +
				'}' +
			'},' +
			'"sets": [' +
				'{' +
					'"label": "Date",' +
					'"params": ["year", "month", "day"]' +
				'}' +
			'],' +
			'"maps": {' +
				'"exampleconsumer": {' +
					'"cuser": "user",' +
					'"cdate": ["year", "month", "day"],' +
					'"ccomment": [ ["comment"] ]' +
				'}' +
			'}' +
		'}' +
		'</templatedata>' +
		'Trailing text at the end.',
		params = {
			user: {
				label: 'Username',
				type: 'wiki-user-name',
				required: true,
				description: 'User name of person who forgot to sign their comment.',
				aliases: [ '1' ]
			},
			date: {
				label: 'Date',
				aliases: [ '2' ],
				autovalue: '{{subst:CURRENTMONTHNAME}}',
				suggested: true,
				type: undefined
			},
			year: {
				label: 'Year',
				type: 'number'
			},
			month: {
				label: 'Month',
				inherits: 'year',
				type: undefined
			},
			day: {
				label: 'Day',
				inherits: 'year',
				type: undefined
			},
			comment: {
				required: false,
				type: undefined
			}
		},
		jsonWithMaps = {
			description: {
				en: 'Template description in English.'
			},
			params: params,
			sets: [
				{
					label: 'Date',
					params: [
						'year',
						'month',
						'day'
					]
				}
			],
			maps: {
				exampleconsumer: {
					cuser: 'user',
					cdate: [ 'year', 'month', 'day' ],
					ccomment: [ [ 'comment' ] ]
				}
			}
		};

	return sourceHandler.buildModel( wikitextWithMaps )
		.done( function ( model ) {
			assert.deepEqual(
				model.outputTemplateData(),
				jsonWithMaps,
				'Final templatedata output'
			);
		} );
} );

// Test model fail
QUnit.test( 'TemplateData sourceHandler failure', function ( assert ) {
	var sourceHandler = new SourceHandler(),
		erronousString = '<templatedata>{\n' +
			'"params": {\n' +
				// Open quote
				'"user: {\n' +
					'"label": "Username",\n' +
					'"type": "wiki-user-name",\n' +
					'"required": true,\n' +
					'"description": "User name of person who forgot to sign their comment.",\n' +
					'"aliases": [\n' +
						'"1"\n' +
					']\n' +
				'},\n' +
				'"date": {\n' +
					'"label": "Date",\n' +
					'"description": {\n' +
						// Forgotten quotes
						currLanguage + ': "Timestamp of when the comment was posted, in YYYY-MM-DD format."\n' +
					'}\n' +
					'"suggested": true\n' +
				'}\n' +
			'}\n' +
		'}</templatedata>',
		done = assert.async(),
		promise;

	promise = sourceHandler.buildModel( erronousString );
	promise.always( function () {
		assert.strictEqual( promise.state(), 'rejected', 'Promise rejected on erronous json string.' );
		done();
	} );
} );

// Test model gets default format
QUnit.test( 'TemplateData sourceHandler adding default format', function ( assert ) {
	var sourceHandler = new SourceHandler(),
		simpleTemplateDataNoFormat = '<templatedata>{\n' +
				'"params": {}\n' +
			'}</templatedata>',
		simpleTemplateDataDefaultFormat = {
			params: {}
		};

	return sourceHandler.buildModel( simpleTemplateDataNoFormat )
		.done( function ( model ) {
			assert.deepEqual(
				model.outputTemplateData(),
				simpleTemplateDataDefaultFormat,
				'Final templatedata output'
			);
		} );
} );

QUnit.test( 'Duplicate parameter names', function ( assert ) {
	var model = new Model();
	model.addParam( 'color' );
	assert.deepEqual( model.getParams(), {
		color: { name: 'color' }
	} );
	assert.deepEqual( model.getTemplateParamOrder(), [ 'color' ] );
	model.addParam( 'color' );
	assert.deepEqual( model.getParams(), {
		color: { name: 'color' },
		color2: { name: 'color' }
	} );
	assert.deepEqual( model.getTemplateParamOrder(), [ 'color', 'color2' ] );

	model.setParamProperty( 'color2', 'name', '1' );
	assert.deepEqual( model.getParams(), {
		color: { name: 'color' },
		color2: { deleted: true },
		1: { name: '1' }
	} );
	assert.deepEqual( model.getTemplateParamOrder(), [ 'color', '1' ] );
	model.setParamProperty( 'color', 'name', '1' );
	assert.deepEqual( model.getParams(), {
		color: { deleted: true },
		color2: { deleted: true },
		1: { name: '1' },
		'1-3': { name: '1' }
	} );
	assert.deepEqual( model.getTemplateParamOrder(), [ '1-3', '1' ] );
} );

QUnit.test( 'safesubst: hack with an unnamed parameter', function ( assert ) {
	var handler = new SourceHandler(),
		wikitext = '{{ {{{|safesubst:}}}#invoke:…|{{{1}}}|{{{ 1 }}}}}';

	assert.deepEqual(
		handler.extractParametersFromTemplateCode( wikitext ),
		[ '1' ]
	);
} );
