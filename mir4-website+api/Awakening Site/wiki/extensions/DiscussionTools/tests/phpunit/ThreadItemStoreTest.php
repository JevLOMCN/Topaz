<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use ExtensionRegistry;
use ImportStringSource;
use MediaWiki\Extension\DiscussionTools\ThreadItemStore;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\TitleValue;
use TestUser;

/**
 * @group DiscussionTools
 * @group Database
 * @covers \MediaWiki\Extension\DiscussionTools\ThreadItemStore
 */
class ThreadItemStoreTest extends IntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		if (
			$this->db->getType() === 'mysql' &&
			strpos( $this->db->getSoftwareLink(), 'MySQL' ) &&
			!$this->getCliArg( 'use-normal-tables' )
		) {
			$this->markTestSkipped( 'Set PHPUNIT_USE_NORMAL_TABLES=1 env variable to run these tests, ' .
				'otherwise they would fail due to a MySQL bug with temporary tables (T256006)' );
		}
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Liquid Threads' ) ) {
			$this->setMwGlobals( 'wgLqtTalkPages', false );
		}
	}

	private function importTestCase( string $dir ) {
		// Create users for the imported revisions
		new TestUser( 'X' );
		new TestUser( 'Y' );
		new TestUser( 'Z' );

		// Import revisions
		$source = new ImportStringSource( static::getText( "$dir/dump.xml" ) );
		$importer = MediaWikiServices::getInstance()
			->getWikiImporterFactory()
			->getWikiImporter( $source, $this->getTestSysop()->getAuthority() );
		// `true` means to assign edits to the users we created above
		$importer->setUsernamePrefix( 'import', true );
		$importer->doImport();
	}

	/**
	 * @dataProvider provideInsertCases
	 */
	public function testInsertThreadItems( string $dir ): void {
		$this->importTestCase( $dir );

		// Check that expected data has been stored in the database
		$expected = [];
		$actual = [];
		$tables = [
			'discussiontools_items' => [ 'it_id' ],
			'discussiontools_item_pages' => [ 'itp_id' ],
			// We reuse rows causing the primary key to be all out of order.
			// Use a consistent ordering for the output here.
			'discussiontools_item_revisions' => [ 'itr_revision_id', 'itr_items_id', 'itr_itemid_id' ],
			'discussiontools_item_ids' => [ 'itid_id' ],
		];
		foreach ( $tables as $table => $order ) {
			$expected[$table] = static::getJson( "../$dir/$table.json", true );

			$res = $this->getDb()->newSelectQueryBuilder()
				->from( $table )
				->field( '*' )
				->caller( __METHOD__ )
				->orderBy( $order )
				->fetchResultSet();
			foreach ( $res as $i => $row ) {
				foreach ( $row as $key => $val ) {
					$actual[$table][$i][$key] = $val;
				}
			}
		}

		// Optionally write updated content to the JSON files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			foreach ( $tables as $table => $order ) {
				static::overwriteJsonFile( "../$dir/$table.json", $actual[$table] );
			}
		}

		static::assertEquals( $expected, $actual );
	}

	public static function provideInsertCases(): array {
		return [
			[ 'cases/ThreadItemStore/1simple-example' ],
			[ 'cases/ThreadItemStore/2archived-section' ],
			[ 'cases/ThreadItemStore/3indistinguishable-comments' ],
			[ 'cases/ThreadItemStore/4transcluded-section' ],
			[ 'cases/ThreadItemStore/5changed-comment-indentation' ],
			[ 'cases/ThreadItemStore/6changed-heading-level' ],
			[ 'cases/ThreadItemStore/7identical-rev-timestamp' ],
			[ 'cases/ThreadItemStore/8indistinguishable-comments-same-page' ],
		];
	}

	public function testFindSimple(): void {
		$this->importTestCase( 'cases/ThreadItemStore/1simple-example' );
		/** @var ThreadItemStore $itemStore */
		$itemStore = $this->getServiceContainer()->getService( 'DiscussionTools.ThreadItemStore' );

		// BY NAME
		// Valid name finds the item
		$set = $itemStore->findNewestRevisionsByName( 'c-Y-20220720010200' );
		$this->assertCount( 1, $set );
		$this->assertEquals( 'ThreadItemStore1', $set[0]->getPage()->getDBkey() );
		$this->assertEquals( 'c-Y-20220720010200', $set[0]->getName() );

		// Wrong name - no results
		$set = $itemStore->findNewestRevisionsByName( 'c-blah' );
		$this->assertCount( 0, $set );

		// BY ID
		// Valid ID finds the item
		$set = $itemStore->findNewestRevisionsById( 'c-Y-20220720010200-X-20220720010100' );
		$this->assertCount( 1, $set );
		$this->assertEquals( 'ThreadItemStore1', $set[0]->getPage()->getDBkey() );
		$this->assertEquals( 'c-Y-20220720010200-X-20220720010100', $set[0]->getId() );

		// Wrong ID - no results
		$set = $itemStore->findNewestRevisionsById( 'c-blah' );
		$this->assertCount( 0, $set );

		// BY HEADING
		// Valid heading + page title finds the item
		$page = $this->getServiceContainer()->getPageStore()->getPageByName( NS_TALK, 'ThreadItemStore1' );
		$set = $itemStore->findNewestRevisionsByHeading( 'A', $page->getId(), TitleValue::newFromPage( $page ) );
		$this->assertCount( 1, $set );
		$this->assertEquals( 'ThreadItemStore1', $set[0]->getPage()->getDBkey() );

		// Wrong heading - no results
		$set = $itemStore->findNewestRevisionsByHeading( 'blah', $page->getId(), TitleValue::newFromPage( $page ) );
		$this->assertCount( 0, $set );

		// Wrong page - but we still get a result, since it's unique (case 3.)
		$set = $itemStore->findNewestRevisionsByHeading( 'A', 12345, new TitleValue( NS_TALK, 'Blah' ) );
		$this->assertCount( 1, $set );
	}

	public function testFindArchived(): void {
		$this->importTestCase( 'cases/ThreadItemStore/2archived-section' );
		/** @var ThreadItemStore $itemStore */
		$itemStore = $this->getServiceContainer()->getService( 'DiscussionTools.ThreadItemStore' );

		// Valid name finds the original item in old revision, and the item in the archive
		$set = $itemStore->findNewestRevisionsByName( 'c-X-20220720020100' );
		$this->assertCount( 2, $set );

		// Valid ID finds the original item in old revision, and the item in the archive
		$set = $itemStore->findNewestRevisionsById( 'c-X-20220720020100-A' );
		$this->assertCount( 2, $set );

		// Valid heading + page title finds the original item in old revision, and the item in the archive
		$page = $this->getServiceContainer()->getPageStore()->getPageByName( NS_TALK, 'ThreadItemStore2' );
		$set = $itemStore->findNewestRevisionsByHeading( 'A', $page->getId(), TitleValue::newFromPage( $page ) );
		$this->assertCount( 2, $set );
	}
}
