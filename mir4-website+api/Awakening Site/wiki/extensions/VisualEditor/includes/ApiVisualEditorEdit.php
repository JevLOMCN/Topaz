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
use ApiMain;
use BagOStuff;
use ContentHandler;
use Deflate;
use DerivativeContext;
use DifferenceEngine;
use ExtensionRegistry;
use FlaggablePageView;
use IBufferingStatsdDataFactory;
use IDBAccessObject;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Request\DerivativeRequest;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Storage\PageEditStash;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use ObjectCache;
use RequestContext;
use Sanitizer;
use SkinFactory;
use Wikimedia\ParamValidator\ParamValidator;

class ApiVisualEditorEdit extends ApiBase {
	use ApiParsoidTrait;

	private const MAX_CACHE_RECENT = 2;
	private const MAX_CACHE_TTL = 900;

	private VisualEditorHookRunner $hookRunner;
	private PageEditStash $pageEditStash;
	private SkinFactory $skinFactory;
	private WikiPageFactory $wikiPageFactory;
	private SpecialPageFactory $specialPageFactory;
	private VisualEditorParsoidClientFactory $parsoidClientFactory;

	public function __construct(
		ApiMain $main,
		string $name,
		HookContainer $hookContainer,
		IBufferingStatsdDataFactory $statsdDataFactory,
		PageEditStash $pageEditStash,
		SkinFactory $skinFactory,
		WikiPageFactory $wikiPageFactory,
		SpecialPageFactory $specialPageFactory,
		VisualEditorParsoidClientFactory $parsoidClientFactory
	) {
		parent::__construct( $main, $name );
		$this->setLogger( LoggerFactory::getInstance( 'VisualEditor' ) );
		$this->setStats( $statsdDataFactory );
		$this->hookRunner = new VisualEditorHookRunner( $hookContainer );
		$this->pageEditStash = $pageEditStash;
		$this->skinFactory = $skinFactory;
		$this->wikiPageFactory = $wikiPageFactory;
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
	 * Attempt to save a given page's wikitext to MediaWiki's storage layer via its API
	 *
	 * @param Title $title The title of the page to write
	 * @param string $wikitext The wikitext to write
	 * @param array $params The edit parameters
	 * @return mixed The result of the save attempt
	 */
	protected function saveWikitext( Title $title, $wikitext, $params ) {
		$apiParams = [
			'action' => 'edit',
			'title' => $title->getPrefixedDBkey(),
			'text' => $wikitext,
			'summary' => $params['summary'],
			'basetimestamp' => $params['basetimestamp'],
			'starttimestamp' => $params['starttimestamp'],
			'token' => $params['token'],
			'watchlist' => $params['watchlist'],
			// NOTE: Must use getText() to work; PHP array from $params['tags'] is not understood
			// by the edit API.
			'tags' => $this->getRequest()->getText( 'tags' ),
			'section' => $params['section'],
			'sectiontitle' => $params['sectiontitle'],
			'captchaid' => $params['captchaid'],
			'captchaword' => $params['captchaword'],
			'returnto' => $params['returnto'],
			'returntoquery' => $params['returntoquery'],
			'returntoanchor' => $params['returntoanchor'],
			'errorformat' => 'html',
			( $params['minor'] !== null ? 'minor' : 'notminor' ) => true,
		];

		// Pass any unrecognized query parameters to the internal action=edit API request. This is
		// necessary to support extensions that add extra stuff to the edit form (e.g. FlaggedRevs)
		// and allows passing any other query parameters to be used for edit tagging (e.g. T209132).
		// Exclude other known params from here and ApiMain.
		// TODO: This doesn't exclude params from the formatter
		$allParams = $this->getRequest()->getValues();
		$knownParams = array_keys( $this->getAllowedParams() + $this->getMain()->getAllowedParams() );
		foreach ( $knownParams as $knownParam ) {
			unset( $allParams[ $knownParam ] );
		}

		$context = new DerivativeContext( $this->getContext() );
		$context->setRequest(
			new DerivativeRequest(
				$context->getRequest(),
				$apiParams + $allParams,
				/* was posted? */ true
			)
		);
		$api = new ApiMain(
			$context,
			/* enable write? */ true
		);

		$api->execute();

		return $api->getResult()->getResultData();
	}

	/**
	 * Load into an array the output of MediaWiki's parser for a given revision
	 *
	 * @param int $newRevId The revision to load
	 * @param array $params Original request params
	 * @return array Some properties haphazardly extracted from an action=parse API response
	 */
	protected function parseWikitext( $newRevId, array $params ) {
		$apiParams = [
			'action' => 'parse',
			'oldid' => $newRevId,
			'prop' => 'text|revid|categorieshtml|sections|displaytitle|subtitle|modules|jsconfigvars',
			'useskin' => $params['useskin'],
		];
		// Boolean parameters must be omitted completely to be treated as false.
		// Param is added by hook in MobileFrontend, so it may be unset.
		if ( isset( $params['mobileformat'] ) && $params['mobileformat'] ) {
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
			/* enable write? */ true
		);

		$api->execute();
		$result = $api->getResult()->getResultData( null, [
			/* Transform content nodes to '*' */ 'BC' => [],
			/* Add back-compat subelements */ 'Types' => [],
			/* Remove any metadata keys from the links array */ 'Strip' => 'all',
		] );
		$content = $result['parse']['text']['*'] ?? false;
		$categorieshtml = $result['parse']['categorieshtml']['*'] ?? false;
		$sections = isset( $result['parse']['showtoc'] ) ? $result['parse']['sections'] : [];
		$displaytitle = $result['parse']['displaytitle'] ?? false;
		$subtitle = $result['parse']['subtitle'] ?? false;
		$modules = array_merge(
			$result['parse']['modules'] ?? [],
			$result['parse']['modulestyles'] ?? []
		);
		$jsconfigvars = $result['parse']['jsconfigvars'] ?? [];

		if ( $displaytitle !== false ) {
			// Escape entities as in OutputPage::setPageTitle()
			$displaytitle = Sanitizer::removeSomeTags( $displaytitle );
		}

		return [
			'content' => $content,
			'categorieshtml' => $categorieshtml,
			'sections' => $sections,
			'displayTitleHtml' => $displaytitle,
			'contentSub' => $subtitle,
			'modules' => $modules,
			'jsconfigvars' => $jsconfigvars
		];
	}

	/**
	 * Create and load the parsed wikitext of an edit, or from the serialisation cache if available.
	 *
	 * @param Title $title The title of the page
	 * @param array $params The edit parameters
	 * @param array $parserParams The parser parameters
	 * @return string The wikitext of the edit
	 */
	protected function getWikitext( Title $title, $params, $parserParams ) {
		if ( $params['cachekey'] !== null ) {
			$wikitext = $this->trySerializationCache( $params['cachekey'] );
			if ( !is_string( $wikitext ) ) {
				$this->dieWithError( 'apierror-visualeditor-badcachekey', 'badcachekey' );
			}
		} else {
			$wikitext = $this->getWikitextNoCache( $title, $params, $parserParams );
		}
		'@phan-var string $wikitext';
		return $wikitext;
	}

	/**
	 * Create and load the parsed wikitext of an edit, ignoring the serialisation cache.
	 *
	 * @param Title $title The title of the page
	 * @param array $params The edit parameters
	 * @param array $parserParams The parser parameters
	 * @return string The wikitext of the edit
	 */
	protected function getWikitextNoCache( Title $title, $params, $parserParams ) {
		$this->requireOnlyOneParameter( $params, 'html' );
		if ( Deflate::isDeflated( $params['html'] ) ) {
			$status = Deflate::inflate( $params['html'] );
			if ( !$status->isGood() ) {
				$this->dieWithError( 'deflate-invaliddeflate', 'invaliddeflate' );
			}
			$html = $status->getValue();
		} else {
			$html = $params['html'];
		}
		$wikitext = $this->transformHTML(
			$title, $html, $parserParams['oldid'] ?? null, $params['etag'] ?? null
		)['body'];
		return $wikitext;
	}

	/**
	 * Load the parsed wikitext of an edit into the serialisation cache.
	 *
	 * @param Title $title The title of the page
	 * @param string $wikitext The wikitext of the edit
	 * @return string|false The key of the wikitext in the serialisation cache
	 */
	protected function storeInSerializationCache( Title $title, $wikitext ) {
		if ( $wikitext === false ) {
			return false;
		}

		$cache = ObjectCache::getLocalClusterInstance();

		// Store the corresponding wikitext, referenceable by a new key
		$hash = md5( $wikitext );
		$key = $cache->makeKey( 'visualeditor', 'serialization', $hash );
		$ok = $cache->set( $key, $wikitext, self::MAX_CACHE_TTL );
		if ( $ok ) {
			$this->pruneExcessStashedEntries( $cache, $this->getUser(), $key );
		}

		$status = $ok ? 'ok' : 'failed';
		$this->getStats()->increment( "editstash.ve_serialization_cache.set_" . $status );

		// Also parse and prepare the edit in case it might be saved later
		$pageUpdater = $this->wikiPageFactory->newFromTitle( $title )->newPageUpdater( $this->getUser() );
		$content = ContentHandler::makeContent( $wikitext, $title, CONTENT_MODEL_WIKITEXT );

		$status = $this->pageEditStash->parseAndCache( $pageUpdater, $content, $this->getUser(), '' );
		if ( $status === $this->pageEditStash::ERROR_NONE ) {
			$logger = LoggerFactory::getInstance( 'StashEdit' );
			$logger->debug( "Cached parser output for VE content key '$key'." );
		}
		$this->getStats()->increment( "editstash.ve_cache_stores.$status" );

		return $hash;
	}

	/**
	 * @param BagOStuff $cache
	 * @param UserIdentity $user
	 * @param string $newKey
	 */
	private function pruneExcessStashedEntries( BagOStuff $cache, UserIdentity $user, $newKey ) {
		$key = $cache->makeKey( 'visualeditor-serialization-recent', $user->getName() );

		$keyList = $cache->get( $key ) ?: [];
		if ( count( $keyList ) >= self::MAX_CACHE_RECENT ) {
			$oldestKey = array_shift( $keyList );
			$cache->delete( $oldestKey );
		}

		$keyList[] = $newKey;
		$cache->set( $key, $keyList, 2 * self::MAX_CACHE_TTL );
	}

	/**
	 * Load some parsed wikitext of an edit from the serialisation cache.
	 *
	 * @param string $hash The key of the wikitext in the serialisation cache
	 * @return string|null The wikitext
	 */
	protected function trySerializationCache( $hash ) {
		$cache = ObjectCache::getLocalClusterInstance();
		$key = $cache->makeKey( 'visualeditor', 'serialization', $hash );
		$value = $cache->get( $key );

		$status = ( $value !== false ) ? 'hit' : 'miss';
		$this->getStats()->increment( "editstash.ve_serialization_cache.get_$status" );

		return $value;
	}

	/**
	 * Calculate the different between the wikitext of an edit and an existing revision.
	 *
	 * @param Title $title The title of the page
	 * @param int|null $fromId The existing revision of the page to compare with
	 * @param string $wikitext The wikitext to compare against
	 * @param int|null $section Whether the wikitext refers to a given section or the whole page
	 * @return array The comparison, or `[ 'result' => 'nochanges' ]` if there are none
	 */
	protected function diffWikitext( Title $title, ?int $fromId, $wikitext, $section = null ) {
		$apiParams = [
			'action' => 'compare',
			'prop' => 'diff',
			// Because we're just providing wikitext, we only care about the main slot
			'slots' => SlotRecord::MAIN,
			'fromtitle' => $title->getPrefixedDBkey(),
			'fromrev' => $fromId,
			'fromsection' => $section,
			'toslots' => SlotRecord::MAIN,
			'totext-main' => $wikitext,
			'topst' => true,
		];

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
		$result = $api->getResult()->getResultData();

		if ( !isset( $result['compare']['bodies'][SlotRecord::MAIN] ) ) {
			$this->dieWithError( 'apierror-visualeditor-difffailed', 'difffailed' );
		}
		$diffRows = $result['compare']['bodies'][SlotRecord::MAIN];

		$context = new DerivativeContext( $this->getContext() );
		$context->setTitle( $title );
		$engine = new DifferenceEngine( $context );
		return [
			'result' => 'success',
			'diff' => $diffRows ? $engine->addHeader(
				$diffRows,
				$context->msg( 'currentrev' )->parse(),
				$context->msg( 'yourtext' )->parse()
			) : ''
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$user = $this->getUser();
		$params = $this->extractRequestParams();

		$result = [];
		$title = Title::newFromText( $params['page'] );
		if ( $title && $title->isSpecialPage() ) {
			// Convert Special:CollabPad/MyPage to MyPage so we can serialize properly
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
		$this->getErrorFormatter()->setContextTitle( $title );

		$parserParams = [];
		if ( isset( $params['oldid'] ) ) {
			$parserParams['oldid'] = $params['oldid'];
		}

		if ( isset( $params['wikitext'] ) ) {
			$wikitext = str_replace( "\r\n", "\n", $params['wikitext'] );
		} else {
			$wikitext = $this->getWikitext( $title, $params, $parserParams );
		}

		if ( $params['paction'] === 'serialize' ) {
			$result = [ 'result' => 'success', 'content' => $wikitext ];
		} elseif ( $params['paction'] === 'serializeforcache' ) {
			$key = $this->storeInSerializationCache(
				$title,
				$wikitext
			);
			$result = [ 'result' => 'success', 'cachekey' => $key ];
		} elseif ( $params['paction'] === 'diff' ) {
			$section = $params['section'] ?? null;
			$result = $this->diffWikitext( $title, $params['oldid'], $wikitext, $section );
		} elseif ( $params['paction'] === 'save' ) {
			$pluginData = [];
			foreach ( $params['plugins'] ?? [] as $plugin ) {
				$pluginData[$plugin] = $params['data-' . $plugin];
			}
			$presaveHook = $this->hookRunner->onVisualEditorApiVisualEditorEditPreSave(
				$title->toPageIdentity(),
				$user,
				$wikitext,
				$params,
				$pluginData,
				$result
			);

			if ( $presaveHook === false ) {
				$this->dieWithError( $result['message'], 'hookaborted', $result );
			}

			$saveresult = $this->saveWikitext( $title, $wikitext, $params );
			$editStatus = $saveresult['edit']['result'];

			// Error
			if ( $editStatus !== 'Success' ) {
				$result['result'] = 'error';
				$result['edit'] = $saveresult['edit'];
			} else {
				// Success
				$result['result'] = 'success';

				if ( $params['nocontent'] ) {
					$result['nocontent'] = true;
				} else {
					if ( isset( $saveresult['edit']['newrevid'] ) ) {
						$newRevId = intval( $saveresult['edit']['newrevid'] );
					} else {
						$newRevId = $title->getLatestRevID();
					}

					// Return result of parseWikitext instead of saveWikitext so that the
					// frontend can update the page rendering without a refresh.
					$parseWikitextResult = $this->parseWikitext( $newRevId, $params );

					$result = array_merge( $result, $parseWikitextResult );
				}

				$result['isRedirect'] = (string)$title->isRedirect();

				if ( ExtensionRegistry::getInstance()->isLoaded( 'FlaggedRevs' ) ) {
					$newContext = new DerivativeContext( RequestContext::getMain() );
					// Defeat !$this->isPageView( $request ) || $request->getVal( 'oldid' ) check in setPageContent
					$newRequest = new DerivativeRequest(
						$this->getRequest(),
						[
							'diff' => null,
							'oldid' => '',
							'title' => $title->getPrefixedText(),
							'action' => 'view'
						] + $this->getRequest()->getValues()
					);
					$newContext->setRequest( $newRequest );
					$newContext->setTitle( $title );

					// Must be after $globalContext->setTitle since FlaggedRevs constructor
					// inspects global Title
					$view = FlaggablePageView::newFromTitle( $title );
					// Most likely identical to $globalState, but not our concern
					$originalContext = $view->getContext();
					$view->setContext( $newContext );

					// The two parameters here are references but we don't care
					// about what FlaggedRevs does with them.
					$outputDone = null;
					$useParserCache = null;
					// @phan-suppress-next-line PhanTypeMismatchArgument
					$view->setPageContent( $outputDone, $useParserCache );
					$view->displayTag();
					$view->setContext( $originalContext );
				}

				$lang = $this->getLanguage();

				if ( isset( $saveresult['edit']['newtimestamp'] ) ) {
					$ts = $saveresult['edit']['newtimestamp'];

					$result['lastModified'] = [
						'date' => $lang->userDate( $ts, $user ),
						'time' => $lang->userTime( $ts, $user )
					];
				}

				if ( isset( $saveresult['edit']['newrevid'] ) ) {
					$result['newrevid'] = intval( $saveresult['edit']['newrevid'] );
				}

				if ( isset( $saveresult['edit']['tempusercreated'] ) ) {
					$result['tempusercreated'] = $saveresult['edit']['tempusercreated'];
				}
				if ( isset( $saveresult['edit']['tempusercreatedredirect'] ) ) {
					$result['tempusercreatedredirect'] = $saveresult['edit']['tempusercreatedredirect'];
				}

				$result['watched'] = $saveresult['edit']['watched'] ?? false;
				$result['watchlistexpiry'] = $saveresult['edit']['watchlistexpiry'] ?? null;
			}

			// Refresh article ID (which is used by toPageIdentity()) in case we just created the page.
			// Maybe it's not great to rely on this side-effect…
			$title->getArticleID( IDBAccessObject::READ_LATEST );

			$this->hookRunner->onVisualEditorApiVisualEditorEditPostSave(
				$title->toPageIdentity(),
				$user,
				$wikitext,
				$params,
				$pluginData,
				$saveresult,
				$result
			);
		}
		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'paction' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => [
					'serialize',
					'serializeforcache',
					'diff',
					'save',
				],
			],
			'page' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'token' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'wikitext' => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_DEFAULT => null,
			],
			'section' => null,
			'sectiontitle' => null,
			'basetimestamp' => [
				ParamValidator::PARAM_TYPE => 'timestamp',
			],
			'starttimestamp' => [
				ParamValidator::PARAM_TYPE => 'timestamp',
			],
			'oldid' => [
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'minor' => null,
			'watchlist' => null,
			'html' => [
				// Use the 'raw' type to avoid Unicode NFC normalization.
				// This makes the parameter binary safe, so that (a) if
				// we use client-side compression it is not mangled, and/or
				// (b) deprecated Unicode sequences explicitly encoded in
				// wikitext (ie, &#x2001;) are not mangled.  Wikitext is
				// in Unicode Normal Form C, but because of explicit entities
				// the output HTML is not guaranteed to be.
				ParamValidator::PARAM_TYPE => 'raw',
				ParamValidator::PARAM_DEFAULT => null,
			],
			'etag' => null,
			'summary' => null,
			'captchaid' => null,
			'captchaword' => null,
			'cachekey' => null,
			'nocontent' => false,
			'returnto' => [
				ParamValidator::PARAM_TYPE => 'title',
				ApiBase::PARAM_HELP_MSG => 'apihelp-edit-param-returnto',
			],
			'returntoquery' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
				ApiBase::PARAM_HELP_MSG => 'apihelp-edit-param-returntoquery',
			],
			'returntoanchor' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
				ApiBase::PARAM_HELP_MSG => 'apihelp-edit-param-returntoanchor',
			],
			'useskin' => [
				ParamValidator::PARAM_TYPE => array_keys( $this->skinFactory->getInstalledSkins() ),
				ApiBase::PARAM_HELP_MSG => 'apihelp-parse-param-useskin',
			],
			'tags' => [
				ParamValidator::PARAM_ISMULTI => true,
			],
			'plugins' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			// Additional data sent by the client. Not used directly in the ApiVisualEditorEdit workflows, but
			// is passed alongside the other parameters to implementations of onApiVisualEditorEditPostSave and
			// onApiVisualEditorEditPreSave
			'data-{plugin}' => [
				ApiBase::PARAM_TEMPLATE_VARS => [ 'plugin' => 'plugins' ]
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
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
		return true;
	}

}
