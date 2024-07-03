<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\ChangeTags;

use Generator;
use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagsManager;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use RecentChange;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger
 */
class ChangeTaggerTest extends MediaWikiUnitTestCase {
	/**
	 * @return ChangeTagger
	 */
	private function getTagger(): ChangeTagger {
		$manager = $this->createMock( ChangeTagsManager::class );
		$manager->method( 'getCondsLimitTag' )->willReturn( 'tag' );
		return new ChangeTagger( $manager );
	}

	/**
	 * @return Generator
	 */
	public function getActionData(): Generator {
		$titleText = 'FOO';
		$title = new TitleValue( NS_MAIN, $titleText );
		$userName = 'Foobar';
		$getRCFromAttribs = function ( array $attribs ): RecentChange {
			$rc = $this->createMock( RecentChange::class );
			$rc->method( 'getAttribute' )->willReturnCallback(
				static function ( $name ) use ( $attribs ) {
					return $attribs[$name];
				}
			);
			return $rc;
		};
		$baseAttribs = [
			'rc_namespace' => NS_MAIN,
			'rc_title' => $titleText,
			'rc_user' => 42,
			'rc_user_text' => $userName,
			'rc_ip' => '127.0.0.1',
		];
		$specifierFromArray = static function ( array $specs ): ActionSpecifier {
			return new ActionSpecifier(
				$specs['action'],
				$specs['target'],
				new UserIdentityValue( 42, $specs['username'] ),
				$specs['ip'],
				$specs['accountname'] ?? null
			);
		};
		$baseSpecs = [ 'username' => $userName, 'target' => $title, 'ip' => '127.0.0.1' ];

		$rcAttribs = [ 'rc_log_type' => null ] + $baseAttribs;
		yield 'edit' => [
			'specifier' => $specifierFromArray( [ 'action' => 'edit' ] + $baseSpecs ),
			'recentchange' => $getRCFromAttribs( $rcAttribs )
		];

		$rcAttribs = [ 'rc_log_type' => 'newusers', 'rc_log_action' => 'create2' ] + $baseAttribs;
		yield 'createaccount' => [
			'specifier' => $specifierFromArray(
				[ 'action' => 'createaccount', 'accountname' => $userName ] + $baseSpecs
			),
			'recentchange' => $getRCFromAttribs( $rcAttribs )
		];

		$rcAttribs = [ 'rc_log_type' => 'newusers', 'rc_log_action' => 'autocreate' ] + $baseAttribs;
		yield 'autocreate' => [
			'specifier' => $specifierFromArray(
				[ 'action' => 'autocreateaccount', 'accountname' => $userName ] + $baseSpecs
			),
			'recentchange' => $getRCFromAttribs( $rcAttribs )
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		$this->getTagger()->clearBuffer();
	}

	/**
	 * @param ActionSpecifier $specifier
	 * @param RecentChange $rc
	 * @covers ::bufferTagsToSetByAction
	 * @dataProvider getActionData
	 */
	public function testTagsToSetWillNotContainDuplicates( ActionSpecifier $specifier, RecentChange $rc ) {
		$tagger = $this->getTagger();

		$iterations = 3;
		while ( $iterations-- ) {
			$tagger->addTags( $specifier, [ 'uniqueTag' ] );
			$this->assertSame( [ 'uniqueTag' ], $tagger->getTagsForRecentChange( $rc ) );
		}
	}

	/**
	 * @param ActionSpecifier $specifier
	 * @param RecentChange $rc
	 * @covers ::clearBuffer
	 * @dataProvider getActionData
	 */
	public function testClearBuffer( ActionSpecifier $specifier, RecentChange $rc ) {
		$tagger = $this->getTagger();

		$tagger->addTags( $specifier, [ 'a', 'b', 'c' ] );
		$tagger->clearBuffer();
		$this->assertSame( [], $tagger->getTagsForRecentChange( $rc ) );
	}

	/**
	 * @param ActionSpecifier $specifier
	 * @param RecentChange $rc
	 * @covers ::addConditionsLimitTag
	 * @dataProvider getActionData
	 */
	public function testAddConditionsLimitTag( ActionSpecifier $specifier, RecentChange $rc ) {
		$tagger = $this->getTagger();

		$tagger->addConditionsLimitTag( $specifier );
		$this->assertCount( 1, $tagger->getTagsForRecentChange( $rc ) );
	}

	/**
	 * @param ActionSpecifier $specifier
	 * @param RecentChange $rc
	 * @covers ::addTags
	 * @covers ::getTagsForRecentChange
	 * @covers ::getIDFromRecentChange
	 * @covers ::getActionID
	 * @covers ::getTagsForID
	 * @covers ::bufferTagsToSetByAction
	 * @dataProvider getActionData
	 */
	public function testAddGetTags( ActionSpecifier $specifier, RecentChange $rc ) {
		$tagger = $this->getTagger();

		$expected = [ 'foo', 'bar', 'baz' ];
		$tagger->addTags( $specifier, $expected );
		$this->assertSame( $expected, $tagger->getTagsForRecentChange( $rc ) );
	}

	/**
	 * @param ActionSpecifier $specifier
	 * @param RecentChange $rc
	 * @covers ::addTags
	 * @covers ::getActionID
	 * @covers ::bufferTagsToSetByAction
	 * @dataProvider getActionData
	 */
	public function testAddTags_multiple( ActionSpecifier $specifier, RecentChange $rc ) {
		$tagger = $this->getTagger();

		$expected = [ 'foo', 'bar', 'baz' ];
		foreach ( $expected as $tag ) {
			$tagger->addTags( $specifier, [ $tag ] );
		}
		$this->assertSame( $expected, $tagger->getTagsForRecentChange( $rc ) );
	}

	/**
	 * @param ActionSpecifier $specifier
	 * @param RecentChange $rc
	 * @covers ::getTagsForRecentChange
	 * @covers ::getIDFromRecentChange
	 * @covers ::getActionID
	 * @covers ::getTagsForID
	 * @dataProvider getActionData
	 */
	public function testGetTags_clear( ActionSpecifier $specifier, RecentChange $rc ) {
		$tagger = $this->getTagger();

		$expected = [ 'foo', 'bar', 'baz' ];
		$tagger->addTags( $specifier, $expected );

		$tagger->getTagsForRecentChange( $rc, false );
		$this->assertSame( $expected, $tagger->getTagsForRecentChange( $rc ), 'no clear' );
		$tagger->getTagsForRecentChange( $rc );
		$this->assertSame( [], $tagger->getTagsForRecentChange( $rc ), 'clear' );
	}
}
