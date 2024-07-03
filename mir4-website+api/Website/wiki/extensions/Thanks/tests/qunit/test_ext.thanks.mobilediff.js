QUnit.module( 'Thanks mobilediff', function () {

	QUnit.test( 'render button for logged in users', function ( assert ) {
		var $container = $( '<div>' );
		var $user = $( '<div>' )
			.data( 'user-name', 'jon' )
			.data( 'revision-id', 1 )
			.data( 'user-gender', 'male' );

		// eslint-disable-next-line no-underscore-dangle
		mw.thanks._mobileDiffInit( $user, $container );
		assert.strictEqual( $container.find( '.cdx-button' ).length, 1, 'Thanks button was created.' );
	} );

} );
