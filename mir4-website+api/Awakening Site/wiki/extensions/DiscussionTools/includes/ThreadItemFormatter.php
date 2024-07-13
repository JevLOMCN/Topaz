<?php

namespace MediaWiki\Extension\DiscussionTools;

use MediaWiki\Extension\DiscussionTools\ThreadItem\DatabaseThreadItem;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\TitleValue;
use MessageLocalizer;

/**
 * Displays links to comments and headings represented as ThreadItems.
 */
class ThreadItemFormatter {

	private LinkRenderer $linkRenderer;

	public function __construct(
		LinkRenderer $linkRenderer
	) {
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 * Make a link to a thread item on the page.
	 */
	public function makeLink( DatabaseThreadItem $item, ?string $text = null ): string {
		$title = TitleValue::newFromPage( $item->getPage() )->createFragmentTarget( $item->getId() );

		$query = [];
		if ( !$item->getRevision()->isCurrent() ) {
			$query['oldid'] = $item->getRevision()->getId();
		}

		$link = $this->linkRenderer->makeLink( $title, $text, [], $query );

		return $link;
	}

	/**
	 * Make a link to a thread item on the page, with additional information (used on special pages).
	 */
	public function formatLine( DatabaseThreadItem $item, MessageLocalizer $context ): string {
		$contents = [];

		$contents[] = $this->makeLink( $item );

		if ( !$item->getRevision()->isCurrent() ) {
			$contents[] = $context->msg( 'discussiontools-findcomment-results-notcurrent' )->escaped();
		}

		if ( is_string( $item->getTranscludedFrom() ) ) {
			$contents[] = $context->msg( 'discussiontools-findcomment-results-transcluded' )->escaped();
		}

		return implode( $context->msg( 'word-separator' )->escaped(), $contents );
	}

}
