<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use AbuseFilterCreateAccountTestTrait;
use ApiTestCase;
use ApiUsageException;
use Content;
use FormatJson;
use Generator;
use JsonContent;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\AbuseLogger;
use MediaWiki\Extension\AbuseFilter\AbuseLoggerFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesLookup;
use MediaWiki\Extension\AbuseFilter\EditRevUpdater;
use MediaWiki\Extension\AbuseFilter\EmergencyCache;
use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\LastEditInfo;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\FilterProfiler;
use MediaWiki\Extension\AbuseFilter\Hooks\Handlers\FilteredActionsHandler;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Watcher\EmergencyWatcher;
use MediaWiki\Extension\AbuseFilter\Watcher\UpdateHitCountWatcher;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use NullStatsdDataFactory;
use WikitextContent;

/**
 * @group Database
 * @group medium
 * @group AbuseFilter
 * @group AbuseFilterGeneric
 */
class ActionVariablesIntegrationTest extends ApiTestCase {
	use AbuseFilterCreateAccountTestTrait;

	private function prepareServices(): void {
		$this->setService(
			FilterProfiler::SERVICE_NAME,
			$this->createMock( FilterProfiler::class )
		);
		$this->setService(
			EmergencyCache::SERVICE_NAME,
			$this->createMock( EmergencyCache::class )
		);
		$this->setService(
			EmergencyWatcher::SERVICE_NAME,
			$this->createMock( EmergencyWatcher::class )
		);
		$this->setService(
			UpdateHitCountWatcher::SERVICE_NAME,
			$this->createMock( UpdateHitCountWatcher::class )
		);
		$this->setService(
			EditRevUpdater::SERVICE_NAME,
			$this->createMock( EditRevUpdater::class )
		);

		$this->overrideConfigValues( [
			MainConfigNames::PageCreationLog => false,
			'AbuseFilterCentralDB' => false,
		] );

		$filter = new ExistingFilter(
			new Specs( '1 === 1', '', 'Test Filter', [ 'disallow' ], 'default' ),
			new Flags( true, false, false, false ),
			[ 'disallow' => [ 'abusefilter-disallow' ] ],
			new LastEditInfo( 1, 'Filter User', '20220713000000' ),
			1,
			0,
			false
		);
		$filterLookup = $this->createMock( FilterLookup::class );
		$filterLookup->expects( $this->once() )
			->method( 'getAllActiveFiltersInGroup' )
			->with( 'default', false )
			->willReturn( [ $filter ] );
		$filterLookup->method( 'getFilter' )
			->with( 1, false )
			->willReturn( $filter );
		$this->setService( FilterLookup::SERVICE_NAME, $filterLookup );

		$consequencesLookup = $this->createMock( ConsequencesLookup::class );
		$consequencesLookup->method( 'getConsequencesForFilters' )
			->with( $this->logicalOr( [ 1 ], [ '1' ] ) )
			->willReturn( [ 1 => $filter->getActions() ] );
		$this->setService( ConsequencesLookup::SERVICE_NAME, $consequencesLookup );
	}

	private function setAbuseLoggerFactoryWithEavesdrop( VariableHolder &$varHolder = null ): void {
		$factory = $this->createMock( AbuseLoggerFactory::class );
		$factory->method( 'newLogger' )
			->willReturnCallback( function ( $title, $user, $vars ) use ( &$varHolder ) {
				$varHolder = $vars;
				$logger = $this->createMock( AbuseLogger::class );
				$logger->method( 'addLogEntries' )
					->willReturn( [ 'local' => [ 1 ], 'global' => [] ] );
				return $logger;
			} );
		$this->setService( AbuseLoggerFactory::SERVICE_NAME, $factory );
	}

