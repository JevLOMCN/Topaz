<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Special;

use Generator;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseLog;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Permissions\SimpleAuthority;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;
use stdClass;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseLog
 */
class SpecialAbuseLogTest extends MediaWikiIntegrationTestCase {
	/**
	 * @param stdClass $row
	 * @param RevisionRecord $revRec
	 * @param bool $canSeeHidden
	 * @param bool $canSeeSuppressed
	 * @param string $expected
	 * @dataProvider provideEntryAndVisibility
	 * @covers ::getEntryVisibilityForUser
	 */
	public function testGetEntryVisibilityForUser(
		stdClass $row,
		RevisionRecord $revRec,
		bool $canSeeHidden,
		bool $canSeeSuppressed,
		string $expected
	) {
		$user = $this->createMock( UserIdentity::class );
		$authority = new SimpleAuthority( $user, $canSeeSuppressed ? [ 'viewsuppressed' ] : [] );
		$afPermManager = $this->createMock( AbuseFilterPermissionManager::class );
		$afPermManager->method( 'canSeeHiddenLogEntries' )->with( $authority )->willReturn( $canSeeHidden );
		$revLookup = $this->createMock( RevisionLookup::class );
		$revLookup->method( 'getRevisionById' )->willReturn( $revRec );
		$this->setService( 'RevisionLookup', $revLookup );
		$this->assertSame(
			$expected,
			SpecialAbuseLog::getEntryVisibilityForUser( $row, $authority, $afPermManager )
		);
	}

	public static function provideEntryAndVisibility(): Generator {
		$visibleRow = (object)[ 'afl_rev_id' => 1, 'afl_deleted' => 0 ];
		$hiddenRow = (object)[ 'afl_rev_id' => 1, 'afl_deleted' => 1 ];
		$page = new PageIdentityValue( 1, NS_MAIN, 'Foo', PageIdentityValue::LOCAL );
		$visibleRev = new MutableRevisionRecord( $page );

		yield 'Visible entry and rev, cannot see hidden, cannot see suppressed' =>
			[ $visibleRow, $visibleRev, false, false, SpecialAbuseLog::VISIBILITY_VISIBLE ];
		yield 'Visible entry and rev, can see hidden, cannot see suppressed' =>
			[ $visibleRow, $visibleRev, true, false, SpecialAbuseLog::VISIBILITY_VISIBLE ];
		yield 'Visible entry and rev, cannot see hidden, can see suppressed' =>
			[ $visibleRow, $visibleRev, false, false, SpecialAbuseLog::VISIBILITY_VISIBLE ];
		yield 'Visible entry and rev, can see hidden, can see suppressed' =>
			[ $visibleRow, $visibleRev, true, false, SpecialAbuseLog::VISIBILITY_VISIBLE ];

		yield 'Hidden entry, visible rev, can see hidden, cannot see suppressed' =>
			[ $hiddenRow, $visibleRev, true, false, SpecialAbuseLog::VISIBILITY_VISIBLE ];
		yield 'Hidden entry, visible rev, cannot see hidden, cannot see suppressed' =>
			[ $hiddenRow, $visibleRev, false, false, SpecialAbuseLog::VISIBILITY_HIDDEN ];
		yield 'Hidden entry, visible rev, can see hidden, can see suppressed' =>
			[ $hiddenRow, $visibleRev, true, true, SpecialAbuseLog::VISIBILITY_VISIBLE ];
		yield 'Hidden entry, visible rev, cannot see hidden, can see suppressed' =>
			[ $hiddenRow, $visibleRev, false, true, SpecialAbuseLog::VISIBILITY_HIDDEN ];

		$userSupRev = new MutableRevisionRecord( $page );
		$userSupRev->setVisibility( RevisionRecord::SUPPRESSED_USER );
		yield 'Hidden entry, user suppressed rev, can see hidden, cannot see suppressed' =>
			[ $hiddenRow, $userSupRev, true, false, SpecialAbuseLog::VISIBILITY_HIDDEN_IMPLICIT ];
		yield 'Hidden entry, user suppressed rev, cannot see hidden, cannot see suppressed' =>
			[ $hiddenRow, $userSupRev, false, false, SpecialAbuseLog::VISIBILITY_HIDDEN ];
		yield 'Hidden entry, user suppressed rev, can see hidden, can see suppressed' =>
			[ $hiddenRow, $userSupRev, true, true, SpecialAbuseLog::VISIBILITY_VISIBLE ];
		yield 'Hidden entry, user suppressed rev, cannot see hidden, can see suppressed' =>
			[ $hiddenRow, $userSupRev, false, true, SpecialAbuseLog::VISIBILITY_HIDDEN ];

		$allSuppRev = new MutableRevisionRecord( $page );
		$allSuppRev->setVisibility( RevisionRecord::SUPPRESSED_ALL );
		yield 'Hidden entry, all suppressed rev, can see hidden, cannot see suppressed' =>
			[ $hiddenRow, $allSuppRev, true, false, SpecialAbuseLog::VISIBILITY_HIDDEN_IMPLICIT ];
		yield 'Hidden entry, all suppressed rev, cannot see hidden, cannot see suppressed' =>
			[ $hiddenRow, $allSuppRev, false, false, SpecialAbuseLog::VISIBILITY_HIDDEN ];
		yield 'Hidden entry, all suppressed rev, can see hidden, can see suppressed' =>
			[ $hiddenRow, $allSuppRev, true, true, SpecialAbuseLog::VISIBILITY_VISIBLE ];
		yield 'Hidden entry, all suppressed rev, cannot see hidden, can see suppressed' =>
			[ $hiddenRow, $allSuppRev, false, true, SpecialAbuseLog::VISIBILITY_HIDDEN ];
	}
}
