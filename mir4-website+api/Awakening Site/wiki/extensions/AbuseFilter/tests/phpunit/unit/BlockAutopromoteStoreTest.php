<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use BagOStuff;
use HashBagOStuff;
use MediaWiki\Extension\AbuseFilter\BlockAutopromoteStore;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\BlockAutopromoteStore
 * @covers ::__construct
 */
class BlockAutopromoteStoreTest extends MediaWikiUnitTestCase {

	private function getStore( BagOStuff $cache ): BlockAutopromoteStore {
		return new BlockAutopromoteStore(
			$cache,
			new NullLogger(),
			$this->createMock( FilterUser::class )
		);
	}

	/**
	 * @covers ::blockAutoPromote
	 */
	public function testBlockAutopromote_success() {
		$store = $this->getStore( new HashBagOStuff() );
		$target = new UserIdentityValue( 1, 'Blocked user' );
		$this->assertTrue( $store->blockAutoPromote( $target, '', 1 ) );
	}

	/**
	 * @covers ::blockAutoPromote
	 */
	public function testBlockAutopromote_cannotSet() {
		$cache = $this->createMock( BagOStuff::class );
		$cache->expects( $this->once() )->method( 'set' )->willReturn( false );
		$cache->method( 'makeKey' )->willReturn( 'foo' );
		$store = $this->getStore( $cache );
		$target = new UserIdentityValue( 1, 'Blocked user' );
		$this->assertFalse( $store->blockAutoPromote( $target, '', 1 ) );
	}

	/**
	 * @covers ::unblockAutoPromote
	 */
	public function testUnblockAutopromote_success() {
		$cache = $this->createMock( BagOStuff::class );
		$cache->expects( $this->once() )->method( 'changeTTL' )->willReturn( true );
		$cache->method( 'makeKey' )->willReturn( 'foo' );
		$store = $this->getStore( $cache );
		$target = new UserIdentityValue( 1, 'Blocked user' );
		$this->assertTrue( $store->unblockAutoPromote( $target, $target, '' ) );
	}

	/**
	 * @covers ::unblockAutoPromote
	 */
	public function testUnblockAutopromote_notBlocked() {
		$store = $this->getStore( new HashBagOStuff() );
		$target = new UserIdentityValue( 1, 'Blocked user' );
		$this->assertFalse( $store->unblockAutoPromote( $target, $target, '' ) );
	}

	/**
	 * @covers ::blockAutoPromote
	 * @covers ::getAutoPromoteBlockStatus
	 * @covers ::unblockAutopromote
	 * @covers ::getAutoPromoteBlockKey
	 */
	public function testRoundTrip() {
		$cache = new HashBagOStuff();
		$store = $this->getStore( $cache );
		$target = new UserIdentityValue( 1, 'Blocked user' );
		$this->assertTrue( $store->blockAutoPromote( $target, '', 3000 ), 'block' );
		$this->assertSame( 1, $store->getAutoPromoteBlockStatus( $target ), 'should be blocked' );
		$this->assertTrue( $store->unblockAutoPromote( $target, $target, '' ), 'can unblock' );
		$this->assertSame( 0, $store->getAutoPromoteBlockStatus( $target ), 'should not be blocked' );
	}
}