	private function assertVariables( array $expected, array $export ) {
		foreach ( $expected as $var => $value ) {
			$this->assertArrayHasKey( $var, $export, "Variable '$var' not set" );

			$actual = $export[$var];
			if ( $var === 'new_html' && is_array( $value ) ) {
				// Special case for new_html: avoid flaky tests, and only check containment
				$this->assertStringContainsString( 'mw-parser-output', $actual );
				$this->assertDoesNotMatchRegularExpression( "/<!--\s*NewPP limit/", $actual );
				$this->assertDoesNotMatchRegularExpression( "/<!--\s*Transclusion/", $actual );
				foreach ( $value as $needle ) {
					$this->assertStringContainsString( $needle, $actual, 'Checking new_html' );
				}
			} else {
				if ( is_string( $actual ) ) {
					// TODO: remove and fix expected values once
					// https://gerrit.wikimedia.org/r/c/mediawiki/core/+/987191/ has been merged
					$actual = str_replace( "\t", '    ', $actual );
				}

				$this->assertSame( $value, $actual, $var );
			}
		}
	}

	public static function provideEditVariables(): Generator {
		$summary = __METHOD__;
		$new = '[https://a.com Test] foo';

		yield 'create page' => [
			'expected' => [
				'action' => 'edit',
				'old_wikitext' => '',
				'old_content_model' => '',
				'new_wikitext' => $new,
				'new_content_model' => 'wikitext',
				'summary' => $summary,
				'new_pst' => $new,
				'new_text' => "Test foo",
				'edit_diff' => "@@ -1,0 +1,1 @@\n+$new\n",
				'edit_diff_pst' => "@@ -1,0 +1,1 @@\n+$new\n",
				'new_size' => strlen( $new ),
				'old_size' => 0,
				'edit_delta' => strlen( $new ),
				'added_lines' => [ $new ],
				'removed_lines' => [],
				'added_lines_pst' => [ $new ],
				'all_links' => [ 'https://a.com/' ],
				'old_links' => [],
				'added_links' => [ 'https://a.com/' ],
				'removed_links' => [],
			],
			'params' => [ 'text' => $new, 'summary' => $summary, 'createonly' => true ],
		];

		// phpcs:disable Generic.Files.LineLength
		$old = '[https://a.com Test] foo';
		$new = "'''Random'''.\nSome ''special'' chars: àèìòù 名探偵コナン.\n[[Help:PST|]] test, [//www.b.com link]";

		yield 'PST and special chars' => [
			'expected' => [
				'action' => 'edit',
				'old_wikitext' => $old,
				'old_content_model' => 'wikitext',
				'new_wikitext' => $new,
				'new_content_model' => 'wikitext',
				'summary' => $summary,
				'new_pst' => "'''Random'''.\nSome ''special'' chars: àèìòù 名探偵コナン.\n[[Help:PST|PST]] test, [//www.b.com link]",
				'new_text' => "Random.\nSome special chars: àèìòù 名探偵コナン.\nPST test, link",
				'edit_diff' => "@@ -1,1 +1,3 @@\n-[https://a.com Test] foo\n+'''Random'''.\n+Some ''special'' chars: àèìòù 名探偵コナン.\n+[[Help:PST|]] test, [//www.b.com link]\n",
				'edit_diff_pst' => "@@ -1,1 +1,3 @@\n-[https://a.com Test] foo\n+'''Random'''.\n+Some ''special'' chars: àèìòù 名探偵コナン.\n+[[Help:PST|PST]] test, [//www.b.com link]\n",
				'new_size' => strlen( $new ),
				'old_size' => strlen( $old ),
				'edit_delta' => strlen( $new ) - strlen( $old ),
				'added_lines' => explode( "\n", $new ),
				'removed_lines' => [ $old ],
				'added_lines_pst' => [ "'''Random'''.", "Some ''special'' chars: àèìòù 名探偵コナン.", '[[Help:PST|PST]] test, [//www.b.com link]' ],
				'old_links' => [ 'https://a.com/' ],
				'all_links' => [ 'https://www.b.com/' ],
				'removed_links' => [ 'https://a.com/' ],
				'added_links' => [ 'https://www.b.com/' ],
			],
			'params' => [ 'text' => $new, 'summary' => $summary ],
			'oldContent' => new WikitextContent( $old ),
		];

		$old = "'''Random'''.\nSome ''special'' chars: àèìòù 名探偵コナン.\n[[Help:PST|PST]] test, [//www.b.com link]";
		$new = '[https://a.com Test] foo';

		yield 'PST and special chars, reverse' => [
			'expected' => [
				'action' => 'edit',
				'old_wikitext' => $old,
				'old_content_model' => 'wikitext',
				'new_wikitext' => $new,
				'new_content_model' => 'wikitext',
				'summary' => $summary,
				'new_html' => [ 'Test</a>' ],
				'new_pst' => '[https://a.com Test] foo',
				'new_text' => 'Test foo',
				'edit_diff' => "@@ -1,3 +1,1 @@\n-'''Random'''.\n-Some ''special'' chars: àèìòù 名探偵コナン.\n-[[Help:PST|PST]] test, [//www.b.com link]\n+[https://a.com Test] foo\n",
				'edit_diff_pst' => "@@ -1,3 +1,1 @@\n-'''Random'''.\n-Some ''special'' chars: àèìòù 名探偵コナン.\n-[[Help:PST|PST]] test, [//www.b.com link]\n+[https://a.com Test] foo\n",
				'new_size' => strlen( $new ),
				'old_size' => strlen( $old ),
				'edit_delta' => strlen( $new ) - strlen( $old ),
				'added_lines' => [ $new ],
				'removed_lines' => explode( "\n", $old ),
				'added_lines_pst' => [ $new ],
				'old_links' => [ 'https://www.b.com/' ],
				'all_links' => [ 'https://a.com/' ],
				'removed_links' => [ 'https://www.b.com/' ],
				'added_links' => [ 'https://a.com/' ],
			],
			'params' => [ 'text' => $new, 'summary' => $summary ],
			'oldContent' => new WikitextContent( $old ),
		];
		// phpcs:enable Generic.Files.LineLength

		$old = 'This edit will be pretty smal';
		$new = $old . 'l';

		yield 'Small edit' => [
			'expected' => [
				'action' => 'edit',
				'old_wikitext' => $old,
				'old_content_model' => 'wikitext',
				'new_wikitext' => $new,
				'new_content_model' => 'wikitext',
				'summary' => $summary,
				'new_html' => [ "<p>This edit will be pretty small\n</p>" ],
				'new_pst' => $new,
				'new_text' => $new,
				'edit_diff' => "@@ -1,1 +1,1 @@\n-$old\n+$new\n",
				'edit_diff_pst' => "@@ -1,1 +1,1 @@\n-$old\n+$new\n",
				'new_size' => strlen( $new ),
				'old_size' => strlen( $old ),
				'edit_delta' => 1,
				'removed_lines' => [ $old ],
				'added_lines' => [ $new ],
				'added_lines_pst' => [ $new ],
				'old_links' => [],
				'all_links' => [],
				'removed_links' => [],
				'added_links' => [],
			],
			'params' => [ 'text' => $new, 'summary' => $summary ],
			'oldContent' => new WikitextContent( $old ),
		];

		yield 'content model change to wikitext' => [
			'expected' => [
				'action' => 'edit',
				'old_wikitext' => "{\n    \"key\": \"value\"\n}",
				'old_content_model' => 'json',
				'new_wikitext' => 'new test https://en.wikipedia.org',
				'new_content_model' => 'wikitext',
				'old_links' => [],
				'all_links' => [ 'https://en.wikipedia.org/' ],
			],
			'params' => [
				'text' => 'new test https://en.wikipedia.org',
				'contentmodel' => 'wikitext',
			],
			'oldContent' => new JsonContent( FormatJson::encode( [ 'key' => 'value' ] ) ),
		];

		yield 'content model change from wikitext' => [
			'expected' => [
				'action' => 'edit',
				'old_wikitext' => 'test https://en.wikipedia.org',
				'old_content_model' => 'wikitext',
				'new_wikitext' => '{"key": "value"}',
				'new_content_model' => 'json',
				'old_links' => [ 'https://en.wikipedia.org/' ],
				'all_links' => [],
			],
			'params' => [
				'text' => '{"key": "value"}',
				'contentmodel' => 'json',
			],
			'oldContent' => new WikitextContent( 'test https://en.wikipedia.org' ),
		];
	}

