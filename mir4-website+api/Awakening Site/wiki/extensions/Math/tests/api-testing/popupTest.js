'use strict';

const { assert, REST } = require( 'api-testing' );

describe( 'Math popup endpoint test', () => {
	const client = new REST( 'rest.php/math/v0' );

	it( 'should get a 400 response for bad value of hash param', async () => {
		const { status, body } = await client.get( '/popup/html/thebadvalue' );
		assert.strictEqual( status, 400 );
		assert.strictEqual( body.httpReason, 'Bad Request' );
		assert.include( body.messageTranslations.en, 'thebadvalue' );
	} );

	it( 'should get a 400 response for a malformed item ID starting with Q', async () => {
		const { status, body } = await client.get( '/popup/html/Q1' );
		assert.strictEqual( status, 400 );
		assert.strictEqual( body.httpReason, 'Bad Request' );
		assert.include( body.messageTranslations.en, 'Q1' );
	} );
} );
