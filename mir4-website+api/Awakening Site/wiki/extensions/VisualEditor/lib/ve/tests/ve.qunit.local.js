/*!
 * VisualEditor core-only plugin for QUnit.
 *
 * @copyright See AUTHORS.txt
 */

// Extend QUnit.module to provide a fixture element. This used to be in tests/index.html, but
// dynamic test runners like Karma build their own web page.
( function ( QUnit ) {
	var origModule = QUnit.module;

	QUnit.dump.maxDepth = 10;

	QUnit.module = function ( name, localEnv ) {
		localEnv = localEnv || {};
		origModule( name, {
			beforeEach: function () {
				this.fixture = document.createElement( 'div' );
				this.fixture.id = 'qunit-fixture';
				document.body.appendChild( this.fixture );

				this.fixture.appendChild( ve.init.target.$element[ 0 ] );

				if ( localEnv.beforeEach ) {
					return localEnv.beforeEach.apply( this, arguments );
				}
			},
			afterEach: function () {
				var res;
				if ( localEnv.afterEach ) {
					res = localEnv.afterEach.apply( this, arguments );
				}

				this.fixture.parentNode.removeChild( this.fixture );
				return res;
			}
		} );
	};
}( QUnit ) );
