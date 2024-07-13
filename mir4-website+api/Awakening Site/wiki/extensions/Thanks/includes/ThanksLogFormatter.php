<?php

namespace MediaWiki\Extension\Thanks;

use LogFormatter;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Message;

/**
 * This class formats log entries for thanks
 */
class ThanksLogFormatter extends LogFormatter {
	/**
	 * @inheritDoc
	 */
	protected function getMessageParameters() {
		$params = parent::getMessageParameters();
		// Convert target from a pageLink to a userLink since the target is
		// actually a user, not a page.
		$recipient = User::newFromName( $this->entry->getTarget()->getText(), false );
		$params[2] = Message::rawParam( $this->makeUserLink( $recipient ) );
		$params[3] = $recipient->getName();
		return $params;
	}

	public function getPreloadTitles() {
		// Add the recipient's user talk page to LinkBatch
		return [ Title::makeTitle( NS_USER_TALK, $this->entry->getTarget()->getText() ) ];
	}
}
