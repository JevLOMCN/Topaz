<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration\Api;

use MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;

/**
 * This trait contains helper methods for Api integration tests.
 */
trait AbuseFilterApiTestTrait {

	/**
	 * @param FilterEvaluator|null $ruleChecker
	 * @return RuleCheckerFactory
	 */
	protected function getRuleCheckerFactory( FilterEvaluator $ruleChecker = null ): RuleCheckerFactory {
		$factory = $this->createMock( RuleCheckerFactory::class );
		if ( $ruleChecker !== null ) {
			$factory->expects( $this->atLeastOnce() )
				->method( 'newRuleChecker' )
				->willReturn( $ruleChecker );
		} else {
			$factory->expects( $this->never() )->method( 'newRuleChecker' );
		}
		return $factory;
	}
}
