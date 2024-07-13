/*!
 * VisualEditor ContentEditable table delete key down handler tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.TableDeleteKeyDownHandler', {
	// See https://github.com/platinumazure/eslint-plugin-qunit/issues/68
	// eslint-disable-next-line qunit/resolve-async
	beforeEach: function ( assert ) {
		var done = assert.async();
		return ve.init.platform.getInitializedPromise().then( done );
	}
} );

QUnit.test( 'special key down: table backspace/delete', function ( assert ) {
	var done = assert.async(),
		promise = Promise.resolve(),
		mergedCellsDoc = ve.dm.example.createExampleDocument( 'mergedCells' ),
		cases = [
			{
				htmlOrDoc: mergedCellsDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 2,
					toRow: 1
				},
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 4, 3,
						{ type: 'paragraph', internal: { generated: 'wrapper' } },
						{ type: '/paragraph' }
					);
					data.splice( 8, 3,
						{ type: 'paragraph', internal: { generated: 'wrapper' } },
						{ type: '/paragraph' }
					);
					data.splice( 12, 3,
						{ type: 'paragraph', internal: { generated: 'wrapper' } },
						{ type: '/paragraph' }
					);
					data.splice( 33, 3,
						{ type: 'paragraph', internal: { generated: 'wrapper' } },
						{ type: '/paragraph' }
					);
					data.splice( 37, 3,
						{ type: 'paragraph', internal: { generated: 'wrapper' } },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 166 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 2,
					toRow: 1
				},
				msg: 'Table cells emptied by backspace'
			}
		];

	cases.forEach( function ( caseItem ) {
		promise = promise.then( function () {
			return ve.test.utils.runSurfaceHandleSpecialKeyTest( assert, caseItem );
		} );
	} );

	promise.finally( () => done() );
} );
