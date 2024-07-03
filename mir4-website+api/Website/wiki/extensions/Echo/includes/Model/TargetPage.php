<?php

namespace MediaWiki\Extension\Notifications\Model;

use InvalidArgumentException;
use MediaWiki\Title\Title;
use stdClass;

/**
 * Map a title to an echo event so that we can mark a notification as read
 * when visiting the page. This only supports titles with ids because majority
 * of notifications have page_id and searching by namespace and title is slow
 */
class TargetPage extends AbstractEntity {

	/**
	 * @var Title|null|false False if not initialized yet
	 */
	protected $title = false;

	/**
	 * @var int
	 */
	protected $pageId;

	/**
	 * @var Event|null
	 */
	protected $event;

	/**
	 * @var int
	 */
	protected $eventId;

	/**
	 * @var string
	 */
	protected $eventType;

	/**
	 * Only allow creating instance internally
	 */
	protected function __construct() {
	}

	/**
	 * Create a TargetPage instance from Title and Event
	 *
	 * @param Title $title
	 * @param Event $event
	 * @return TargetPage|null
	 */
	public static function create( Title $title, Event $event ) {
		// This only support title with a page_id
		if ( !$title->getArticleID() ) {
			return null;
		}
		$obj = new self();
		$obj->event = $event;
		$obj->eventId = $event->getId();
		$obj->eventType = $event->getType();
		$obj->title = $title;
		$obj->pageId = $title->getArticleID();

		return $obj;
	}

	/**
	 * Create a TargetPage instance from stdClass object
	 *
	 * @param stdClass $row
	 * @return TargetPage
	 */
	public static function newFromRow( $row ) {
		$requiredFields = [
			'etp_page',
			'etp_event'
		];
		foreach ( $requiredFields as $field ) {
			if ( !isset( $row->$field ) || !$row->$field ) {
				throw new InvalidArgumentException( $field . ' is not set in the row!' );
			}
		}
		$obj = new self();
		$obj->pageId = $row->etp_page;
		$obj->eventId = $row->etp_event;
		if ( isset( $row->event_type ) ) {
			$obj->eventType = $row->event_type;
		}

		return $obj;
	}

	/**
	 * @return Title|null
	 */
	public function getTitle() {
		if ( $this->title === false ) {
			$this->title = Title::newFromID( $this->pageId );
		}

		return $this->title;
	}

	/**
	 * @return int
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * @return Event
	 */
	public function getEvent() {
		if ( !$this->event ) {
			$this->event = Event::newFromID( $this->eventId );
		}

		return $this->event;
	}

	/**
	 * @return int
	 */
	public function getEventId() {
		return $this->eventId;
	}

	/**
	 * @return string
	 */
	public function getEventType() {
		if ( !$this->eventType ) {
			$this->eventType = $this->getEvent()->getType();
		}

		return $this->eventType;
	}

	/**
	 * Convert the properties to a database row
	 * @return int[]
	 */
	public function toDbArray() {
		return [
			'etp_page' => $this->pageId,
			'etp_event' => $this->eventId
		];
	}
}
