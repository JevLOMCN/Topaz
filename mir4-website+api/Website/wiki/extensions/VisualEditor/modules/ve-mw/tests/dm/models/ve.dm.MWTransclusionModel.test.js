/*!
 * VisualEditor DataModel MWTransclusionModel tests.
 *
 * @copyright See AUTHORS.txt
 */

{
	QUnit.module( 've.dm.MWTransclusionModel', ve.test.utils.newMwEnvironment( {
		beforeEach() {
			// Mock XHR for mw.Api()
			this.server = this.sandbox.useFakeServer();
			this.server.respondImmediately = true;

		}
	} ) );

	const runAddPartTest = function ( assert, name, response, server, callback ) {
		const doc = ve.dm.Document.static.newBlankDocument(),
			transclusion = new ve.dm.MWTransclusionModel( doc ),
			part = ve.dm.MWTemplateModel.newFromName( transclusion, name ),
			done = assert.async();

		server.respondWith( [ 200, { 'Content-Type': 'application/json' }, JSON.stringify( response ) ] );

		transclusion.addPart( part )
			.then( () => {
				callback( transclusion );
			} )
			.always( () => {
				done();
			} );
	};

	QUnit.test( 'nextUniquePartId', function ( assert ) {
		const transclusion = new ve.dm.MWTransclusionModel();
		assert.strictEqual( transclusion.nextUniquePartId(), 'part_0' );
		assert.strictEqual( transclusion.nextUniquePartId(), 'part_1' );
		assert.strictEqual( transclusion.nextUniquePartId(), 'part_2' );
	} );

	QUnit.test( 'fetch template part data', function ( assert ) {
		const response = {
			batchcomplete: '',
			pages: {
				1331311: {
					title: 'Template:Test',
					description: { en: 'MWTransclusionModel template test' },
					params: {
						test: {
							label: { en: 'Test param' },
							type: 'string',
							description: { en: 'This is a test param' },
							required: false,
							suggested: false,
							example: null,
							deprecated: false,
							aliases: [],
							autovalue: null,
							default: null
						}
					},
					paramOrder: [ 'test' ],
					format: 'inline',
					sets: [],
					maps: {}
				}
			}
		};

		runAddPartTest( assert, 'Test', response, this.server, ( transclusion ) => {
			const parts = transclusion.getParts(),
				spec = parts[ 0 ].getSpec();

			assert.strictEqual( parts.length, 1 );
			assert.strictEqual( spec.getDescription( 'en' ), 'MWTransclusionModel template test' );
			assert.strictEqual( spec.getParameterLabel( 'test', 'en' ), 'Test param' );
		} );
	} );

	// T243868
	QUnit.test( 'fetch part data for parameterized template with no TemplateData', function ( assert ) {
		const response = {
			batchcomplete: '',
			pages: {
				1331311: {
					title: 'Template:NoData',
					notemplatedata: true,
					params: {
						foo: [],
						bar: []
					}
				}
			}
		};

		runAddPartTest( assert, 'NoData', response, this.server, ( transclusion ) => {
			const parts = transclusion.getParts(),
				spec = parts[ 0 ].getSpec();

			assert.strictEqual( parts.length, 1 );
			assert.deepEqual( spec.getKnownParameterNames(), [ 'foo', 'bar' ] );
		} );
	} );

	QUnit.test( 'fetch part data for template with no TemplateData and no params', function ( assert ) {
		const response = {
			batchcomplete: '',
			pages: {
				1331311: {
					title: 'Template:NoParams',
					notemplatedata: true,
					params: []
				}
			}
		};

		runAddPartTest( assert, 'NoParams', response, this.server, ( transclusion ) => {
			const parts = transclusion.getParts(),
				spec = parts[ 0 ].getSpec();

			assert.strictEqual( parts.length, 1 );
			assert.deepEqual( spec.getKnownParameterNames(), [] );
		} );
	} );
}
