const { UiElement } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.ui', QUnit.newMwEnvironment( {
		beforeEach: function () {
			this.clock = this.sandbox.useFakeTimers();
		}
	} ) );

	QUnit.test( 'handleEvent()', function ( assert ) {
		const element = new UiElement( $( '<div>' ) );

		element.handleEvent( 'mmv-foo', function () {
			assert.true( true, 'Event is handled' );
		} );

		$( document ).trigger( new $.Event( 'mmv-foo' ) );

		element.clearEvents();

		$( document ).trigger( new $.Event( 'mmv-foo' ) );
	} );

	QUnit.test( 'setInlineStyle()', function ( assert ) {
		const element = new UiElement( $( '<div>' ) );
		const $testDiv = $( '<div>' ).attr( 'id', 'mmv-testdiv' ).text( '!!!' ).appendTo( '#qunit-fixture' );

		assert.true( $testDiv.is( ':visible' ), 'Test div is visible' );

		element.setInlineStyle( 'test', '#mmv-testdiv { display: none; }' );

		assert.strictEqual( $testDiv.is( ':visible' ), false, 'Test div is hidden by inline style' );

		element.setInlineStyle( 'test', null );

		assert.strictEqual( $testDiv.is( ':visible' ), true, 'Test div is visible again' );
	} );

	QUnit.test( 'setTimer()/clearTimer()/resetTimer()', function ( assert ) {
		const element = new UiElement( $( '<div>' ) );
		const element2 = new UiElement( $( '<div>' ) );
		let spy = this.sandbox.spy();
		let spy2 = this.sandbox.spy();

		element.setTimer( 'foo', spy, 10 );
		this.clock.tick( 100 );
		assert.strictEqual( spy.called, true, 'Timeout callback was called' );
		assert.strictEqual( spy.calledOnce, true, 'Timeout callback was called once' );
		assert.strictEqual( spy.calledOn( element ), true, 'Timeout callback was called on the element' );

		spy = this.sandbox.spy();
		element.setTimer( 'foo', spy, 10 );
		element.setTimer( 'foo', spy2, 20 );
		this.clock.tick( 100 );
		assert.strictEqual( spy.called, false, 'Old timeout callback was not called after update' );
		assert.strictEqual( spy2.called, true, 'New timeout callback was called after update' );

		spy = this.sandbox.spy();
		spy2 = this.sandbox.spy();
		element.setTimer( 'foo', spy, 10 );
		element.setTimer( 'bar', spy2, 20 );
		this.clock.tick( 100 );
		assert.strictEqual( spy.called && spy2.called, true, 'Timeouts with different names do not conflict' );

		spy = this.sandbox.spy();
		spy2 = this.sandbox.spy();
		element.setTimer( 'foo', spy, 10 );
		element2.setTimer( 'foo', spy2, 20 );
		this.clock.tick( 100 );
		assert.strictEqual( spy.called && spy2.called, true, 'Timeouts in different elements do not conflict' );

		spy = this.sandbox.spy();
		element.setTimer( 'foo', spy, 10 );
		element.clearTimer( 'foo' );
		this.clock.tick( 100 );
		assert.strictEqual( spy.called, false, 'Timeout is invalidated by clearing' );

		spy = this.sandbox.spy();
		element.setTimer( 'foo', spy, 100 );
		this.clock.tick( 80 );
		element.resetTimer( 'foo' );
		this.clock.tick( 80 );
		assert.strictEqual( spy.called, false, 'Timeout is reset' );
		this.clock.tick( 80 );
		assert.strictEqual( spy.called, true, 'Timeout works after reset' );

		spy = this.sandbox.spy();
		element.setTimer( 'foo', spy, 100 );
		this.clock.tick( 80 );
		element.resetTimer( 'foo', 200 );
		this.clock.tick( 180 );
		assert.strictEqual( spy.called, false, 'Timeout is reset to the designated delay' );
		this.clock.tick( 80 );
		assert.strictEqual( spy.called, true, 'Timeout works after changing the delay' );
	} );

	QUnit.test( 'correctEW()', function ( assert ) {
		const element = new UiElement( $( '<div>' ) );

		element.isRTL = this.sandbox.stub().returns( true );

		assert.strictEqual( element.correctEW( 'e' ), 'w', 'e (east) is flipped' );
		assert.strictEqual( element.correctEW( 'ne' ), 'nw', 'ne (northeast) is flipped' );
		assert.strictEqual( element.correctEW( 'W' ), 'E', 'uppercase is flipped' );
		assert.strictEqual( element.correctEW( 's' ), 's', 'non-horizontal directions are ignored' );

		element.isRTL.returns( false );

		assert.strictEqual( element.correctEW( 'e' ), 'e', 'no flipping in LTR documents' );
	} );
}() );
