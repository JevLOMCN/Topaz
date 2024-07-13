<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use BadMethodCallException;
use Language;
use MediaWiki\Extension\Notifications\DiscussionParser;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Parser;

/**
 * Component that represents a section of a page to be used from EchoEventPresentationModel subclass.
 */
class EchoPresentationModelSection {

	/**
	 * @var string|false|null
	 */
	private $rawSectionTitle = null;

	/**
	 * @var string|false|null
	 */
	private $parsedSectionTitle = null;

	/**
	 * @var Event
	 */
	protected $event;

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @param Event $event
	 * @param User $user
	 * @param Language $language
	 */
	public function __construct( Event $event, User $user, Language $language ) {
		$this->event = $event;
		$this->user = $user;
		$this->language = $language;
	}

	/**
	 * Get the raw (unparsed) section title
	 * @return string|false Section title
	 */
	protected function getRawSectionTitle() {
		if ( $this->rawSectionTitle !== null ) {
			return $this->rawSectionTitle;
		}
		$sectionTitle = $this->event->getExtraParam( 'section-title' );
		if ( !$sectionTitle ) {
			$this->rawSectionTitle = false;
			return false;
		}
		// Check permissions
		if ( !$this->event->userCan( RevisionRecord::DELETED_TEXT, $this->user ) ) {
			$this->rawSectionTitle = false;
			return false;
		}

		$this->rawSectionTitle = $sectionTitle;
		return $this->rawSectionTitle;
	}

	/**
	 * Get the section title parsed to plain text
	 * @return string|false Section title (plain text)
	 */
	protected function getParsedSectionTitle() {
		if ( $this->parsedSectionTitle !== null ) {
			return $this->parsedSectionTitle;
		}
		$rawSectionTitle = $this->getRawSectionTitle();
		if ( !$rawSectionTitle ) {
			$this->parsedSectionTitle = false;
			return false;
		}
		$this->parsedSectionTitle = DiscussionParser::getTextSnippet(
			$rawSectionTitle,
			$this->language,
			DiscussionParser::DEFAULT_SNIPPET_LENGTH,
			$this->event->getTitle(),
			// linestart=false, because this wikitext was inside a heading like `== … ==`,
			// so start-of-line markup like `*` should not be parsed (T299572)
			false
		);
		return $this->parsedSectionTitle;
	}

	/**
	 * Check if there is a section.
	 *
	 * This also returns false if the revision is deleted,
	 * even if there is a section, because the section can't
	 * be viewed in that case.
	 * @return bool Whether there is a section
	 */
	public function exists() {
		return (bool)$this->getRawSectionTitle();
	}

	/**
	 * Get a Title pointing to the section, if available.
	 * @return Title
	 */
	public function getTitleWithSection() {
		$title = $this->event->getTitle();
		if ( $title === null ) {
			throw new BadMethodCallException( 'Event #' . $this->event->getId() . ' with no title' );
		}
		$section = $this->getParsedSectionTitle();
		if ( $section ) {
			$fragment = substr( Parser::guessSectionNameFromStrippedText( $section ), 1 );
			$title = $title->createFragmentTarget( $fragment );
		}
		return $title;
	}

	/**
	 * Get truncated section title, according to user's language.
	 * You should only call this if EchoPresentationModelSection::exists returns true.
	 * @return string
	 */
	public function getTruncatedSectionTitle() {
		return $this->language->embedBidi( $this->language->truncateForVisual(
			$this->getParsedSectionTitle(),
			EchoEventPresentationModel::SECTION_TITLE_RECOMMENDED_LENGTH,
			'...',
			false
		) );
	}
}

class_alias( EchoPresentationModelSection::class, 'EchoPresentationModelSection' );
