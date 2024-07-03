<?php

namespace MediaWiki\Extension\DiscussionTools;

use Exception;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Extension\DiscussionTools\ThreadItem\CommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\DatabaseCommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\DatabaseHeadingItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\DatabaseThreadItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\HeadingItem;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageStore;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Title\TitleFormatter;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\ActorStore;
use MediaWiki\Utils\MWTimestamp;
use stdClass;
use Wikimedia\NormalizedException\NormalizedException;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LikeValue;
use Wikimedia\Rdbms\ReadOnlyMode;
use Wikimedia\Rdbms\SelectQueryBuilder;
use Wikimedia\Timestamp\TimestampException;

/**
 * Stores and fetches ThreadItemSets from the database.
 */
class ThreadItemStore {

	private Config $config;
	private ILBFactory $dbProvider;
	private ReadOnlyMode $readOnlyMode;
	private PageStore $pageStore;
	private RevisionStore $revStore;
	private TitleFormatter $titleFormatter;
	private ActorStore $actorStore;

	public function __construct(
		ConfigFactory $configFactory,
		ILBFactory $dbProvider,
		ReadOnlyMode $readOnlyMode,
		PageStore $pageStore,
		RevisionStore $revStore,
		TitleFormatter $titleFormatter,
		ActorStore $actorStore
	) {
		$this->config = $configFactory->makeConfig( 'discussiontools' );
		$this->dbProvider = $dbProvider;
		$this->readOnlyMode = $readOnlyMode;
		$this->pageStore = $pageStore;
		$this->revStore = $revStore;
		$this->titleFormatter = $titleFormatter;
		$this->actorStore = $actorStore;
	}

	/**
	 * Returns true if the tables necessary for this feature haven't been created yet,
	 * to allow failing softly in that case.
	 */
	public function isDisabled(): bool {
		return !$this->config->get( 'DiscussionToolsEnablePermalinksBackend' );
	}

	/**
	 * Find the thread items with the given name in the newest revision of every page in which they
	 * have appeared.
	 *
	 * @param string|string[] $itemName
	 * @param int|null $limit
	 * @return DatabaseThreadItem[]
	 */
	public function findNewestRevisionsByName( $itemName, ?int $limit = 50 ): array {
		if ( $this->isDisabled() ) {
			return [];
		}

		$queryBuilder = $this->getIdsNamesBuilder()
			->caller( __METHOD__ )
			->where( [
				'it_itemname' => $itemName,
				// Disallow querying for headings of sections that contain no comments.
				// They all share the same name, so this would return a huge useless list on most wikis.
				// (But we still store them, as we might need this data elsewhere.)
				"it_itemname != 'h-'",
			] );

		if ( $limit !== null ) {
			$queryBuilder->limit( $limit );
		}

		$result = $this->fetchItemsResultSet( $queryBuilder );
		$revs = $this->fetchRevisionAndPageForItems( $result );

		$threadItems = [];
		foreach ( $result as $row ) {
			$threadItem = $this->getThreadItemFromRow( $row, null, $revs );
			if ( $threadItem ) {
				$threadItems[] = $threadItem;
			}
		}
		return $threadItems;
	}

	/**
	 * Find the thread items with the given ID in the newest revision of every page in which they have
	 * appeared.
	 *
	 * @param string|string[] $itemId
	 * @param int|null $limit
	 * @return DatabaseThreadItem[]
	 */
	public function findNewestRevisionsById( $itemId, ?int $limit = 50 ): array {
		if ( $this->isDisabled() ) {
			return [];
		}

		$queryBuilder = $this->getIdsNamesBuilder()
			->caller( __METHOD__ );

		// First find the name associated with the ID; then find by name. Otherwise we wouldn't find the
		// latest revision in case comment ID changed, e.g. the comment was moved elsewhere on the page.
		$itemNameQueryBuilder = $this->getIdsNamesBuilder()
			->where( [ 'itid_itemid' => $itemId ] )
			->field( 'it_itemname' );
			// I think there may be more than 1 only in case of headings?
			// For comments, any ID corresponds to just 1 name.
			// Not sure how bad it is to not have limit( 1 ) here?
			// It might scan a bunch of rows...
			// ->limit( 1 );

		$queryBuilder
			->where( [
				'it_itemname IN (' . $itemNameQueryBuilder->getSQL() . ')',
				"it_itemname != 'h-'",
			] );

		if ( $limit !== null ) {
			$queryBuilder->limit( $limit );
		}

		$result = $this->fetchItemsResultSet( $queryBuilder );
		$revs = $this->fetchRevisionAndPageForItems( $result );

		$threadItems = [];
		foreach ( $result as $row ) {
			$threadItem = $this->getThreadItemFromRow( $row, null, $revs );
			if ( $threadItem ) {
				$threadItems[] = $threadItem;
			}
		}
		return $threadItems;
	}

