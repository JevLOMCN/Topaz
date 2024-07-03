<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration\Api;

use ApiTestCase;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\InternalException;
use MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator;
use MediaWiki\Extension\AbuseFilter\Parser\ParserStatus;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Api\EvalExpression
 * @covers ::__construct
 * @group medium
 */
class EvalExpressionTest extends ApiTestCase {
	use AbuseFilterApiTestTrait;
	use MockAuthorityTrait;

	/**
	 * @covers ::execute
	 */
	public function testExecute_noPermissions() {
		$this->expectApiErrorCode( 'permissiondenied' );

		$this->setService( RuleCheckerFactory::SERVICE_NAME, $this->getRuleCheckerFactory() );

		$this->doApiRequest( [
			'action' => 'abusefilterevalexpression',
			'expression' => 'sampleExpression',
		], null, null, $this->mockRegisteredNullAuthority() );
	}

	/**
	 * @covers ::execute
	 * @covers ::evaluateExpression
	 */
	public function testExecute_error() {
		$this->expectApiErrorCode( 'abusefilter-tools-syntax-error' );
		$expression = 'sampleExpression';
		$status = new ParserStatus( $this->createMock( InternalException::class ), [], 1 );
		$ruleChecker = $this->createMock( FilterEvaluator::class );
		$ruleChecker->method( 'checkSyntax' )->with( $expression )
			->willReturn( $status );
		$this->setService( RuleCheckerFactory::SERVICE_NAME, $this->getRuleCheckerFactory( $ruleChecker ) );

		$this->doApiRequest( [
			'action' => 'abusefilterevalexpression',
			'expression' => $expression,
		] );
	}

	/**
	 * @covers ::execute
	 * @covers ::evaluateExpression
	 */
	public function testExecute_Ok() {
		$expression = 'sampleExpression';
		$status = new ParserStatus( null, [], 1 );
		$ruleChecker = $this->createMock( FilterEvaluator::class );
		$ruleChecker->method( 'checkSyntax' )->with( $expression )
			->willReturn( $status );
		$ruleChecker->expects( $this->once() )->method( 'evaluateExpression' )
			->willReturn( 'output' );
		$this->setService( RuleCheckerFactory::SERVICE_NAME, $this->getRuleCheckerFactory( $ruleChecker ) );

		$result = $this->doApiRequest( [
			'action' => 'abusefilterevalexpression',
			'expression' => $expression,
			'prettyprint' => false,
		] );

		$this->assertArrayEquals(
			[
				'abusefilterevalexpression' => [
					'result' => "'output'"
				]
			],
			$result[0],
			false,
			true
		);
	}

	/**
	 * @covers ::execute
	 * @covers ::evaluateExpression
	 */
	public function testExecute_OkAndPrettyPrint() {
		$expression = 'sampleExpression';
		$status = new ParserStatus( null, [], 1 );
		$ruleChecker = $this->createMock( FilterEvaluator::class );
		$ruleChecker->method( 'checkSyntax' )->with( $expression )
			->willReturn( $status );
		$ruleChecker->expects( $this->once() )->method( 'evaluateExpression' )
			->willReturn( [ 'value1', 2 ] );
		$this->setService( RuleCheckerFactory::SERVICE_NAME, $this->getRuleCheckerFactory( $ruleChecker ) );

		$result = $this->doApiRequest( [
			'action' => 'abusefilterevalexpression',
			'expression' => $expression,
			'prettyprint' => true,
		] );

		$this->assertArrayEquals(
			[
				'abusefilterevalexpression' => [
					'result' => "[\n\t0 => 'value1',\n\t1 => 2\n]"
				]
			],
			$result[0],
			false,
			true
		);
	}
}
