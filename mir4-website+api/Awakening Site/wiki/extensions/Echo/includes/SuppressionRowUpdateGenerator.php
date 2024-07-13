<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\Title\Title;
use RowUpdateGenerator;
use stdClass;

/**
 * Performs updates required for respecting suppression within echo:
 *   Updates event_page_id based on event_page_title and event_page_namespace
 *   Updates extra data for page-linked events to contain page id's
 */
class SuppressionRowUpdateGenerator implements RowUpdateGenerator {
	/**
	 * @var callable Hack to allow replacing Title::makeTitleSafe in tests
	 */
	protected $newTitleFromNsAndText = [ 'Title', 'makeTitleSafe' ];

	/**
	 * @inheritDoc
	 */
	public function update( $row ) {
		$update = $this->updatePageIdFromTitle( $row );
		if ( $row->event_extra !== null && $row->event_type === 'page-linked' ) {
			$update = $this->updatePageLinkedExtraData( $row, $update );
		}

		return $update;
	}

	/**
	 * Hackish method of mocking Title::newFromText for tests
	 *
	 * @param callable $callable
	 */
	public function setNewTitleFromNsAndText( $callable ) {
		$this->newTitleFromNsAndText = $callable;
	}

	/**
	 * Hackish method of mocking Title::makeTitleSafe for tests
	 *
	 * @param int $namespace The namespace of the page to look up
	 * @param string $text The page name to look up
	 * @return Title|null The title located for the namespace + text, or null if invalid
	 */
	protected function newTitleFromNsAndText( $namespace, $text ) {
		return call_user_func( $this->newTitleFromNsAndText, $namespace, $text );
	}

	/**
	 * Migrates all echo events from having page title and namespace as rows in the table
	 * to having only a page id in the table.  Any event from a page that doesn't have an
	 * article id gets the title+namespace moved to the event extra data
	 *
	 * @param stdClass $row A row from the database
	 * @return array All updates required for this row
	 */
	protected function updatePageIdFromTitle( $row ) {
		$update = [];
		$title = $this->newTitleFromNsAndText( $row->event_page_namespace, $row->event_page_title );
		if ( $title !== null ) {
			$pageId = $title->getArticleID();
			if ( $pageId ) {
				// If the title has a proper id from the database, store it
				$update['event_page_id'] = $pageId;
			} else {
				// For titles that do not refer to a WikiPage stored in the database
				// move the title/namespace into event_extra
				$extra = $this->extra( $row );
				$extra['page_title'] = $row->event_page_title;
				$extra['page_namespace'] = $row->event_page_namespace;

				$update['event_extra'] = serialize( $extra );
			}
		}

		return $update;
	}

	/**
	 * Updates the extra data for page-linked events to point to the id of the article
	 * rather than the namespace+title combo.
	 *
	 * @param stdClass $row A row from the database
	 * @param array $update
	 *
	 * @return array All updates required for this row
	 */
	protected function updatePageLinkedExtraData( $row, array $update ) {
		$extra = $this->extra( $row, $update );

		if ( isset( $extra['link-from-title'] ) && isset( $extra['link-from-namespace'] ) ) {
			$title = $this->newTitleFromNsAndText( $extra['link-from-namespace'], $extra['link-from-title'] );
			unset( $extra['link-from-title'], $extra['link-from-namespace'] );
			// Link from page is always from a content page, if null or no article id it was
			// somehow invalid
			if ( $title !== null && $title->getArticleID() ) {
				$extra['link-from-page-id'] = $title->getArticleID();
			}

			$update['event_extra'] = serialize( $extra );
		}

		return $update;
	}

	/**
	 * Return the extra data for a row, if an update wants to change the
	 * extra data returns that updated data rather than the original. If
	 * no extra data exists returns []
	 *
	 * @param stdClass $row The database row being updated
	 * @param array $update Updates that need to be applied to the database row
	 * @return array The event extra data
	 */
	protected function extra( $row, array $update = [] ) {
		if ( isset( $update['event_extra'] ) ) {
			return unserialize( $update['event_extra'] );
		}

		if ( $row->event_extra ) {
			return unserialize( $row->event_extra );
		}

		return [];
	}

}