	/**
	 * Find heading items matching some text which:
	 *
	 *  1. appeared at some point in the history of the targetpage, or if this returns no results:
	 *  2. currently appear on a sub-page of the target page, or if this returns no results:
	 *  3. currently appears on any page, but only if it is a unique match
	 *
	 * @param string|string[] $heading Heading text to match
	 * @param int $articleId Article ID of the target page
	 * @param TitleValue $title Title of the target page
	 * @param int|null $limit
	 * @return DatabaseThreadItem[]
	 */
	public function findNewestRevisionsByHeading(
		$heading, int $articleId, TitleValue $title, ?int $limit = 50
	): array {
		if ( $this->isDisabled() ) {
			return [];
		}

		$language = MediaWikiServices::getInstance()->getContentLanguage();
		$heading = $language->truncateForDatabase( $heading, 80, '' );

		$dbw = $this->dbProvider->getPrimaryDatabase();

		// 1. Try to find items which have appeared on the page at some point
		//    in its history.
		$itemIdInPageHistoryQueryBuilder = $this->getIdsNamesBuilder()
			->caller( __METHOD__ . ' case 1' )
			->join( 'revision', null, [ 'rev_id = itr_revision_id' ] )
			->where( $dbw->expr( 'itid_itemid', IExpression::LIKE, new LikeValue(
				'h-' . $heading . '-',
				$dbw->anyString()
			) ) )
			// Has once appered on the specified page ID
			->where( [ 'rev_page' => $articleId ] )
			->field( 'itid_itemid' );

		$threadItems = $this->findNewestRevisionsByQuery( __METHOD__ . ' case 1',
			$itemIdInPageHistoryQueryBuilder, $limit );

		if ( count( $threadItems ) ) {
			return $threadItems;
		}

		// 2. If the thread item's database hasn't been back-filled with historical revisions
		//    then approach (1) may not work, instead look for matching headings the currently
		//    appear on sub-pages, which matches the archiving convention on most wikis.
		$itemIdInSubPageQueryBuilder = $this->getIdsNamesBuilder()
			->caller( __METHOD__ . ' case 2' )
			->join( 'page', null, [ 'page_id = itp_page_id' ] )
			->where( $dbw->expr( 'itid_itemid', IExpression::LIKE, new LikeValue(
				'h-' . $heading . '-',
				$dbw->anyString()
			) ) )
			->where( $dbw->expr( 'page_title', IExpression::LIKE, new LikeValue(
				$title->getText() . '/',
				$dbw->anyString()
			) ) )
			->where( [ 'page_namespace' => $title->getNamespace() ] )
			->field( 'itid_itemid' );

		$threadItems = $this->findNewestRevisionsByQuery( __METHOD__ . ' case 2',
			$itemIdInSubPageQueryBuilder, $limit );

		if ( count( $threadItems ) ) {
			return $threadItems;
		}

		// 3. Look for an "exact" match of the heading on any page. Because we are searching
		//    so broadly, only return if there is exactly one match to the heading name.
		$itemIdInAnyPageQueryBuilder = $this->getIdsNamesBuilder()
			->caller( __METHOD__ . ' case 3' )
			->join( 'page', null, [ 'page_id = itp_page_id', 'page_latest = itr_revision_id' ] )
			->where( $dbw->expr( 'itid_itemid', IExpression::LIKE, new LikeValue(
				'h-' . $heading . '-',
				$dbw->anyString()
			) ) )
			->field( 'itid_itemid' )
			// We only care if there is one, or more than one result
			->limit( 2 );

		// Check there is only one result in the sub-query
		$itemIds = $itemIdInAnyPageQueryBuilder->fetchFieldValues();
		if ( count( $itemIds ) === 1 ) {
			return $this->findNewestRevisionsByQuery( __METHOD__ . ' case 3', $itemIds[ 0 ] );
		}

		return [];
	}

