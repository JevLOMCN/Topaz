<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Linter;

use FormatJson;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use stdClass;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Database logic
 */
class Database {

	/**
	 * Maximum number of errors to save per category,
	 * for a page, the rest are just dropped
	 */
	public const MAX_PER_CAT = 20;
	public const MAX_ACCURATE_COUNT = 20;

	/**
	 * The linter_tag field has a maximum length of 32 characters, linter_template field a maximum of 255 characters
	 * so to ensure the length is not exceeded, the tag and template strings are truncated a few bytes below that limit
	 */
	public const MAX_TAG_LENGTH = 30;
	public const MAX_TEMPLATE_LENGTH = 250;

	/**
	 * @var int
	 */
	private $pageId;

	/**
	 * During the addition of this column to the table, the initial value of null allows the migrate stage code to
	 * determine the needs to fill in the field for that record, as the record was created prior to
	 * the write stage code being active and filling it in during record creation. Once the migrate code runs once
	 * no nulls should exist in this field for any record, and if the migrate code times out during execution,
	 * can be restarted and continue without duplicating work. The final code that enables the use of this field
	 * during records search will depend on this fields index being valid for all records.
	 * @var int|null
	 */
	private $namespaceID;

	/**
	 * @var CategoryManager
	 */
	private $categoryManager;

	/**
	 * @param int $pageId
	 * @param int|null $namespaceID
	 */
	public function __construct( $pageId, $namespaceID = null ) {
		$this->pageId = $pageId;
		$this->namespaceID = $namespaceID;
		$this->categoryManager = new CategoryManager();
	}

	/**
	 * @param int $mode DB_PRIMARY or DB_REPLICA
	 * @return IDatabase
	 */
	public static function getDBConnectionRef( int $mode ): IDatabase {
		return MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( $mode );
	}

	/**
	 * @return IReadableDatabase
	 */
	public static function getReplicaDBConnection(): IReadableDatabase {
		return MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getReplicaDatabase();
	}

