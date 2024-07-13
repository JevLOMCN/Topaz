<?php

namespace MediaWiki\Extension\DiscussionTools;

use ApiMain;
use ApiResult;
use DerivativeContext;
use IContextSource;
use MediaWiki\Extension\VisualEditor\ParsoidClient;
use MediaWiki\Extension\VisualEditor\VisualEditorParsoidClientFactory;
use MediaWiki\Request\DerivativeRequest;
use MediaWiki\Request\WebRequest;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\TempUser\TempUserCreator;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * Random methods we want to share between API modules.
 *
 * @property VisualEditorParsoidClientFactory $parsoidClientFactory
 * @property CommentParser $commentParser
 * @property TempUserCreator $tempUserCreator
 * @property UserFactory $userFactory
 */
trait ApiDiscussionToolsTrait {

	/**
	 * Given parameters describing a reply or new topic, transform them into wikitext using Parsoid,
	 * then preview the wikitext using the legacy parser.
	 *
	 * @param string $type 'topic' or 'reply'
	 * @param Title $title Context title for wikitext transformations
	 * @param array $params Associative array with the following keys:
	 *  - `wikitext` (string|null) Content of the message, mutually exclusive with `html`
	 *  - `html` (string|null) Content of the message, mutually exclusive with `wikitext`
	 *  - `sectiontitle` (string) Content of the title, when `type` is 'topic'
	 *  - `signature` (string|null) Wikitext signature to add to the message
	 * @param array $originalParams Original params from the source API call
	 * @return ApiResult action=parse API result
	 */
	protected function previewMessage(
		string $type, Title $title, array $params, array $originalParams = []
	): ApiResult {
		$wikitext = $params['wikitext'] ?? null;
		$html = $params['html'] ?? null;
		$signature = $params['signature'] ?? null;

		switch ( $type ) {
			case 'topic':
				if ( $wikitext !== null ) {
					if ( $signature !== null ) {
						$wikitext = CommentModifier::appendSignatureWikitext( $wikitext, $signature );
					}
				} else {
					$doc = DOMUtils::parseHTML( '' );
					$container = DOMUtils::parseHTMLToFragment( $doc, $html );
					if ( $signature !== null ) {
						CommentModifier::appendSignature( $container, $signature );
					}
					$html = DOMUtils::getFragmentInnerHTML( $container );
					$wikitext = $this->transformHTML( $title, $html )[ 'body' ];
				}

				if ( $params['sectiontitle'] ) {
					$wikitext = "== " . $params['sectiontitle'] . " ==\n" . $wikitext;
				}

				break;

			case 'reply':
				$doc = DOMUtils::parseHTML( '' );

				if ( $wikitext !== null ) {
					$container = CommentModifier::prepareWikitextReply( $doc, $wikitext );
				} else {
					$container = CommentModifier::prepareHtmlReply( $doc, $html );
				}

				if ( $signature !== null ) {
					CommentModifier::appendSignature( $container, $signature );
				}
				$list = CommentModifier::transferReply( $container );
				$html = DOMCompat::getOuterHTML( $list );

				$wikitext = $this->transformHTML( $title, $html )[ 'body' ];

				break;
		}

		$apiParams = [
			'action' => 'parse',
			'title' => $title->getPrefixedText(),
			'text' => $wikitext,
			'pst' => '1',
			'preview' => '1',
			'disableeditsection' => '1',
			'prop' => 'text|modules|jsconfigvars',
		];
		if ( isset( $originalParams['useskin'] ) ) {
			$apiParams['useskin'] = $originalParams['useskin'];
		}
		if ( isset( $originalParams['mobileformat'] ) && $originalParams['mobileformat'] ) {
			$apiParams['mobileformat'] = '1';
		}

		$context = new DerivativeContext( $this->getContext() );
		$context->setRequest(
			new DerivativeRequest(
				$context->getRequest(),
				$apiParams,
				/* was posted? */ true
			)
		);
		$api = new ApiMain(
			$context,
			/* enable write? */ false
		);

		$api->execute();
		return $api->getResult();
	}

	/**
	 * @see ApiParse::getUserForPreview
	 * @return User
	 */
	private function getUserForPreview() {
		$user = $this->getUser();
		if ( $this->tempUserCreator->shouldAutoCreate( $user, 'edit' ) ) {
			return $this->userFactory->newUnsavedTempUser(
				$this->tempUserCreator->getStashedName( $this->getRequest()->getSession() )
			);
		}
		return $user;
	}

	/**
	 * @see VisualEditorParsoidClientFactory
	 * @return ParsoidClient
	 */
	protected function getParsoidClient(): ParsoidClient {
		return $this->parsoidClientFactory->createParsoidClient(
			$this->getContext()->getRequest()->getHeader( 'Cookie' )
		);
	}

	/**
	 * @warning (T323357) - Calling this method writes to stash, so it should be called
	 *   only when we are fetching page HTML for editing.
	 */
	abstract protected function requestRestbasePageHtml( RevisionRecord $revision ): array;

	abstract protected function transformHTML(
		Title $title, string $html, int $oldid = null, string $etag = null
	): array;

	/**
	 * @return IContextSource
	 */
	abstract public function getContext();

	/**
	 * @return User
	 */
	abstract public function getUser();

	/**
	 * @return WebRequest
	 */
	abstract public function getRequest();

}
