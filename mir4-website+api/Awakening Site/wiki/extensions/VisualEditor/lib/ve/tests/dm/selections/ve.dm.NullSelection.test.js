/*!
 * VisualEditor DataModel Null Selection tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.NullSelection' );

/* Tests */

QUnit.test( 'Construction and getters (getDocument, getRanges)', function ( assert ) {
	var selection = new ve.dm.NullSelection();

	assert.deepEqual( selection.getRanges(), [], 'getRanges' );
	assert.strictEqual( selection.getName(), 'null', 'getName' );
} );

QUnit.test( 'Basic methods (collapse*, isCollased, equals, isNull)', function ( assert ) {
	var selection = new ve.dm.NullSelection();

	assert.deepEqual( selection.collapseToStart(), selection, 'collapseToStart' );
	assert.deepEqual( selection.collapseToEnd(), selection, 'collapseToEnd' );
	assert.deepEqual( selection.collapseToFrom(), selection, 'collapseToFrom' );
	assert.deepEqual( selection.collapseToTo(), selection, 'collapseToTo' );
	assert.strictEqual( selection.isCollapsed(), true, 'isCollapsed' );
	assert.strictEqual( selection.equals( selection ), true, 'equals' );
	assert.strictEqual( selection.isNull(), true, 'null' );
} );

QUnit.test( 'Factory methods & serialization (newFromJSON, toJSON, getDescription)', function ( assert ) {
	var selection = new ve.dm.NullSelection();

	assert.deepEqual( selection.toJSON(), { type: 'null' }, 'toJSON' );
	assert.deepEqual(
		ve.dm.Selection.static.newFromJSON( JSON.stringify( { type: 'null' } ) ),
		selection,
		'newFromJSON'
	);
	assert.strictEqual( selection.getDescription(), 'Null', 'getDescription' );
} );

// TODO: translateByTransaction
