const { ViewLogger } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.logging.ViewLogger', QUnit.newMwEnvironment( {
		beforeEach: function () {
			this.clock = this.sandbox.useFakeTimers();

			// since jQuery 2/3, $.now will capture a reference to Date.now
			// before above fake timer gets a chance to override it, so I'll
			// override that new behavior in order to run these tests...
			// @see https://github.com/sinonjs/lolex/issues/76
			this.oldNow = $.now;
			$.now = () => Date.now();
		},

		afterEach: function () {
			$.now = this.oldNow;
			this.clock.restore();
		}
	} ) );

	QUnit.test( 'focus and blur', function ( assert ) {
		const $fakeWindow = $( '<div>' );
		const viewLogger = new ViewLogger( { recordVirtualViewBeaconURI: function () {} }, $fakeWindow, { log: function () {} } );

		this.clock.tick( 1 ); // This is just so that the timer ticks up in the fake timer environment

		viewLogger.attach();

		this.clock.tick( 5 );

		$fakeWindow.triggerHandler( 'blur' );

		this.clock.tick( 2 );

		$fakeWindow.triggerHandler( 'focus' );

		this.clock.tick( 3 );

		$fakeWindow.triggerHandler( 'blur' );

		this.clock.tick( 4 );

		assert.strictEqual( viewLogger.viewDuration, 8, 'Only focus duration was logged' );
	} );

	QUnit.test( 'stopViewDuration before startViewDuration', function ( assert ) {
		const viewLogger = new ViewLogger( { recordVirtualViewBeaconURI: function () {} }, {}, { log: function () {} } );

		this.clock.tick( 1 ); // This is just so that the timer ticks up in the fake timer environment

		viewLogger.stopViewDuration();

		this.clock.tick( 2 );

		viewLogger.startViewDuration();

		this.clock.tick( 3 );

		viewLogger.stopViewDuration();

		assert.strictEqual( viewLogger.viewDuration, 3, 'Only last timeframe was logged' );
	} );
}() );
