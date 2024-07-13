<?php
/**
 * Parsoid/RESTBase+MediaWiki API wrapper.
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2021 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */

namespace MediaWiki\Extension\VisualEditor;

use ApiBase;
use ApiBlockInfoTrait;
use ApiMain;
use ApiResult;
use Article;
use Config;
use ContentHandler;
use DerivativeContext;
use ExtensionRegistry;
use IBufferingStatsdDataFactory;
use MediaWiki\Content\Transform\ContentTransformer;
use MediaWiki\EditPage\EditPage;
use MediaWiki\EditPage\IntroMessageBuilder;
use MediaWiki\EditPage\PreloadedContentBuilder;
use MediaWiki\EditPage\TextboxBuilder;
use MediaWiki\Language\RawMessage;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Request\DerivativeRequest;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\TempUser\TempUserCreator;
use MediaWiki\User\UserFactory;
use MediaWiki\Watchlist\WatchlistManager;
use MessageLocalizer;
use RequestContext;
use User;
use Wikimedia\Assert\Assert;
use Wikimedia\ParamValidator\ParamValidator;
use WikitextContent;

class ApiVisualEditor extends ApiBase {
	use ApiBlockInfoTrait;
	use ApiParsoidTrait;

	private RevisionLookup $revisionLookup;
	private TempUserCreator $tempUserCreator;
	private UserFactory $userFactory;
	private UserOptionsLookup $userOptionsLookup;
	private WatchlistManager $watchlistManager;
	private ContentTransformer $contentTransformer;
	private WikiPageFactory $wikiPageFactory;
	private IntroMessageBuilder $introMessageBuilder;
	private PreloadedContentBuilder $preloadedContentBuilder;
	private SpecialPageFactory $specialPageFactory;
	private VisualEditorParsoidClientFactory $parsoidClientFactory;

	public function __construct(
		ApiMain $main,
		string $name,
		RevisionLookup $revisionLookup,
		TempUserCreator $tempUserCreator,
		UserFactory $userFactory,
		UserOptionsLookup $userOptionsLookup,
		WatchlistManager $watchlistManager,
		ContentTransformer $contentTransformer,
		IBufferingStatsdDataFactory $statsdDataFactory,
		WikiPageFactory $wikiPageFactory,
		IntroMessageBuilder $introMessageBuilder,
		PreloadedContentBuilder $preloadedContentBuilder,
		SpecialPageFactory $specialPageFactory,
		VisualEditorParsoidClientFactory $parsoidClientFactory
	) {
		parent::__construct( $main, $name );
		$this->setLogger( LoggerFactory::getInstance( 'VisualEditor' ) );
		$this->setStats( $statsdDataFactory );
		$this->revisionLookup = $revisionLookup;
		$this->tempUserCreator = $tempUserCreator;
		$this->userFactory = $userFactory;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->watchlistManager = $watchlistManager;
		$this->contentTransformer = $contentTransformer;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->introMessageBuilder = $introMessageBuilder;
		$this->preloadedContentBuilder = $preloadedContentBuilder;
		$this->specialPageFactory = $specialPageFactory;
		$this->parsoidClientFactory = $parsoidClientFactory;
	}

	/**
	 * @inheritDoc
	 */
	protected function getParsoidClient(): ParsoidClient {
		return $this->parsoidClientFactory->createParsoidClient(
			$this->getRequest()->getHeader( 'Cookie' )
		);
	}

