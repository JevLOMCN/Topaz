<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use Generator;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Filter\AbstractFilter;
use MediaWiki\Extension\AbuseFilter\Filter\MutableFilter;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager
 */
class AbuseFilterPermissionManagerTest extends MediaWikiUnitTestCase {
	use MockAuthorityTrait;

	private function getPermMan(): AbuseFilterPermissionManager {
		// No longer has any dependencies
		return new AbuseFilterPermissionManager();
	}

	public function provideCanEdit(): Generator {
		$sitewideBlock = $this->createMock( DatabaseBlock::class );
		$sitewideBlock->method( 'isSiteWide' )->willReturn( true );
		yield 'blocked sitewide' => [ $sitewideBlock, [], false ];

		$partialBlock = $this->createMock( DatabaseBlock::class );
		$partialBlock->method( 'isSiteWide' )->willReturn( false );
		yield 'partially blocked' => [ $partialBlock, [], false ];

		yield 'unblocked, no right' => [ null, [], false ];

		yield 'success' => [ null, [ 'abusefilter-modify' ], true ];
	}

	/**
	 * @param ?DatabaseBlock $block
	 * @param array $rights
	 * @param bool $expected
	 * @covers ::canEdit
	 * @dataProvider provideCanEdit
	 */
	public function testCanEdit( ?DatabaseBlock $block, array $rights, bool $expected ) {
		if ( $block !== null ) {
			$performer = $this->mockUserAuthorityWithBlock(
				$this->mockRegisteredUltimateAuthority()->getUser(),
				$block,
				$rights
			);
		} else {
			$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		}
		$this->assertSame(
			$expected,
			$this->getPermMan()->canEdit( $performer )
		);
	}

	public static function provideCanEditGlobal(): Generator {
		yield 'not allowed' => [ [], false ];
		yield 'allowed' => [ [ 'abusefilter-modify-global' ], true ];
	}

