<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences\Consequence;

use ConsequenceGetMessageTestTrait;
use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Warn;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequenceNotPrecheckedException;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Session\Session;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Wikimedia\TestingAccessWrapper;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Warn
 * @covers ::__construct
 */
class WarnTest extends MediaWikiUnitTestCase {
	use ConsequenceGetMessageTestTrait;

	private function getWarn( Parameters $params = null ): Warn {
		return new Warn(
			$params ?? $this->createMock( Parameters::class ),
			'foo-bar-message',
			$this->createMock( Session::class )
		);
	}

	private function getParamsAndWarnKey(): array {
		$filter = $this->createMock( ExistingFilter::class );
		$filter->method( 'getID' )->willReturn( 42 );
		$params = new Parameters(
			$filter,
			false,
			new ActionSpecifier(
				'edit',
				new TitleValue( NS_HELP, 'Some title' ),
				new UserIdentityValue( 1, 'Warned user' ),
				'1.2.3.4',
				null
			)
		);
		/** @var Warn $warnWrap */
		$warnWrap = TestingAccessWrapper::newFromObject( $this->getWarn( $params ) );
		return [ $params, $warnWrap->getWarnKey() ];
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_notPrechecked() {
		$warn = $this->getWarn();
		$this->expectException( ConsequenceNotPrecheckedException::class );
		$warn->execute();
	}

	public function provideWarnsAndSuccess() {
		$mockSession = $this->createMock( Session::class );
		$noKeyWarn = new Warn(
			$this->getParamsAndWarnKey()[0],
			'some-msg',
			$mockSession
		);
		yield 'should warn' => [ $noKeyWarn, true, $mockSession ];

		[ $params, $key ] = $this->getParamsAndWarnKey();
		$keySession = $this->createMock( Session::class );
		$keySession->method( 'offsetExists' )->with( $key )->willReturn( true );
		$keySession->method( 'offsetGet' )->with( $key )->willReturn( true );

		$keyWarn = new Warn(
			$params,
			'some-msg',
			$keySession
		);
		yield 'already warned' => [ $keyWarn, false, $keySession ];
	}

	/**
	 * @covers ::shouldDisableOtherConsequences
	 * @covers ::shouldBeWarned
	 * @covers ::getWarnKey
	 * @dataProvider provideWarnsAndSuccess
	 */
	public function testShouldDisableOtherConsequences( Warn $warn, bool $shouldDisable ) {
		$this->assertSame( $shouldDisable, $warn->shouldDisableOtherConsequences() );
	}

	/**
	 * @covers ::execute
	 * @covers ::setWarn
	 * @covers ::getWarnKey
	 * @dataProvider provideWarnsAndSuccess
	 */
	public function testExecute( Warn $warn, bool $shouldDisable, MockObject $session ) {
		$session->expects( $this->once() )
			->method( 'offsetSet' )
			->with( $this->anything(), $shouldDisable );
		$warn->shouldDisableOtherConsequences();
		$this->assertSame( $shouldDisable, $warn->execute() );
	}

	/**
	 * @covers ::getMessage
	 * @dataProvider provideGetMessageParameters
	 */
	public function testGetMessage( Parameters $params ) {
		$msg = 'some-warning-message';
		$rangeBlock = new Warn( $params, $msg, $this->createMock( Session::class ) );
		$this->doTestGetMessage( $rangeBlock, $params, $msg );
	}
}
