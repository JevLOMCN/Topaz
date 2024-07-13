<?php

use PHPUnit\Framework\MockObject\Invocation;
use PHPUnit\Framework\MockObject\Invocation\StaticInvocation;
use PHPUnit\Framework\MockObject\Stub\Stub;

class EchoExecuteFirstArgumentStub implements Stub {
	public function invoke( Invocation $invocation ) {
		if ( !$invocation instanceof StaticInvocation ) {
			throw new PHPUnit\Framework\Exception( 'wrong invocation type' );
		}
		if ( !$invocation->arguments ) {
			throw new PHPUnit\Framework\Exception( 'Method call must have an argument' );
		}

		return call_user_func( reset( $invocation->arguments ) );
	}

	public function toString(): string {
		return 'return result of call_user_func on first invocation argument';
	}
}