	/**
	 * @param string $fname
	 * @param SelectQueryBuilder|string $itemIdOrQueryBuilder Sub-query which returns item ID's, or an itemID
	 * @param int|null $limit
	 * @return DatabaseThreadItem[]
	 */
	private function findNewestRevisionsByQuery( $fname, $itemIdOrQueryBuilder, ?int $limit = 50 ): array {
		$queryBuilder = $this->getIdsNamesBuilder()->caller( $fname . ' / ' . __METHOD__ );
		if ( $itemIdOrQueryBuilder instanceof SelectQueryBuilder ) {
			$queryBuilder
				->where( [
					'itid_itemid IN (' . $itemIdOrQueryBuilder->getSQL() . ')'
				] );
		} else {
			$queryBuilder->where( [ 'itid_itemid' => $itemIdOrQueryBuilder ] );
		}

		if ( $limit !== null ) {
			$queryBuilder->limit( $limit );
		}

		$result = $this->fetchItemsResultSet( $queryBuilder );
		$revs = $this->fetchRevisionAndPageForItems( $result );

		$threadItems = [];
		foreach ( $result as $row ) {
			$threadItem = $this->getThreadItemFromRow( $row, null, $revs );
			if ( $threadItem ) {
				$threadItems[] = $threadItem;
			}
		}
		return $threadItems;
	}

	private function fetchItemsResultSet( SelectQueryBuilder $queryBuilder ): IResultWrapper {
		$queryBuilder
			->fields( [
				'itr_id',
				'it_itemname',
				'it_timestamp',
				'it_actor',
				'itid_itemid',
				'itr_parent_id',
				'itr_transcludedfrom',
				'itr_level',
				'itr_headinglevel',
				'itr_revision_id',
			] )
			// PageStore fields for the transcluded-from page
			->leftJoin( 'page', null, [ 'page_id = itr_transcludedfrom' ] )
			->fields( $this->pageStore->getSelectFields() )
			// ActorStore fields for the author
			->leftJoin( 'actor', null, [ 'actor_id = it_actor' ] )
			->fields( [ 'actor_id', 'actor_name', 'actor_user' ] )
			// Parent item ID (the string, not just the primary key)
			->leftJoin(
				$this->getIdsNamesBuilder()
					->caller( __METHOD__ )
					->fields( [
						'itr_parent__itr_id' => 'itr_id',
						'itr_parent__itid_itemid' => 'itid_itemid',
					] ),
				null,
				[ 'itr_parent_id = itr_parent__itr_id' ]
			)
			->field( 'itr_parent__itid_itemid' );

		return $queryBuilder->fetchResultSet();
	}

	/**
	 * @param IResultWrapper $result
	 * @return stdClass[]
	 */
	private function fetchRevisionAndPageForItems( IResultWrapper $result ): array {
		// This could theoretically be done in the same query as fetchItemsResultSet(),
		// but the resulting query would be two screens long
		// and we'd have to alias a lot of fields to avoid conflicts.
		$revs = [];
		foreach ( $result as $row ) {
			$revs[ $row->itr_revision_id ] = null;
		}
		$revQueryBuilder = $this->dbProvider->getReplicaDatabase()->newSelectQueryBuilder()
			->caller( __METHOD__ )
			->queryInfo( $this->revStore->getQueryInfo( [ 'page' ] ) )
			->fields( $this->pageStore->getSelectFields() )
			->where( $revs ? [ 'rev_id' => array_keys( $revs ) ] : '0=1' );
		$revResult = $revQueryBuilder->fetchResultSet();
		foreach ( $revResult as $row ) {
			$revs[ $row->rev_id ] = $row;
		}
		return $revs;
	}

