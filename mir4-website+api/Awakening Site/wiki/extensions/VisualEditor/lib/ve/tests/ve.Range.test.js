/*!
 * VisualEditor Range tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.Range' );

/* Tests */

QUnit.test( 'Basic usage (isCollapsed, isBackwards, getLength, equals, equalsSelection, containsOffset, containsRange, touchesRange)', function ( assert ) {
	var range = new ve.Range( 100, 200 );

	assert.strictEqual( range.isCollapsed(), false, 'forwards range is not collapsed' );
	assert.strictEqual( range.isBackwards(), false, 'forwards range is not backwards' );
	assert.strictEqual( range.getLength(), 100, 'forwards range has length 100' );
	assert.strictEqual( range.equals( new ve.Range( 100, 200 ) ), true, 'equals matches identical range' );
	assert.strictEqual( range.equals( new ve.Range( 200, 100 ) ), false, 'equals doesn\'t match reverse range' );
	assert.strictEqual( range.equalsSelection( new ve.Range( 200, 100 ) ), true, 'equalsSelection matches reverse range' );
	assert.strictEqual( range.containsOffset( 99 ), false, 'doesn\'t contain 99' );
	assert.strictEqual( range.containsOffset( 100 ), true, 'contains 100' );
	assert.strictEqual( range.containsOffset( 199 ), true, 'contains 199' );
	assert.strictEqual( range.containsOffset( 200 ), false, 'doesn\'t contain 200' );
	assert.strictEqual( range.containsRange( new ve.Range( 99, 100 ) ), false, 'doesn\'t contain 99, 100' );
	assert.strictEqual( range.containsRange( new ve.Range( 100, 199 ) ), true, 'contains 101, 199' );
	assert.strictEqual( range.containsRange( range ), true, 'contains itself' );
	assert.strictEqual( range.containsRange( new ve.Range( 100, 201 ) ), false, 'doesn\'t contain 100, 201' );

	range = new ve.Range( 200, 100 );
	assert.strictEqual( range.isCollapsed(), false, 'backwards range is not collapsed' );
	assert.strictEqual( range.isBackwards(), true, 'backwards range is backwards' );
	assert.strictEqual( range.getLength(), 100, 'backwards range has length 100' );
	assert.strictEqual( range.containsOffset( 99 ), false, 'doesn\'t contain 99' );
	assert.strictEqual( range.containsOffset( 100 ), true, 'contains 100' );
	assert.strictEqual( range.containsOffset( 199 ), true, 'contains 199' );
	assert.strictEqual( range.containsOffset( 200 ), false, 'doesn\'t contain 200' );

	assert.strictEqual( range.containsRange( new ve.Range( 99, 100 ) ), false, 'doesn\'t contain 99, 100' );
	assert.strictEqual( range.containsRange( new ve.Range( 100, 199 ) ), true, 'contains 101, 199' );
	assert.strictEqual( range.containsRange( range ), true, 'contains itself' );
	assert.strictEqual( range.containsRange( new ve.Range( 100, 201 ) ), false, 'doesn\'t contain 100, 201' );

	assert.strictEqual( range.touchesRange( new ve.Range( 98, 99 ) ), false, 'doesn\'t touch 98, 99' );
	assert.strictEqual( range.touchesRange( new ve.Range( 203, 201 ) ), false, 'doesn\'t touch 203, 201' );
	assert.strictEqual( range.touchesRange( new ve.Range( 98, 100 ) ), true, 'touches 98, 100' );
	assert.strictEqual( range.touchesRange( new ve.Range( 200, 201 ) ), true, 'touches 200, 201' );
	assert.strictEqual( range.touchesRange( new ve.Range( 150, 98 ) ), true, 'touches 150, 98' );
	assert.strictEqual( range.touchesRange( new ve.Range( 0, 300 ) ), true, 'touches 0, 300' );
	assert.strictEqual( range.touchesRange( range ), true, 'returns true when passed itself' );

	assert.strictEqual( range.overlapsRange( new ve.Range( 99, 100 ) ), false, 'doesn\'t overlap 99, 100' );
	assert.strictEqual( range.overlapsRange( new ve.Range( 202, 200 ) ), false, 'doesn\'t overlap 202, 200' );
	assert.strictEqual( range.overlapsRange( new ve.Range( 99, 101 ) ), true, 'overlaps 99, 101' );
	assert.strictEqual( range.overlapsRange( new ve.Range( 199, 201 ) ), true, 'overlaps 199, 201' );
	assert.strictEqual( range.overlapsRange( new ve.Range( 150, 98 ) ), true, 'overlaps 150, 98' );
	assert.strictEqual( range.overlapsRange( new ve.Range( 0, 300 ) ), true, 'overlaps 0, 300' );
	assert.strictEqual( range.overlapsRange( range ), true, 'returns true when passed itself' );

	range = new ve.Range( 100 );
	assert.strictEqual( range.isCollapsed(), true, 'collapsed range is collapsed' );
	assert.strictEqual( range.isBackwards(), false, 'collapsed range is not backwards' );
	assert.strictEqual( range.getLength(), 0, 'collapsed range has zero length' );

} );

