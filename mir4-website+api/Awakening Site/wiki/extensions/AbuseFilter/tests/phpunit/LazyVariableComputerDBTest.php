<?php

use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\Variables\LazyLoadedVariable;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentityValue;

/**
 * @group Test
 * @group AbuseFilter
 * @group Database
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer
 * @todo Move to LazyVariableComputerTest
 */
class LazyVariableComputerDBTest extends MediaWikiIntegrationTestCase {

	/**
	 * Make different users edit a page, so that we can check their names against
	 * the actual value of a _recent_contributors variable
	 * @param Title $title
	 * @return string[]
	 */
	private function computeRecentContributors( Title $title ) {
		// This test uses a custom DB query and it's hard to use mocks
		$user = $this->getMutableTestUser()->getUser();
		// Create the page and make a couple of edits from different users
		$this->editPage(
			$title,
			'AbuseFilter test for title variables',
			'',
			NS_MAIN,
			$user
		);
		$mockContributors = [ 'X>Alice', 'X>Bob', 'X>Charlie' ];
		foreach ( $mockContributors as $contributor ) {
			$this->editPage(
				$title,
				"page revision by $contributor",
				'',
				NS_MAIN,
				new UltimateAuthority( UserIdentityValue::newAnonymous( $contributor ) )
			);
		}
		$contributors = array_reverse( $mockContributors );
		$contributors[] = $user->getName();
		return $contributors;
	}

	/**
	 * @covers ::compute
	 * @covers ::getLastPageAuthors
	 */
	public function testRecentContributors() {
		$varName = "page_recent_contributors";
		$title = Title::makeTitle( NS_MAIN, "Page to test $varName" );

		$expected = $this->computeRecentContributors( $title );
		$computer = AbuseFilterServices::getLazyVariableComputer();
		$var = new LazyLoadedVariable(
			'load-recent-authors',
			[ 'title' => $title ]
		);
		$forbidComputeCB = static function () {
			throw new LogicException( 'Not expected to be called' );
		};
		$actual = $computer->compute( $var, new VariableHolder(), $forbidComputeCB )->toNative();
		$this->assertSame( $expected, $actual );
	}
}