	/**
	 * @dataProvider provideEditVariables
	 * @covers \MediaWiki\Extension\AbuseFilter\Hooks\Handlers\FilteredActionsHandler
	 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\RunVariableGenerator
	 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGenerator
	 * @covers \MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer
	 */
	public function testEditVariables(
		array $expected, array $params, Content $oldContent = null
	) {
		$varHolder = null;
		$this->prepareServices();
		$this->setAbuseLoggerFactoryWithEavesdrop( $varHolder );

		$title = 'My test page';
		$page = $this->getNonexistingTestPage( $title );
		if ( $oldContent ) {
			$status = $this->editPage( $page, $oldContent, 'Creating test page' );
			$this->assertStatusGood( $status );
		}

		$handler = new FilteredActionsHandler(
			new NullStatsdDataFactory(),
			AbuseFilterServices::getFilterRunnerFactory(),
			AbuseFilterServices::getVariableGeneratorFactory(),
			AbuseFilterServices::getEditRevUpdater(),
			AbuseFilterServices::getBlockedDomainFilter(),
			MediaWikiServices::getInstance()->getPermissionManager()
		);
		$this->setTemporaryHook(
			'EditFilterMergedContent',
			[ $handler, 'onEditFilterMergedContent' ],
			true
		);

		$ex = null;
		try {
			$this->doApiRequestWithToken(
				[ 'action' => 'edit', 'title' => $title ] + $params
			);
		} catch ( ApiUsageException $ex ) {
		}
		$this->assertNotNull( $ex, 'Exception should be thrown' );
		$this->assertNotNull( $varHolder, 'Variables should be set' );
		$export = AbuseFilterServices::getVariablesManager()->dumpAllVars(
			$varHolder,
			array_keys( $expected )
		);
		$this->assertVariables( $expected, $export );
	}

