const { LightboxImage } = require( 'mmv' );

QUnit.module( 'mmv.lightboximage', QUnit.newMwEnvironment() );

QUnit.test( 'Sense test', function ( assert ) {
	const lightboxImage = new LightboxImage( 'foo.png' );

	assert.true( lightboxImage instanceof LightboxImage, 'Object created' );
} );