	private function getThreadItemFromRow(
		stdClass $row, ?DatabaseThreadItemSet $set, array $revs
	): ?DatabaseThreadItem {
		if ( $revs[ $row->itr_revision_id ] === null ) {
			// We didn't find the 'revision' table row at all, this revision is deleted.
			// (The page may or may not have other non-deleted revisions.)
			// Pretend the thread item doesn't exist to avoid leaking data to users who shouldn't see it.
			// TODO Allow privileged users to see it (we'd need to query from 'archive')
			return null;
		}

		$revRow = $revs[$row->itr_revision_id];
		$page = $this->pageStore->newPageRecordFromRow( $revRow );
		$rev = $this->revStore->newRevisionFromRow( $revRow );
		if ( $rev->isDeleted( RevisionRecord::DELETED_TEXT ) ) {
			// This revision is revision-deleted.
			// TODO Allow privileged users to see it
			return null;
		}

		if ( $set && $row->itr_parent__itid_itemid ) {
			$parent = $set->findCommentById( $row->itr_parent__itid_itemid );
		} else {
			$parent = null;
		}

		$transcludedFrom = $row->itr_transcludedfrom === null ? false : (
			$row->itr_transcludedfrom === '0' ? true :
				$this->titleFormatter->getPrefixedText(
					$this->pageStore->newPageRecordFromRow( $row )
				)
		);

		if ( $row->it_timestamp !== null && $row->it_actor !== null ) {
			$author = $this->actorStore->newActorFromRow( $row )->getName();

			$item = new DatabaseCommentItem(
				$page,
				$rev,
				$row->it_itemname,
				$row->itid_itemid,
				$parent,
				$transcludedFrom,
				(int)$row->itr_level,
				$row->it_timestamp,
				$author
			);
		} else {
			$item = new DatabaseHeadingItem(
				$page,
				$rev,
				$row->it_itemname,
				$row->itid_itemid,
				$parent,
				$transcludedFrom,
				(int)$row->itr_level,
				$row->itr_headinglevel === null ? null : (int)$row->itr_headinglevel
			);
		}

		if ( $parent ) {
			$parent->addReply( $item );
		}
		return $item;
	}

	/**
	 * Find the thread item set for the given revision, assuming that it is the current revision of
	 * its page.
	 */
	public function findThreadItemsInCurrentRevision( int $revId ): DatabaseThreadItemSet {
		if ( $this->isDisabled() ) {
			return new DatabaseThreadItemSet();
		}

		$queryBuilder = $this->getIdsNamesBuilder();
		$queryBuilder
			->where( [ 'itr_revision_id' => $revId ] )
			// We must process parents before their children in the loop later
			->orderBy( 'itr_id', SelectQueryBuilder::SORT_ASC );

		$result = $this->fetchItemsResultSet( $queryBuilder );
		$revs = $this->fetchRevisionAndPageForItems( $result );

		$set = new DatabaseThreadItemSet();
		foreach ( $result as $row ) {
			$threadItem = $this->getThreadItemFromRow( $row, $set, $revs );
			if ( $threadItem ) {
				$set->addThreadItem( $threadItem );
				$set->updateIdAndNameMaps( $threadItem );
			}
		}
		return $set;
	}

	private function getIdsNamesBuilder(): SelectQueryBuilder {
		$dbr = $this->dbProvider->getReplicaDatabase();

		$queryBuilder = $dbr->newSelectQueryBuilder()
			->from( 'discussiontools_items' )
			->join( 'discussiontools_item_pages', null, [ 'itp_items_id = it_id' ] )
			->join( 'discussiontools_item_revisions', null, [
				'itr_items_id = it_id',
				// Only the latest revision of the items with each name
				'itr_revision_id = itp_newest_revision_id',
			] )
			->join( 'discussiontools_item_ids', null, [ 'itid_id = itr_itemid_id' ] );

		return $queryBuilder;
	}

