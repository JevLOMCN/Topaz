<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks\Handlers;

use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagsManager;

class ChangeTagsHandler implements
	\MediaWiki\ChangeTags\Hook\ListDefinedTagsHook,
	\MediaWiki\ChangeTags\Hook\ChangeTagsListActiveHook
{

	/** @var ChangeTagsManager */
	private $changeTagsManager;

	/**
	 * @param ChangeTagsManager $changeTagsManager
	 */
	public function __construct( ChangeTagsManager $changeTagsManager ) {
		$this->changeTagsManager = $changeTagsManager;
	}

	/**
	 * @param string[] &$tags
	 */
	public function onListDefinedTags( &$tags ) {
		$tags = array_merge(
			$tags,
			$this->changeTagsManager->getTagsDefinedByFilters(),
			[ $this->changeTagsManager->getCondsLimitTag() ]
		);
	}

	/**
	 * @param string[] &$tags
	 */
	public function onChangeTagsListActive( &$tags ) {
		$tags = array_merge(
			$tags,
			$this->changeTagsManager->getTagsDefinedByActiveFilters(),
			[ $this->changeTagsManager->getCondsLimitTag() ]
		);
	}
}
