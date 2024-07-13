/* global QUnit */
const clientPreferences = require( 'skins.vector.clientPreferences' );

/*!
 * Vector integration tests.
 *
 * This should only be used to test APIs that Vector depends on to work.
 * For unit tests please see tests/jest.
 */
QUnit.module( 'Vector (integration)', function () {
	QUnit.test( 'Client preferences: Behaves same for all users', function ( assert ) {
		const sandbox = this.sandbox;
		const helper = ( feature, isNamedReturnValue ) => {
			document.documentElement.setAttribute( 'class', `${ feature }-clientpref-0` );
			const stub = sandbox.stub( mw.user, 'isNamed', () => isNamedReturnValue );
			clientPreferences.toggleDocClassAndSave( feature, '1', {
				'vector-feature-limited-width': {
					options: [ '1', '0' ],
					preferenceKey: 'vector-limited-width'
				}
			} );
			stub.restore();
			return document.documentElement.getAttribute( 'class' );
		};

		assert.strictEqual(
			helper( 'vector-feature-limited-width', false ),
			helper( 'vector-feature-limited-width', true ),
			'The same classes are modified regardless of the user status.'
		);
	} );
} );
