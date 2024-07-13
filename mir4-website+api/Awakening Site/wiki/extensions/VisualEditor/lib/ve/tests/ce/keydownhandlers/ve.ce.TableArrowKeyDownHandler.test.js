/*!
 * VisualEditor ContentEditable table arrow key down handler tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.TableArrowKeyDownHandler', {
	// See https://github.com/platinumazure/eslint-plugin-qunit/issues/68
	// eslint-disable-next-line qunit/resolve-async
	beforeEach: function ( assert ) {
		var done = assert.async();
		return ve.init.platform.getInitializedPromise().then( done );
	}
} );

QUnit.test( 'special key down: table arrow keys (complex movements)', function ( assert ) {
	var done = assert.async(),
		promise = Promise.resolve(),
		mergedCellsDoc = ve.dm.example.createExampleDocument( 'mergedCells' ),
		complexTableDoc = ve.dm.example.createExampleDocument( 'complexTable' ),
		cases = [
			{
				htmlOrDoc: mergedCellsDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 1,
					fromRow: 0
				},
				keys: [ 'ENTER', 'TAB' ],
				expectedRangeOrSelection: {
					type: 'linear',
					range: new ve.Range( 16 )
				},
				msg: 'Tab while in a table cell moves inside the next cell'
			},
			{
				htmlOrDoc: mergedCellsDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 1,
					fromRow: 0
				},
				keys: [ 'ENTER', 'SHIFT+TAB' ],
				expectedRangeOrSelection: {
					type: 'linear',
					range: new ve.Range( 6 )
				},
				msg: 'Shift+tab while in a table cell moves inside the previous cell'
			},
			{
				htmlOrDoc: ( function () {
					// Create a full surface and return the view, as the UI surface is required for the insert action
					var surface = ve.test.utils.createSurfaceFromDocument( ve.dm.example.createExampleDocument( 'mergedCells' ) );
					// Detach $blockers so selections aren't rendered, resulting in false code coverage
					surface.$blockers.detach();
					return surface.view;
				}() ),
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 5,
					fromRow: 6
				},
				keys: [ 'TAB' ],
				expectedData: function ( data ) {
					var tableCell = [
						{ type: 'tableCell', attributes: { style: 'data', colspan: 1, rowspan: 1 } },
						{ type: 'paragraph', internal: { generated: 'wrapper' } },
						{ type: '/paragraph' },
						{ type: '/tableCell' }
					];
					data.splice.apply( data, [ 169, 0 ].concat( { type: 'tableRow' }, tableCell, tableCell, tableCell, tableCell, tableCell, tableCell, { type: '/tableRow' } ) );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 197 ),
					fromCol: 0,
					fromRow: 7
				},
				msg: 'Tab at end of table inserts new row'
			},
			{
				htmlOrDoc: mergedCellsDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 5,
					fromRow: 6
				},
				keys: [ 'CTRL+TAB' ],
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 5,
					fromRow: 6
				},
				expectedDefaultPrevented: [ false ],
				msg: 'Ctrl+tab is always ignored'
			},
			{
				htmlOrDoc: mergedCellsDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 2,
					fromRow: 0
				},
				keys: [ 'UP' ],
				expectedRangeOrSelection: new ve.Range( 0 ),
				msg: 'Up in first row of table moves out of table'
			},
			{
				htmlOrDoc: mergedCellsDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 2,
					fromRow: 6
				},
				keys: [ 'DOWN' ],
				expectedRangeOrSelection: new ve.Range( 171 ),
				msg: 'Down in last row of table moves out of table'
			},
			{
				htmlOrDoc: complexTableDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 51 ),
					fromCol: 0,
					fromRow: 0
				},
				keys: [ 'SHIFT+TAB' ],
				expectedRangeOrSelection: new ve.Range( 3 ),
				msg: 'Shift+tab inside the first cell of a table moves into the caption'
			},
			{
				htmlOrDoc: complexTableDoc,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 51 ),
					fromCol: 0,
					fromRow: 0
				},
				keys: [ 'UP' ],
				expectedRangeOrSelection: new ve.Range( 3 ),
				msg: 'Up in first row of table moves into caption if present'
			}
		];

	cases.forEach( function ( caseItem ) {
		promise = promise.then( function () {
			return ve.test.utils.runSurfaceHandleSpecialKeyTest( assert, caseItem );
		} );
	} );

	promise.finally( () => done() );
} );

QUnit.test( 'special key down: table arrow keys (simple movements)', function ( assert ) {
	var fn = function () {},
		tables = {
			mergedCells: {
				view: ve.test.utils.createSurfaceViewFromDocument(
					ve.dm.example.createExampleDocument( 'mergedCells' )
				),
				tableRange: new ve.Range( 0, 171 )
			},
			rtl: {
				view: ve.test.utils.createSurfaceViewFromHtml( ve.dm.example.singleLine`
					<table style="direction: rtl;">
						<tr><td>1</td><td>2</td></tr>
						<tr><td>3</td><td>4</td></tr>
					</table>
				` ),
				tableRange: new ve.Range( 0, 28 )
			}
		},
		cases = [
			{
				msg: 'Simple move right',
				key: 'RIGHT',
				selectionOffsets: [ 0, 0 ],
				expectedSelectionOffsets: [ 1, 0 ]
			},
			{
				msg: 'Simple move right with tab',
				key: 'TAB',
				selectionOffsets: [ 0, 0 ],
				expectedSelectionOffsets: [ 1, 0 ]
			},
			{
				msg: 'Move right with tab at end wraps to next line',
				key: 'TAB',
				selectionOffsets: [ 5, 0 ],
				expectedSelectionOffsets: [ 0, 1 ]
			},
			{
				msg: 'Simple move end',
				key: 'END',
				selectionOffsets: [ 0, 0 ],
				expectedSelectionOffsets: [ 5, 0 ]
			},
			{
				msg: 'Simple move down',
				key: 'DOWN',
				selectionOffsets: [ 0, 0 ],
				expectedSelectionOffsets: [ 0, 1 ]
			},
			{
				msg: 'Simple move page down',
				key: 'PAGEDOWN',
				selectionOffsets: [ 0, 0 ],
				expectedSelectionOffsets: [ 0, 6 ]
			},
			{
				msg: 'Simple move left',
				key: 'LEFT',
				selectionOffsets: [ 5, 6 ],
				expectedSelectionOffsets: [ 4, 6 ]
			},
			{
				msg: 'Simple move left with shift+tab',
				key: 'TAB',
				shiftKey: true,
				selectionOffsets: [ 5, 6 ],
				expectedSelectionOffsets: [ 4, 6 ]
			},
			{
				msg: 'Move left with shift+tab at start wraps to previous line',
				key: 'TAB',
				shiftKey: true,
				selectionOffsets: [ 0, 1 ],
				expectedSelectionOffsets: [ 5, 0 ]
			},
			{
				msg: 'Simple move home',
				key: 'HOME',
				selectionOffsets: [ 5, 6 ],
				expectedSelectionOffsets: [ 0, 6 ]
			},
			{
				msg: 'Simple move page up',
				key: 'PAGEUP',
				selectionOffsets: [ 5, 6 ],
				expectedSelectionOffsets: [ 5, 0 ]
			},
			{
				msg: 'Move left at start',
				key: 'LEFT',
				selectionOffsets: [ 0, 0 ],
				expectedSelectionOffsets: [ 0, 0 ]
			},
			{
				msg: 'Move right at end',
				key: 'RIGHT',
				selectionOffsets: [ 5, 6 ],
				expectedSelectionOffsets: [ 5, 6 ]
			},
			{
				msg: 'Move from merged cell to merged cell',
				key: 'RIGHT',
				selectionOffsets: [ 1, 1, 2, 1 ],
				expectedSelectionOffsets: [ 3, 0, 3, 2 ]
			},
			{
				msg: 'Move from merged cell to non-merged cell (horizontal)',
				key: 'RIGHT',
				selectionOffsets: [ 4, 1, 4, 4 ],
				expectedSelectionOffsets: [ 5, 1, 5, 1 ]
			},
			{
				msg: 'Move from merged cell to non-merged cell (vertical)',
				key: 'DOWN',
				selectionOffsets: [ 1, 1, 2, 1 ],
				expectedSelectionOffsets: [ 1, 2, 1, 2 ]
			},
			{
				msg: 'Move from merged cell to non-merged cell (horizontal)',
				key: 'HOME',
				selectionOffsets: [ 4, 1, 4, 4 ],
				expectedSelectionOffsets: [ 0, 1, 0, 1 ]
			},
			{
				msg: 'Move from merged cell to non-merged cell (vertical)',
				key: 'PAGEDOWN',
				selectionOffsets: [ 1, 1, 2, 1 ],
				expectedSelectionOffsets: [ 1, 6, 1, 6 ]
			},
			{
				msg: 'Shift-select through merged cells',
				key: 'PAGEDOWN',
				shiftKey: true,
				selectionOffsets: [ 1, 0, 1, 0 ],
				expectedSelectionOffsets: [ 1, 0, 3, 6 ]
			},
			{
				msg: 'Expanded selection collapses',
				key: 'DOWN',
				selectionOffsets: [ 0, 0, 2, 0 ],
				expectedSelectionOffsets: [ 0, 1 ]
			},
			{
				msg: 'Left in RTL table increments column',
				table: 'rtl',
				key: 'LEFT',
				selectionOffsets: [ 0, 0 ],
				expectedSelectionOffsets: [ 1, 0 ]
			}
		];

	cases.forEach( function ( caseItem ) {
		var offsets = caseItem.selectionOffsets;
		var table = tables[ caseItem.table || 'mergedCells' ];
		var view = table.view;
		var model = view.getModel();
		model.setSelection( new ve.dm.TableSelection(
			table.tableRange, offsets[ 0 ], offsets[ 1 ], offsets[ 2 ], offsets[ 3 ] )
		);
		ve.ce.keyDownHandlerFactory.executeHandlersForKey(
			OO.ui.Keys[ caseItem.key ], model.getSelection().getName(), view,
			{
				keyCode: OO.ui.Keys[ caseItem.key ],
				shiftKey: !!caseItem.shiftKey,
				preventDefault: fn,
				stopPropagation: fn
			}
		);
		var selection = model.getSelection();
		var expectedSelectionOffsets = caseItem.expectedSelectionOffsets.length > 2 ?
			caseItem.expectedSelectionOffsets :
			caseItem.expectedSelectionOffsets.concat( caseItem.expectedSelectionOffsets );
		assert.deepEqual(
			[ selection.fromCol, selection.fromRow, selection.toCol, selection.toRow ],
			expectedSelectionOffsets,
			caseItem.msg
		);
	} );
} );