	/**
	 * @param callable $find Function that does a SELECT and returns primary key field
	 * @param callable $insert Function that does an INSERT IGNORE and returns last insert ID
	 * @param bool &$didInsert Set to true if the insert succeeds
	 * @param RevisionRecord $rev For error logging
	 * @return int Return value of whichever function succeeded
	 */
	private function findOrInsertId(
		callable $find, callable $insert, bool &$didInsert, RevisionRecord $rev
	) {
		$dbw = $this->dbProvider->getPrimaryDatabase();

		$id = $find( $dbw );
		if ( !$id ) {
			$id = $insert( $dbw );
			if ( $id ) {
				$didInsert = true;
			} else {
				// Maybe it's there, but we can't see it due to REPEATABLE_READ?
				// Try again in another connection. (T339882, T322701)
				$dbwAnother = $this->dbProvider->getMainLB()
					->getConnection( DB_PRIMARY, [], false, ILoadBalancer::CONN_TRX_AUTOCOMMIT );
				$id = $find( $dbwAnother );
				if ( !$id ) {
					throw new NormalizedException(
						"Database can't find our row and won't let us insert it on page {page} revision {revision}",
						[
							'page' => $rev->getPageId(),
							'revision' => $rev->getId(),
						]
					);
				}
			}
		}
		return $id;
	}

