<?php

namespace MediaWiki\Extension\VisualEditor;

use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\User\UserIdentity;

/**
 * VisualEditorApiVisualEditorEditPreSaveHook
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2021 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */

interface VisualEditorApiVisualEditorEditPreSaveHook {

	/**
	 * This hook is executed in calls to ApiVisualEditorEdit using action=save, before the save is attempted.
	 *
	 * This hook can abort the save attempt by returning false.
	 *
	 * @param ProperPageIdentity $page The page identity of the title used in the save attempt.
	 * @param UserIdentity $user User associated with the save attempt.
	 * @param string $wikitext The wikitext used in the save attempt.
	 * @param array &$params The params passed by the client in the API request. See
	 *   ApiVisualEditorEdit::getAllowedParams(). Note that these params are then passed on to ApiEditPage, so
	 *   the array is modifiable in case the hook implementer needs to adjust any of the parameters.
	 * @param array $pluginData Associative array containing additional data specified by plugins, where the keys of
	 *   the array are plugin names, and the value are arbitrary data. Plugins are expected to be in a one-to-one
	 *  correlation with hook handlers and can be specified via the 'plugins' and 'data-*' parameters of the API.
	 * @param array &$apiResponse The modifiable API response that VisualEditor will return to the client.
	 *   There are several keys that are used by VisualEditor and will be overwritten if set here, notable ones include
	 *   "result" and "edit". See ApiVisualEditorEdit::execute().
	 *   Note: When returning false, the "message" key must be set to a valid i18n message key, e.g.
	 *     ```php
	 *     $apiResponse['message'] = [ 'growthexperiments-addlink-notinstore', $title->getPrefixedText() ];
	 *     return false;
	 * @return void|bool
	 *   False will abort the save attempt and return an error to the client. If false is returned, the implementer
	 *   must also set the "message" key in $apiResponse for rendering the error response to the client.
	 */
	public function onVisualEditorApiVisualEditorEditPreSave(
		ProperPageIdentity $page,
		UserIdentity $user,
		string $wikitext,
		array &$params,
		array $pluginData,
		array &$apiResponse
	);

}
