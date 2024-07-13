<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences\Consequence;

use ConsequenceGetMessageTestTrait;
use MediaWiki\Block\BlockUser;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Block;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use MessageLocalizer;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Block
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\BlockingConsequence
 */
class BlockTest extends MediaWikiUnitTestCase {
	use ConsequenceGetMessageTestTrait;

	private function getMsgLocalizer(): MessageLocalizer {
		$ml = $this->createMock( MessageLocalizer::class );
		$ml->method( 'msg' )->willReturnCallback( function ( $k, $p ) {
			return $this->getMockMessage( $k, $p );
		} );
		return $ml;
	}

	/**
	 * This helper is needed because for reverts we check that the blocker is our filter user, so we want
	 * to always use the same object.
	 * @return FilterUser
	 */
	private function getFilterUser(): FilterUser {
		$filterUser = $this->createMock( FilterUser::class );
		$filterUser->method( 'getUserIdentity' )
			->willReturn( new UserIdentityValue( 2, 'FilterUser' ) );
		return $filterUser;
	}

	public static function provideExecute(): iterable {
		foreach ( [ true, false ] as $result ) {
			$resStr = wfBoolToStr( $result );
			yield "IPv4, $resStr" => [ new UserIdentityValue( 0, '1.2.3.4' ), $result ];
			yield "IPv6, $resStr" => [
				// random IP from https://en.wikipedia.org/w/index.php?title=IPv6&oldid=989727833
				new UserIdentityValue( 0, '2001:0db8:0000:0000:0000:ff00:0042:8329' ),
				$result
			];
			yield "Registered, $resStr" => [ new UserIdentityValue( 3, 'Some random user' ), $result ];
		}
	}

	/**
	 * @dataProvider provideExecute
	 * @covers ::__construct
	 * @covers ::execute
	 */
	public function testExecute( UserIdentity $target, bool $result ) {
		$expiry = '1 day';
		$params = $this->provideGetMessageParameters( $target )->current()[0];
		$blockUser = $this->createMock( BlockUser::class );
		$blockUser->expects( $this->once() )
			->method( 'placeBlockUnsafe' )
			->willReturn( $result ? Status::newGood() : Status::newFatal( 'error' ) );
		$blockUserFactory = $this->createMock( BlockUserFactory::class );
		$blockUserFactory->expects( $this->once() )
			->method( 'newBlockUser' )
			->with(
				$target->getName(),
				$this->anything(),
				$expiry,
				$this->anything(),
				$this->anything()
			)
			->willReturn( $blockUser );

		$block = new Block(
			$params,
			$expiry,
			$preventsTalkEdit = true,
			$blockUserFactory,
			$this->createMock( DatabaseBlockStore::class ),
			static function () {
				return null;
			},
			$this->getFilterUser(),
			$this->getMsgLocalizer(),
			new NullLogger()
		);
		$this->assertSame( $result, $block->execute() );
	}

	/**
	 * @covers ::getMessage
	 * @dataProvider provideGetMessageParameters
	 */
	public function testGetMessage( Parameters $params ) {
		$block = new Block(
			$params,
			'0',
			false,
			$this->createMock( BlockUserFactory::class ),
			$this->createMock( DatabaseBlockStore::class ),
			static function () {
				return null;
			},
			$this->createMock( FilterUser::class ),
			$this->getMsgLocalizer(),
			new NullLogger()
		);
		$this->doTestGetMessage( $block, $params, 'abusefilter-blocked-display' );
	}

	public function provideRevert() {
		yield 'no block to revert' => [ null, null, false ];

		$filterUserIdentity = $this->getFilterUser()->getUserIdentity();

		$notAFBlock = $this->createMock( DatabaseBlock::class );
		$notAFBlock->method( 'getBy' )->willReturn( $filterUserIdentity->getId() + 1 );
		yield 'not blocked by AF user' => [ $notAFBlock, null, false ];

		$blockByFilter = $this->createMock( DatabaseBlock::class );
		$blockByFilter->method( 'getBy' )->willReturn( $filterUserIdentity->getId() );
		$failBlockStore = $this->createMock( DatabaseBlockStore::class );
		$failBlockStore->expects( $this->once() )->method( 'deleteBlock' )->willReturn( false );
		yield 'cannot delete block' => [ $blockByFilter, $failBlockStore, false ];

		$succeedBlockStore = $this->createMock( DatabaseBlockStore::class );
		$succeedBlockStore->expects( $this->once() )->method( 'deleteBlock' )->willReturn( true );
		yield 'succeed' => [ $blockByFilter, $succeedBlockStore, true ];
	}

	/**
	 * @covers ::revert
	 * @dataProvider provideRevert
	 */
	public function testRevert( ?DatabaseBlock $block, ?DatabaseBlockStore $blockStore, bool $expected ) {
		$params = $this->createMock( Parameters::class );
		$params->method( 'getUser' )->willReturn( new UserIdentityValue( 1, 'Foobar' ) );
		$block = new Block(
			$params,
			'0',
			false,
			$this->createMock( BlockUserFactory::class ),
			$blockStore ?? $this->createMock( DatabaseBlockStore::class ),
			static function () use ( $block ) {
				return $block;
			},
			$this->getFilterUser(),
			$this->getMsgLocalizer(),
			new NullLogger()
		);
		$this->assertSame( $expected, $block->revert( $this->createMock( UserIdentity::class ), '' ) );
	}
}
