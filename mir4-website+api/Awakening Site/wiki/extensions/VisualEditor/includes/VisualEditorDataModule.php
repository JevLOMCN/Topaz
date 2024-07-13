<?php
/**
 * Resource loader module providing extra data from the server to VisualEditor.
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2020 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */

namespace MediaWiki\Extension\VisualEditor;

use MediaWiki\ResourceLoader\Context as ResourceLoaderContext;
use MediaWiki\ResourceLoader\Module as ResourceLoaderModule;
use MediaWiki\Title\Title;

class VisualEditorDataModule extends ResourceLoaderModule {

	/**
	 * @param ResourceLoaderContext $context Object containing information about the state of this
	 *   specific loader request.
	 * @return string JavaScript code
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$msgInfo = $this->getMessageInfo( $context );
		$parsedMessages = [];
		$plainMessages = [];
		foreach ( $msgInfo['parse'] as $msgKey => $msgObj ) {
			$parsedMessages[ $msgKey ] = $msgObj->parse();
		}
		foreach ( $msgInfo['plain'] as $msgKey => $msgObj ) {
			$plainMessages[ $msgKey ] = $msgObj->plain();
		}

		return 've.init.platform.addParsedMessages(' . $context->encodeJson(
				$parsedMessages
			) . ');' .
			've.init.platform.addMessages(' . $context->encodeJson(
				$plainMessages
			) . ');';
	}

	/**
	 * Get the definition summary for this module.
	 *
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	public function getDefinitionSummary( ResourceLoaderContext $context ) {
		$summary = parent::getDefinitionSummary( $context );

		$msgVersion = [];
		$msgInfo = $this->getMessageInfo( $context );
		$msgInfo = array_merge( $msgInfo['parse'], $msgInfo['plain'] );
		foreach ( $msgInfo as $msgKey => $msgObj ) {
			$msgVersion[ $msgKey ] = [
				// Include the text of the message, in case the canonical translation changes
				$msgObj->plain(),
				// Include the page touched time, in case the on-wiki override is invalidated
				Title::makeTitle( NS_MEDIAWIKI, ucfirst( $msgObj->getKey() ) )->getTouched(),
			];
		}
		$summary[] = [ 've-messages' => $msgVersion ];
		return $summary;
	}

	/**
	 * @param ResourceLoaderContext $context Object containing information about the state of this
	 *   specific loader request.
	 * @return array[] Messages in various states of parsing
	 */
	protected function getMessageInfo( ResourceLoaderContext $context ) {
		$editSubmitButtonLabelPublish = $this->getConfig()
			->get( 'EditSubmitButtonLabelPublish' );
		$saveButtonLabelKey = $editSubmitButtonLabelPublish ? 'publishchanges' : 'savechanges';
		$saveButtonLabel = $context->msg( $saveButtonLabelKey )->text();

		// Messages to be exported as parsed html
		$parseMsgs = [
			'missingsummary' => $context->msg( 'missingsummary', $saveButtonLabel ),
			'summary' => $context->msg( 'summary' ),
			'visualeditor-browserwarning' => $context->msg( 'visualeditor-browserwarning' ),
			'visualeditor-wikitext-warning' => $context->msg( 'visualeditor-wikitext-warning' ),
		];

		// Messages to be exported as plain text
		$plainMsgs = [
			'visualeditor-feedback-link' =>
				$context->msg( 'visualeditor-feedback-link' )
				->inContentLanguage(),
			'visualeditor-feedback-source-link' =>
				$context->msg( 'visualeditor-feedback-source-link' )
				->inContentLanguage(),
			'visualeditor-quick-access-characters.json' =>
				$context->msg( 'visualeditor-quick-access-characters.json' )
				->inContentLanguage(),
			'visualeditor-template-tools-definition.json' =>
				$context->msg( 'visualeditor-template-tools-definition.json' )
				->inContentLanguage(),
		];

		return [
			'parse' => $parseMsgs,
			'plain' => $plainMsgs,
		];
	}

	/**
	 * @inheritDoc
	 *
	 * Always true.
	 */
	public function enableModuleContentVersion() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getDependencies( ResourceLoaderContext $context = null ) {
		return [
			'ext.visualEditor.base',
			'ext.visualEditor.mediawiki',
		];
	}
}
