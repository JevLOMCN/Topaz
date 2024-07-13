<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration\Api;

use ApiTestCase;
use FormatJson;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\InternalException;
use MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator;
use MediaWiki\Extension\AbuseFilter\Parser\ParserStatus;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerStatus;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Api\CheckMatch
 * @covers ::__construct
 * @group medium
 */
class CheckMatchTest extends ApiTestCase {
	use AbuseFilterApiTestTrait;
	use MockAuthorityTrait;

	/**
	 * @covers ::execute
	 */
	public function testExecute_noPermissions() {
		$this->expectApiErrorCode( 'permissiondenied' );

		$this->setService( RuleCheckerFactory::SERVICE_NAME, $this->getRuleCheckerFactory() );

		$this->doApiRequest( [
			'action' => 'abusefiltercheckmatch',
			'filter' => 'sampleFilter',
			'vars' => FormatJson::encode( [] ),
		], null, null, $this->mockRegisteredNullAuthority() );
	}

	public static function provideExecuteOk() {
		return [
			'matched' => [ true ],
			'no match' => [ false ],
		];
	}

	/**
	 * @dataProvider provideExecuteOk
	 * @covers ::execute
	 */
	public function testExecute_Ok( bool $expected ) {
		$filter = 'sampleFilter';
		$checkStatus = new ParserStatus( null, [], 1 );
		$resultStatus = new RuleCheckerStatus( $expected, false, null, [], 1 );
		$ruleChecker = $this->createMock( FilterEvaluator::class );
		$ruleChecker->expects( $this->once() )
			->method( 'checkSyntax' )->with( $filter )
			->willReturn( $checkStatus );
		$ruleChecker->expects( $this->once() )
			->method( 'checkConditions' )->with( $filter )
			->willReturn( $resultStatus );
		$this->setService( RuleCheckerFactory::SERVICE_NAME, $this->getRuleCheckerFactory( $ruleChecker ) );

		$result = $this->doApiRequest( [
			'action' => 'abusefiltercheckmatch',
			'filter' => $filter,
			'vars' => FormatJson::encode( [] ),
		] );

		$this->assertArrayEquals(
			[
				'abusefiltercheckmatch' => [
					'result' => $expected
				]
			],
			$result[0],
			false,
			true
		);
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_error() {
		$this->expectApiErrorCode( 'badsyntax' );
		$filter = 'sampleFilter';
		$status = new ParserStatus( $this->createMock( InternalException::class ), [], 1 );
		$ruleChecker = $this->createMock( FilterEvaluator::class );
		$ruleChecker->expects( $this->once() )
			->method( 'checkSyntax' )->with( $filter )
			->willReturn( $status );
		$this->setService( RuleCheckerFactory::SERVICE_NAME, $this->getRuleCheckerFactory( $ruleChecker ) );

		$this->doApiRequest( [
			'action' => 'abusefiltercheckmatch',
			'filter' => $filter,
			'vars' => FormatJson::encode( [] ),
		] );
	}

}
