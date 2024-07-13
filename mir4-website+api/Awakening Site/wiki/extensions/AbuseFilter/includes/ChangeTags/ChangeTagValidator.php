<?php

namespace MediaWiki\Extension\AbuseFilter\ChangeTags;

use ChangeTags;
use MediaWiki\Status\Status;

/**
 * Service for testing whether filters can tag edits and other changes
 * with a specific tag
 * @todo Use DI when available in core (T245964)
 */
class ChangeTagValidator {

	public const SERVICE_NAME = 'AbuseFilterChangeTagValidator';

	/** @var ChangeTagsManager */
	private $changeTagsManager;

	/**
	 * @param ChangeTagsManager $changeTagsManager
	 */
	public function __construct( ChangeTagsManager $changeTagsManager ) {
		$this->changeTagsManager = $changeTagsManager;
	}

	/**
	 * Check whether a filter is allowed to use a tag
	 *
	 * @param string $tag Tag name
	 * @return Status
	 */
	public function validateTag( string $tag ): Status {
		$tagNameStatus = ChangeTags::isTagNameValid( $tag );

		if ( !$tagNameStatus->isGood() ) {
			return $tagNameStatus;
		}

		$canAddStatus = ChangeTags::canAddTagsAccompanyingChange( [ $tag ] );

		if ( $canAddStatus->isGood() ) {
			return $canAddStatus;
		}

		if ( $tag === $this->changeTagsManager->getCondsLimitTag() ) {
			return Status::newFatal( 'abusefilter-tag-reserved' );
		}

		// note: these are both local and global tags
		$alreadyDefinedTags = $this->changeTagsManager->getTagsDefinedByFilters();
		if ( in_array( $tag, $alreadyDefinedTags, true ) ) {
			return Status::newGood();
		}

		// note: this check is only done for the local wiki
		// global filters could interfere with existing tags on remote wikis
		$canCreateTagStatus = ChangeTags::canCreateTag( $tag );
		if ( $canCreateTagStatus->isGood() ) {
			return $canCreateTagStatus;
		}

		return Status::newFatal( 'abusefilter-edit-bad-tags' );
	}
}