	/**
	 * Store the thread item set.
	 *
	 * @param RevisionRecord $rev
	 * @param ContentThreadItemSet $threadItemSet
	 * @throws TimestampException
	 * @throws DBError
	 * @throws Exception
	 * @return bool
	 */
	public function insertThreadItems( RevisionRecord $rev, ContentThreadItemSet $threadItemSet ): bool {
		if ( $this->readOnlyMode->isReadOnly() ) {
			return false;
		}

		$dbw = $this->dbProvider->getPrimaryDatabase();
		$didInsert = false;
		$method = __METHOD__;

		// Map of item IDs (strings) to their discussiontools_item_ids.itid_id field values (ints)
		$itemIdsIds = [];
		'@phan-var array<string,int> $itemIdsIds';
		// Map of item IDs (strings) to their discussiontools_items.it_id field values (ints)
		$itemsIds = [];
		'@phan-var array<string,int> $itemsIds';

		// Insert or find discussiontools_item_ids rows, fill in itid_id field values.
		// (This is not in a transaction. Orphaned rows in this table are harmlessly ignored,
		// and long transactions caused performance issues on Wikimedia wikis: T315353#8218914.)
		foreach ( $threadItemSet->getThreadItems() as $item ) {
			$itemIdsId = $this->findOrInsertId(
				static function ( IReadableDatabase $dbw ) use ( $item, $method ) {
					return $dbw->newSelectQueryBuilder()
						->from( 'discussiontools_item_ids' )
						->field( 'itid_id' )
						->where( [ 'itid_itemid' => $item->getId() ] )
						->caller( $method )
						->fetchField();
				},
				static function ( IDatabase $dbw ) use ( $item, $method ) {
					$dbw->newInsertQueryBuilder()
						->table( 'discussiontools_item_ids' )
						->row( [ 'itid_itemid' => $item->getId() ] )
						->ignore()
						->caller( $method )
						->execute();
					return $dbw->affectedRows() ? $dbw->insertId() : null;
				},
				$didInsert,
				$rev
			);
			$itemIdsIds[ $item->getId() ] = $itemIdsId;
		}

		// Insert or find discussiontools_items rows, fill in it_id field values.
		// (This is not in a transaction. Orphaned rows in this table are harmlessly ignored,
		// and long transactions caused performance issues on Wikimedia wikis: T315353#8218914.)
		foreach ( $threadItemSet->getThreadItems() as $item ) {
			$itemsId = $this->findOrInsertId(
				static function ( IReadableDatabase $dbw ) use ( $item, $method ) {
					return $dbw->newSelectQueryBuilder()
						->from( 'discussiontools_items' )
						->field( 'it_id' )
						->where( [ 'it_itemname' => $item->getName() ] )
						->caller( $method )
						->fetchField();
				},
				function ( IDatabase $dbw ) use ( $item, $method ) {
					$dbw->newInsertQueryBuilder()
						->table( 'discussiontools_items' )
						->row(
							[
								'it_itemname' => $item->getName(),
							] +
							( $item instanceof CommentItem ? [
								'it_timestamp' =>
									$dbw->timestamp( $item->getTimestampString() ),
								'it_actor' =>
									$this->actorStore->findActorIdByName( $item->getAuthor(), $dbw ),
							] : [] )
						)
						->ignore()
						->caller( $method )
						->execute();
					return $dbw->affectedRows() ? $dbw->insertId() : null;
				},
				$didInsert,
				$rev
			);
			$itemsIds[ $item->getId() ] = $itemsId;
		}

		// Insert or update discussiontools_item_pages and discussiontools_item_revisions rows.
		// This IS in a transaction. We don't really want rows for different items on the same
		// page to point to different revisions.
		$dbw->doAtomicSection( $method, /** @throws TimestampException */ function ( IDatabase $dbw ) use (
			$method, $rev, $threadItemSet, $itemsIds, $itemIdsIds, &$didInsert
		) {
			// Map of item IDs (strings) to their discussiontools_item_revisions.itr_id field values (ints)
			$itemRevisionsIds = [];
			'@phan-var array<string,int> $itemRevisionsIds';

			$revUpdateRows = [];
			// Insert or update discussiontools_item_pages rows.
			foreach ( $threadItemSet->getThreadItems() as $item ) {
				// Update (or insert) the references to oldest/newest item revision.
				// The page revision we're processing is usually the newest one, but it doesn't have to be
				// (in case of backfilling using the maintenance script, or in case of revisions being
				// imported), so we need all these funky queries to see if we need to update oldest/newest.

				$itemPagesRow = $dbw->newSelectQueryBuilder()
					->from( 'discussiontools_item_pages' )
					->join( 'revision', 'revision_oldest', [ 'itp_oldest_revision_id = revision_oldest.rev_id' ] )
					->join( 'revision', 'revision_newest', [ 'itp_newest_revision_id = revision_newest.rev_id' ] )
					->field( 'itp_id' )
					->field( 'itp_oldest_revision_id' )
					->field( 'itp_newest_revision_id' )
					->field( 'revision_oldest.rev_timestamp', 'oldest_rev_timestamp' )
					->field( 'revision_newest.rev_timestamp', 'newest_rev_timestamp' )
					->where( [
						'itp_items_id' => $itemsIds[ $item->getId() ],
						'itp_page_id' => $rev->getPageId(),
					] )
					->fetchRow();
				if ( $itemPagesRow === false ) {
					$dbw->newInsertQueryBuilder()
						->table( 'discussiontools_item_pages' )
						->row( [
							'itp_items_id' => $itemsIds[ $item->getId() ],
							'itp_page_id' => $rev->getPageId(),
							'itp_oldest_revision_id' => $rev->getId(),
							'itp_newest_revision_id' => $rev->getId(),
						] )
						->ignore()
						->caller( $method )
						->execute();
				} else {
					$oldestTime = ( new MWTimestamp( $itemPagesRow->oldest_rev_timestamp ) )->getTimestamp( TS_MW );
					$newestTime = ( new MWTimestamp( $itemPagesRow->newest_rev_timestamp ) )->getTimestamp( TS_MW );
					$currentTime = $rev->getTimestamp();

					$oldestId = (int)$itemPagesRow->itp_oldest_revision_id;
					$newestId = (int)$itemPagesRow->itp_newest_revision_id;
					$currentId = $rev->getId();

					$updatePageField = null;
					if ( [ $oldestTime, $oldestId ] > [ $currentTime, $currentId ] ) {
						$updatePageField = 'itp_oldest_revision_id';
					} elseif ( [ $newestTime, $newestId ] < [ $currentTime, $currentId ] ) {
						$updatePageField = 'itp_newest_revision_id';
					}
					if ( $updatePageField ) {
						$dbw->newUpdateQueryBuilder()
							->table( 'discussiontools_item_pages' )
							->set( [ $updatePageField => $rev->getId() ] )
							->where( [ 'itp_id' => $itemPagesRow->itp_id ] )
							->caller( $method )
							->execute();
						if ( $oldestId !== $newestId ) {
							// This causes most rows in discussiontools_item_revisions referring to the previously
							// oldest/newest revision to be unused, so try re-using them.
							$revUpdateRows[ $itemsIds[ $item->getId() ] ] = $itemPagesRow->$updatePageField;
						}
					}
				}
			}

			// Insert or update discussiontools_item_revisions rows, fill in itr_id field values.
			foreach ( $threadItemSet->getThreadItems() as $item ) {
				$transcl = $item->getTranscludedFrom();
				$newOrUpdateRevRow =
					[
						'itr_itemid_id' => $itemIdsIds[ $item->getId() ],
						'itr_revision_id' => $rev->getId(),
						'itr_items_id' => $itemsIds[ $item->getId() ],
						'itr_parent_id' =>
							// This assumes that parent items were processed first
							$item->getParent() ? $itemRevisionsIds[ $item->getParent()->getId() ] : null,
						'itr_transcludedfrom' =>
							$transcl === false ? null : (
								$transcl === true ? 0 :
									$this->pageStore->getPageByText( $transcl )->getId()
							),
						'itr_level' => $item->getLevel(),
					] +
					( $item instanceof HeadingItem ? [
						'itr_headinglevel' => $item->isPlaceholderHeading() ? null : $item->getHeadingLevel(),
					] : [] );

				$itemRevisionsConds = [
					'itr_itemid_id' => $itemIdsIds[ $item->getId() ],
					'itr_items_id' => $itemsIds[ $item->getId() ],
					'itr_revision_id' => $rev->getId(),
				];
				$itemRevisionsId = $dbw->newSelectQueryBuilder()
					->from( 'discussiontools_item_revisions' )
					->field( 'itr_id' )
					->where( $itemRevisionsConds )
					->caller( $method )
					->fetchField();
				if ( $itemRevisionsId === false ) {
					$itemRevisionsUpdateId = null;
					if ( isset( $revUpdateRows[ $itemsIds[ $item->getId() ] ] ) ) {
						$itemRevisionsUpdateId = $dbw->newSelectQueryBuilder()
							->from( 'discussiontools_item_revisions' )
							->field( 'itr_id' )
							->where( [
								'itr_revision_id' => $revUpdateRows[ $itemsIds[ $item->getId() ] ],
								// We only keep up to 2 discussiontools_item_revisions rows with the same
								// (itr_itemid_id, itr_items_id) pair, for the oldest and newest revision known.
								// Here we find any rows we don't want to keep and re-use them.
								'itr_itemid_id' => $itemIdsIds[ $item->getId() ],
								'itr_items_id' => $itemsIds[ $item->getId() ],
							] )
							->caller( $method )
							->fetchField();
						// The row to re-use may not be found if it has a different itr_itemid_id than the row
						// we want to add.
					}
					if ( $itemRevisionsUpdateId ) {
						$dbw->newUpdateQueryBuilder()
							->table( 'discussiontools_item_revisions' )
							->set( $newOrUpdateRevRow )
							->where( [ 'itr_id' => $itemRevisionsUpdateId ] )
							->caller( $method )
							->execute();
						$itemRevisionsId = $itemRevisionsUpdateId;
						$didInsert = true;
					} else {
						$itemRevisionsId = $this->findOrInsertId(
							static function ( IReadableDatabase $dbw ) use ( $itemRevisionsConds, $method ) {
								return $dbw->newSelectQueryBuilder()
									->from( 'discussiontools_item_revisions' )
									->field( 'itr_id' )
									->where( $itemRevisionsConds )
									->caller( $method )
									->fetchField();
							},
							static function ( IDatabase $dbw ) use ( $newOrUpdateRevRow, $method ) {
								$dbw->newInsertQueryBuilder()
									->table( 'discussiontools_item_revisions' )
									->row( $newOrUpdateRevRow )
									// Fix rows with corrupted itr_items_id=0,
									// which are causing conflicts (T339882, T343859#9185559)
									->onDuplicateKeyUpdate()
									->uniqueIndexFields( [ 'itr_itemid_id', 'itr_revision_id' ] )
									// Omit redundant updates to avoid warnings (T353432)
									->set( array_diff_key(
										$newOrUpdateRevRow,
										[ 'itr_itemid_id' => true, 'itr_revision_id' => true ]
									) )
									->caller( $method )
									->execute();
								return $dbw->affectedRows() ? $dbw->insertId() : null;
							},
							$didInsert,
							$rev
						);
					}
				}

				$itemRevisionsIds[ $item->getId() ] = $itemRevisionsId;
			}
		}, $dbw::ATOMIC_CANCELABLE );

		return $didInsert;
	}
}
