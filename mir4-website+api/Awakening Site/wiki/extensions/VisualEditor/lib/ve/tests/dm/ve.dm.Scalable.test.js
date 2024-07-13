/*!
 * VisualEditor DataModel Scalable tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.Scalable' );

QUnit.test( 'construction/clone/getters/setters/toggleDefault/clearers', function ( assert ) {
	var eventEmitted = false,
		currentDimensions = {
			width: 300,
			height: 200
		},
		originalDimensions = {
			width: 600,
			height: 400
		},
		defaultDimensions = {
			width: 150,
			height: 50
		},
		minDimensions = {
			width: 1,
			height: 1
		},
		maxDimensions = {
			width: 1200,
			height: 800
		},
		scalable = new ve.dm.Scalable( {
			currentDimensions: currentDimensions,
			originalDimensions: originalDimensions,
			defaultDimensions: defaultDimensions,
			minDimensions: minDimensions,
			maxDimensions: maxDimensions
		} ),
		clone = scalable.clone();

	assert.deepEqual( scalable, clone, 'Clone is deepEqual' );
	assert.notStrictEqual( scalable, clone, 'Clone is not reference equal' );
	assert.deepEqual( scalable.getCurrentDimensions(), currentDimensions, 'getCurrentDimensions' );
	assert.deepEqual( scalable.getOriginalDimensions(), originalDimensions, 'getOriginalDimensions' );
	assert.deepEqual( scalable.getDefaultDimensions(), defaultDimensions, 'getDefaultDimensions' );
	assert.deepEqual( scalable.getMinDimensions(), minDimensions, 'getMinDimensions' );
	assert.deepEqual( scalable.getMaxDimensions(), maxDimensions, 'getMaxDimensions' );
	assert.strictEqual( scalable.getRatio(), 1.5, 'getRatio' );

	scalable.toggleDefault();
	assert.deepEqual( scalable.getCurrentDimensions(), scalable.getDefaultDimensions(), 'toggleDefault makes an image use default dimensions' );
	scalable.toggleDefault();
	assert.deepEqual( scalable.getCurrentDimensions(), scalable.getDefaultDimensions(), 'toggleDefault on an already-default image has no effect' );
	scalable.toggleDefault( false );
	assert.deepEqual( scalable.getCurrentDimensions(), scalable.getDefaultDimensions(), 'toggleDefault( false ) on an already-default image has no effect' );

	scalable.clearDefaultDimensions();
	assert.strictEqual( scalable.getDefaultDimensions(), null, 'clearDefaultDimensions' );

	scalable.on( 'defaultSizeChange', function () {
		eventEmitted = true;
	} );
	eventEmitted = false;
	scalable.clearDefaultDimensions();
	scalable.off( 'defaultSizeChange' );
	assert.strictEqual( eventEmitted, false, 'clearDefaultDimensions doesn\'t re-run' );

	scalable.clearOriginalDimensions();
	assert.strictEqual( scalable.getOriginalDimensions(), null, 'clearOriginalDimensions' );

	scalable.on( 'originalSizeChange', function () {
		eventEmitted = true;
	} );
	eventEmitted = false;
	scalable.clearOriginalDimensions();
	scalable.off( 'originalSizeChange' );
	assert.strictEqual( eventEmitted, false, 'clearOriginalDimensions doesn\'t re-run' );

	scalable.clearMinDimensions();
	assert.strictEqual( scalable.getMinDimensions(), null, 'clearMinDimensions' );

	scalable.on( 'minSizeChange', function () {
		eventEmitted = true;
	} );
	eventEmitted = false;
	scalable.clearMinDimensions();
	scalable.off( 'minSizeChange' );
	assert.strictEqual( eventEmitted, false, 'clearMinDimensions doesn\'t re-run' );

	scalable.clearMaxDimensions();
	assert.strictEqual( scalable.getMaxDimensions(), null, 'clearMaxDimensions' );

	scalable.on( 'maxSizeChange', function () {
		eventEmitted = true;
	} );
	eventEmitted = false;
	scalable.clearMaxDimensions();
	scalable.off( 'maxSizeChange' );
	assert.strictEqual( eventEmitted, false, 'clearMaxDimensions doesn\'t re-run' );

	assert.deepEqual( scalable.getBoundedDimensions( { width: 448, height: 317 }, 10 ), { width: 450, height: 300 }, 'getBoundedDimensions without bounds snapped to 10px grid' );

	scalable.fixedRatio = false;

	assert.deepEqual( scalable.getBoundedDimensions( { width: 448, height: 317 }, 10 ), { width: 450, height: 320 }, 'getBoundedDimensions without bounds or fixed ratio snapped to 10px grid' );

	scalable.fixedRatio = true;
	scalable.setRatioFromDimensions( scalable.getCurrentDimensions() );

	clone = scalable.clone();
	clone.setOriginalDimensions();
	clone.setDefaultDimensions();
	clone.setMinDimensions();
	clone.setMaxDimensions();

	assert.strictEqual( clone.getOriginalDimensions(), scalable.getOriginalDimensions(), 'setOriginalDimensions without values is a no-op' );
	assert.strictEqual( clone.getDefaultDimensions(), scalable.getDefaultDimensions(), 'setDefaultDimensions without values is a no-op' );
	assert.strictEqual( clone.getMinDimensions(), scalable.getMinDimensions(), 'setMinDimensions without values is a no-op' );
	assert.strictEqual( clone.getMaxDimensions(), scalable.getMaxDimensions(), 'setMaxDimensions without values is a no-op' );

	clone = scalable.clone();
	clone.setRatioFromDimensions();
	assert.strictEqual( clone.getRatio(), scalable.getRatio(), 'setRatioFromDimensions without values is a no-op' );

	scalable.setOriginalDimensions( { width: 300, height: 100 } );
	assert.strictEqual( scalable.getRatio(), 3, 'setOriginalDimensions overwrites the ratio if set' );

	scalable.fixedRatio = false;
	scalable.setOriginalDimensions( { width: 300, height: 200 } );
	assert.strictEqual( scalable.getRatio(), 3, 'setOriginalDimensions doesn\'t overwrite the ratio if not set' );

	scalable.fixedRatio = true;
	scalable.setOriginalDimensions( { width: 1, height: 1 } );
	assert.strictEqual( scalable.getRatio(), 1, 'setOriginalDimensions overwrites the ratio if set again' );

} );

QUnit.test( 'getBoundedDimensions/getCurrentScale/isCurrentDimensionsValid/isTooSmall/isTooLarge', function ( assert ) {
	var currentDimensions = {
			width: 300,
			height: 200
		},
		originalDimensions = {
			width: 600,
			height: 400
		},
		minDimensions = {
			width: 150,
			height: 100
		},
		maxDimensions = {
			width: 1200,
			height: 800
		},
		scalable = new ve.dm.Scalable( {
			currentDimensions: currentDimensions,
			originalDimensions: originalDimensions,
			minDimensions: minDimensions,
			maxDimensions: maxDimensions
		} );

	assert.deepEqual( scalable.getBoundedDimensions( { width: 600, height: 600 } ), { width: 600, height: 400 }, 'getBoundedDimensions' );
	assert.deepEqual( scalable.getBoundedDimensions( { width: 2000, height: 2000 } ), { width: 1200, height: 800 }, 'getBoundedDimensions beyond maxDimensions' );
	assert.deepEqual( scalable.getBoundedDimensions( { width: 30, height: 30 } ), { width: 150, height: 100 }, 'getBoundedDimensions beyond minDimensions' );
	assert.deepEqual( scalable.getBoundedDimensions( { width: 448, height: 317 }, 10 ), { width: 450, height: 300 }, 'getBoundedDimensions snapped to 10px grid' );

	scalable.fixedRatio = false;

	assert.strictEqual( scalable.getCurrentScale(), null, 'Scale is null when not fixed ratio' );

	assert.deepEqual( scalable.getBoundedDimensions( { width: 600, height: 600 } ), { width: 600, height: 600 }, 'getBoundedDimensions, no fixed ratio' );
	assert.deepEqual( scalable.getBoundedDimensions( { width: 448, height: 317 }, 10 ), { width: 450, height: 320 }, 'getBoundedDimensions snapped to 10px grid, no fixed ratio' );

	scalable.fixedRatio = true;

	assert.strictEqual( scalable.isCurrentDimensionsValid(), true, '300x200 are valid dimensions' );
	assert.strictEqual( scalable.getCurrentScale(), 0.5, '300x200 is scale of 0.5' );

	scalable.setCurrentDimensions( { width: 1200, height: 800 } );
	assert.strictEqual( scalable.getCurrentScale(), 2, '1200x800 is scale of 2' );

	scalable.setCurrentDimensions( { width: 1300, height: 810 } );
	assert.strictEqual( scalable.isCurrentDimensionsValid(), false, 'Too large dimensions are not valid' );
	assert.strictEqual( scalable.isTooSmall(), false, 'Too large dimensions are not too small' );
	assert.strictEqual( scalable.isTooLarge(), true, 'Too large dimensions are too large' );

	scalable.setCurrentDimensions( { width: 30, height: 20 } );
	assert.strictEqual( scalable.isCurrentDimensionsValid(), false, 'Too small dimensions are not valid' );
	assert.strictEqual( scalable.isTooSmall(), true, 'Too large dimensions are too small' );
	assert.strictEqual( scalable.isTooLarge(), false, 'Too large dimensions are not too large' );

} );

QUnit.test( 'isDefault/toggleDefault', function ( assert ) {
	var scalable = new ve.dm.Scalable( {
			isDefault: true
		} ),
		clone = scalable.clone();

	assert.deepEqual( scalable, clone, 'Clone is deepEqual even when config is sparse' );

	assert.strictEqual( scalable.isDefault(), true, 'isDefault' );
	scalable.toggleDefault();
	assert.strictEqual( scalable.isDefault(), false, 'toggleDefault changes true to false' );
	scalable.toggleDefault();
	assert.strictEqual( scalable.isDefault(), true, 'toggleDefault changes false to true' );
} );

QUnit.test( 'isDimensionsObjectValid', function ( assert ) {
	var cases = [
		{ dimensions: null, expected: false, msg: 'Null' },
		{ dimensions: { width: 200 }, expected: true, msg: 'Only width' },
		{ dimensions: { height: 200 }, expected: true, msg: 'Only height' },
		{ dimensions: {}, expected: false, msg: 'Empty object' },
		{ dimensions: { width: undefined, height: undefined }, expected: false, msg: 'Explicity undefined' }
	];

	cases.forEach( function ( caseItem ) {
		assert.strictEqual( ve.dm.Scalable.static.isDimensionsObjectValid( caseItem.dimensions ), caseItem.expected, caseItem.msg );
	} );
} );

QUnit.test( 'getDimensionsFromValue', function ( assert ) {
	var cases = [
		{ dimensions: { width: 200 }, ratio: 1, expected: { width: 200, height: 200 }, msg: 'Only width' },
		{ dimensions: { height: 200 }, ratio: 2, expected: { width: 400, height: 200 }, msg: 'Only height' },
		{ dimensions: { width: '', height: 400 }, ratio: 0.5, expected: { width: 200, height: 400 }, msg: 'Empty width' },
		{ dimensions: { width: 200, height: '' }, ratio: 0.5, expected: { width: 200, height: 400 }, msg: 'Empty height' }
	];

	cases.forEach( function ( caseItem ) {
		assert.deepEqual( ve.dm.Scalable.static.getDimensionsFromValue( caseItem.dimensions, caseItem.ratio ), caseItem.expected, caseItem.msg );
	} );
} );
