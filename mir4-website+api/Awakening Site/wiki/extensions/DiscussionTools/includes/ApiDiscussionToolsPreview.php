<?php

namespace MediaWiki\Extension\DiscussionTools;

use ApiBase;
use ApiMain;
use ApiUsageException;
use MediaWiki\Extension\VisualEditor\ApiParsoidTrait;
use MediaWiki\Extension\VisualEditor\VisualEditorParsoidClientFactory;
use MediaWiki\Title\Title;
use MediaWiki\User\TempUser\TempUserCreator;
use MediaWiki\User\UserFactory;
use SkinFactory;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

class ApiDiscussionToolsPreview extends ApiBase {
	use ApiDiscussionToolsTrait;
	use ApiParsoidTrait;

	private CommentParser $commentParser;
	private VisualEditorParsoidClientFactory $parsoidClientFactory;
	private TempUserCreator $tempUserCreator;
	private UserFactory $userFactory;
	private SkinFactory $skinFactory;

	public function __construct(
		ApiMain $main,
		string $name,
		VisualEditorParsoidClientFactory $parsoidClientFactory,
		CommentParser $commentParser,
		TempUserCreator $tempUserCreator,
		UserFactory $userFactory,
		SkinFactory $skinFactory
	) {
		parent::__construct( $main, $name );
		$this->parsoidClientFactory = $parsoidClientFactory;
		$this->commentParser = $commentParser;
		$this->tempUserCreator = $tempUserCreator;
		$this->userFactory = $userFactory;
		$this->skinFactory = $skinFactory;
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$title = Title::newFromText( $params['page'] );

		if ( !$title ) {
			$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $params['page'] ) ] );
		}
		if ( $params['type'] === 'topic' ) {
			$this->requireAtLeastOneParameter( $params, 'sectiontitle' );
		}

		// Try without adding a signature
		$result = $this->previewMessage(
			$params['type'],
			$title,
			[
				'wikitext' => $params['wikitext'],
				'sectiontitle' => $params['sectiontitle']
			],
			$params
		);
		$resultHtml = $result->getResultData( [ 'parse', 'text' ] );

		// Check if there was a signature in a proper place
		$container = DOMCompat::getBody( DOMUtils::parseHTML( $resultHtml ) );
		$threadItemSet = $this->commentParser->parse( $container, $title->getTitleValue() );
		if ( !CommentUtils::isSingleCommentSignedBy(
			$threadItemSet, $this->getUserForPreview()->getName(), $container
		) ) {
			// If not, add the signature and re-render
			$signature = $this->msg( 'discussiontools-signature-prefix' )->inContentLanguage()->text() . '~~~~';
			// Drop opacity of signature in preview to make message body preview clearer.
			// Extract any leading spaces outside the <span> markup to ensure accurate previews.
			$signature = preg_replace( '/([^ ].*)/', '<span style="opacity: 0.6;">$1</span>',
				$signature );

			$result = $this->previewMessage(
				$params['type'],
				$title,
				[
					'wikitext' => $params['wikitext'],
					'sectiontitle' => $params['sectiontitle'],
					'signature' => $signature
				],
				$params
			);
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result->serializeForApiResult() );
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'type' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => [
					'reply',
					'topic',
				],
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [
					'reply' => 'apihelp-discussiontoolsedit-paramvalue-paction-addcomment',
					'topic' => 'apihelp-discussiontoolsedit-paramvalue-paction-addtopic',
				],
			],
			'page' => [
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-visualeditoredit-param-page',
			],
			'wikitext' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'text',
			],
			'sectiontitle' => [
				ParamValidator::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG => 'apihelp-edit-param-sectiontitle',
			],
			'useskin' => [
				ParamValidator::PARAM_TYPE => array_keys( $this->skinFactory->getInstalledSkins() ),
				ApiBase::PARAM_HELP_MSG => 'apihelp-parse-param-useskin',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function isInternal() {
		return true;
	}
}
