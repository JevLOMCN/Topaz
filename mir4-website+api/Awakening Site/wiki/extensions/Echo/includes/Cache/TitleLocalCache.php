<?php

namespace MediaWiki\Extension\Notifications\Cache;

use MediaWiki\Page\PageRecord;
use MediaWiki\Page\PageStore;
use MediaWiki\Title\TitleFactory;

/**
 * Cache class that maps article id to Title object.
 * @fixme There should be no need for this class. Core's PageStore should be responsible for caching, if it's
 * deemed necessary. See also T344124.
 */
class TitleLocalCache extends LocalCache {
	private PageStore $pageStore;
	private TitleFactory $titleFactory;

	/**
	 * @param PageStore $pageStore
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		PageStore $pageStore,
		TitleFactory $titleFactory
	) {
		parent::__construct();
		$this->pageStore = $pageStore;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @inheritDoc
	 */
	protected function resolve( array $lookups ) {
		if ( $lookups ) {
			$titles = $this->pageStore
				->newSelectQueryBuilder()
				->wherePageIds( $lookups )
				->caller( __METHOD__ )
				->fetchPageRecords();

			/** @var PageRecord $title */
			foreach ( $titles as $title ) {
				$title = $this->titleFactory->castFromPageIdentity( $title );
				yield $title->getArticleID() => $title;
			}
		}
	}

}
