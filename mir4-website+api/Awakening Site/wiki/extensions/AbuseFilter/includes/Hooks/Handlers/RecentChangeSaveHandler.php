<?php
// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\AbuseFilter\Hooks\Handlers;

use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Hook\RecentChange_saveHook;

class RecentChangeSaveHandler implements RecentChange_saveHook {
	/** @var ChangeTagger */
	private $changeTagger;

	/**
	 * @param ChangeTagger $changeTagger
	 */
	public function __construct( ChangeTagger $changeTagger ) {
		$this->changeTagger = $changeTagger;
	}

	/**
	 * @inheritDoc
	 */
	public function onRecentChange_save( $recentChange ) {
		$tags = $this->changeTagger->getTagsForRecentChange( $recentChange );
		if ( $tags ) {
			$recentChange->addTags( $tags );
		}
	}
}
