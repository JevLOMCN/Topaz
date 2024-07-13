/*!
 * VisualEditor UserInterface Actions TableAction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.TableAction' );

/* Tests */

QUnit.test( 'create / insert / mergeCells / delete / changeCellStyle / moveRelative', function ( assert ) {
	var tableCellTail = [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{ type: '/paragraph' },
			{ type: '/tableCell' }
		],
		tableHeader = [
			{
				type: 'tableCell',
				attributes: {
					colspan: 1,
					rowspan: 1,
					style: 'header'
				}
			}
		].concat( tableCellTail ),
		tableData = [
			{
				type: 'tableCell',
				attributes: {
					colspan: 1,
					rowspan: 1,
					style: 'data'
				}
			}
		].concat( tableCellTail ),
		cases = [
			{
				rangeOrSelection: new ve.Range( 0 ),
				method: 'create',
				args: [ {
					cols: 1,
					rows: 1,
					attributes: { sortable: true }
				} ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 0, 0 ].concat(
						[
							{ type: 'table', attributes: { sortable: true } },
							{ type: 'tableSection', attributes: { style: 'body' } },
							{ type: 'tableRow' }
						],
						tableData,
						[
							{ type: '/tableRow' },
							{ type: '/tableSection' },
							{ type: '/table' }
						]
					) );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 10 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				msg: 'create single cell table with attributes'
			},
			{
				rangeOrSelection: new ve.Range( 0 ),
				method: 'create',
				args: [ {
					cols: 1,
					rows: 1,
					caption: true
				} ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 0, 0 ].concat(
						[
							{ type: 'table' },
							{ type: 'tableCaption' },
							{ type: 'paragraph', internal: { generated: 'wrapper' } },
							{ type: '/paragraph' },
							{ type: '/tableCaption' },
							{ type: 'tableSection', attributes: { style: 'body' } },
							{ type: 'tableRow' }
						],
						tableData,
						[
							{ type: '/tableRow' },
							{ type: '/tableSection' },
							{ type: '/table' }
						]
					) );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 14 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				msg: 'create single cell table with caption'
			},
			{
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 5, 37 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				method: 'create',
				expectedData: function () {},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 5, 37 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				msg: 'create table with table selection is no-op'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				method: 'insert',
				args: [ 'col', 'after' ],
				expectedData: function () {},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'insert with linear selection is no-op'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				method: 'moveRelative',
				args: [ 'col', 'after' ],
				expectedData: function () {},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'moveRelative with linear selection is no-op'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				method: 'move',
				args: [ 'col', 0 ],
				expectedData: function () {},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'move with linear selection is no-op'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				method: 'delete',
				args: [ 'table' ],
				expectedData: function () {},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'delete with linear selection is no-op'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				method: 'delete',
				args: [ 'header' ],
				expectedData: function () {},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'changeCellStyle with linear selection is no-op'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				method: 'mergeCells',
				expectedData: function () {},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'mergeCells with linear selection is no-op'
			},
			{
				rangeOrSelection: new ve.Range( 0 ),
				method: 'create',
				args: [ {
					cols: 3,
					rows: 2,
					header: true
				} ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 0, 0 ].concat(
						[
							{ type: 'table' },
							{ type: 'tableSection', attributes: { style: 'body' } },
							{ type: 'tableRow' }
						],
						tableHeader,
						tableHeader,
						tableHeader,
						[
							{ type: '/tableRow' },
							{ type: 'tableRow' }
						],
						tableData,
						tableData,
						tableData,
						[
							{ type: '/tableRow' },
							{ type: 'tableRow' }
						],
						tableData,
						tableData,
						tableData,
						[
							{ type: '/tableRow' },
							{ type: '/tableSection' },
							{ type: '/table' }
						]
					) );
				},
				msg: 'create small table with header'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 5,
					fromRow: 1,
					toCol: 5,
					toRow: 1
				},
				method: 'insert',
				args: [ 'col', 'after' ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 168, 0 ].concat( tableData ) );
					data.splice.apply( data, [ 130, 0 ].concat( tableData ) );
					data.splice.apply( data, [ 116, 0 ].concat( tableData ) );
					data.splice.apply( data, [ 102, 0 ].concat( tableData ) );
					data.splice.apply( data, [ 82, 0 ].concat( tableData ) );
					data.splice.apply( data, [ 56, 0 ].concat( tableData ) );
					data.splice.apply( data, [ 33, 0 ].concat( tableData ) );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 199 ),
					fromCol: 5,
					fromRow: 1,
					toCol: 5,
					toRow: 1
				},
				msg: 'insert column at end of table'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 3,
					fromRow: 0,
					toCol: 3,
					toRow: 0
				},
				method: 'insert',
				args: [ 'col', 'before' ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 150, 0 ].concat( tableData ) );
					data[ 90 ].attributes.colspan = 4;
					data.splice.apply( data, [ 76, 0 ].concat( tableData ) );
					data.splice.apply( data, [ 45, 0 ].concat( tableData ) );
					data.splice.apply( data, [ 18, 0 ].concat( tableData ) );
				},
				msg: 'insert column in middle of table'
			},
			{
				html: '<table><tr></tr><tr><td>A</td><td>B</td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 18 ),
					fromCol: 0,
					fromRow: 1,
					toCol: 0,
					toRow: 1
				},
				method: 'insert',
				args: [ 'col', 'after' ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 10, 0 ].concat( tableData ) );
				},
				msg: 'insert column at middle of table with sparse row'
			},
			{
				html: '<table><tr></tr><tr><td>A</td><td>B</td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 18 ),
					fromCol: 1,
					fromRow: 1,
					toCol: 1,
					toRow: 1
				},
				method: 'insert',
				args: [ 'col', 'after' ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 15, 0 ].concat( tableData ) );
				},
				msg: 'insert column at end of table with sparse row'
			},
			{
				html: '<table><tr><td rowspan="3">a</td><td>b</td><td>c</td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 21 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 2
				},
				method: 'insert',
				args: [ 'row', 'after' ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 19, 0 ].concat(
						{ type: 'tableRow' },
						tableData,
						{ type: '/tableRow' }
					) );
				},
				msg: 'insert row after row containing cell with excessive rowspan'
			},
			{
				html: '<table><tr><td rowspan="3">a</td><td>b</td><td>c</td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 21 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 2
				},
				method: 'insert',
				args: [ 'col', 'after' ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 8, 0 ].concat( tableData ) );
				},
				msg: 'insert column after row containing cell with excessive rowspan'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 0,
					fromRow: 6,
					toCol: 0,
					toRow: 6
				},
				method: 'insert',
				args: [ 'row', 'after' ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 169, 0 ].concat(
						[
							{ type: 'tableRow' }
						],
						tableData,
						tableData,
						tableData,
						tableData,
						tableData,
						tableData,
						[
							{ type: '/tableRow' }
						] )
					);
				},
				msg: 'insert row at end of table'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 0,
					fromRow: 3,
					toCol: 0,
					toRow: 3
				},
				method: 'insert',
				args: [ 'row', 'before' ],
				expectedData: function ( data ) {
					data[ 45 ].attributes.rowspan = 5;
					data.splice.apply( data, [ 83, 0 ].concat(
						[
							{ type: 'tableRow' }
						],
						tableData,
						tableData,
						tableData,
						tableData,
						tableData,
						[
							{ type: '/tableRow' }
						] )
					);
				},
				msg: 'insert row in middle of table'
			},
			{
				html: '<table><tr><th>a</th><td>b</td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 16 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				method: 'insert',
				args: [ 'row', 'after' ],
				expectedData: function ( data ) {
					data.splice.apply( data, [ 14, 0 ].concat(
						[
							{ type: 'tableRow' }
						],
						tableHeader,
						tableData,
						[
							{ type: '/tableRow' }
						] )
					);
				},
				msg: 'insert row of mixed styles'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 2,
					toRow: 1
				},
				method: 'mergeCells',
				args: [],
				expectedData: function ( data ) {
					data[ 3 ].attributes.colspan = 3;
					data[ 3 ].attributes.rowspan = 2;
					data.splice( 40, 5 );
					data.splice( 35, 5 );
					data.splice( 13, 5 );
					data.splice( 8, 5 );
				},
				msg: 'merge cells'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 1,
					fromRow: 3,
					toCol: 3,
					toRow: 5
				},
				method: 'mergeCells',
				args: [],
				expectedData: function ( data ) {
					data[ 90 ].attributes.colspan = 1;
					data[ 90 ].attributes.rowspan = 1;
					data.splice.apply( data, [ 124, 0 ].concat( tableData, tableData, tableData ) );
					data.splice.apply( data, [ 110, 0 ].concat( tableData, tableData, tableData ) );
					data.splice.apply( data, [ 96, 0 ].concat( tableData, tableData ) );
				},
				msg: 'unmerge cells'
			},
			{
				html: '<table><tr><td rowspan="3">a</td><td>b</td><td>c</td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 21 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 1,
					toRow: 2
				},
				method: 'mergeCells',
				expectedData: function ( data ) {
					data[ 3 ].attributes.colspan = 1;
					data[ 3 ].attributes.rowspan = 1;
					data.splice( 8, 5 );
				},
				msg: 'unmerge cell with excessive rowspan'
			},
			{
				html: '<table><tr><td rowspan="3">a</td><td>b</td><td>c</td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 21 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 2
				},
				method: 'mergeCells',
				expectedData: function ( data ) {
					data[ 3 ].attributes.colspan = 1;
					data[ 3 ].attributes.rowspan = 1;
				},
				msg: 'merge cell with excessive rowspan'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 5,
					toRow: 5
				},
				method: 'mergeCells',
				args: [],
				expectedData: function ( data ) {
					data[ 3 ].attributes.colspan = 6;
					data[ 3 ].attributes.rowspan = 1;
					data.splice( 8, 122 );
				},
				msg: 'merge full rows'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 3,
					toRow: 6
				},
				method: 'mergeCells',
				args: [],
				expectedData: function ( data ) {
					data[ 3 ].attributes.colspan = 1;
					data[ 3 ].attributes.rowspan = 7;
					data.splice( 132, 24 );
					data.splice( 118, 6 );
					data.splice( 104, 6 );
					data.splice( 84, 12 );
					data.splice( 58, 18 );
					data.splice( 35, 10 );
					data.splice( 8, 15 );
				},
				msg: 'merge full columns'
			},
			{
				html: '<table><tr><td></td><td>A</td></tr><tr><td></td><td></td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 25 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 1,
					toRow: 0
				},
				method: 'mergeCells',
				args: [],
				expectedData: function ( data ) {
					data.splice( 3, 4 );
					data[ 3 ].attributes.colspan = 2;
					data[ 3 ].attributes.rowspan = 1;
				},
				msg: 'merge when first cell is empty'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				method: 'delete',
				args: [ 'table' ],
				expectedData: function ( data ) {
					data.splice( 0, 171,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				msg: 'delete whole table'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 0,
					fromRow: 3,
					toCol: 0,
					toRow: 3
				},
				method: 'delete',
				args: [ 'row' ],
				expectedData: function ( data ) {
					data[ 90 ].attributes.rowspan = 2;
					data[ 45 ].attributes.rowspan = 3;
					data.splice(
						110,
						0,
						{
							type: 'tableCell',
							attributes: {
								colspan: 3,
								rowspan: 2,
								style: 'data'
							}
						},
						{ type: 'paragraph', internal: { generated: 'wrapper' } },
						'1', '6',
						{ type: '/paragraph' },
						{ type: '/tableCell' }
					);
					data.splice( 83, 20 );
				},
				expectedRangeOrSelection: { type: 'null' },
				msg: 'delete row'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 3,
					fromRow: 0,
					toCol: 3,
					toRow: 0
				},
				method: 'delete',
				args: [ 'col' ],
				expectedData: function ( data ) {
					data[ 90 ].attributes.colspan = 2;
					data.splice( 150, 6 );
					data.splice( 18, 5 );
				},
				expectedRangeOrSelection: { type: 'null' },
				msg: 'delete column'
			},
			{
				html: ve.dm.example.mergedCellsHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 171 ),
					fromCol: 1,
					fromRow: 0,
					toCol: 2,
					toRow: 1
				},
				method: 'changeCellStyle',
				args: [ 'header' ],
				expectedData: function ( data ) {
					data[ 8 ].attributes.style = 'header';
					data[ 13 ].attributes.style = 'header';
					data[ 40 ].attributes.style = 'header';
				},
				msg: 'change style to header'
			},
			{
				html: ve.dm.example.annotatedTableHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 52 ),
					fromCol: 0,
					fromRow: 1,
					toCol: 0,
					toRow: 1
				},
				method: 'moveRelative',
				args: [ 'row', 'before' ],
				expectedData: function ( data ) {
					var row = data.splice( 25, 25 );
					data.splice.apply( data, [ 2, 0 ].concat( row ) );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 52 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				msg: 'move row before'
			},
			{
				html: ve.dm.example.annotatedTableHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 52 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				method: 'moveRelative',
				args: [ 'row', 'after' ],
				expectedData: function ( data ) {
					var row = data.splice( 25, 25 );
					data.splice.apply( data, [ 2, 0 ].concat( row ) );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 52 ),
					fromCol: 0,
					fromRow: 1,
					toCol: 0,
					toRow: 1
				},
				msg: 'move row after'
			},
			{
				html: ve.dm.example.annotatedTableHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 52 ),
					fromCol: 1,
					fromRow: 0,
					toCol: 1,
					toRow: 0
				},
				method: 'moveRelative',
				args: [ 'col', 'before' ],
				expectedData: function ( data ) {
					var cell2 = data.splice( 34, 8 ),
						cell1 = data.splice( 10, 7 );

					data.splice.apply( data, [ 26 - cell1.length, 0 ].concat( cell2 ) );
					data.splice.apply( data, [ 3, 0 ].concat( cell1 ) );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 52 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				msg: 'move column before'
			},
			{
				html: ve.dm.example.annotatedTableHtml,
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 52 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				},
				method: 'moveRelative',
				args: [ 'col', 'after' ],
				expectedData: function ( data ) {
					var cell2 = data.splice( 26, 8 ),
						cell1 = data.splice( 3, 7 );

					data.splice.apply( data, [ 42 - cell1.length - cell2.length, 0 ].concat( cell2 ) );
					data.splice.apply( data, [ 17 - cell1.length, 0 ].concat( cell1 ) );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 52 ),
					fromCol: 1,
					fromRow: 0,
					toCol: 1,
					toRow: 0
				},
				msg: 'move column after'
			},
			{
				html: '<table><tr></tr><tr><td>A</td></tr></table>',
				rangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 13 ),
					fromCol: 0,
					fromRow: 1,
					toCol: 0,
					toRow: 1
				},
				method: 'moveRelative',
				args: [ 'row', 'before' ],
				expectedData: function ( data ) {
					var row = data.splice( 4, 7 );
					data.splice.apply( data, [ 2, 0 ].concat( row ) );
				},
				msg: 'move row adjacent to sparse row'
			}
		];

	cases.forEach( function ( caseItem ) {
		ve.test.utils.runActionTest(
			'table', assert, caseItem.html, false, caseItem.method, caseItem.args, caseItem.rangeOrSelection, caseItem.msg,
			{
				expectedData: caseItem.expectedData,
				expectedRangeOrSelection: caseItem.expectedRangeOrSelection
			}
		);
	} );
} );
