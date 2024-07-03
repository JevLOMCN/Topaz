<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use Content;
use MediaWiki\Extension\AbuseFilter\BlockedDomainFilter;
use MediaWiki\Extension\AbuseFilter\BlockedDomainStorage;
use MediaWiki\Extension\AbuseFilter\EditRevUpdater;
use MediaWiki\Extension\AbuseFilter\FilterRunner;
use MediaWiki\Extension\AbuseFilter\FilterRunnerFactory;
use MediaWiki\Extension\AbuseFilter\Hooks\Handlers\FilteredActionsHandler;
use MediaWiki\Extension\AbuseFilter\Parser\AFPData;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\RunVariableGenerator;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use Message;
use NullStatsdDataFactory;
use RequestContext;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Hooks\Handlers\FilteredActionsHandler
 * @group Database
 */
class FilteredActionsHandlerTest extends \MediaWikiIntegrationTestCase {

	private array $blockedDomains = [ 'foo.com' => true ];

	/**
	 * @dataProvider provideOnEditFilterMergedContent
	 * @covers ::onEditFilterMergedContent
	 * @covers \MediaWiki\Extension\AbuseFilter\BlockedDomainFilter
	 */
	public function testOnEditFilterMergedContent( $urlsAdded, $expected ) {
		$this->setMwGlobals( 'wgAbuseFilterEnableBlockedExternalDomain', true );

		$filteredActionsHandler = $this->getFilteredActionsHandler( $urlsAdded );
		$context = RequestContext::getMain();
		$context->setTitle( Title::newFromText( 'TestPage' ) );
		$content = $this->createMock( Content::class );
		$user = $this->getTestUser()->getUser();

		$status = Status::newGood();

		$res = $filteredActionsHandler->onEditFilterMergedContent(
			$context,
			$content,
			$status,
			'Edit summary',
			$user,
			false
		);
		$this->assertSame( $expected, $res );
		$this->assertSame( $expected, $status->isOK() );

		if ( !$expected ) {
			// If it's failing, it should report the URL somewhere
			$this->assertStringContainsString(
				'foo.com',
				$status->getErrors()[0]['message']->toString( Message::FORMAT_PLAIN )
			);
		}
	}

	public static function provideOnEditFilterMergedContent() {
		return [
			'subdomain of blocked domain' => [ 'https://bar.foo.com', false ],
			'bare domain with nothing' => [ 'https://foo.com', false ],
			'blocked domain with path' => [ 'https://foo.com/foo/', false ],
			'blocked domain with parameters' => [ 'https://foo.com?foo=bar', false ],
			'blocked domain with path and parameters' => [ 'https://foo.com/foo/?foo=bar', false ],
			'blocked domain with port' => [ 'https://foo.com:9000', false ],
			'blocked domain as uppercase' => [ 'https://FOO.com', false ],
			'unusual protocol' => [ 'ftp://foo.com', false ],
			'mailto is special' => [ 'mailto://user@foo.com', false ],
			'domain not blocked' => [ 'https://foo.bar.com', true ],
			'domain not blocked but it might mistake the subdomain' => [ 'https://foo.com.bar.com', true ],
		];
	}

	private function getFilteredActionsHandler( $urlsAdded ): FilteredActionsHandler {
		$mockRunner = $this->createMock( FilterRunner::class );
		$mockRunner->method( 'run' )
			->willReturn( Status::newGood() );
		$filterRunnerFactory = $this->createMock( FilterRunnerFactory::class );
		$filterRunnerFactory->method( 'newRunner' )
			->willReturn( $mockRunner );

		$vars = new VariableHolder();
		$vars->setVar( 'added_links', AFPData::newFromPHPVar( $urlsAdded ) );

		$variableGenerator = $this->createMock( RunVariableGenerator::class );
		$variableGenerator->method( 'getEditVars' )
			->willReturn( $vars );

		$variableGeneratorFactory = $this->createMock( VariableGeneratorFactory::class );
		$variableGeneratorFactory->method( 'newRunGenerator' )
			 ->willReturn( $variableGenerator );

		$editRevUpdater = $this->createMock( EditRevUpdater::class );

		$variablesManager = $this->createMock( VariablesManager::class );
		$variablesManager->method( 'getVar' )
			->willReturnCallback( fn ( $unused, $vars ) => AFPData::newFromPHPVar( $urlsAdded ) );

		$blockedDomainStorage = $this->createMock( BlockedDomainStorage::class );
		$blockedDomainStorage->method( 'loadComputed' )
			->willReturn( $this->blockedDomains );
		$blockedDomainFilter = new BlockedDomainFilter( $variablesManager, $blockedDomainStorage );

		$permissionManager = $this->createMock( PermissionManager::class );
		$permissionManager->method( 'userHasRight' )
			 ->willReturn( false );

		return new FilteredActionsHandler(
			new NullStatsdDataFactory(),
			$filterRunnerFactory,
			$variableGeneratorFactory,
			$editRevUpdater,
			$blockedDomainFilter,
			$permissionManager
		);
	}
}