	/**
	 * Get a specific LintError by id
	 *
	 * @param int $id linter_id
	 * @return bool|LintError
	 */
	public function getFromId( $id ) {
		$row = self::getReplicaDBConnection()->newSelectQueryBuilder()
			->select( [ 'linter_cat', 'linter_params', 'linter_start', 'linter_end' ] )
			->from( 'linter' )
			->where( [ 'linter_id' => $id, 'linter_page' => $this->pageId ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $row ) {
			$row->linter_id = $id;
			return $this->makeLintError( $row );
		} else {
			return false;
		}
	}

	/**
	 * Turn a database row into a LintError object
	 *
	 * @param stdClass $row
	 * @return LintError|bool false on error
	 */
	public static function makeLintError( $row ) {
		try {
			$name = ( new CategoryManager() )->getCategoryName( $row->linter_cat );
		} catch ( MissingCategoryException $e ) {
			LoggerFactory::getInstance( 'Linter' )->error(
				'Could not find name for id: {linter_cat}',
				[ 'linter_cat' => $row->linter_cat ]
			);
			return false;
		}
		return new LintError(
			$name,
			[ (int)$row->linter_start, (int)$row->linter_end ],
			$row->linter_params,
			$row->linter_cat,
			(int)$row->linter_id
		);
	}

	/**
	 * Get all the lint errors for a page
	 *
	 * @return LintError[]
	 */
	public function getForPage() {
		$rows = self::getReplicaDBConnection()->newSelectQueryBuilder()
			->select( [ 'linter_id', 'linter_cat', 'linter_start', 'linter_end', 'linter_params' ] )
			->from( 'linter' )
			->where( [ 'linter_page' => $this->pageId ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$result = [];
		foreach ( $rows as $row ) {
			$error = self::makeLintError( $row );
			if ( !$error ) {
				continue;
			}
			$result[$error->id()] = $error;
		}

		return $result;
	}

	/**
	 * Convert a LintError object into an array for
	 * inserting/querying in the database
	 *
	 * @param LintError $error
	 * @return array
	 */
	private function buildErrorRow( LintError $error ) {
		$mwServices = MediaWikiServices::getInstance();
		$config = $mwServices->getMainConfig();

		$result = [
			'linter_page' => $this->pageId,
			'linter_cat' => $this->categoryManager->getCategoryId( $error->category, $error->catId ),
			'linter_params' => FormatJson::encode( $error->params, false, FormatJson::ALL_OK ),
			'linter_start' => $error->location[ 0 ],
			'linter_end' => $error->location[ 1 ]
		];

		// To enable 756101
		$enableWriteNamespaceColumn = $config->get( 'LinterWriteNamespaceColumnStage' );
		if ( $enableWriteNamespaceColumn && $this->namespaceID !== null ) {
			$result[ 'linter_namespace' ] = $this->namespaceID;
		}

		// To enable 720130
		$enableWriteTagAndTemplateColumns = $config->get( 'LinterWriteTagAndTemplateColumnsStage' );
		if ( $enableWriteTagAndTemplateColumns ) {
			$templateInfo = $error->templateInfo ?? '';
			if ( is_array( $templateInfo ) ) {
				if ( isset( $templateInfo[ 'multiPartTemplateBlock' ] ) ) {
					$templateInfo = 'multi-part-template-block';
				} else {
					$templateInfo = $templateInfo[ 'name' ] ?? '';
				}
			}
			$templateInfo = mb_strcut( $templateInfo, 0, self::MAX_TEMPLATE_LENGTH );
			$result[ 'linter_template' ] = $templateInfo;

			$tagInfo = $error->tagInfo ?? '';
			$tagInfo = mb_strcut( $tagInfo, 0, self::MAX_TAG_LENGTH );
			$result[ 'linter_tag' ] = $tagInfo;
		}

		return $result;
	}

	/**
	 * @param LintError[] $errors
	 * @return array
	 */
	private function countByCat( array $errors ) {
		$count = [];
		foreach ( $errors as $error ) {
			if ( !isset( $count[$error->category] ) ) {
				$count[$error->category] = 1;
			} else {
				$count[$error->category] += 1;
			}
		}

		return $count;
	}

	/**
	 * Save the specified lint errors in the
	 * database
	 *
	 * @param LintError[] $errors
	 * @return array [ 'deleted' => [ cat => count ], 'added' => [ cat => count ] ]
	 */
	public function setForPage( $errors ) {
		$previous = $this->getForPage();
		$dbw = self::getDBConnectionRef( DB_PRIMARY );
		if ( !$previous && !$errors ) {
			return [ 'deleted' => [], 'added' => [] ];
		} elseif ( !$previous && $errors ) {
			$toInsert = array_values( $errors );
			$toDelete = [];
		} elseif ( $previous && !$errors ) {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( 'linter' )
				->where( [ 'linter_page' => $this->pageId ] )
				->caller( __METHOD__ )
				->execute();
			return [ 'deleted' => $this->countByCat( $previous ), 'added' => [] ];
		} else {
			$toInsert = [];
			$toDelete = $previous;
			// Diff previous and errors
			foreach ( $errors as $error ) {
				$uniqueId = $error->id();
				if ( isset( $previous[$uniqueId] ) ) {
					unset( $toDelete[$uniqueId] );
				} else {
					$toInsert[] = $error;
				}
			}
		}

		if ( $toDelete ) {
			$ids = [];
			foreach ( $toDelete as $lintError ) {
				if ( $lintError->lintId ) {
					$ids[] = $lintError->lintId;
				}
			}
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( 'linter' )
				->where( [ 'linter_id' => $ids ] )
				->caller( __METHOD__ )
				->execute();
		}

		if ( $toInsert ) {
			// Insert into db, ignoring any duplicate key errors
			// since they're the same lint error
			$dbw->newInsertQueryBuilder()
				->insertInto( 'linter' )
				->ignore()
				->rows( array_map( [ $this, 'buildErrorRow' ], $toInsert ) )
				->caller( __METHOD__ )
				->execute();
		}

		return [
			'deleted' => $this->countByCat( $toDelete ),
			'added' => $this->countByCat( $toInsert ),
		];
	}

	/**
	 * Get an estimate of how many rows are there for the
	 * specified category with EXPLAIN SELECT COUNT(*).
	 * If the category actually has no rows, then 0 will
	 * be returned.
	 *
	 * @param int $catId
	 *
	 * @return int
	 */
	private function getTotalsEstimate( $catId ) {
		$dbr = self::getReplicaDBConnection();
		// First see if there are no rows, or a moderate number
		// within the limit specified by the MAX_ACCURATE_COUNT.
		// The distinction between 0, a few and a lot is important
		// to determine first, as estimateRowCount seem to never
		// return 0 or accurate low error counts.
		$rows = $dbr->newSelectQueryBuilder()
			->select( '*' )
			->from( 'linter' )
			->where( [ 'linter_cat' => $catId ] )
			// Select 1 more so we can see if we're over the max limit
			->limit( self::MAX_ACCURATE_COUNT + 1 )
			->caller( __METHOD__ )
			->fetchRowCount();
		// Return an accurate count if the number of errors is
		// below the maximum accurate count limit
		if ( $rows <= self::MAX_ACCURATE_COUNT ) {
			return $rows;
		}
		// Now we can just estimate if the maximum accurate count limit
		// was returned, which isn't the actual count but the limit reached
		return $dbr->newSelectQueryBuilder()
			->select( '*' )
			->from( 'linter' )
			->where( [ 'linter_cat' => $catId ] )
			->caller( __METHOD__ )
			->estimateRowCount();
	}

	/**
	 *
	 * This uses COUNT(*), which is accurate, but can be significantly
	 * slower depending upon how many rows are in the database.
	 * @return int[]
	 */
	public function getTotalsForPage(): array {
		$rows = self::getReplicaDBConnection()->newSelectQueryBuilder()
			->select( [ 'linter_cat', 'COUNT(*) AS count' ] )
			->from( 'linter' )
			->where( [ 'linter_page' => $this->pageId ] )
			->caller( __METHOD__ )
			->groupBy( 'linter_cat' )
			->fetchResultSet();

		// Initialize zero values
		$categories = $this->categoryManager->getVisibleCategories();
		$ret = array_fill_keys( $categories, 0 );
		foreach ( $rows as $row ) {
			try {
				$catName = $this->categoryManager->getCategoryName( $row->linter_cat );
			} catch ( MissingCategoryException $e ) {
				continue;
			}
			// Only set visible categories.  Alternatively, we could add another
			// where clause to the selection above.
			if ( !in_array( $catName, $categories, true ) ) {
				continue;
			}
			$ret[$catName] = (int)$row->count;
		}
		return $ret;
	}

	/**
	 * @return int[]
	 */
	public function getTotals() {
		$ret = [];
		foreach ( $this->categoryManager->getVisibleCategories() as $cat ) {
			$id = $this->categoryManager->getCategoryId( $cat );
			$ret[$cat] = $this->getTotalsEstimate( $id );
		}

		return $ret;
	}

	/**
	 * This code migrates namespace ID identified by the Linter records linter_page
	 * field and populates the new linter_namespace field if it is unpopulated.
	 * This code is intended to be run once though it could be run multiple times
	 * using `--force` if needed via the maintenance script.
	 * It is safe to run more than once, and will quickly exit if no records need updating.
	 * @param int $pageBatchSize
	 * @param int $linterBatchSize
	 * @param int $sleep
	 * @param bool $bypassConfig
	 * @return int number of pages updated, each with one or more linter records
	 */
	public static function migrateNamespace( int $pageBatchSize,
		 int $linterBatchSize,
		 int $sleep,
		 bool $bypassConfig = false ): int {
		// code used by phpunit test, bypassed when run as a maintenance script
		if ( !$bypassConfig ) {
			$mwServices = MediaWikiServices::getInstance();
			$config = $mwServices->getMainConfig();
			$enableMigrateNamespaceStage = $config->get( 'LinterWriteNamespaceColumnStage' );
			if ( !$enableMigrateNamespaceStage ) {
				return 0;
			}
		}
		if ( gettype( $sleep ) !== 'integer' || $sleep < 0 ) {
			$sleep = 0;
		}

		$logger = LoggerFactory::getInstance( 'MigrateNamespaceChannel' );

		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$dbw = self::getDBConnectionRef( DB_PRIMARY );
		$dbread = self::getDBConnectionRef( DB_REPLICA );

		$logger->info( "Migrate namespace starting\n" );

		$updated = 0;
		$lastElement = 0;
		do {
			// Gather some unique pageId values in linter table records into an array
			$linterPages = [];

			$result = $dbw->newSelectQueryBuilder()
				->select( 'linter_page' )
				->distinct()
				->from( 'linter' )
				->where( $dbw->expr( 'linter_page', '>', $lastElement ) )
				->andWhere( [ 'linter_namespace' => null ] )
				->orderBy( 'linter_page' )
				->limit( $linterBatchSize )
				->caller( __METHOD__ )
				->fetchResultSet();

			foreach ( $result as $row ) {
				$lastElement = intval( $row->linter_page );
				$linterPages[] = $lastElement;
			}
			$linterPagesLength = count( $linterPages );

			$pageStartIndex = 0;
			do {
				$pageIdBatch = array_slice( $linterPages, $pageStartIndex, $pageBatchSize );

				if ( count( $pageIdBatch ) > 0 ) {

					$pageResults = $dbread->newSelectQueryBuilder()
						->select( [ 'page_id', 'page_namespace' ] )
						->from( 'page' )
						->where( [ 'page_id' => $pageIdBatch ] )
						->caller( __METHOD__ )
						->fetchResultSet();

					foreach ( $pageResults as $pageRow ) {
						$pageId = intval( $pageRow->page_id );
						$namespaceID = intval( $pageRow->page_namespace );

						// If a record about to be updated has been removed by another process,
						// the update will not error, and continue updating the existing records.
						$dbw->newUpdateQueryBuilder()
							->update( 'linter' )
							->set( [ 'linter_namespace' => $namespaceID ] )
							->where( [
								'linter_namespace' => null,
								'linter_page' => $pageId
							] )
							->caller( __METHOD__ )
							->execute();
						$updated += $dbw->affectedRows();
					}

					// Sleep between batches for replication to catch up
					$lbFactory->waitForReplication();
					sleep( $sleep );
				}

				$pageStartIndex += $pageBatchSize;
			} while ( $linterPagesLength > $pageStartIndex );

			$logger->info( 'Migrated ' . $updated . " page IDs\n" );

		} while ( $linterPagesLength > 0 );

		$logger->info( "Migrate namespace finished!\n" );

		return $updated;
	}

	/**
	 * This code migrates the content of Linter record linter_params to linter_template and
	 * linter_tag fields if they are unpopulated or stale.
	 * This code should only be run once and thereafter disabled but must run to completion.
	 * It can be restarted if interrupted and will pick up where new divergences are found.
	 * Note: When linter_params are not set, the content is set to '[]' indicating no content
	 * and the code also handles a null linter_params field if found.
	 * This code is only run once by maintenance script migrateTagTemplate.php
	 * @param int $batchSize
	 * @param int $sleep
	 * @param bool $bypassConfig
	 * @return int
	 */
	public static function migrateTemplateAndTagInfo( int $batchSize,
		int $sleep,
		bool $bypassConfig = false
	): int {
		// code used by phpunit test, bypassed when run as a maintenance script
		if ( !$bypassConfig ) {
			$mwServices = MediaWikiServices::getInstance();
			$config = $mwServices->getMainConfig();
			$enableMigrateTagAndTemplateColumnsStage = $config->get( 'LinterWriteTagAndTemplateColumnsStage' );
			if ( !$enableMigrateTagAndTemplateColumnsStage ) {
				return 0;
			}
		}
		if ( gettype( $sleep ) !== 'integer' || $sleep < 0 ) {
			$sleep = 0;
		}

		$logger = LoggerFactory::getInstance( 'MigrateTagAndTemplateChannel' );

		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$dbw = self::getDBConnectionRef( DB_PRIMARY );

		$logger->info( "Migration of linter_params field to linter_tag and linter_template fields starting\n" );

		$updated = 0;
		$lastElement = 0;
		do {
			$results = $dbw->newSelectQueryBuilder()
				->select( [ 'linter_id', 'linter_params', 'linter_template', 'linter_tag' ] )
				->from( 'linter' )
				->where( [
					$dbw->expr( "linter_params", '!=', '[]' ),
					$dbw->expr( "linter_params", '!=', null ),
					$dbw->expr( "linter_id", '>', $lastElement )
				] )
				->orderBy( 'linter_id', selectQueryBuilder::SORT_ASC )
				->limit( $batchSize )
				->caller( __METHOD__ )
				->fetchResultSet();
			$linterBatchLength = 0;

			foreach ( $results as $row ) {
				$linter_id = intval( $row->linter_id );
				$lastElement = $linter_id;
				$linter_params = FormatJson::decode( $row->linter_params );
				$templateInfo = $linter_params->templateInfo ?? '';
				if ( is_object( $templateInfo ) ) {
					if ( isset( $templateInfo->multiPartTemplateBlock ) ) {
						$templateInfo = 'multi-part-template-block';
					} else {
						$templateInfo = $templateInfo->name ?? '';
					}
				}
				$templateInfo = mb_strcut( $templateInfo, 0, self::MAX_TEMPLATE_LENGTH );

				$tagInfo = $linter_params->name ?? '';
				$tagInfo = mb_strcut( $tagInfo, 0, self::MAX_TAG_LENGTH );

				// compare the content of linter_params to the template and tag field contents
				// and if they diverge, update the field with the correct template and tag info.
				// This behavior allows this function to be restarted should it be interrupted
				// and avoids repeating database record updates that are already correct due to
				// having been populated when the error record was created with the new recordLintError
				// write code that populates the template and tag fields, or for records populated
				// during a previous but interrupted run of this migrate code.
				if ( $templateInfo != $row->linter_template || $tagInfo != $row->linter_tag ) {
					// If the record about to be updated has been removed by another process,
					// the update will not do anything and just return with no records updated.
					$dbw->newUpdateQueryBuilder()
						->update( 'linter' )
						->set( [ 'linter_template' => $templateInfo, 'linter_tag' => $tagInfo, ] )
						->where( [ 'linter_id' => $linter_id ] )
						->caller( __METHOD__ )
						->execute();
					$updated += $dbw->affectedRows();
				}
				$linterBatchLength++;
			}

			// Sleep between batches for replication to catch up
			$lbFactory->waitForReplication();
			if ( $sleep > 0 ) {
				sleep( $sleep );
			}

			$logger->info( 'Migrated ' . $updated . " linter IDs\n" );

		} while ( $linterBatchLength > 0 );

		$logger->info( "Migrate linter_params to linter_tag and linter_template fields finished!\n" );

		return $updated;
	}

}