	/**
	 * @see EditPage::getUserForPermissions
	 * @return User
	 */
	private function getUserForPermissions() {
		$user = $this->getUser();
		if ( $this->tempUserCreator->shouldAutoCreate( $user, 'edit' ) ) {
			return $this->userFactory->newUnsavedTempUser(
				$this->tempUserCreator->getStashedName( $this->getRequest()->getSession() )
			);
		}
		return $user;
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
	 * Run wikitext through the parser's Pre-Save-Transform
	 *
	 * @param Title $title The title of the page to use as the parsing context
	 * @param string $wikitext The wikitext to transform
	 * @return string The transformed wikitext
	 */
	protected function pstWikitext( Title $title, $wikitext ) {
		$content = ContentHandler::makeContent( $wikitext, $title, CONTENT_MODEL_WIKITEXT );
		return $this->contentTransformer->preSaveTransform(
			$content,
			$title,
			$this->getUserForPreview(),
			$this->wikiPageFactory->newFromTitle( $title )->makeParserOptions( $this->getContext() )
		)
		->serialize( 'text/x-wiki' );
	}

	/**
	 * @inheritDoc
	 * @suppress PhanPossiblyUndeclaredVariable False positives
	 */
	public function execute() {
		$user = $this->getUser();
		$params = $this->extractRequestParams();
		$permissionManager = $this->getPermissionManager();

		$title = Title::newFromText( $params['page'] );
		if ( $title && $title->isSpecialPage() ) {
			// Convert Special:CollabPad/MyPage to MyPage so we can parsefragment properly
			[ $special, $subPage ] = $this->specialPageFactory->resolveAlias( $title->getDBkey() );
			if ( $special === 'CollabPad' ) {
				$title = Title::newFromText( $subPage );
			}
		}
		if ( !$title ) {
			$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $params['page'] ) ] );
		}
		if ( !$title->canExist() ) {
			$this->dieWithError( 'apierror-pagecannotexist' );
		}

		wfDebugLog( 'visualeditor', "called on '$title' with paction: '{$params['paction']}'" );
		switch ( $params['paction'] ) {
			case 'parse':
			case 'wikitext':
			case 'metadata':
				// Dirty hack to provide the correct context for FlaggedRevs when it generates edit notices
				// and save dialog checkboxes. (T307852)
				// FIXME Don't write to globals! Eww.
				RequestContext::getMain()->setTitle( $title );

				$preloaded = false;
				$restbaseHeaders = null;

				$section = $params['section'] ?? null;

				// Get information about current revision
				if ( $title->exists() ) {
					$latestRevision = $this->revisionLookup->getRevisionByTitle( $title );
					if ( !$latestRevision ) {
						$this->dieWithError(
							[ 'apierror-missingrev-title', wfEscapeWikiText( $title->getPrefixedText() ) ],
							'nosuchrevid'
						);
					}
					if ( isset( $params['oldid'] ) ) {
						$revision = $this->revisionLookup->getRevisionById( $params['oldid'] );
						if ( !$revision ) {
							$this->dieWithError( [ 'apierror-nosuchrevid', $params['oldid'] ] );
						}
					} else {
						$revision = $latestRevision;
					}

					$baseTimestamp = $latestRevision->getTimestamp();
					$oldid = $revision->getId();

					// If requested, request HTML from Parsoid/RESTBase
					if ( $params['paction'] === 'parse' ) {
						$wikitext = $params['wikitext'] ?? null;
						if ( $wikitext !== null ) {
							$stash = $params['stash'];
							if ( $params['pst'] ) {
								$wikitext = $this->pstWikitext( $title, $wikitext );
							}
							if ( $section !== null ) {
								$sectionContent = new WikitextContent( $wikitext );
								$page = $this->wikiPageFactory->newFromTitle( $title );
								$newSectionContent = $page->replaceSectionAtRev(
									$section, $sectionContent, '', $oldid
								);
								'@phan-var WikitextContent $newSectionContent';
								$wikitext = $newSectionContent->getText();
							}
							$response = $this->transformWikitext(
								$title, $wikitext, false, $oldid, $stash
							);
						} else {
							$response = $this->requestRestbasePageHtml( $revision );
						}
						$content = $response['body'];
						$restbaseHeaders = $response['headers'];
					} elseif ( $params['paction'] === 'wikitext' ) {
						$apiParams = [
							'action' => 'query',
							'revids' => $oldid,
							'prop' => 'revisions',
							'rvprop' => 'content|ids'
						];

						$apiParams['rvsection'] = $section;

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
							/* enable write? */ true
						);
						$api->execute();
						$result = $api->getResult()->getResultData();
						$pid = $title->getArticleID();
						$content = false;
						if ( isset( $result['query']['pages'][$pid]['revisions'] ) ) {
							foreach ( $result['query']['pages'][$pid]['revisions'] as $revArr ) {
								// Check 'revisions' is an array (T193718)
								if ( is_array( $revArr ) && $revArr['revid'] === $oldid ) {
									$content = $revArr['content'];
								}
							}
						}
					}
				} else {
					$revision = null;
				}

				// Use $title as the context page in every processed message (T300184)
				$localizerWithTitle = new class( $this, $title ) implements MessageLocalizer {
					private MessageLocalizer $base;
					private PageReference $page;

					public function __construct( MessageLocalizer $base, PageReference $page ) {
						$this->base = $base;
						$this->page = $page;
					}

					/**
					 * @inheritDoc
					 */
					public function msg( $key, ...$params ) {
						return $this->base->msg( $key, ...$params )->page( $this->page );
					}
				};

				if ( !$title->exists() || $section === 'new' ) {
					if ( isset( $params['wikitext'] ) ) {
						$content = $params['wikitext'];
						if ( $params['pst'] ) {
							$content = $this->pstWikitext( $title, $content );
						}
					} else {
						$contentObj = $this->preloadedContentBuilder->getPreloadedContent(
							$title->toPageIdentity(),
							$user,
							$params['preload'],
							$params['preloadparams'] ?? [],
							$section
						);
						$dfltContent = $section === 'new' ? null :
							$this->preloadedContentBuilder->getDefaultContent( $title->toPageIdentity() );
						$preloaded = $dfltContent ? !$contentObj->equals( $dfltContent ) : !$contentObj->isEmpty();
						$content = $contentObj->serialize();
					}

					if ( $content !== '' && $params['paction'] !== 'wikitext' ) {
						$response = $this->transformWikitext( $title, $content, false, null, true );
						$content = $response['body'];
						$restbaseHeaders = $response['headers'];
					}
					$baseTimestamp = wfTimestampNow();
					$oldid = 0;
				}

				// Look at protection status to set up notices + surface class(es)
				$builder = new TextboxBuilder();
				$protectedClasses = $builder->getTextboxProtectionCSSClasses( $title );

				// Simplified EditPage::getEditPermissionErrors()
				// TODO: Use API
				// action=query&prop=info&intestactions=edit&intestactionsdetail=full&errorformat=html&errorsuselocal=1
				$permErrors = $permissionManager->getPermissionErrors(
					'edit', $this->getUserForPermissions(), $title, 'full' );
				if ( $permErrors ) {
					// Show generic permission errors, including page protection, user blocks, etc.
					$notice = $this->getOutput()->formatPermissionsErrorMessage( $permErrors, 'edit' );
					// That method returns wikitext (eww), hack to get it parsed:
					$notice = ( new RawMessage( '$1', [ $notice ] ) )->page( $title )->parseAsBlock();
					// Invent a message key 'permissions-error' to store in $notices
					// (This probably shouldn't use the notices system…)
					$notices = [ 'permissions-error' => $notice ];
				} else {
					$notices = $this->introMessageBuilder->getIntroMessages(
						IntroMessageBuilder::LESS_FRAMES,
						[
							// This message was not shown by VisualEditor before it was switched to use
							// IntroMessageBuilder, and it may be unexpected to display it now, so skip it.
							'editpage-head-copy-warn',
							// This message was not shown by VisualEditor previously, and on many Wikipedias it's
							// technically non-empty but hidden with CSS, and not a real edit notice (T337633).
							'editnotice-notext',
						],
						$localizerWithTitle,
						$title->toPageIdentity(),
						$revision,
						$user,
						$params['editintro'],
						null,
						false,
						$section
					);
				}

				// Will be false e.g. if user is blocked or page is protected
				$canEdit = !$permErrors;

				$blockinfo = null;
				// Blocked user notice
				if ( $permissionManager->isBlockedFrom( $user, $title, true ) ) {
					$block = $user->getBlock();
					if ( $block ) {
						// Already added to $notices via #getPermissionErrors above.
						// Add block info for MobileFrontend:
						$blockinfo = $this->getBlockDetails( $block );
					}
				}

				// HACK: Build a fake EditPage so we can get checkboxes from it
				// Deliberately omitting ,0 so oldid comes from request
				$article = new Article( $title );
				$editPage = new EditPage( $article );
				$req = $this->getRequest();
				$req->setVal( 'format', $editPage->contentFormat );
				// By reference for some reason (T54466)
				$editPage->importFormData( $req );
				$states = [
					'minor' => $this->userOptionsLookup->getOption( $user, 'minordefault' ) && $title->exists(),
					'watch' => $this->userOptionsLookup->getOption( $user, 'watchdefault' ) ||
						( $this->userOptionsLookup->getOption( $user, 'watchcreations' ) && !$title->exists() ) ||
						$this->watchlistManager->isWatched( $user, $title ),
				];
				$checkboxesDef = $editPage->getCheckboxesDefinition( $states );
				$checkboxesMessagesList = [];
				foreach ( $checkboxesDef as &$options ) {
					if ( isset( $options['tooltip'] ) ) {
						$checkboxesMessagesList[] = "accesskey-{$options['tooltip']}";
						$checkboxesMessagesList[] = "tooltip-{$options['tooltip']}";
					}
					if ( isset( $options['title-message'] ) ) {
						$checkboxesMessagesList[] = $options['title-message'];
						if ( !is_string( $options['title-message'] ) ) {
							// Extract only the key. Any parameters are included in the fake message definition
							// passed via $checkboxesMessages. (This changes $checkboxesDef by reference.)
							$options['title-message'] = $this->msg( $options['title-message'] )->getKey();
						}
					}
					$checkboxesMessagesList[] = $options['label-message'];
					if ( !is_string( $options['label-message'] ) ) {
						// Extract only the key. Any parameters are included in the fake message definition
						// passed via $checkboxesMessages. (This changes $checkboxesDef by reference.)
						$options['label-message'] = $this->msg( $options['label-message'] )->getKey();
					}
				}
				$checkboxesMessages = [];
				foreach ( $checkboxesMessagesList as $messageSpecifier ) {
					// $messageSpecifier may be a string or a Message object
					$message = $this->msg( $messageSpecifier );
					$checkboxesMessages[ $message->getKey() ] = $message->plain();
				}

				foreach ( $checkboxesDef as &$value ) {
					// Don't convert the boolean to empty string with formatversion=1
					$value[ApiResult::META_BC_BOOLS] = [ 'default' ];
				}

				$copyrightWarning = EditPage::getCopyrightWarning(
					$title,
					'parse',
					$this
				);

				// Copied from EditPage::maybeActivateTempUserCreate
				// Used by code in MobileFrontend and DiscussionTools.
				// TODO Make them use API
				// action=query&prop=info&intestactions=edit&intestactionsautocreate=1
				$wouldautocreate =
					!$user->isRegistered()
						&& $this->tempUserCreator->isAutoCreateAction( 'edit' )
						&& $permissionManager->userHasRight( $user, 'createaccount' );

				$result = [
					'result' => 'success',
					'notices' => $notices,
					'copyrightWarning' => $copyrightWarning,
					'checkboxesDef' => $checkboxesDef,
					'checkboxesMessages' => $checkboxesMessages,
					'protectedClasses' => implode( ' ', $protectedClasses ),
					'basetimestamp' => $baseTimestamp,
					'starttimestamp' => wfTimestampNow(),
					'oldid' => $oldid,
					'blockinfo' => $blockinfo,
					'wouldautocreate' => $wouldautocreate,
					'canEdit' => $canEdit,
				];
				if ( isset( $restbaseHeaders['etag'] ) ) {
					$result['etag'] = $restbaseHeaders['etag'];
				}
				if ( isset( $params['badetag'] ) ) {
					$badetag = $params['badetag'];
					$goodetag = $result['etag'] ?? '';
					$this->getLogger()->info(
						__METHOD__ . ": Client reported bad ETag: {badetag}, expected: {goodetag}",
						[
							'badetag' => $badetag,
							'goodetag' => $goodetag,
						]
					);
				}

				if ( isset( $content ) ) {
					Assert::postcondition( is_string( $content ), 'Content expected' );
					$result['content'] = $content;
					$result['preloaded'] = $preloaded;
				}
				break;

			case 'templatesused':
				// HACK: Build a fake EditPage so we can get checkboxes from it
				// Deliberately omitting ,0 so oldid comes from request
				$article = new Article( $title );
				$editPage = new EditPage( $article );
				$result = $editPage->makeTemplatesOnThisPageList( $editPage->getTemplates() );
				break;

			case 'parsefragment':
				$wikitext = $params['wikitext'];
				if ( $wikitext === null ) {
					$this->dieWithError( [ 'apierror-missingparam', 'wikitext' ] );
				}
				if ( $params['pst'] ) {
					$wikitext = $this->pstWikitext( $title, $wikitext );
				}
				$content = $this->transformWikitext(
					$title, $wikitext, true
				)['body'];
				Assert::postcondition( is_string( $content ), 'Content expected' );
				$result = [
					'result' => 'success',
					'content' => $content
				];
				break;
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * Check if the configured allowed namespaces include the specified namespace
	 *
	 * @param Config $config
	 * @param int $namespaceId Namespace ID
	 * @return bool
	 */
	public static function isAllowedNamespace( Config $config, $namespaceId ) {
		return in_array( $namespaceId, self::getAvailableNamespaceIds( $config ), true );
	}

	/**
	 * Get a list of allowed namespace IDs
	 *
	 * @param Config $config
	 * @return int[]
	 */
	public static function getAvailableNamespaceIds( Config $config ) {
		$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
		$configuredNamespaces = array_replace(
			ExtensionRegistry::getInstance()->getAttribute( 'VisualEditorAvailableNamespaces' ),
			$config->get( 'VisualEditorAvailableNamespaces' )
		);
		$normalized = [];
		foreach ( $configuredNamespaces as $id => $enabled ) {
			// Convert canonical namespace names to IDs
			$id = $namespaceInfo->getCanonicalIndex( strtolower( $id ) ) ?? $id;
			$normalized[$id] = $enabled && $namespaceInfo->exists( $id );
		}
		ksort( $normalized );
		return array_keys( array_filter( $normalized ) );
	}

	/**
	 * Check if the configured allowed content models include the specified content model
	 *
	 * @param Config $config
	 * @param string $contentModel Content model ID
	 * @return bool
	 */
	public static function isAllowedContentType( Config $config, $contentModel ) {
		$availableContentModels = array_merge(
			ExtensionRegistry::getInstance()->getAttribute( 'VisualEditorAvailableContentModels' ),
			$config->get( 'VisualEditorAvailableContentModels' )
		);
		return (bool)( $availableContentModels[$contentModel] ?? false );
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'page' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'badetag' => null,
			'format' => [
				ParamValidator::PARAM_DEFAULT => 'jsonfm',
				ParamValidator::PARAM_TYPE => [ 'json', 'jsonfm' ],
			],
			'paction' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => [
					'parse',
					'metadata',
					'templatesused',
					'wikitext',
					'parsefragment',
				],
			],
			'wikitext' => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_DEFAULT => null,
			],
			'section' => null,
			'stash' => false,
			'oldid' => [
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'editintro' => null,
			'pst' => false,
			'preload' => null,
			'preloadparams' => [
				ParamValidator::PARAM_ISMULTI => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function isInternal() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode() {
		return false;
	}
}
