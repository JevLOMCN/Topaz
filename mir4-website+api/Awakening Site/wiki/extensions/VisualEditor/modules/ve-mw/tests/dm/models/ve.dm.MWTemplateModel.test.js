/*!
 * VisualEditor DataModel MWTemplateModel tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

{
	const transclusionData = {
		params: {
			foo: { wt: 'Foo value' },
			bar: { wt: 'Bar value' },
			Bar: { wt: 'Bar value' },
			empty: { wt: '' },
			'': { wt: '' }
		},
		target: {
			href: './Template:Test',
			wt: 'Test'
		}
	};

	QUnit.module( 've.dm.MWTemplateModel' );

	/**
	 * Create a new MWTemplateModel initialized with a static transclusion data fixture.
	 *
	 * @return {ve.dm.MWTemplateModel}
	 */
	const newTemplateModel = function () {
		const doc = ve.dm.Document.static.newBlankDocument(),
			transclusion = new ve.dm.MWTransclusionModel( doc ),
			clonedTransclusionData = ve.extendObject( {}, transclusionData );

		return ve.dm.MWTemplateModel.newFromData( transclusion, clonedTransclusionData );
	};

	/* Tests */

	[
		[ undefined, null ],
		[ '', null ],
		[ 'no_prefix', 'no prefix' ],
		[ '/unexpected_prefix', '/unexpected prefix' ],
		[ './Template:%C3%9Cnicode%5Fexample/subpage', 'Template:Ünicode example/subpage' ],
		[ './Template:Possibly_invalid%5B%5D', 'Template:Possibly invalid[]' ]
	].forEach( ( [ href, expected ] ) =>
		QUnit.test( 'getTitle: ' + href, ( assert ) => {
			const transclusion = { nextUniquePartId: () => '' },
				template = new ve.dm.MWTemplateModel( transclusion, { href } );
			assert.strictEqual( template.getTitle(), expected );
		} )
	);

	QUnit.test( 'hasParameter', ( assert ) => {
		const template = newTemplateModel();

		// All parameters are primary as long as the TemplateData documentation isn't known
		assert.strictEqual( template.hasParameter( 'bar' ), true );
		assert.strictEqual( template.hasParameter( 'resolved-bar' ), false );
		assert.strictEqual( template.hasParameter( 'alternative-bar' ), false );

		template.getSpec().setTemplateData( { params: {
			'resolved-bar': { aliases: [ 'bar', 'alternative-bar' ] }
		} } );

		// Now "bar" and "alternative-bar" are aliases, and "resolved-bar" is the primary name
		assert.strictEqual( template.hasParameter( 'bar' ), true );
		assert.strictEqual( template.hasParameter( 'resolved-bar' ), true );
		assert.strictEqual( template.hasParameter( 'alternative-bar' ), true );
	} );

	QUnit.test( 'getOriginalParameterName', ( assert ) => {
		const template = newTemplateModel();
		template.addParameter( new ve.dm.MWParameterModel( template, 'p1' ) );
		template.addParameter( new ve.dm.MWParameterModel( template, 'p2-alias' ) );

		// These are all independent parameters as long as we don't know anything about aliases
		assert.strictEqual( template.getOriginalParameterName( 'p1' ), 'p1' );
		assert.strictEqual( template.getOriginalParameterName( 'p1-alias' ), 'p1-alias' );
		assert.strictEqual( template.getOriginalParameterName( 'p2' ), 'p2' );
		assert.strictEqual( template.getOriginalParameterName( 'p2-alias' ), 'p2-alias' );
		assert.strictEqual( template.getOriginalParameterName( 'p3' ), 'p3' );
		assert.strictEqual( template.getOriginalParameterName( 'p3-alias' ), 'p3-alias' );

		template.getSpec().setTemplateData( { params: {
			p1: { aliases: [ 'p1-alias' ] },
			p2: { aliases: [ 'p2-alias' ] },
			p3: { aliases: [ 'p3-alias' ] }
		} } );

		assert.strictEqual( template.getOriginalParameterName( 'p1' ), 'p1' );
		assert.strictEqual( template.getOriginalParameterName( 'p1-alias' ), 'p1' );
		assert.strictEqual( template.getOriginalParameterName( 'p2' ), 'p2-alias' );
		assert.strictEqual( template.getOriginalParameterName( 'p2-alias' ), 'p2-alias' );
		assert.strictEqual( template.getOriginalParameterName( 'p3' ), 'p3' );
		assert.strictEqual( template.getOriginalParameterName( 'p3-alias' ), 'p3' );
	} );

	QUnit.test( 'serialize input parameters', ( assert ) => {
		const template = newTemplateModel();

		const serialization = template.serialize();
		assert.deepEqual( serialization, { template: {
			params: {
				foo: { wt: 'Foo value' },
				bar: { wt: 'Bar value' },
				Bar: { wt: 'Bar value' },
				empty: { wt: '' }
			},
			target: { href: './Template:Test', wt: 'Test' }
		} } );
	} );

	QUnit.test( 'serialize changed input parameters', ( assert ) => {
		const template = newTemplateModel(),
			newParameter = new ve.dm.MWParameterModel( template, 'baz', 'Baz value' );

		template.addParameter( newParameter );

		const serialization = template.serialize();
		assert.deepEqual( serialization.template.params.baz, { wt: 'Baz value' } );
	} );

	// T75134
	QUnit.test( 'serialize after parameter was removed', ( assert ) => {
		const template = newTemplateModel(),
			existingParameter = template.getParameter( 'bar' );

		template.removeParameter( existingParameter );

		const serialization = template.serialize();
		assert.deepEqual( serialization.template.params, {
			foo: { wt: 'Foo value' },
			Bar: { wt: 'Bar value' },
			empty: { wt: '' }
		} );
	} );

	// T101075
	QUnit.test( 'serialize without empty parameter not present in original parameter set', ( assert ) => {
		const template = newTemplateModel(),
			parameterWithoutValue = new ve.dm.MWParameterModel( template, 'new_empty', '' );

		template.addParameter( parameterWithoutValue );

		const serialization = template.serialize();
		assert.deepEqual( serialization.template.params, {
			foo: { wt: 'Foo value' },
			bar: { wt: 'Bar value' },
			Bar: { wt: 'Bar value' },
			empty: { wt: '' }
		} );
	} );

	[
		{
			name: 'serialize with explicit parameter order',
			spec: {
				params: {
					foo: {},
					empty: {},
					bar: {},
					Bar: {}
				},
				paramOrder: [ 'bar', 'foo', 'empty', 'Bar' ]
			},
			expected: [ 'foo', 'bar', 'Bar', 'empty' ]
		},
		{
			name: 'serialize with no parameter order',
			spec: {
				params: {
					foo: {},
					empty: {},
					bar: {},
					Bar: {}
				}
			},
			expected: [ 'foo', 'bar', 'Bar', 'empty' ]
		},
		{
			name: 'serialize with aliases',
			spec: {
				params: {
					foo: {},
					Bar: {},
					empty: {},
					hasaliases: { aliases: [ 'bar', 'baz' ] }
				}
			},
			expected: [ 'foo', 'bar', 'Bar', 'empty' ]
		},
		{
			name: 'serialize with unknown params',
			spec: {
				params: {
					bar: {}
				}
			},
			expected: [ 'foo', 'bar', 'Bar', 'empty' ]
		}
	].forEach( ( { name, spec, expected } ) =>
		QUnit.test( name, ( assert ) => {
			const template = newTemplateModel();

			template.getSpec().setTemplateData( spec );

			const serialization = template.serialize();
			assert.deepEqual( Object.keys( serialization.template.params ), expected );
		} )
	);

	[
		{
			name: 'no spec retrieved',
			spec: null,
			expected: [
				'bar',
				'Bar',
				'empty',
				'foo',
				''
			]
		},
		{
			name: 'empty spec',
			spec: {},
			expected: [
				'bar',
				'Bar',
				'empty',
				'foo',
				''
			]
		},
		{
			name: 'spec with explicit paramOrder and all known params',
			spec: {
				params: {
					bar: {},
					Bar: {},
					empty: {},
					unused: {},
					foo: {}
				},
				paramOrder: [ 'foo', 'Bar', 'empty', 'bar', 'unused' ]
			},
			expected: [
				'foo',
				'Bar',
				'empty',
				'bar',
				''
			]
		},
		{
			name: 'spec with explicit paramOrder and some unknown params',
			spec: {
				params: {
					empty: {},
					unused: {},
					foo: {}
				},
				paramOrder: [ 'foo', 'empty', 'unused' ]
			},
			expected: [
				'foo',
				'empty',
				'bar',
				'Bar',
				''
			]
		},
		{
			name: 'spec with explicit paramOrder but all unknown params',
			spec: {
				params: {},
				paramOrder: []
			},
			expected: [
				'bar',
				'Bar',
				'empty',
				'foo',
				''
			]
		},
		{
			name: 'spec with no paramOrder, all known params',
			spec: {
				params: {
					bar: {},
					Bar: {},
					foo: {},
					unused: {},
					empty: {}
				}
			},
			expected: [
				'bar',
				'Bar',
				'foo',
				'empty',
				''
			]
		},
		{
			name: 'spec with no paramOrder and some unknown params',
			spec: {
				params: {
					empty: {},
					unused: {},
					foo: {}
				}
			},
			expected: [
				'empty',
				'foo',
				'bar',
				'Bar',
				''
			]
		}
	].forEach( ( { name, spec, expected } ) =>
		QUnit.test( 'getOrderedParameterNames: ' + name, ( assert ) => {
			const template = newTemplateModel();

			if ( spec ) {
				template.getSpec().setTemplateData( spec );
			}

			assert.deepEqual( template.getOrderedParameterNames(), expected );
		} )
	);

	[
		{
			name: 'no spec retrieved',
			spec: null,
			expected: [
				'bar',
				'Bar',
				'empty',
				'foo',
				''
			]
		},
		{
			name: 'spec with explicit paramOrder and all known params',
			spec: {
				params: {
					bar: {},
					Bar: {},
					empty: {},
					unused: {},
					foo: {}
				},
				paramOrder: [ 'foo', 'Bar', 'empty', 'unused', 'bar' ]
			},
			expected: [
				'foo',
				'Bar',
				'empty',
				'unused',
				'bar',
				''
			]
		},
		{
			name: 'spec with explicit paramOrder and some unknown params',
			spec: {
				params: {
					empty: {},
					unused: {},
					foo: {}
				},
				paramOrder: [ 'foo', 'empty', 'unused' ]
			},
			expected: [
				'foo',
				'empty',
				'unused',
				'bar',
				'Bar',
				''
			]
		},
		{
			name: 'spec with explicit paramOrder but all unknown params',
			spec: {
				params: {},
				paramOrder: []
			},
			expected: [
				'bar',
				'Bar',
				'empty',
				'foo',
				''
			]
		},
		{
			name: 'spec with no paramOrder, all known params',
			spec: {
				params: {
					bar: {},
					Bar: {},
					foo: {},
					unused: {},
					empty: {}
				}
			},
			expected: [
				'bar',
				'Bar',
				'foo',
				'unused',
				'empty',
				''
			]
		},
		{
			name: 'spec with no paramOrder and some unknown params',
			spec: {
				params: {
					empty: {},
					unused: {},
					foo: {}
				}
			},
			expected: [
				'empty',
				'unused',
				'foo',
				'bar',
				'Bar',
				''
			]
		},
		{
			name: 'spec with explicit paramOrder and aliases',
			spec: {
				params: {
					empty: {},
					unused: {},
					hasalias: {
						aliases: [ 'bar', 'baz' ]
					}
				},
				paramOrder: [ 'hasalias', 'empty', 'unused' ]
			},
			expected: [
				'bar',
				'empty',
				'unused',
				'Bar',
				'foo',
				''
			]
		}
	].forEach( ( { name, spec, expected } ) =>
		QUnit.test( 'getAllParametersOrdered: ' + name, ( assert ) => {
			const template = newTemplateModel();

			if ( spec ) {
				template.getSpec().setTemplateData( spec );
			}

			assert.deepEqual( template.getAllParametersOrdered(), expected );
		} )
	);

	QUnit.test( 'same documented parameter used with different aliases', ( assert ) => {
		const transclusion = new ve.dm.MWTransclusionModel();
		const template = ve.dm.MWTemplateModel.newFromData( transclusion, {
			target: {},
			params: { d: {}, c: {}, b: {}, a: {} }
		} );
		template.getSpec().setTemplateData( {
			params: { a: { aliases: [ 'b', 'c' ] }, e: {} }
		} );

		assert.deepEqual( template.getOrderedParameterNames(), [ 'a', 'c', 'b', 'd' ] );
		assert.deepEqual( template.getAllParametersOrdered(), [ 'a', 'c', 'b', 'e', 'd' ] );
	} );

	[
		[ 'a', 'b', 'Template:A', 'prefers .wt when it is a valid title' ],
		[ '{{a}}', 'subst:b', 'subst:b', 'falls back to unmodified getTitle' ],
		[ 'subst:a', 'b', 'Template:A', 'strips subst:' ],
		[ 'safesubst:a', 'b', 'Template:A', 'strips safesubst:' ],
		[ ' SUBST: a', 'b', 'Template:A', 'ignores capitalization and whitespace' ],
		[ 'subst :a', 'b', 'Template:Subst :a', 'leaves bad whitespace untouched' ],
		[ 'int:a', 'b', 'Template:Int:a', 'leaves other prefixes untouched' ]
	].forEach( ( [ wt, href, expected, message ] ) =>
		QUnit.test( 'getTemplateDataQueryTitle: ' + message, ( assert ) => {
			const transclusion = { nextUniquePartId: () => '' },
				data = { target: { wt, href } },
				model = ve.dm.MWTemplateModel.newFromData( transclusion, data );

			assert.strictEqual( model.getTemplateDataQueryTitle(), expected );
		} )
	);

	[
		[ {}, false, 'no parameters' ],
		[ { p1: {}, p2: { wt: 'foo' } }, true, 'multiple parameters' ],
		[ { p1: {} }, false, 'undefined' ],
		[ { p1: { wt: null } }, false, 'null' ],
		[ { p1: { wt: '' } }, false, 'empty string' ],
		[ { p1: { wt: ' ' } }, true, 'space' ],
		[ { p1: { wt: '0' } }, true, '0' ],
		[ { p1: { wt: '\nfoo' } }, true, 'newline' ]
	].forEach( ( [ params, expected, message ] ) =>
		QUnit.test( 'containsValuableData: ' + message, ( assert ) => {
			const transclusion = { nextUniquePartId: () => '' },
				data = { target: {}, params },
				model = ve.dm.MWTemplateModel.newFromData( transclusion, data );

			assert.strictEqual( model.containsValuableData(), expected );
		} )
	);

}
