/*!
 * VisualEditor UserInterface FragmentInspector tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.FragmentInspector' );

/* Tests */

ve.test.utils.runFragmentInspectorTests = function ( surface, assert, cases ) {
	var promise = Promise.resolve();

	surface.getView().showSelectionState = function () {};

	cases.forEach( function ( caseItem ) {
		promise = promise.then( function () {
			return surface.context.inspectors.getWindow( caseItem.name ).then( function ( inspector ) {
				var surfaceModel = surface.getModel(),
					linearData = ve.copy( surfaceModel.getDocument().getFullData() );

				surfaceModel.setLinearSelection( caseItem.range );
				var setupData = ve.extendObject( { surface: surface, fragment: surfaceModel.getFragment() }, caseItem.setupData );
				return inspector.setup( setupData ).then( function () {
					return inspector.ready( setupData ).then( function () {
						if ( caseItem.input ) {
							caseItem.input.call( inspector );
						}
						// TODO: Skips ActionProcess
						return inspector.teardown( caseItem.actionData || { action: 'done' } ).then( function () {
							assert.equalRange( surfaceModel.getSelection().getRange(), caseItem.expectedRange, caseItem.msg + ': range' );
							if ( caseItem.expectedData ) {
								caseItem.expectedData( linearData );
								assert.equalLinearData(
									surfaceModel.getDocument().getFullData(),
									linearData,
									caseItem.msg + ': data'
								);
							}
							if ( caseItem.expectedInsertionAnnotations ) {
								assert.deepEqual(
									surfaceModel.getInsertionAnnotations().getHashes(),
									caseItem.expectedInsertionAnnotations,
									caseItem.msg + ': insertion annotations'
								);
							}
							while ( surfaceModel.canUndo() ) {
								surfaceModel.undo();
							}
							// Insertion annotations are not cleared by undo
							surfaceModel.setInsertionAnnotations( null );
						} );
					} );
				} );
			} );
		} );
	} );
	return promise;
};

