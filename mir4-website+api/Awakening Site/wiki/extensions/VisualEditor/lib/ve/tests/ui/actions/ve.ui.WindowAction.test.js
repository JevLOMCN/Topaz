/*!
 * VisualEditor UserInterface Actions WindowAction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.WindowAction' );

/* Tests */

QUnit.test( 'Basic open/close', function ( assert ) {
	var surface = ve.test.utils.createSurfaceFromHtml( '' ),
		windowAction = ve.ui.actionFactory.create( 'window', surface ),
		dialogs = surface.getDialogs(),
		done = assert.async( 2 );

	dialogs.once( 'opening', function ( win, opening ) {
		opening.then( function () {
			assert.true( true, 'Window opened' );

			windowAction.close( 'message' );
			done();
		} );
	} );

	dialogs.once( 'closing', function () {
		assert.true( true, 'Window closed' );
		done();
	} );

	windowAction.open( 'message' );
} );
