<?php

namespace MediaWiki\Extension\AbuseFilter;

use Content;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use TextContent;

/**
 * This service provides an interface to convert RevisionRecord and Content objects to some text
 * suitable for running abuse filters.
 *
 * @internal No external code should rely on this representation
 */
class TextExtractor {
	public const SERVICE_NAME = 'AbuseFilterTextExtractor';

	/** @var AbuseFilterHookRunner */
	private $hookRunner;

	/**
	 * @param AbuseFilterHookRunner $hookRunner
	 */
	public function __construct( AbuseFilterHookRunner $hookRunner ) {
		$this->hookRunner = $hookRunner;
	}

	/**
	 * Look up some text of a revision from its revision id
	 *
	 * Note that this is really *some* text, we do not make *any* guarantee
	 * that this text will be even close to what the user actually sees, or
	 * that the form is fit for any intended purpose.
	 *
	 * Note also that if the revision for any reason is not an Revision
	 * the function returns with an empty string.
	 *
	 * For now, this returns all the revision's slots, concatenated together.
	 * In future, this will be replaced by a better solution. See T208769 for
	 * discussion.
	 *
	 * @param RevisionRecord|null $revision a valid revision
	 * @param Authority $performer to check for privileged access
	 * @return string the content of the revision as some kind of string,
	 *        or an empty string if it can not be found
	 * @return-taint none
	 */
	public function revisionToString( ?RevisionRecord $revision, Authority $performer ): string {
		if ( !$revision ) {
			return '';
		}

		$strings = [];

		foreach ( $revision->getSlotRoles() as $role ) {
			$content = $revision->getContent( $role, RevisionRecord::FOR_THIS_USER, $performer );
			if ( $content === null ) {
				continue;
			}
			$strings[$role] = $this->contentToString( $content );
		}

		return implode( "\n\n", $strings );
	}

	/**
	 * Converts the given Content object to a string.
	 *
	 * This uses TextContent::getText() if $content is an instance of TextContent,
	 * or Content::getTextForSearchIndex() otherwise.
	 *
	 * The hook AbuseFilterContentToString can be used to override this
	 * behavior.
	 *
	 * @param Content $content
	 *
	 * @return string a suitable string representation of the content.
	 */
	public function contentToString( Content $content ): string {
		$text = null;

		if ( $this->hookRunner->onAbuseFilter_contentToString(
			$content,
			$text
		) ) {
			$text = $content instanceof TextContent
				? $content->getText()
				: $content->getTextForSearchIndex();
		}

		// T22310
		return TextContent::normalizeLineEndings( (string)$text );
	}
}
