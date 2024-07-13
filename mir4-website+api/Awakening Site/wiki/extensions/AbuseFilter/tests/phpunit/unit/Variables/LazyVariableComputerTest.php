<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use Generator;
use Language;
use LogicException;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\Parser\AFPData;
use MediaWiki\Extension\AbuseFilter\TextExtractor;
use MediaWiki\Extension\AbuseFilter\Variables\LazyLoadedVariable;
use MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Permissions\RestrictionStore;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserEditTracker;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentityUtils;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use ParserFactory;
use Psr\Log\NullLogger;
use UnexpectedValueException;
use WANObjectCache;
use Wikimedia\Rdbms\LBFactory;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer
 * @covers ::__construct
 */
class LazyVariableComputerTest extends MediaWikiUnitTestCase {

	private function getComputer(
		array $services = [],
		array $hookHandlers = [],
		string $wikiID = ''
	): LazyVariableComputer {
		return new LazyVariableComputer(
			$this->createMock( TextExtractor::class ),
			new AbuseFilterHookRunner( $this->createHookContainer( $hookHandlers ) ),
			new NullLogger(),
			$this->createMock( LBFactory::class ),
			$this->createMock( WANObjectCache::class ),
			$services['RevisionLookup'] ?? $this->createMock( RevisionLookup::class ),
			$this->createMock( RevisionStore::class ),
			$services['ContentLanguage'] ?? $this->createMock( Language::class ),
			$this->createMock( ParserFactory::class ),
			$services['UserEditTracker'] ?? $this->createMock( UserEditTracker::class ),
			$services['UserGroupManager'] ?? $this->createMock( UserGroupManager::class ),
			$services['PermissionManager'] ?? $this->createMock( PermissionManager::class ),
			$services['RestrictionStore'] ?? $this->createMock( RestrictionStore::class ),
			$services['UserIdentityUtils'] ?? $this->createMock( UserIdentityUtils::class ),
			$wikiID
		);
	}

	private function getForbidComputeCB(): callable {
		return static function () {
			throw new LogicException( 'Not expected to be called' );
		};
	}

	/**
	 * @covers ::compute
	 */
	public function testWikiNameVar() {
		$fakeID = 'some-wiki-ID';
		$var = new LazyLoadedVariable( 'get-wiki-name', [] );
		$computer = $this->getComputer( [], [], $fakeID );
		$this->assertSame(
			$fakeID,
			$computer->compute( $var, new VariableHolder(), $this->getForbidComputeCB() )->toNative()
		);
	}

	/**
	 * @covers ::compute
	 */
	public function testWikiLanguageVar() {
		$fakeCode = 'foobar';
		$fakeLang = $this->createMock( Language::class );
		$fakeLang->method( 'getCode' )->willReturn( $fakeCode );
		$computer = $this->getComputer( [ 'ContentLanguage' => $fakeLang ] );
		$var = new LazyLoadedVariable( 'get-wiki-language', [] );
		$this->assertSame(
			$fakeCode,
			$computer->compute( $var, new VariableHolder(), $this->getForbidComputeCB() )->toNative()
		);
	}

	/**
	 * @covers ::compute
	 */
	public function testCompute_invalidName() {
		$computer = $this->getComputer();
		$this->expectException( UnexpectedValueException::class );
		$computer->compute(
			new LazyLoadedVariable( 'method-does-not-exist', [] ),
			new VariableHolder(),
			$this->getForbidComputeCB()
		);
	}

