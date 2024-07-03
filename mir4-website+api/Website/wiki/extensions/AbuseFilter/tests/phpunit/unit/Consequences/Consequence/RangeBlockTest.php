<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences\Consequence;

use ConsequenceGetMessageTestTrait;
use MediaWiki\Block\BlockUser;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\RangeBlock;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\Status\Status;
use MediaWikiUnitTestCase;
use MessageLocalizer;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\RangeBlock
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\BlockingConsequence
 */
class RangeBlockTest extends MediaWikiUnitTestCase {
	use ConsequenceGetMessageTestTrait;

	private const CIDR_LIMIT = [
		'IPv4' => 16,
		'IPv6' => 19,
	];

	private function getMsgLocalizer(): MessageLocalizer {
		$ml = $this->createMock( MessageLocalizer::class );
		$ml->method( 'msg' )->willReturnCallback( function ( $k, $p ) {
			return $this->getMockMessage( $k, $p );
		} );
		return $ml;
	}

	public static function provideExecute(): iterable {
		yield 'IPv4 range block' => [
			'1.2.3.4',
			[
				'IPv4' => 16,
				'IPv6' => 18,
			],
			'1.2.0.0/16',
			true
		];
		yield 'IPv6 range block' => [
			// random IP from https://en.wikipedia.org/w/index.php?title=IPv6&oldid=989727833
			'2001:0db8:0000:0000:0000:ff00:0042:8329',
			[
				'IPv4' => 15,
				'IPv6' => 19,
			],
			'2001:0:0:0:0:0:0:0/19',
			true
		];
		yield 'IPv4 range block constrained by core limits' => [
			'1.2.3.4',
			[
				'IPv4' => 15,
				'IPv6' => 19,
			],
			'1.2.0.0/16',
			true
		];
		yield 'IPv6 range block constrained by core limits' => [
			'2001:0db8:0000:0000:0000:ff00:0042:8329',
			[
				'IPv4' => 16,
				'IPv6' => 18,
			],
			'2001:0:0:0:0:0:0:0/19',
			true
		];
		yield 'failure' => [
			'1.2.3.4',
			self::CIDR_LIMIT,
			'1.2.0.0/16',
			false
		];
	}

	/**
	 * @dataProvider provideExecute
	 * @covers ::__construct
	 * @covers ::execute
	 */
	public function testExecute(
		string $requestIP, array $rangeBlockSize, string $target, bool $result
	) {
		$specifier = $this->createMock( ActionSpecifier::class );
		$specifier->method( 'getIP' )->willReturn( $requestIP );
		$params = new Parameters(
			$this->createMock( ExistingFilter::class ),
			false,
			$specifier
		);
		$blockUser = $this->createMock( BlockUser::class );
		$blockUser->expects( $this->once() )
			->method( 'placeBlockUnsafe' )
			->willReturn( $result ? Status::newGood() : Status::newFatal( 'error' ) );
		$blockUserFactory = $this->createMock( BlockUserFactory::class );
		$blockUserFactory->expects( $this->once() )
			->method( 'newBlockUser' )
			->with(
				$target,
				$this->anything(),
				'1 week',
				$this->anything(),
				$this->anything()
			)
			->willReturn( $blockUser );

		$rangeBlock = new RangeBlock(
			$params,
			'1 week',
			$blockUserFactory,
			$this->createMock( FilterUser::class ),
			$this->getMsgLocalizer(),
			new NullLogger(),
			$rangeBlockSize,
			self::CIDR_LIMIT
		);
		$this->assertSame( $result, $rangeBlock->execute() );
	}

	/**
	 * @covers ::getMessage
	 * @dataProvider provideGetMessageParameters
	 */
	public function testGetMessage( Parameters $params ) {
		$rangeBlock = new RangeBlock(
			$params,
			'0',
			$this->createMock( BlockUserFactory::class ),
			$this->createMock( FilterUser::class ),
			$this->getMsgLocalizer(),
			new NullLogger(),
			[ 'IPv6' => 24, 'IPv4' => 24 ],
			self::CIDR_LIMIT
		);
		$this->doTestGetMessage( $rangeBlock, $params, 'abusefilter-blocked-display' );
	}
}
