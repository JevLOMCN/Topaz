<?php

namespace MediaWiki\Extension\OATHAuth\Tests\Auth;

use MediaWiki\Auth\AuthenticationRequestTestCase;
use MediaWiki\Extension\OATHAuth\Auth\TOTPAuthenticationRequest;

/**
 * @covers \MediaWiki\Extension\OATHAuth\Auth\TOTPAuthenticationRequest
 */
class TOTPAuthenticationRequestTest extends AuthenticationRequestTestCase {

	protected function getInstance( array $args = [] ) {
		return new TOTPAuthenticationRequest();
	}

	public static function provideLoadFromSubmission() {
		return [
			[ [], [], false ],
			[ [], [ 'OATHToken' => '123456' ], [ 'OATHToken' => '123456' ] ],
		];
	}
}
