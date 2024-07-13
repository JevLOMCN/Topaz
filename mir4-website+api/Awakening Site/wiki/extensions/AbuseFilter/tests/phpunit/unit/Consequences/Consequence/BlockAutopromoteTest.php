<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences\Consequence;

use ConsequenceGetMessageTestTrait;
use MediaWiki\Extension\AbuseFilter\BlockAutopromoteStore;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\BlockAutopromote;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\User\UserIdentityUtils;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use MessageLocalizer;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\BlockAutopromote
 * @covers ::__construct
 */
class BlockAutopromoteTest extends MediaWikiUnitTestCase {
	use ConsequenceGetMessageTestTrait;

	private function getMsgLocalizer(): MessageLocalizer {
		$ml = $this->createMock( MessageLocalizer::class );
		$ml->method( 'msg' )->willReturnCallback( function ( $k, $p ) {
			return $this->getMockMessage( $k, $p );
		} );
		return $ml;
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_anonymous() {
		$user = new UserIdentityValue( 0, 'Anonymous user' );
		$params = $this->provideGetMessageParameters( $user )->current()[0];
		$blockAutopromoteStore = $this->createMock( BlockAutopromoteStore::class );
		$blockAutopromoteStore->expects( $this->never() )
			->method( 'blockAutoPromote' );
		$userIdentityUtils = $this->createMock( UserIdentityUtils::class );
		$blockAutopromote = new BlockAutopromote(
			$params,
			5 * 86400,
			$blockAutopromoteStore,
			$this->getMsgLocalizer(),
			$userIdentityUtils
		);
		$this->assertFalse( $blockAutopromote->execute() );
	}

	/**
	 * @covers ::execute
	 * @dataProvider provideExecute
	 */
	public function testExecute( bool $success ) {
		$target = new UserIdentityValue( 1, 'A new user' );
		$userIdentityUtils = $this->createMock( UserIdentityUtils::class );
		$userIdentityUtils->method( 'isNamed' )->willReturn( true );
		$params = $this->provideGetMessageParameters( $target )->current()[0];
		$duration = 5 * 86400;
		$blockAutopromoteStore = $this->createMock( BlockAutopromoteStore::class );
		$blockAutopromoteStore->expects( $this->once() )
			->method( 'blockAutoPromote' )
			->with( $target, $this->anything(), $duration )
			->willReturn( $success );
		$blockAutopromote = new BlockAutopromote(
			$params,
			$duration,
			$blockAutopromoteStore,
			$this->getMsgLocalizer(),
			$userIdentityUtils
		);
		$this->assertSame( $success, $blockAutopromote->execute() );
	}

	public static function provideExecute(): array {
		return [
			[ true ],
			[ false ]
		];
	}

	/**
	 * @covers ::revert
	 * @dataProvider provideExecute
	 */
	public function testRevert( bool $success ) {
		$target = new UserIdentityValue( 1, 'A new user' );
		$performer = new UserIdentityValue( 2, 'Reverting user' );
		$params = $this->provideGetMessageParameters( $target )->current()[0];
		$blockAutopromoteStore = $this->createMock( BlockAutopromoteStore::class );
		$blockAutopromoteStore->expects( $this->once() )
			->method( 'unblockAutoPromote' )
			->with( $target, $performer, $this->anything() )
			->willReturn( $success );
		$userIdentityUtils = $this->createMock( UserIdentityUtils::class );
		$blockAutopromote = new BlockAutopromote(
			$params,
			0,
			$blockAutopromoteStore,
			$this->getMsgLocalizer(),
			$userIdentityUtils
		);
		$this->assertSame( $success, $blockAutopromote->revert( $performer, 'reason' ) );
	}

	/**
	 * @covers ::getMessage
	 * @dataProvider provideGetMessageParameters
	 */
	public function testGetMessage( Parameters $params ) {
		$userIdentityUtils = $this->createMock( UserIdentityUtils::class );
		$rangeBlock = new BlockAutopromote(
			$params,
			83,
			$this->createMock( BlockAutopromoteStore::class ),
			$this->getMsgLocalizer(),
			$userIdentityUtils
		);
		$this->doTestGetMessage( $rangeBlock, $params, 'abusefilter-autopromote-blocked' );
	}
}
