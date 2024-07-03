<?php
/**
 * DiscussionTools tag hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use MediaWiki\ChangeTags\Hook\ChangeTagsListActiveHook;
use MediaWiki\ChangeTags\Hook\ListDefinedTagsHook;
use MediaWiki\Hook\RecentChange_saveHook;
use RecentChange;
use RequestContext;

class TagHooks implements
	ChangeTagsListActiveHook,
	ListDefinedTagsHook,
	RecentChange_saveHook
{
	private const TAGS = [
		'discussiontools',
		// Features:
		'discussiontools-reply',
		'discussiontools-edit',
		'discussiontools-newtopic',
		'discussiontools-added-comment',
		// Input methods:
		'discussiontools-source',
		'discussiontools-visual',
		// Temporary input method:
		'discussiontools-source-enhanced',
	];

	/**
	 * @param string[] &$tags List of all active tags. Append to this array.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onChangeTagsListActive( &$tags ) {
		$this->onListDefinedTags( $tags );
	}

	/**
	 * Populate core Special:Tags with the change tags in use by DiscussionTools.
	 *
	 * @param string[] &$tags List of tags
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onListDefinedTags( &$tags ) {
		$tags = array_merge( $tags, static::TAGS );
	}

	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * Implements the RecentChange_save hook, to add an allowed set of changetags
	 * to edits.
	 *
	 * @param RecentChange $recentChange
	 * @return bool
	 */
	public function onRecentChange_save( $recentChange ) {
		// only apply to api edits, since there's no case where discussiontools
		// should be using the form-submit method.
		if ( !defined( 'MW_API' ) ) {
			return true;
		}

		$tags = static::getDiscussionToolsTagsFromRequest();
		if ( $tags ) {
			$recentChange->addTags( $tags );
		}

		return true;
	}

	/**
	 * Get DT tags from the dttags param in the request, and validate against known tags.
	 */
	public static function getDiscussionToolsTagsFromRequest(): array {
		$request = RequestContext::getMain()->getRequest();
		$tags = explode( ',', $request->getText( 'dttags' ) );
		return array_values( array_intersect( $tags, static::TAGS ) );
	}
}
