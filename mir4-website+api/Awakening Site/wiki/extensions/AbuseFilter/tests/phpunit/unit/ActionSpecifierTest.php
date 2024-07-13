<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use InvalidArgumentException;
use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\ActionSpecifier
 */
class ActionSpecifierTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 * @covers ::getAction
	 * @covers ::getTitle
	 * @covers ::getUser
	 * @covers ::getIP
	 * @covers ::getAccountName
	 */
	public function testGetters() {
		$action = 'edit';
		$title = new TitleValue( NS_MAIN, 'Foobar' );
		$user = new UserIdentityValue( 42, 'John Doe' );
		$ip = '127.0.0.1';
		$accountname = 'foobar';
		$spec = new ActionSpecifier( $action, $title, $user, $ip, $accountname );
		$this->assertSame( $action, $spec->getAction(), 'action' );
		$this->assertSame( $title, $spec->getTitle(), 'title' );
		$this->assertSame( $user, $spec->getUser(), 'user' );
		$this->assertSame( $ip, $spec->getIP(), 'IP' );
		$this->assertSame( $accountname, $spec->getAccountName(), 'accountname' );
	}

	/**
	 * @covers ::__construct
	 */
	public function testInvalidAccountName() {
		$this->expectException( InvalidArgumentException::class );
		new ActionSpecifier(
			'createaccount',
			$this->createMock( LinkTarget::class ),
			$this->createMock( UserIdentity::class ),
			'127.0.0.1',
			null
		);
	}
}
