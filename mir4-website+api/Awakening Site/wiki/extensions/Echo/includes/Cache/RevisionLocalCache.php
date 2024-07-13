<?php

namespace MediaWiki\Extension\Notifications\Cache;

use MediaWiki\Revision\RevisionStore;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * Cache class that maps revision id to RevisionStore object
 * @xxx Like TitleLocalCache, this class shouldn't need to exist.
 */
class RevisionLocalCache extends LocalCache {
	private IConnectionProvider $dbProvider;
	private RevisionStore $revisionStore;

	/**
	 * @param IConnectionProvider $dbProvider
	 * @param RevisionStore $revisionStore
	 */
	public function __construct(
		IConnectionProvider $dbProvider,
		RevisionStore $revisionStore
	) {
		parent::__construct();
		$this->dbProvider = $dbProvider;
		$this->revisionStore = $revisionStore;
	}

	/**
	 * @inheritDoc
	 */
	protected function resolve( array $lookups ) {
		$dbr = $this->dbProvider->getReplicaDatabase();
		$revQuery = $this->revisionStore->getQueryInfo( [ 'page', 'user' ] );
		$res = $dbr->select(
			$revQuery['tables'],
			$revQuery['fields'],
			[ 'rev_id' => $lookups ],
			__METHOD__,
			[],
			$revQuery['joins']
		);
		foreach ( $res as $row ) {
			yield $row->rev_id => $this->revisionStore->newRevisionFromRow( $row );
		}
	}
}
