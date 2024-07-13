{
	QUnit.module( 've.dm.MWTemplateSpecModel' );

	/**
	 * @param {string[]} [parameterNames]
	 * @return {ve.dm.MWTemplateModel} but it's a mock
	 */
	const createTemplateMock = ( parameterNames ) => {
		const params = {};
		( parameterNames || [] ).forEach( ( name ) => {
			params[ name ] = {};
		} );
		return {
			params,
			getTemplateDataQueryTitle: () => null,
			getTarget: () => {
				return { wt: 'RawTemplateName' };
			},
			getParameters: function () {
				return this.params;
			}
		};
	};

	QUnit.test( 'Basic behavior on empty template', ( assert ) => {
		const template = createTemplateMock(),
			spec = new ve.dm.MWTemplateSpecModel( template );

		assert.strictEqual( spec.getLabel(), 'RawTemplateName', 'getLabel' );
		assert.strictEqual( spec.getDescription(), null, 'getDescription' );
		assert.deepEqual( spec.getDocumentedParameterOrder(), [], 'getDocumentedParameterOrder' );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [], 'getUndocumentedParameterNames' );
		assert.strictEqual( spec.isKnownParameterOrAlias( 'unknown' ), false, 'isKnownParameterOrAlias' );
		assert.strictEqual( spec.isParameterAlias( 'unknown' ), false, 'isParameterAlias' );
		assert.strictEqual( spec.getParameterLabel( 'unknown' ), 'unknown', 'getParameterLabel' );
		assert.strictEqual( spec.getParameterDescription( 'unknown' ), null, 'getParameterDescription' );
		assert.deepEqual( spec.getParameterSuggestedValues( 'unknown' ), [], 'getParameterSuggestedValues' );
		assert.strictEqual( spec.getParameterDefaultValue( 'unknown' ), '', 'getParameterDefaultValue' );
		assert.strictEqual( spec.getParameterExampleValue( 'unknown' ), null, 'getParameterExampleValue' );
		assert.strictEqual( spec.getParameterAutoValue( 'unknown' ), '', 'getParameterAutoValue' );
		assert.strictEqual( spec.getParameterType( 'unknown' ), 'string', 'getParameterType' );
		assert.deepEqual( spec.getParameterAliases( 'unknown' ), [], 'getParameterAliases' );
		assert.strictEqual( spec.getPrimaryParameterName( 'unknown' ), 'unknown', 'getPrimaryParameterName' );
		assert.strictEqual( spec.isParameterRequired( 'unknown' ), false, 'isParameterRequired' );
		assert.strictEqual( spec.isParameterSuggested( 'unknown' ), false, 'isParameterSuggested' );
		assert.strictEqual( spec.isParameterDeprecated( 'unknown' ), false, 'isParameterDeprecated' );
		assert.strictEqual( spec.getParameterDeprecationDescription( 'unknown' ), '', 'getParameterDeprecationDescription' );
		assert.deepEqual( spec.getKnownParameterNames(), [], 'getKnownParameterNames' );
		assert.deepEqual( spec.getParameterSets(), [], 'getParameterSets' );
		assert.deepEqual( spec.getMaps(), {}, 'getMaps' );
	} );

	QUnit.test( 'Basic behavior on non-empty template', ( assert ) => {
		const template = createTemplateMock( [ 'p1', 'p2' ] ),
			spec = new ve.dm.MWTemplateSpecModel( template );

		assert.strictEqual( spec.getLabel(), 'RawTemplateName', 'getLabel' );
		assert.strictEqual( spec.getDescription(), null, 'getDescription' );
		assert.deepEqual( spec.getDocumentedParameterOrder(), [], 'getDocumentedParameterOrder' );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [ 'p1', 'p2' ], 'getUndocumentedParameterNames' );
		assert.strictEqual( spec.isKnownParameterOrAlias( 'p2' ), true, 'isKnownParameterOrAlias' );
		assert.strictEqual( spec.isParameterAlias( 'p2' ), false, 'isParameterAlias' );
		assert.strictEqual( spec.getParameterLabel( 'p2' ), 'p2', 'getParameterLabel' );
		assert.strictEqual( spec.getParameterDescription( 'p2' ), null, 'getParameterDescription' );
		assert.deepEqual( spec.getParameterSuggestedValues( 'p2' ), [], 'getParameterSuggestedValues' );
		assert.strictEqual( spec.getParameterDefaultValue( 'p2' ), '', 'getParameterDefaultValue' );
		assert.strictEqual( spec.getParameterExampleValue( 'p2' ), null, 'getParameterExampleValue' );
		assert.strictEqual( spec.getParameterAutoValue( 'p2' ), '', 'getParameterAutoValue' );
		assert.strictEqual( spec.getParameterType( 'p2' ), 'string', 'getParameterType' );
		assert.deepEqual( spec.getParameterAliases( 'p2' ), [], 'getParameterAliases' );
		assert.strictEqual( spec.getPrimaryParameterName( 'p2' ), 'p2', 'getPrimaryParameterName' );
		assert.strictEqual( spec.isParameterRequired( 'p2' ), false, 'isParameterRequired' );
		assert.strictEqual( spec.isParameterSuggested( 'p2' ), false, 'isParameterSuggested' );
		assert.strictEqual( spec.isParameterDeprecated( 'p2' ), false, 'isParameterDeprecated' );
		assert.strictEqual( spec.getParameterDeprecationDescription( 'p2' ), '', 'getParameterDeprecationDescription' );
		assert.deepEqual( spec.getKnownParameterNames(), [ 'p1', 'p2' ], 'getKnownParameterNames' );
		assert.deepEqual( spec.getParameterSets(), [], 'getParameterSets' );
		assert.deepEqual( spec.getMaps(), {}, 'getMaps' );
	} );

	QUnit.test( 'Basic behavior with later fillFromTemplate()', ( assert ) => {
		const template = createTemplateMock( [ 'p1' ] ),
			spec = new ve.dm.MWTemplateSpecModel( template );

		template.params.p2 = {};
		spec.fillFromTemplate();

		assert.strictEqual( spec.getLabel(), 'RawTemplateName', 'getLabel' );
		assert.strictEqual( spec.getDescription(), null, 'getDescription' );
		assert.deepEqual( spec.getDocumentedParameterOrder(), [], 'getDocumentedParameterOrder' );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [ 'p1', 'p2' ], 'getUndocumentedParameterNames' );
		assert.strictEqual( spec.isKnownParameterOrAlias( 'p2' ), true, 'isKnownParameterOrAlias' );
		assert.strictEqual( spec.isParameterAlias( 'p2' ), false, 'isParameterAlias' );
		assert.strictEqual( spec.getParameterLabel( 'p2' ), 'p2', 'getParameterLabel' );
		assert.strictEqual( spec.getParameterDescription( 'p2' ), null, 'getParameterDescription' );
		assert.deepEqual( spec.getParameterSuggestedValues( 'p2' ), [], 'getParameterSuggestedValues' );
		assert.strictEqual( spec.getParameterDefaultValue( 'p2' ), '', 'getParameterDefaultValue' );
		assert.strictEqual( spec.getParameterExampleValue( 'p2' ), null, 'getParameterExampleValue' );
		assert.strictEqual( spec.getParameterAutoValue( 'p2' ), '', 'getParameterAutoValue' );
		assert.strictEqual( spec.getParameterType( 'p2' ), 'string', 'getParameterType' );
		assert.deepEqual( spec.getParameterAliases( 'p2' ), [], 'getParameterAliases' );
		assert.strictEqual( spec.getPrimaryParameterName( 'p2' ), 'p2', 'getPrimaryParameterName' );
		assert.strictEqual( spec.isParameterRequired( 'p2' ), false, 'isParameterRequired' );
		assert.strictEqual( spec.isParameterSuggested( 'p2' ), false, 'isParameterSuggested' );
		assert.strictEqual( spec.isParameterDeprecated( 'p2' ), false, 'isParameterDeprecated' );
		assert.strictEqual( spec.getParameterDeprecationDescription( 'p2' ), '', 'getParameterDeprecationDescription' );
		assert.deepEqual( spec.getKnownParameterNames(), [ 'p1', 'p2' ], 'getKnownParameterNames' );
		assert.deepEqual( spec.getParameterSets(), [], 'getParameterSets' );
		assert.deepEqual( spec.getMaps(), {}, 'getMaps' );
	} );

	[
		[ 'a_a', 'b_b', 'A a', 'parses .wt if possible' ],
		[ 'subst:a_a', 'b_b', 'A a', 'resolves subst:' ],
		[ '{{a_a}}', './Template:b_b', 'B b', 'strips template namespace' ],
		[ '{{a_a}}', './Talk:b_b', 'Talk:B b', 'does not strip other namespaces' ],
		[ '{{a_a}}', './b_b', ':B b', 'title in main namespace must be prefixed' ],
		[ '{{a_a}}', './Template:{{b_b}}', 'Template:{{b b}}', 'falls back to unmodified href if invalid' ]
	].forEach( ( [ wt, href, expected, message ] ) =>
		QUnit.test( 'getLabel: ' + message, ( assert ) => {
			const transclusion = new ve.dm.MWTransclusionModel(),
				template = new ve.dm.MWTemplateModel( transclusion, { wt, href } ),
				spec = new ve.dm.MWTemplateSpecModel( template );

			assert.strictEqual( spec.getLabel(), expected );
		} )
	);

	[
		undefined,
		null,
		[],
		{}
	].forEach( ( templateData ) =>
		QUnit.test( 'Invalid TemplateData, e.g. empty or without params', ( assert ) => {
			const template = createTemplateMock(),
				spec = new ve.dm.MWTemplateSpecModel( template );

			spec.setTemplateData( templateData );

			assert.deepEqual( spec.getDocumentedParameterOrder(), [], 'getDocumentedParameterOrder' );
			assert.deepEqual( spec.getUndocumentedParameterNames(), [], 'getUndocumentedParameterNames' );
			assert.strictEqual( spec.getParameterLabel( 'p' ), 'p', 'getParameterLabel' );
			assert.strictEqual( spec.getParameterDescription( 'p' ), null, 'getParameterDescription' );
			assert.deepEqual( spec.getParameterSuggestedValues( 'p' ), [], 'getParameterSuggestedValues' );
			assert.strictEqual( spec.getParameterDefaultValue( 'p' ), '', 'getParameterDefaultValue' );
			assert.strictEqual( spec.getParameterExampleValue( 'p' ), null, 'getParameterExampleValue' );
			assert.strictEqual( spec.getParameterAutoValue( 'p' ), '', 'getParameterAutoValue' );
			assert.strictEqual( spec.getParameterType( 'p' ), 'string', 'getParameterType' );
			assert.deepEqual( spec.getParameterAliases( 'p' ), [], 'getParameterAliases' );
			assert.strictEqual( spec.isParameterRequired( 'p' ), false, 'isParameterRequired' );
			assert.strictEqual( spec.isParameterSuggested( 'p' ), false, 'isParameterSuggested' );
			assert.strictEqual( spec.isParameterDeprecated( 'p' ), false, 'isParameterDeprecated' );
			assert.strictEqual( spec.getParameterDeprecationDescription( 'p' ), '', 'getParameterDeprecationDescription' );
		} )
	);

	QUnit.test( 'Basic behavior with minimal setTemplateData()', ( assert ) => {
		const template = createTemplateMock( [ 'p1' ] ),
			spec = new ve.dm.MWTemplateSpecModel( template );

		spec.setTemplateData( { params: { p2: {} } } );

		assert.strictEqual( spec.getLabel(), 'RawTemplateName', 'getLabel' );
		assert.strictEqual( spec.getDescription(), null, 'getDescription' );
		assert.deepEqual( spec.getDocumentedParameterOrder(), [ 'p2' ], 'getDocumentedParameterOrder' );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [ 'p1' ], 'getUndocumentedParameterNames' );
		assert.strictEqual( spec.isKnownParameterOrAlias( 'p2' ), true, 'isKnownParameterOrAlias' );
		assert.strictEqual( spec.isParameterAlias( 'p2' ), false, 'isParameterAlias' );
		assert.strictEqual( spec.getParameterLabel( 'p2' ), 'p2', 'getParameterLabel' );
		assert.strictEqual( spec.getParameterDescription( 'p2' ), null, 'getParameterDescription' );
		assert.deepEqual( spec.getParameterSuggestedValues( 'p2' ), [], 'getParameterSuggestedValues' );
		assert.strictEqual( spec.getParameterDefaultValue( 'p2' ), '', 'getParameterDefaultValue' );
		assert.strictEqual( spec.getParameterExampleValue( 'p2' ), null, 'getParameterExampleValue' );
		assert.strictEqual( spec.getParameterAutoValue( 'p2' ), '', 'getParameterAutoValue' );
		assert.strictEqual( spec.getParameterType( 'p2' ), 'string', 'getParameterType' );
		assert.deepEqual( spec.getParameterAliases( 'p2' ), [], 'getParameterAliases' );
		assert.strictEqual( spec.getPrimaryParameterName( 'p2' ), 'p2', 'getPrimaryParameterName' );
		assert.strictEqual( spec.isParameterRequired( 'p2' ), false, 'isParameterRequired' );
		assert.strictEqual( spec.isParameterSuggested( 'p2' ), false, 'isParameterSuggested' );
		assert.strictEqual( spec.isParameterDeprecated( 'p2' ), false, 'isParameterDeprecated' );
		assert.strictEqual( spec.getParameterDeprecationDescription( 'p2' ), '', 'getParameterDeprecationDescription' );
		assert.deepEqual( spec.getKnownParameterNames(), [ 'p1', 'p2' ], 'getKnownParameterNames' );
		assert.deepEqual( spec.getParameterSets(), [], 'getParameterSets' );
		assert.deepEqual( spec.getMaps(), {}, 'getMaps' );
	} );

	QUnit.test( 'Complex setTemplateData() with alias', ( assert ) => {
		const template = createTemplateMock(),
			spec = new ve.dm.MWTemplateSpecModel( template );

		spec.setTemplateData( {
			description: 'TemplateDescription',
			params: {
				p: {
					label: 'ParamLabel',
					description: 'ParamDescription',
					suggestedvalues: [ 'SuggestedValue' ],
					default: 'ParamDefault',
					example: 'ParamExample',
					autovalue: 'ParamAutoValue',
					type: 'DummyType',
					aliases: [ 'a' ],
					required: true,
					suggested: true,
					deprecated: 'DeprecationText'
				}
			},
			paramOrder: [ 'DummyOrder' ],
			sets: [ 'DummySet' ],
			maps: { dummyMap: true }
		} );

		assert.strictEqual( spec.getLabel(), 'RawTemplateName', 'getLabel' );
		assert.strictEqual( spec.getDescription(), 'TemplateDescription', 'getDescription' );
		assert.deepEqual( spec.getDocumentedParameterOrder(), [ 'DummyOrder' ], 'getDocumentedParameterOrder' );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [], 'getUndocumentedParameterNames' );
		assert.strictEqual( spec.isKnownParameterOrAlias( 'a' ), true, 'isKnownParameterOrAlias' );
		assert.strictEqual( spec.isParameterAlias( 'a' ), true, 'isParameterAlias' );
		assert.strictEqual( spec.getParameterLabel( 'a' ), 'ParamLabel', 'getParameterLabel' );
		assert.strictEqual( spec.getParameterDescription( 'a' ), 'ParamDescription', 'getParameterDescription' );
		assert.deepEqual( spec.getParameterSuggestedValues( 'a' ), [ 'SuggestedValue' ], 'getParameterSuggestedValues' );
		assert.strictEqual( spec.getParameterDefaultValue( 'a' ), 'ParamDefault', 'getParameterDefaultValue' );
		assert.strictEqual( spec.getParameterExampleValue( 'a' ), 'ParamExample', 'getParameterExampleValue' );
		assert.strictEqual( spec.getParameterAutoValue( 'a' ), 'ParamAutoValue', 'getParameterAutoValue' );
		assert.strictEqual( spec.getParameterType( 'a' ), 'DummyType', 'getParameterType' );
		assert.deepEqual( spec.getParameterAliases( 'a' ), [ 'a' ], 'getParameterAliases' );
		assert.strictEqual( spec.getPrimaryParameterName( 'a' ), 'p', 'getPrimaryParameterName' );
		assert.strictEqual( spec.isParameterRequired( 'a' ), true, 'isParameterRequired' );
		assert.strictEqual( spec.isParameterSuggested( 'a' ), true, 'isParameterSuggested' );
		assert.strictEqual( spec.isParameterDeprecated( 'a' ), true, 'isParameterDeprecated' );
		assert.strictEqual( spec.getParameterDeprecationDescription( 'a' ), 'DeprecationText', 'getParameterDeprecationDescription' );
		assert.deepEqual( spec.getKnownParameterNames(), [ 'p' ], 'getKnownParameterNames' );
		assert.deepEqual( spec.getParameterSets(), [ 'DummySet' ], 'getParameterSets' );
		assert.deepEqual( spec.getMaps(), { dummyMap: true }, 'getMaps' );
	} );

	QUnit.test( 'Template uses aliases', ( assert ) => {
		const template = createTemplateMock( [ 'p0', 'p1-alias', 'p2' ] ),
			spec = new ve.dm.MWTemplateSpecModel( template );

		assert.strictEqual( spec.isParameterAlias( 'p1-alias' ), false );
		assert.strictEqual( spec.getParameterLabel( 'p1-alias' ), 'p1-alias' );
		assert.deepEqual( spec.getKnownParameterNames(), [ 'p0', 'p1-alias', 'p2' ] );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [ 'p0', 'p1-alias', 'p2' ], 'getUndocumentedParameterNames' );

		spec.setTemplateData( { params: { p1: { aliases: [ 'p1-alias' ] } } } );

		assert.strictEqual( spec.isParameterAlias( 'p1-alias' ), true );
		assert.strictEqual( spec.getParameterLabel( 'p1-alias' ), 'p1-alias' );
		assert.deepEqual( spec.getKnownParameterNames(), [ 'p0', 'p1', 'p2' ] );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [ 'p0', 'p2' ], 'getUndocumentedParameterNames' );
	} );

	QUnit.test( 'Alias conflicts with another parameter', ( assert ) => {
		const template = createTemplateMock(),
			spec = new ve.dm.MWTemplateSpecModel( template );

		spec.setTemplateData( { params: {
			p1: {
				label: 'Parameter one'
			},
			p2: {
				label: 'Parameter two',
				// Note: This is impossible in real-world scenarios, but better be safe than sorry
				aliases: [ 'p1' ]
			}
		} } );

		assert.strictEqual( spec.getParameterLabel( 'p1' ), 'Parameter two' );
		assert.strictEqual( spec.getParameterLabel( 'p2' ), 'Parameter two' );
	} );

	QUnit.test( 'fillFromTemplate() must skip aliases', ( assert ) => {
		const template = createTemplateMock( [ 'colour' ] ),
			spec = new ve.dm.MWTemplateSpecModel( template );

		spec.setTemplateData( { params: { color: { aliases: [ 'colour' ] } } } );

		assert.deepEqual( spec.getKnownParameterNames(), [ 'color' ] );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [], 'getUndocumentedParameterNames' );

		spec.fillFromTemplate();

		assert.deepEqual( spec.getKnownParameterNames(), [ 'color' ] );
		assert.deepEqual( spec.getUndocumentedParameterNames(), [], 'getUndocumentedParameterNames' );
	} );

	[
		[ { params: { p: {} } }, true, 'documented' ],
		[ {}, true, 'documented but no params' ],
		[ { notemplatedata: true }, false, 'undocumented' ],
		[ { notemplatedata: true, params: { p: {} } }, false, 'auto-detected params' ],

		// Make sure bad input is not reported as being documented
		[ undefined, false, 'undefined' ],
		[ null, false, 'null' ],
		[ [], false, 'empty array' ],

		[ { notemplatedata: false }, true, 'unexpected false' ],
		[ { notemplatedata: '' }, true, 'unsupported formatversion=1' ]
	].forEach( ( [ templateData, expected, message ] ) =>
		QUnit.test( 'isDocumented(): ' + message, ( assert ) => {
			const template = createTemplateMock(),
				spec = new ve.dm.MWTemplateSpecModel( template );

			assert.false( spec.isDocumented(), 'undocumented by default' );

			spec.setTemplateData( templateData );
			assert.strictEqual( spec.isDocumented(), expected );
		} )
	);

	QUnit.test( 'getCanonicalParameterOrder sorting undocumented parameters alphabetically', ( assert ) => {
		const template = createTemplateMock( [ 'ö', '3', 'x', 9, '', 1, 'a' ] ),
			spec = new ve.dm.MWTemplateSpecModel( template );

		assert.deepEqual( spec.getCanonicalParameterOrder(), [ '1', '3', '9', 'a', 'ö', 'x' ] );
	} );

	QUnit.test( 'getDocumentedParameterOrder() should not return a reference', ( assert ) => {
		const template = createTemplateMock(),
			spec = new ve.dm.MWTemplateSpecModel( template );

		spec.setTemplateData( { params: {}, paramOrder: [ 'p' ] } );
		const parameterNames = spec.getDocumentedParameterOrder();
		parameterNames.push( 'x' );

		assert.deepEqual( spec.getDocumentedParameterOrder(), [ 'p' ] );
	} );

	QUnit.test( 'Parameter deprecation with empty string', ( assert ) => {
		const template = createTemplateMock(),
			spec = new ve.dm.MWTemplateSpecModel( template );

		spec.setTemplateData( { params: { p: { deprecated: '' } } } );

		assert.strictEqual( spec.isParameterDeprecated( 'p' ), true );
		assert.strictEqual( spec.getParameterDeprecationDescription( 'p' ), '' );
	} );

}
