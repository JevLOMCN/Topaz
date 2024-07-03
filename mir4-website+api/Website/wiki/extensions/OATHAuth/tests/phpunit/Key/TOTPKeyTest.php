<?php

namespace MediaWiki\Extension\OATHAuth\Tests\Key;

use MediaWiki\Extension\OATHAuth\Key\TOTPKey;

/**
 * @covers \MediaWiki\Extension\OATHAuth\Key\TOTPKey
 */
class TOTPKeyTest extends \MediaWikiIntegrationTestCase {
	public function testDeserialization() {
		$key = TOTPKey::newFromRandom();
		$deserialized = TOTPKey::newFromArray( json_decode( json_encode( $key ), true ) );
		$this->assertSame( $key->getSecret(), $deserialized->getSecret() );
		$this->assertSame( $key->getScratchTokens(), $deserialized->getScratchTokens() );
	}

	public function testIsScratchToken() {
		$key = TOTPKey::newFromArray( [
			'secret' => '123456',
			'scratch_tokens' => [ '64SZLJTTPRI5XBUE' ],
		] );
		$this->assertTrue( $key->isScratchToken( '64SZLJTTPRI5XBUE' ) );
		// Whitespace is stripped
		$this->assertTrue( $key->isScratchToken( ' 64SZLJTTPRI5XBUE ' ) );
		// Wrong token
		$this->assertFalse( $key->isScratchToken( 'WIQGC24UJUFXQDW4' ) );
	}
}
