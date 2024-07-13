/*!
 * VisualEditor DataModel Table Selection tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.TableSelection' );

/* Tests */

QUnit.test( 'Construction and getters (getDocument, getRanges, getOuterRanges, getTableNode)', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument( 'mergedCells' ),
		tableNode = doc.getBranchNodeFromOffset( 1 ),
		tableRange = tableNode.getOuterRange(),
		cases = [
			{
				msg: 'single cell selection',
				selection: new ve.dm.TableSelection( tableRange, 1, 2 ),
				fromCol: 1,
				fromRow: 2,
				toCol: 1,
				toRow: 2,
				startCol: 1,
				startRow: 2,
				endCol: 1,
				endRow: 2,
				ranges: [
					new ve.Range( 65, 69 )
				],
				outerRanges: [
					new ve.Range( 64, 70 )
				]
			},
			{
				msg: 'multi cell selection',
				selection: new ve.dm.TableSelection( tableRange, 1, 2, 0, 1 ),
				fromCol: 1,
				fromRow: 2,
				toCol: 0,
				toRow: 1,
				startCol: 0,
				startRow: 1,
				endCol: 1,
				endRow: 2,
				ranges: [
					new ve.Range( 36, 39 ),
					new ve.Range( 41, 44 ),
					new ve.Range( 59, 63 ),
					new ve.Range( 65, 69 )
				],
				outerRanges: [
					new ve.Range( 35, 40 ),
					new ve.Range( 40, 45 ),
					new ve.Range( 58, 64 ),
					new ve.Range( 64, 70 )
				]
			},
			{
				msg: 'multi cell selection (expanded)',
				selection: ( function () {
					var sel = new ve.dm.TableSelection( tableRange, 1, 2, 0, 1 );
					sel = sel.expand( doc );
					return sel;
				}() ),
				fromCol: 2,
				fromRow: 2,
				toCol: 0,
				toRow: 1,
				startCol: 0,
				startRow: 1,
				endCol: 2,
				endRow: 2,
				ranges: [
					new ve.Range( 36, 39 ),
					new ve.Range( 41, 44 ),
					new ve.Range( 59, 63 ),
					new ve.Range( 65, 69 ),
					new ve.Range( 71, 75 )
				],
				outerRanges: [
					new ve.Range( 35, 40 ),
					new ve.Range( 40, 45 ),
					new ve.Range( 58, 64 ),
					new ve.Range( 64, 70 ),
					new ve.Range( 70, 76 )
				]
			}
		];

	cases.forEach( function ( caseItem ) {
		var selection = caseItem.selection;
		assert.strictEqual( selection.getTableNode( doc ), tableNode, 'getTableNode' );
		assert.strictEqual( selection.getName(), 'table', 'getName' );
		assert.deepEqual( selection.getRanges( doc ), caseItem.ranges, caseItem.msg + ': getRanges' );
		assert.deepEqual( selection.getOuterRanges( doc ), caseItem.outerRanges, caseItem.msg + ': getOuterRanges' );
		assert.strictEqual( selection.fromCol, caseItem.fromCol, caseItem.msg + ': fromCol set' );
		assert.strictEqual( selection.fromRow, caseItem.fromRow, caseItem.msg + ': fromRow set' );
		assert.strictEqual( selection.toCol, caseItem.toCol, caseItem.msg + ': toCol set' );
		assert.strictEqual( selection.toRow, caseItem.toRow, caseItem.msg + ': toRow set' );
		assert.strictEqual( selection.startCol, caseItem.startCol, caseItem.msg + ': startCol set' );
		assert.strictEqual( selection.startRow, caseItem.startRow, caseItem.msg + ': startRow set' );
		assert.strictEqual( selection.endCol, caseItem.endCol, caseItem.msg + ': endCol set' );
		assert.strictEqual( selection.endRow, caseItem.endRow, caseItem.msg + ': endRow set' );
	} );

} );

