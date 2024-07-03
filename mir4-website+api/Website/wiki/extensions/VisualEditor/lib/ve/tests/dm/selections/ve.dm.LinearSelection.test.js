/*!
 * VisualEditor DataModel Linear Selection tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.LinearSelection' );

/* Tests */

QUnit.test( 'Construction and getters (getRange(s))', function ( assert ) {
	var range = new ve.Range( 200, 100 ),
		selection = new ve.dm.LinearSelection( range );

	assert.equalRange( selection.getRange(), range, 'getRange' );
	assert.deepEqual( selection.getRanges(), [ range ], 'getRanges' );
	assert.strictEqual( selection.getName(), 'linear', 'getName' );
} );

QUnit.test( 'Basic methods (collapse*, isCollased, equals, isNull)', function ( assert ) {
	var range = new ve.Range( 200, 100 ),
		selection = new ve.dm.LinearSelection( range ),
		startSelection = new ve.dm.LinearSelection( new ve.Range( 100 ) ),
		endSelection = new ve.dm.LinearSelection( new ve.Range( 200 ) );

	assert.deepEqual( selection.collapseToStart(), startSelection, 'collapseToStart' );
	assert.deepEqual( selection.collapseToEnd(), endSelection, 'collapseToEnd' );
	assert.deepEqual( selection.collapseToFrom(), endSelection, 'collapseToFrom' );
	assert.deepEqual( selection.collapseToTo(), startSelection, 'collapseToTo' );
	assert.strictEqual( selection.isCollapsed(), false, '200-100 is not collapsed' );
	assert.strictEqual( startSelection.isCollapsed(), true, '100-100 is collapsed' );
	assert.strictEqual( selection.equals( selection ), true, 'equals' );
	assert.strictEqual( selection.isNull(), false, 'not null' );
} );

QUnit.test( 'Factory methods & serialization (newFromJSON, toJSON, getDescription)', function ( assert ) {
	var range = new ve.Range( 200, 100 ),
		selection = new ve.dm.LinearSelection( range );

	assert.deepEqual( selection.toJSON(), { type: 'linear', range: range }, 'toJSON' );
	assert.deepEqual(
		ve.dm.Selection.static.newFromJSON( JSON.stringify( { type: 'linear', range: range } ) ),
		selection,
		'newFromJSON'
	);
	assert.strictEqual( selection.getDescription(), 'Linear: 200 - 100', 'getDescription' );
} );

// TODO: translateByTransaction
