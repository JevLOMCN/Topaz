/*!
 * VisualEditor EventSequencer tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.EventSequencer' );

/* Stubs */

// EventSequencer with script-controlled implementation of "postpone"
ve.TestEventSequencer = function VeTestEventSequencer() {
	// Parent constructor
	ve.TestEventSequencer.super.apply( this, arguments );
	// { number: callback } (for faking setTimeout/clearTimeout)
	this.postponedCallbacks = {};
	this.postponedCallbackId = 1;
};

OO.inheritClass( ve.TestEventSequencer, ve.EventSequencer );

ve.TestEventSequencer.prototype.postpone = function ( callback ) {
	this.postponedCallbacks[ this.postponedCallbackId++ ] = callback;
};

ve.TestEventSequencer.prototype.cancelPostponed = function ( timeoutId ) {
	delete this.postponedCallbacks[ timeoutId ];
};

ve.TestEventSequencer.prototype.runPostponed = function () {
	function sortStringIds( a, b ) {
		return parseInt( a ) - parseInt( b );
	}
	var ids;
	while ( ( ids = Object.keys( this.postponedCallbacks ) ).length > 0 ) {
		ids.sort( sortStringIds );
		for ( var i = 0, len = ids.length; i < len; i++ ) {
			var callback = this.postponedCallbacks[ ids[ i ] ];
			delete this.postponedCallbacks[ ids[ i ] ];
			// Check for existence, because a previous iteration may have cancelled
			if ( callback ) {
				callback();
			}
		}
	}
};

/* Tests */

QUnit.test( 'EventSequencer', function ( assert ) {
	var calls = [];

	var sequencer = new ve.TestEventSequencer( [ 'event1', 'event2', 'event3' ] ).on( {
		event1: function () {
			calls.push( 'on1' );
		},
		event3: function () {
			calls.push( 'on3' );
		}
	} ).after( {
		event2: function () {
			calls.push( 'after2' );
		},
		event3: function () {
			calls.push( 'after3' );
		}
	} ).onLoop(
		function () {
			calls.push( 'onLoop' );
		}
	).afterLoop(
		function () {
			calls.push( 'afterLoop' );
		}
	).afterOne( {
		event1: function () {
			calls.push( 'after1One' );
		}
	} );

	sequencer.onEvent( 'event1' );
	sequencer.onEvent( 'event2' );
	sequencer.onEvent( 'event3' );
	sequencer.runPostponed();

	assert.deepEqual(
		calls,
		[ 'onLoop', 'on1', 'after1One', 'after2', 'on3', 'after3', 'afterLoop' ],
		'First event loop'
	);

	calls.length = 0;
	sequencer.afterLoopOne( function () {
		calls.push( 'afterLoopOne' );
	} );

	sequencer.onEvent( 'event1' );
	sequencer.onEvent( 'event2' );
	sequencer.onEvent( 'event3' );
	sequencer.runPostponed();

	assert.deepEqual(
		calls,
		[ 'onLoop', 'on1', 'after2', 'on3', 'after3', 'afterLoop', 'afterLoopOne' ],
		'Second event loop'
	);

	calls.length = 0;

	sequencer = new ve.TestEventSequencer( [ 'keydown', 'keypress' ] ).on( {
		keydown: function () {
			calls.push( 'onkeydown' );
		},
		keypress: function () {
			calls.push( 'onkeypress' );
		}
	} ).after( {
		keydown: function () {
			calls.push( 'afterkeydown' );
		},
		keypress: function () {
			calls.push( 'afterkeypress' );
		}
	} );
	sequencer.onEvent( 'keydown' );
	sequencer.onEvent( 'keypress' );
	sequencer.runPostponed();

	assert.deepEqual(
		calls,
		[ 'onkeydown', 'onkeypress', 'afterkeydown', 'afterkeypress' ],
		'Keydown/keypress special-cased ordering'
	);
} );