	/**
	 * @covers ::canEditGlobal
	 * @dataProvider provideCanEditGlobal
	 */
	public function testCanEditGlobal( array $rights, bool $expected ) {
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$expected,
			$this->getPermMan()->canEditGlobal( $performer )
		);
	}

	public function provideCanEditFilter(): Generator {
		$localFilter = MutableFilter::newDefault();
		$localFilter->setGlobal( false );
		$globalFilter = MutableFilter::newDefault();
		$globalFilter->setGlobal( true );
		foreach ( $this->provideCanEdit() as $name => $editArgs ) {
			foreach ( self::provideCanEditGlobal() as $allowed => $globalArgs ) {
				yield "can edit: $name; can edit global: $allowed; local filter" => [
					$localFilter,
					$editArgs[0],
					array_merge( $editArgs[1], $globalArgs[0] ),
					$editArgs[2]
				];
				yield "can edit: $name; can edit global: $allowed; global filter" => [
					$globalFilter,
					$editArgs[0],
					array_merge( $editArgs[1], $globalArgs[0] ),
					$editArgs[2] && $globalArgs[1]
				];
			}
		}
	}

	/**
	 * @param AbstractFilter $filter
	 * @param ?DatabaseBlock $block
	 * @param array $rights
	 * @param bool $expected
	 * @covers ::canEditFilter
	 * @dataProvider provideCanEditFilter
	 */
	public function testCanEditFilter(
		AbstractFilter $filter,
		?DatabaseBlock $block,
		array $rights,
		bool $expected
	) {
		if ( $block !== null ) {
			$performer = $this->mockUserAuthorityWithBlock(
				$this->mockRegisteredUltimateAuthority()->getUser(),
				$block,
				$rights
			);
		} else {
			$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		}
		$this->assertSame(
			$expected,
			$this->getPermMan()->canEditFilter( $performer, $filter )
		);
	}

	public static function provideCanViewPrivateFilters(): Generator {
		yield 'not privileged' => [ [], false ];
		yield 'modify' => [ [ 'abusefilter-modify' ], true ];
		yield 'private' => [ [ 'abusefilter-view-private' ], true ];
		yield 'both' => [ [ 'abusefilter-modify', 'abusefilter-view-private' ], true ];
	}

	/**
	 * @covers ::canViewPrivateFilters
	 * @dataProvider provideCanViewPrivateFilters
	 */
	public function testCanViewPrivateFilters( array $rights, bool $expected ) {
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$expected,
			$this->getPermMan()->canViewPrivateFilters( $performer )
		);
	}

	public static function provideCanViewPrivateFiltersLogs(): Generator {
		yield 'not privileged' => [ [], false ];
		yield 'can view private' => [ [ 'abusefilter-view-private' ], true ];
		yield 'can view logs' => [ [ 'abusefilter-log-private' ], true ];
		yield 'both' => [ [ 'abusefilter-view-private', 'abusefilter-log-private' ], true ];
	}

	/**
	 * @param array $rights
	 * @param bool $expected
	 * @covers ::canViewPrivateFiltersLogs
	 * @dataProvider provideCanViewPrivateFiltersLogs
	 */
	public function testCanViewPrivateFiltersLogs( array $rights, bool $expected ) {
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$expected,
			$this->getPermMan()->canViewPrivateFiltersLogs( $performer )
		);
	}

	public static function provideCanSeeLogDetailsForFilter(): Generator {
		$details = [ 0 => 'abusefilter-log-detail' ];
		$private = [ 1 => 'abusefilter-log-private' ];
		yield 'filter hidden, not privileged' => [ true, [], false ];
		yield 'filter hidden, details only' => [ true, $details, false ];
		yield 'filter hidden, private logs only' => [ true, $private, false ];
		yield 'filter hidden, details and private logs' => [ true, $details + $private, true ];
		yield 'filter visible, not privileged' => [ false, [], false ];
		yield 'filter visible, privileged' => [ false, $details, true ];
	}

	/**
	 * @param bool $filterHidden
	 * @param array $rights
	 * @param bool $expected
	 * @covers ::canSeeLogDetailsForFilter
	 * @dataProvider provideCanSeeLogDetailsForFilter
	 */
	public function testCanSeeLogDetailsForFilter( bool $filterHidden, array $rights, bool $expected ) {
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$expected,
			$this->getPermMan()->canSeeLogDetailsForFilter( $performer, $filterHidden )
		);
	}

	public static function provideSimpleCases(): array {
		return [
			'not allowed' => [ false ],
			'allowed' => [ true ],
		];
	}

	/**
	 * @covers ::canEditFilterWithRestrictedActions
	 * @dataProvider provideSimpleCases
	 */
	public function testCanEditFilterWithRestrictedActions( bool $allowed ) {
		$rights = $allowed ? [ 'abusefilter-modify-restricted' ] : [];
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$allowed,
			$this->getPermMan()->canEditFilterWithRestrictedActions( $performer )
		);
	}

	/**
	 * @covers ::canViewAbuseLog
	 * @dataProvider provideSimpleCases
	 */
	public function testCanViewAbuseLog( bool $allowed ) {
		$rights = $allowed ? [ 'abusefilter-log' ] : [];
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$allowed,
			$this->getPermMan()->canViewAbuseLog( $performer )
		);
	}

	/**
	 * @covers ::canHideAbuseLog
	 * @dataProvider provideSimpleCases
	 */
	public function testCanHideAbuseLog( bool $allowed ) {
		$rights = $allowed ? [ 'abusefilter-hide-log' ] : [];
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$allowed,
			$this->getPermMan()->canHideAbuseLog( $performer )
		);
	}

	/**
	 * @covers ::canRevertFilterActions
	 * @dataProvider provideSimpleCases
	 */
	public function testCanRevertFilterActions( bool $allowed ) {
		$rights = $allowed ? [ 'abusefilter-revert' ] : [];
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$allowed,
			$this->getPermMan()->canRevertFilterActions( $performer )
		);
	}

	/**
	 * @covers ::canSeeLogDetails
	 * @dataProvider provideSimpleCases
	 */
	public function testCanSeeLogDetails( bool $allowed ) {
		$rights = $allowed ? [ 'abusefilter-log-detail' ] : [];
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$allowed,
			$this->getPermMan()->canSeeLogDetails( $performer )
		);
	}

	/**
	 * @covers ::canSeePrivateDetails
	 * @dataProvider provideSimpleCases
	 */
	public function testCanSeePrivateDetails( bool $allowed ) {
		$rights = $allowed ? [ 'abusefilter-privatedetails' ] : [];
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$allowed,
			$this->getPermMan()->canSeePrivateDetails( $performer )
		);
	}

	/**
	 * @covers ::canSeeHiddenLogEntries
	 * @dataProvider provideSimpleCases
	 */
	public function testCanSeeHiddenLogEntries( bool $allowed ) {
		$rights = $allowed ? [ 'abusefilter-hidden-log' ] : [];
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$allowed,
			$this->getPermMan()->canSeeHiddenLogEntries( $performer )
		);
	}

	/**
	 * @covers ::canUseTestTools
	 * @dataProvider provideSimpleCases
	 */
	public function testCanUseTestTools( bool $allowed ) {
		$rights = $allowed ? [ 'abusefilter-modify' ] : [];
		$performer = $this->mockRegisteredAuthorityWithPermissions( $rights );
		$this->assertSame(
			$allowed,
			$this->getPermMan()->canUseTestTools( $performer )
		);
	}

}