QUnit.test( 'Different selections and inputs', function ( assert ) {
	var done = assert.async(),
		surface = ve.test.utils.createSurfaceFromHtml( ve.dm.example.singleLine`
			<p>Foo <a href="bar">bar</a> baz  x</p>
			<p><!-- comment --> comment</p>
			<p>Fo<a href="bar">o bar</a></p>
		` ),
		fooHash = 'hd5a13e54366d44db',
		barHash = 'h071cb84c069d07a4',
		quuxHash = 'hb085ebec56a162a4',
		cases = [
			{
				msg: 'Collapsed selection expands to word',
				name: 'link',
				range: new ve.Range( 2 ),
				expectedRange: new ve.Range( 1, 4 ),
				expectedData: function ( data ) {
					data.splice(
						1, 3,
						[ 'F', [ fooHash ] ],
						[ 'o', [ fooHash ] ],
						[ 'o', [ fooHash ] ]
					);
				}
			},
			{
				msg: 'Collapsed selection in word (noExpand)',
				name: 'link',
				range: new ve.Range( 2 ),
				setupData: { noExpand: true },
				expectedRange: new ve.Range( 2 ),
				expectedData: function () {}
			},
			{
				msg: 'Cancel restores original data & selection',
				name: 'link',
				range: new ve.Range( 2 ),
				expectedRange: new ve.Range( 2 ),
				expectedData: function () {},
				actionData: {}
			},
			{
				msg: 'Collapsed selection inside existing link',
				name: 'link',
				range: new ve.Range( 6 ),
				expectedRange: new ve.Range( 5, 8 ),
				expectedData: function () {}
			},
			{
				msg: 'Selection inside existing link',
				name: 'link',
				range: new ve.Range( 6, 7 ),
				expectedRange: new ve.Range( 5, 8 ),
				expectedData: function () {}
			},
			{
				msg: 'Selection spanning existing link',
				name: 'link',
				range: new ve.Range( 6, 10 ),
				expectedRange: new ve.Range( 5, 10 ),
				expectedData: function ( data ) {
					data.splice(
						8, 2,
						[ ' ', [ barHash ] ],
						[ 'b', [ barHash ] ]
					);
				}
			},
			{
				msg: 'Collapsed selection inside a word that is partially linked',
				name: 'link',
				range: new ve.Range( 30 ),
				expectedRange: new ve.Range( 29, 36 ),
				expectedData: function ( data ) {
					data.splice(
						29, 2,
						[ 'F', [ barHash ] ],
						[ 'o', [ barHash ] ]
					);
				}
			},
			{
				msg: 'Selection with whitespace is trimmed',
				name: 'link',
				range: new ve.Range( 1, 5 ),
				expectedRange: new ve.Range( 1, 4 )
			},
			{
				msg: 'Link insertion',
				name: 'link',
				range: new ve.Range( 13 ),
				input: function () {
					this.annotationInput.getTextInputWidget().setValue( 'quux' );
				},
				expectedRange: new ve.Range( 17 ),
				expectedData: function ( data ) {
					data.splice(
						13, 0,
						[ 'q', [ quuxHash ] ],
						[ 'u', [ quuxHash ] ],
						[ 'u', [ quuxHash ] ],
						[ 'x', [ quuxHash ] ]
					);
				}
			},
			{
				msg: 'Link insertion with no input is no-op',
				name: 'link',
				range: new ve.Range( 13 ),
				expectedRange: new ve.Range( 13 ),
				expectedData: function () {}
			},
			{
				msg: 'Link modified',
				name: 'link',
				range: new ve.Range( 5, 8 ),
				input: function () {
					this.annotationInput.getTextInputWidget().setValue( 'quux' );
				},
				expectedRange: new ve.Range( 5, 8 ),
				expectedData: function ( data ) {
					data.splice(
						5, 3,
						[ 'b', [ quuxHash ] ],
						[ 'a', [ quuxHash ] ],
						[ 'r', [ quuxHash ] ]
					);
				}
			},
			{
				msg: 'Link removed (clear input)',
				name: 'link',
				range: new ve.Range( 5, 8 ),
				input: function () {
					this.annotationInput.getTextInputWidget().setValue( '' );
				},
				expectedRange: new ve.Range( 5, 8 ),
				expectedData: function ( data ) {
					data.splice(
						5, 3,
						'b', 'a', 'r'
					);
				}
			},
			{
				msg: 'Clear input & cancel is still a no-op',
				name: 'link',
				range: new ve.Range( 5, 8 ),
				input: function () {
					this.annotationInput.getTextInputWidget().setValue( '' );
				},
				expectedRange: new ve.Range( 5, 8 ),
				expectedData: function () {},
				actionData: {}
			},
			{
				msg: 'Comment change',
				name: 'comment',
				range: new ve.Range( 17, 19 ),
				input: function () {
					this.textWidget.setValue( 'new' );
				},
				expectedRange: new ve.Range( 17, 19 ),
				expectedData: function ( data ) {
					data.splice(
						17, 2,
						{
							type: 'comment',
							attributes: { text: ' new ' }
						},
						{ type: '/comment' }
					);
				}
			},
			{
				msg: 'Comment cancel',
				name: 'comment',
				range: new ve.Range( 17, 19 ),
				input: function () {
					this.textWidget.setValue( 'new' );
				},
				actionData: {},
				expectedRange: new ve.Range( 17, 19 ),
				expectedData: function () {}
			},
			{
				msg: 'Comment clear (empty input)',
				name: 'comment',
				range: new ve.Range( 17, 19 ),
				input: function () {
					this.textWidget.setValue( '' );
				},
				expectedRange: new ve.Range( 17 ),
				expectedData: function ( data ) {
					data.splice( 17, 2 );
				}
			},
			{
				msg: 'Comment delete (action button)',
				name: 'comment',
				range: new ve.Range( 17, 19 ),
				actionData: { action: 'remove' },
				expectedRange: new ve.Range( 17 ),
				expectedData: function ( data ) {
					data.splice( 17, 2 );
				}
			},
			{
				msg: 'Language annotation doesn\'t expand',
				name: 'language',
				range: new ve.Range( 2, 3 ),
				expectedRange: new ve.Range( 2, 3 ),
				expectedData: function ( data ) {
					data.splice(
						2, 1,
						[ 'o', [ 'h785e2045ecc398c1' ] ]
					);
				}
			},
			{
				msg: 'Collapsed language annotation becomes insertion annotation',
				name: 'language',
				range: new ve.Range( 13 ),
				expectedRange: new ve.Range( 13 ),
				expectedData: function () {},
				expectedInsertionAnnotations: [ 'h785e2045ecc398c1' ]
			}
		];

	ve.test.utils.runFragmentInspectorTests( surface, assert, cases ).finally( () => done() );
} );
