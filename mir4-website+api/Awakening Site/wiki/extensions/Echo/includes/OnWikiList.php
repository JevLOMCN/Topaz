<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use TextContent;

/**
 * Implements ContainmentList interface for sourcing a list of items from a wiki
 * page. Uses the page's latest revision ID as cache key.
 */
class OnWikiList implements ContainmentList {
	/**
	 * @var Title|null A title object representing the page to source the list from,
	 *  or null if the page does not exist.
	 */
	protected $title;

	/**
	 * @param int $titleNs An NS_* constant representing the mediawiki namespace of the page
	 * @param string $titleString String portion of the wiki page title
	 */
	public function __construct( $titleNs, $titleString ) {
		$title = Title::newFromText( $titleString, $titleNs );
		if ( $title !== null && $title->getArticleID() ) {
			$this->title = $title;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getValues() {
		if ( !$this->title ) {
			return [];
		}

		$article = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $this->title );
		if ( !$article->exists() ) {
			return [];
		}

		$content = $article->getContent();
		$text = ( $content instanceof TextContent ) ? $content->getText() : null;
		if ( $text === null ) {
			return [];
		}
		return array_filter( array_map( 'trim', explode( "\n", $text ) ) );
	}

	/**
	 * @inheritDoc
	 */
	public function getCacheKey() {
		if ( !$this->title ) {
			return '';
		}

		return (string)$this->title->getLatestRevID();
	}
}
