/*!
 * VisualEditor Scheduler tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.Scheduler' );

/* Stubs */

ve.TestScheduler = function VeTestScheduler() {
	// Parent constructor
	ve.TestScheduler.super.apply( this, arguments );

	this.nowCounter = 0;
};

OO.inheritClass( ve.TestScheduler, ve.Scheduler );

ve.TestScheduler.static.maxDelay = 5;

ve.TestScheduler.prototype.now = function () {
	return this.nowCounter++;
};

/* Tests */

QUnit.test( 'Call with no real async', function ( assert ) {
	var scheduler = new ve.TestScheduler(),
		begun = assert.async(),
		done = assert.async();

	scheduler.schedule(
		function () {
			assert.true( true, 'initial action was called' );
			begun();
		},
		function () {
			return true;
		}
	).done( function () {
		assert.true( true, 'promise was resolved' );
		done();
	} );
} );

QUnit.test( 'Call with delay', function ( assert ) {
	var scheduler = new ve.TestScheduler(),
		begun = assert.async(),
		done = assert.async(),
		delayed = false;

	scheduler.schedule(
		function () {
			assert.true( true, 'initial action was called' );
			setTimeout( function () {
				delayed = true;
				assert.true( true, 'setTimeout delay occurred' );
			} );
			begun();
		},
		function () {
			return delayed;
		}
	).done( function () {
		assert.true( true, 'promise was resolved' );
		done();
	} );
} );

QUnit.test( 'Test that throws an exception', function ( assert ) {
	var scheduler = new ve.TestScheduler(),
		begun = assert.async(),
		done = assert.async();

	scheduler.schedule(
		function () {
			assert.true( true, 'initial action was called' );
			begun();
		},
		function () {
			throw new Error();
		}
	).done( function () {
		assert.true( false, 'promise was wrongly resolved as successful' );
	} ).fail( function () {
		assert.true( true, 'promise was rejected' );
		done();
	} );
} );

QUnit.test( 'Test that never succeeds', function ( assert ) {
	var scheduler = new ve.TestScheduler(),
		begun = assert.async(),
		done = assert.async();

	scheduler.schedule(
		function () {
			assert.true( true, 'initial action was called' );
			begun();
		},
		function () {
			return false;
		}
	).done( function () {
		assert.true( false, 'promise was wrongly resolved as successful' );
		done();
	} ).fail( function () {
		assert.true( true, 'promise was rejected' );
		done();
	} );
} );
