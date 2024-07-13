<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\FilterValidator;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWikiIntegrationTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @group Database
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\FilterValidator
 * @covers ::__construct()
 */
class FilterValidatorTest extends MediaWikiIntegrationTestCase {
	/**
	 * @todo Make this a unit test once static methods in ChangeTags are moved to a service
	 * @param string[] $tags
	 * @param string|null $expected
	 * @covers ::checkAllTags
	 * @dataProvider provideAllTags
	 */
	public function testCheckAllTags( array $tags, ?string $expected ) {
		$validator = new FilterValidator(
			AbuseFilterServices::getChangeTagValidator(),
			$this->createMock( RuleCheckerFactory::class ),
			$this->createMock( AbuseFilterPermissionManager::class ),
			new ServiceOptions(
				FilterValidator::CONSTRUCTOR_OPTIONS,
				[
					'AbuseFilterActionRestrictions' => [],
					'AbuseFilterValidGroups' => [ 'default' ]
				]
			)
		);

		$status = $validator->checkAllTags( $tags );
		$actualError = $status->isGood() ? null : $status->getErrors()[0]['message'];
		$this->assertSame( $expected, $actualError );
	}

	public static function provideAllTags() {
		$invalidTags = [
			'a|b',
			'mw-undo',
			'abusefilter-condition-limit',
			'valid_tag',
		];
		$firstTagError = 'tags-create-invalid-chars';
		yield 'invalid' => [ $invalidTags, $firstTagError ];

		yield 'valid' => [ [ 'fooooobar', 'foooobaz' ], null ];
	}
}
