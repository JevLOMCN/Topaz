/*!
 * VisualEditor ContentEditable linear escape down handler tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.LinearEscapeKeyDownHandler', {
	// See https://github.com/platinumazure/eslint-plugin-qunit/issues/68
	// eslint-disable-next-line qunit/resolve-async
	beforeEach: function ( assert ) {
		var done = assert.async();
		return ve.init.platform.getInitializedPromise().then( done );
	}
} );

QUnit.test( 'special key down: linear escape', function ( assert ) {
	var done = assert.async(),
		promise = Promise.resolve(),
		noChange = function () {},
		mergedCellsDoc = ve.dm.example.createExampleDocument( 'mergedCells' ),
		cases = [
			{
				htmlOrDoc: mergedCellsDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 1,
					fromRow: 0
				},
				keys: [ 'ENTER', 'ESCAPE' ],
				expectedData: noChange,
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 1,
					fromRow: 0
				},
				msg: 'Escape to leave a table cell'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				keys: [ 'ESCAPE' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Escape elsewhere make no selection change'
			}
		];

	cases.forEach( function ( caseItem ) {
		promise = promise.then( function () {
			return ve.test.utils.runSurfaceHandleSpecialKeyTest( assert, caseItem );
		} );
	} );

	promise.finally( () => done() );
} );
