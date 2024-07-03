<?php

namespace MediaWiki\Extension\Notifications\Test\Integration;

use MediaWiki\Extension\Notifications\ContainmentSet;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\Notifications\ContainmentSet
 */
class ContainmentSetTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::addTitleIDsFromUserOption
	 * @dataProvider addTitlesFromUserOptionProvider
	 * @param string $prefData
	 * @param string $contains
	 * @param bool $expected
	 */
	public function testAddTitlesFromUserOption(
		$prefData, string $contains, bool $expected
	) {
		$userOptionsLookupMock = $this->createMock( UserOptionsLookup::class );
		$userOptionsLookupMock->method( 'getOption' )->willReturn( $prefData );
		$this->setService( 'UserOptionsLookup', $userOptionsLookupMock );
		$containmentSet = new ContainmentSet( $this->createMock( User::class ) );
		$containmentSet->addTitleIDsFromUserOption( 'preference-name' );
		$this->assertSame( $expected, $containmentSet->contains( $contains ) );
	}

	public static function addTitlesFromUserOptionProvider(): array {
		return [
			[
				'foo',
				'bar',
				false
			],
			[
				[ 'foo', 'bar' ],
				'foo',
				false
			],
			[
				"foo\nbar",
				'bar',
				true
			],
			[
				'{"foo":"bar"}',
				'bar',
				false
			]

		];
	}

}