	/**
	 * @covers ::compute
	 */
	public function testInterceptVariableHook() {
		$expected = new AFPData( AFPData::DSTRING, 'foobar' );
		$handler = static function ( $method, $vars, $params, &$result ) use ( $expected ) {
			$result = $expected;
			return false;
		};
		$computer = $this->getComputer( [], [ 'AbuseFilter-interceptVariable' => $handler ] );
		$actual = $computer->compute(
			new LazyLoadedVariable( 'get-wiki-name', [] ),
			new VariableHolder(),
			$this->getForbidComputeCB()
		);
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers ::compute
	 */
	public function testComputeVariableHook() {
		$expected = new AFPData( AFPData::DSTRING, 'foobar' );
		$handler = static function ( $method, $vars, $params, &$result ) use ( $expected ) {
			$result = $expected;
			return false;
		};
		$computer = $this->getComputer( [], [ 'AbuseFilter-computeVariable' => $handler ] );
		$actual = $computer->compute(
			new LazyLoadedVariable( 'method-does-not-exist', [] ),
			new VariableHolder(),
			$this->getForbidComputeCB()
		);
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @param LazyLoadedVariable $var
	 * @param mixed $expected
	 * @param array $services
	 * @covers ::compute
	 * @dataProvider provideUserRelatedVars
	 */
	public function testUserRelatedVars(
		LazyLoadedVariable $var,
		$expected,
		array $services = []
	) {
		$computer = $this->getComputer( $services );
		$this->assertSame(
			$expected,
			$computer->compute( $var, new VariableHolder(), $this->getForbidComputeCB() )->toNative()
		);
	}

	public function provideUserRelatedVars(): Generator {
		$user = $this->createMock( User::class );
		$getUserVar = static function ( $user, $method ): LazyLoadedVariable {
			return new LazyLoadedVariable(
				$method,
				[ 'user' => $user, 'user-identity' => $user ]
			);
		};

		$editCount = 7;

		$userEditTracker = $this->createMock( UserEditTracker::class );

		$userEditTracker->method( 'getUserEditCount' )->with( $user )->willReturn( $editCount );
		$var = $getUserVar( $user, 'user-editcount' );
		yield 'user_editcount' => [ $var, $editCount, [ 'UserEditTracker' => $userEditTracker ] ];

		$emailConfirm = '20000101000000';
		$user->method( 'getEmailAuthenticationTimestamp' )->willReturn( $emailConfirm );
		$var = $getUserVar( $user, 'user-emailconfirm' );
		yield 'user_emailconfirm' => [ $var, $emailConfirm ];

		$mockUserIdentityUtils = $this->createMock( UserIdentityUtils::class );
		$mockUserIdentityUtils->method( 'isNamed' )->with( $user )->willReturn( true );
		$var = $getUserVar( $user, 'user-type' );
		yield 'user_type for named user' => [ $var, 'named', [ 'UserIdentityUtils' => $mockUserIdentityUtils ] ];

		$mockUserIdentityUtils = $this->createMock( UserIdentityUtils::class );
		$mockUserIdentityUtils->method( 'isNamed' )->with( $user )->willReturn( false );
		$mockUserIdentityUtils->method( 'isTemp' )->with( $user )->willReturn( true );
		$var = $getUserVar( $user, 'user-type' );
		yield 'user_type for named temporary user' => [
			$var, 'temp', [ 'UserIdentityUtils' => $mockUserIdentityUtils ]
		];

		$user = $this->createMock( User::class );
		$user->method( 'getName' )->willReturn( '127.0.0.1' );
		$var = $getUserVar( $user, 'user-type' );
		yield 'user_type for logged-out user' => [ $var, 'ip' ];

		$user = $this->createMock( User::class );
		$user->method( 'getName' )->willReturn( 'mediawiki>testing' );
		$var = $getUserVar( $user, 'user-type' );
		yield 'user_type for an external username' => [ $var, 'external' ];

		$user = $this->createMock( User::class );
		$user->method( 'getName' )->willReturn( 'Non-existing user 1234' );
		$var = $getUserVar( $user, 'user-type' );
		yield 'user_type for unregistered username' => [ $var, 'unknown' ];

		$user = $this->createMock( User::class );
		$groups = [ '*', 'group1', 'group2' ];
		$userGroupManager = $this->createMock( UserGroupManager::class );
		$userGroupManager->method( 'getUserEffectiveGroups' )->with( $user )->willReturn( $groups );
		$var = $getUserVar( $user, 'user-groups' );
		yield 'user_groups' => [ $var, $groups, [ 'UserGroupManager' => $userGroupManager ] ];

		$rights = [ 'abusefilter-foo', 'abusefilter-bar' ];
		$permissionManager = $this->createMock( PermissionManager::class );
		$permissionManager->method( 'getUserPermissions' )->with( $user )->willReturn( $rights );
		$var = $getUserVar( $user, 'user-rights' );
		yield 'user_rights' => [ $var, $rights, [ 'PermissionManager' => $permissionManager ] ];

		$block = new SystemBlock( [] );
		$user->method( 'getBlock' )->willReturn( $block );
		$var = $getUserVar( $user, 'user-block' );
		yield 'user_blocked' => [ $var, (bool)$block ];

		$fakeTime = 1514700000;

		$anonUser = $this->createMock( User::class );
		$anonymousAge = 0;
		$var = new LazyLoadedVariable(
			'user-age',
			[ 'user' => $anonUser, 'asof' => $fakeTime ]
		);
		yield 'user_age, anonymous' => [ $var, $anonymousAge ];

		$user->method( 'isRegistered' )->willReturn( true );

		$missingRegistrationUser = clone $user;
		$var = new LazyLoadedVariable(
			'user-age',
			[ 'user' => $missingRegistrationUser, 'asof' => $fakeTime ]
		);
		$expected = (int)wfTimestamp( TS_UNIX, $fakeTime ) - (int)wfTimestamp( TS_UNIX, '20080115000000' );
		yield 'user_age, registered but not available' => [ $var, $expected ];

		$age = 163;
		$user->method( 'getRegistration' )->willReturn( $fakeTime - $age );
		$var = new LazyLoadedVariable(
			'user-age',
			[ 'user' => $user, 'asof' => $fakeTime ]
		);
		yield 'user_age, registered' => [ $var, $age ];
	}

	/**
	 * @param LazyLoadedVariable $var
	 * @param mixed $expected
	 * @param array $services
	 * @covers ::compute
	 * @dataProvider provideTitleRelatedVars
	 */
	public function testTitleRelatedVars(
		LazyLoadedVariable $var,
		$expected,
		array $services = []
	) {
		$computer = $this->getComputer( $services );
		$this->assertSame(
			$expected,
			$computer->compute( $var, new VariableHolder(), $this->getForbidComputeCB() )->toNative()
		);
	}

	public function provideTitleRelatedVars(): Generator {
		$restrictions = [ 'create', 'edit', 'move', 'upload' ];
		foreach ( $restrictions as $restriction ) {
			$appliedRestrictions = [ 'sysop' ];
			$restrictedTitle = $this->createMock( Title::class );
			$restrictionStore = $this->createMock( RestrictionStore::class );
			$restrictionStore->expects( $this->once() )
				->method( 'getRestrictions' )
				->with( $restrictedTitle, $restriction )
				->willReturn( $appliedRestrictions );
			$var = new LazyLoadedVariable(
				'get-page-restrictions',
				[ 'title' => $restrictedTitle, 'action' => $restriction ]
			);
			yield "*_restrictions_{$restriction}, restricted" => [
				$var, $appliedRestrictions, [ 'RestrictionStore' => $restrictionStore ]
			];

			$unrestrictedTitle = $this->createMock( Title::class );
			$restrictionStore = $this->createMock( RestrictionStore::class );
			$restrictionStore->expects( $this->once() )
				->method( 'getRestrictions' )
				->with( $unrestrictedTitle, $restriction )
				->willReturn( [] );
			$var = new LazyLoadedVariable(
				'get-page-restrictions',
				[ 'title' => $unrestrictedTitle, 'action' => $restriction ]
			);
			yield "*_restrictions_{$restriction}, unrestricted" => [
				$var, [], [ 'RestrictionStore' => $restrictionStore ]
			];
		}

		$fakeTime = 1514700000;

		$age = 163;
		$title = $this->createMock( Title::class );
		$revision = $this->createMock( RevisionRecord::class );
		$revision->method( 'getTimestamp' )->willReturn( $fakeTime - $age );
		$revLookup = $this->createMock( RevisionLookup::class );
		$revLookup->method( 'getFirstRevision' )->with( $title )->willReturn( $revision );
		$var = new LazyLoadedVariable(
			'page-age',
			[ 'title' => $title, 'asof' => $fakeTime ]
		);
		yield "*_age" => [ $var, $age, [ 'RevisionLookup' => $revLookup ] ];

		$title = $this->createMock( Title::class );
		$firstRev = $this->createMock( RevisionRecord::class );
		$firstUserName = 'First author';
		$firstUser = new UserIdentityValue( 1, $firstUserName );
		$firstRev->expects( $this->once() )->method( 'getUser' )->willReturn( $firstUser );
		$revLookup = $this->createMock( RevisionLookup::class );
		$revLookup->method( 'getFirstRevision' )->with( $title )->willReturn( $firstRev );
		$var = new LazyLoadedVariable(
			'load-first-author',
			[ 'title' => $title ]
		);
		yield '*_first_contributor, with rev' => [
			$var, $firstUserName, [ 'RevisionLookup' => $revLookup ]
		];

		$title = $this->createMock( Title::class );
		$revLookup = $this->createMock( RevisionLookup::class );
		$revLookup->method( 'getFirstRevision' )->with( $title )->willReturn( null );
		$var = new LazyLoadedVariable(
			'load-first-author',
			[ 'title' => $title ]
		);
		yield '*_first_contributor, no rev' => [ $var, '', [ 'RevisionLookup' => $revLookup ] ];

		// TODO _recent_contributors is tested in LazyVariableComputerDBTest
	}
}