QUnit.test( 'Modification (flip, truncate, expand, translate)', function ( assert ) {
	var range = new ve.Range( 100, 200 );

	assert.equalRange( range.flip(), new ve.Range( 200, 100 ), 'flip reverses the range' );
	assert.equalRange( range.flip().flip(), range, 'double flip does nothing' );

	assert.equalRange( range.truncate( 50 ), new ve.Range( 100, 150 ), 'truncate 50' );
	assert.equalRange( range.truncate( 150 ), range, 'truncate 150 does nothing' );
	assert.equalRange( range.truncate( -50 ), new ve.Range( 150, 200 ), 'truncate -50' );
	assert.equalRange( range.truncate( -150 ), range, 'truncate -150 does nothing' );

	assert.equalRange( range.expand( new ve.Range( 150, 250 ) ), new ve.Range( 100, 250 ), 'overlapping expand to right' );
	assert.equalRange( range.expand( new ve.Range( 250 ) ), new ve.Range( 100, 250 ), 'non-overlapping expand to right' );
	assert.equalRange( range.expand( new ve.Range( 250, 150 ) ), new ve.Range( 100, 250 ), 'backwards overlapping expand to right' );
	assert.equalRange( range.expand( new ve.Range( 50, 150 ) ), new ve.Range( 50, 200 ), 'overlapping expand to left' );
	assert.equalRange( range.expand( new ve.Range( 50 ) ), new ve.Range( 50, 200 ), 'non-overlapping expand to left' );
	assert.equalRange( range.expand( new ve.Range( 150, 50 ) ), new ve.Range( 50, 200 ), 'backwards overlapping expand to left' );

	assert.equalRange( range.translate( 10 ), new ve.Range( 110, 210 ), 'translate 10' );
	assert.equalRange( range.translate( -10 ), new ve.Range( 90, 190 ), 'translate -10' );

	assert.strictEqual( range.flip().expand( new ve.Range( 250 ) ).isBackwards(), true, 'expands preserves backwards' );

} );

QUnit.test( 'Factory methods & serialization (newCoveringRange, newFromJSON, toJSON)', function ( assert ) {
	var range = new ve.Range( 100, 200 );

	assert.equalRange(
		ve.Range.static.newCoveringRange( [ range, new ve.Range( 150, 250 ) ] ),
		new ve.Range( 100, 250 ),
		'covering range'
	);
	assert.equalRange(
		ve.Range.static.newCoveringRange( [ range, new ve.Range( 150, 250 ) ], true ),
		new ve.Range( 250, 100 ),
		'backwards covering range'
	);

	assert.throws(
		function () {
			ve.Range.static.newCoveringRange( [], true );
		},
		Error,
		'throws an exception when providing an empty array of ranges'
	);

	assert.deepEqual( range.toJSON(), { type: 'range', from: 100, to: 200 }, 'toJSON' );
	assert.deepEqual( range.flip().toJSON(), { type: 'range', from: 200, to: 100 }, 'backwards toJSON' );

	assert.equalRange( ve.Range.static.newFromJSON( JSON.stringify( range.toJSON() ) ), range, 'newFromJSON' );
	assert.equalRange( ve.Range.static.newFromJSON( JSON.stringify( range.flip().toJSON() ) ), range.flip(), 'backwards newFromJSON' );

} );

// TODO: newFromHash
