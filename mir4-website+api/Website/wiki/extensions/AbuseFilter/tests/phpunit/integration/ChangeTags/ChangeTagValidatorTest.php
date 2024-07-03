<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration\ChangeTags;

use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWikiIntegrationTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @group Database
 * @covers \MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagValidator
 */
class ChangeTagValidatorTest extends MediaWikiIntegrationTestCase {
	/**
	 * @todo Make this a unit test once static methods in ChangeTags are moved to a service
	 * @todo When the above is possible, use mocks to test canAddTagsAccompanyingChange and canCreateTag
	 * @param string $tag The tag to validate
	 * @param string|null $expectedError
	 * @covers \MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagValidator::validateTag
	 * @dataProvider provideTags
	 */
	public function testValidateTag( string $tag, ?string $expectedError ) {
		$validator = AbuseFilterServices::getChangeTagValidator();
		$status = $validator->validateTag( $tag );
		$actualError = $status->isGood() ? null : $status->getErrors()[0]['message'];
		$this->assertSame( $expectedError, $actualError );
	}

	/**
	 * Data provider for testValidateTag
	 * @return array
	 */
	public static function provideTags() {
		return [
			'invalid chars' => [ 'a|b', 'tags-create-invalid-chars' ],
			'core-reserved tag' => [ 'mw-undo', 'abusefilter-edit-bad-tags' ],
			'AF-reserved tag' => [ 'abusefilter-condition-limit', 'abusefilter-tag-reserved' ],
			'valid' => [ 'my_tag', null ],
		];
	}
}
