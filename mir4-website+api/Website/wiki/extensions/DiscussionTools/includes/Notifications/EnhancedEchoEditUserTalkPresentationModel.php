<?php
/**
 * Our override of the built-in Echo presentation model for user talk page notifications.
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Notifications;

use EchoEditUserTalkPresentationModel;
use MediaWiki\Language\RawMessage;
use Message;
use Wikimedia\Timestamp\TimestampException;

class EnhancedEchoEditUserTalkPresentationModel extends EchoEditUserTalkPresentationModel {

	use DiscussionToolsEventTrait;

	/**
	 * @inheritDoc
	 */
	public function getPrimaryLink() {
		$linkInfo = parent::getPrimaryLink();
		// For events enhanced by DiscussionTools: link to the individual comment
		$link = $this->getCommentLink();
		if ( $link ) {
			$linkInfo['url'] = $link;
		}
		return $linkInfo;
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyMessage() {
		if ( !$this->isBundled() ) {
			// For events enhanced by DiscussionTools: add a text snippet
			// (Echo can only do this for new sections, not for every comment)
			$snippet = $this->getContentSnippet();
			if ( $snippet ) {
				return new RawMessage( '$1', [ Message::plaintextParam( $snippet ) ] );
			}
		}
		return parent::getBodyMessage();
	}

	/**
	 * @inheritDoc
	 * @throws TimestampException
	 */
	public function jsonSerialize(): array {
		$array = parent::jsonSerialize();

		$array['links']['legacyPrimary'] = $this->addMarkAsRead( parent::getPrimaryLink() ) ?: [];

		return $array;
	}
}