	public static function provideAccountCreationVars(): Generator {
		yield 'create account anonymously' => [
			'expected' => [
				'action' => 'createaccount',
				'accountname' => 'New account',
			]
		];

		yield 'create account by an existing user' => [
			'expected' => [
				'action' => 'createaccount',
				'accountname' => 'New account',
				'user_name' => 'Account creator',
				'user_editcount' => 0,
			],
			'accountName' => 'New account',
			'autocreate' => false,
			'creatorName' => 'Account creator'
		];

		yield 'autocreate an account' => [
			'expected' => [
				'action' => 'autocreateaccount',
				'accountname' => 'New account',
			],
			'accountName' => 'New account',
			'autocreate' => true,
		];
	}

	/**
	 * @dataProvider provideAccountCreationVars
	 * @covers \MediaWiki\Extension\AbuseFilter\AbuseFilterPreAuthenticationProvider
	 * @covers \MediaWiki\Extension\AbuseFilter\Hooks\Handlers\FilteredActionsHandler
	 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\RunVariableGenerator
	 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGenerator
	 */
	public function testAccountCreationVars(
		array $expected,
		string $accountName = 'New account',
		bool $autocreate = false,
		string $creatorName = null
	) {
		$varHolder = null;
		$this->prepareServices();
		$this->setAbuseLoggerFactoryWithEavesdrop( $varHolder );

		$creator = null;
		if ( $creatorName !== null ) {
			$creator = $this->getServiceContainer()->getUserFactory()->newFromName( $creatorName );
			$creator->addToDatabase();
		}
		$status = $this->createAccount( $accountName, $autocreate, $creator );
		$this->assertStatusNotOK( $status );
		$this->assertNotNull( $varHolder, 'Variables should be set' );
		$export = AbuseFilterServices::getVariablesManager()->dumpAllVars(
			$varHolder,
			array_keys( $expected )
		);
		$this->assertVariables( $expected, $export );
	}

}