QUnit.test( 'Basic methods (expand, collapse*, getRange(s), isCollased, isSingleCell, equals, isNull, isFullRow/Col, getRow/ColCount)', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument( 'mergedCells' ),
		tableRange = doc.getBranchNodeFromOffset( 1 ).getOuterRange(),
		selection = new ve.dm.TableSelection( tableRange, 1, 2, 0, 1 ),
		startSelection = new ve.dm.TableSelection( tableRange, 0, 1 ),
		endSelection = new ve.dm.TableSelection( tableRange, 2, 2 ),
		mergedSingleCell = new ve.dm.TableSelection( tableRange, 1, 3, 3, 5 ),
		largeSelection = new ve.dm.TableSelection( tableRange, 0, 0, 3, 6 ),
		otherTableSelection = new ve.dm.TableSelection( new ve.Range( 100, 200 ), 0, 0, 3, 6 );

	selection = selection.expand( doc );
	mergedSingleCell = mergedSingleCell.expand( doc );

	assert.deepEqual( selection.collapseToStart(), startSelection, 'collapseToStart' );
	assert.deepEqual( selection.collapseToEnd(), endSelection, 'collapseToEnd' );
	assert.deepEqual( selection.collapseToFrom(), endSelection, 'collapseToFrom' );
	assert.deepEqual( selection.collapseToTo(), startSelection, 'collapseToTo' );
	assert.strictEqual( selection.isCollapsed(), false, 'multi cell is not collapsed' );
	assert.strictEqual( startSelection.isCollapsed(), false, 'single cell is not collapsed' );
	assert.strictEqual( selection.isSingleCell( doc ), false, 'multi cell selection is not a single cell' );
	assert.strictEqual( startSelection.isSingleCell( doc ), true, 'single cell selection is a single cell' );
	assert.strictEqual( mergedSingleCell.isSingleCell( doc ), true, 'merged single cell selection is a single cell' );
	assert.strictEqual( selection.equals( selection ), true, 'equals' );
	assert.strictEqual( selection.isNull(), false, 'not null' );
	assert.strictEqual( largeSelection.getColCount(), 4, 'getColCount' );
	assert.strictEqual( largeSelection.getRowCount(), 7, 'getRowCount' );
	assert.strictEqual( largeSelection.isFullCol( doc ), true, 'isFullCol' );
	assert.strictEqual( largeSelection.isFullRow( doc ), false, 'isFullRow' );

	var matrixCell = startSelection.getMatrixCells( doc )[ 0 ];
	assert.strictEqual( largeSelection.containsCell( matrixCell ), true, '[1,3;3,5] contains [0,1]' );
	assert.strictEqual( endSelection.containsCell( matrixCell ), false, '[2,2] doesn\'t contain [0,1]' );
	assert.strictEqual( otherTableSelection.containsCell( matrixCell ), false, 'Selection in other table doesn\'t contain cell' );
} );

QUnit.test( 'Factory methods & serialization (newFromJSON, toJSON, getDescription)', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument( 'mergedCells' ),
		tableRange = doc.getBranchNodeFromOffset( 1 ).getOuterRange(),
		selection = new ve.dm.TableSelection( tableRange, 1, 2, 3, 4 ),
		json = {
			type: 'table',
			tableRange: tableRange,
			fromCol: 1,
			fromRow: 2,
			toCol: 3,
			toRow: 4
		};

	assert.deepEqual( selection.toJSON(), json, 'toJSON' );

	assert.deepEqual(
		ve.dm.Selection.static.newFromJSON( JSON.stringify( json ) ),
		selection,
		'newFromJSON'
	);
	assert.strictEqual( selection.getDescription(), 'Table: 0 - 171, c1 r2 - c3 r4', 'getDescription' );
} );

// TODO: getMatrixCells
// TODO: translateByTransaction
// TODO: newFromAdjustment
