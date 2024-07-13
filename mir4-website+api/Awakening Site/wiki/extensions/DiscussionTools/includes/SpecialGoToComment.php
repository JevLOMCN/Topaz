<?php

namespace MediaWiki\Extension\DiscussionTools;

use MediaWiki\SpecialPage\RedirectSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class SpecialGoToComment extends RedirectSpecialPage {

	private ThreadItemStore $threadItemStore;

	public function __construct(
		ThreadItemStore $threadItemStore
	) {
		parent::__construct( 'GoToComment' );
		$this->threadItemStore = $threadItemStore;
	}

	/**
	 * @inheritDoc
	 */
	public function getRedirect( $subpage ) {
		$results = [];

		// Search for all thread items with the given ID or name, returning results from the latest
		// revision of each page they appeared on.
		//
		// If there is exactly one good result (see isCanonicalPermalink()), redirect to it.
		// Otherwise, redirect to full search results on Special:FindComment.

		if ( $subpage ) {
			$threadItems = $this->threadItemStore->findNewestRevisionsById( $subpage );
			foreach ( $threadItems as $item ) {
				if ( $item->isCanonicalPermalink() ) {
					$results[] = $item;
				}
			}
			$threadItems = $this->threadItemStore->findNewestRevisionsByName( $subpage );
			foreach ( $threadItems as $item ) {
				if ( $item->isCanonicalPermalink() ) {
					$results[] = $item;
				}
			}
		}

		if ( count( $results ) === 1 ) {
			return Title::castFromPageIdentity( $results[0]->getPage() )->createFragmentTarget( $results[0]->getId() );
		} else {
			return SpecialPage::getTitleFor( 'FindComment', $subpage ?: false );
		}
	}
}
