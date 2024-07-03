<?php

namespace MediaWiki\Extension\VisualEditor;

use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\User\UserIdentity;

/**
 * VisualEditorApiVisualEditorEditPostSaveHook
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2021 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */

interface VisualEditorApiVisualEditorEditPostSaveHook {

	/**
	 * This hook is executed in the ApiVisualEditorEdit after a action=save attempt.
	 *
	 * ApiVisualEditorEdit will wait for implementations of this hook to complete before returning a response, so
	 * if the implementation needs to do something time-consuming that does not need to be sent back with the response,
	 * consider using a DeferredUpdate or Job.
	 *
	 * @param ProperPageIdentity $page The page identity of the title used in the save attempt.
	 * @param UserIdentity $user User associated with the save attempt.
	 * @param string $wikitext The wikitext used in the save attempt.
	 * @param array $params The params passed by the client in the API request. See
	 *   ApiVisualEditorEdit::getAllowedParams()
	 * @param array $pluginData Associative array containing additional data specified by plugins, where the keys of
	 *   the array are plugin names and the value are arbitrary data.
	 * @param array $saveResult The result from ApiVisualEditorEdit::saveWikitext()
	 * @param array &$apiResponse The modifiable API response that VisualEditor will return to the client.
	 * @return void
	 */
	public function onVisualEditorApiVisualEditorEditPostSave(
		ProperPageIdentity $page,
		UserIdentity $user,
		string $wikitext,
		array $params,
		array $pluginData,
		array $saveResult,
		array &$apiResponse
	): void;

}
