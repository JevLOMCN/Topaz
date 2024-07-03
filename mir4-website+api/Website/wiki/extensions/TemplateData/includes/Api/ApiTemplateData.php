<?php

namespace MediaWiki\Extension\TemplateData\Api;

use ApiBase;
use ApiContinuationManager;
use ApiFormatBase;
use ApiPageSet;
use ApiResult;
use ExtensionRegistry;
use MediaWiki\Extension\EventLogging\EventLogging;
use MediaWiki\Extension\TemplateData\TemplateDataBlob;
use MediaWiki\MediaWikiServices;
use TextContent;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Implement the 'templatedata' query module in the API.
 * Format JSON only.
 * @license GPL-2.0-or-later
 * @ingroup API
 * @emits error.code templatedata-corrupt
 * @todo Support continuation (see I1a6e51cd)
 */
class ApiTemplateData extends ApiBase {

	private ?ApiPageSet $mPageSet = null;

	/**
	 * For backwards compatibility, this module needs to output format=json when
	 * no format is specified.
	 * @return ApiFormatBase|null
	 */
	public function getCustomPrinter() {
		if ( $this->getMain()->getVal( 'format' ) === null ) {
			$this->addDeprecation(
				'apiwarn-templatedata-deprecation-format', 'action=templatedata&!format'
			);
			return $this->getMain()->createPrinterByName( 'json' );
		}
		return null;
	}

	private function getPageSet(): ApiPageSet {
		$this->mPageSet ??= new ApiPageSet( $this );
		return $this->mPageSet;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$services = MediaWikiServices::getInstance();
		$params = $this->extractRequestParams();
		$result = $this->getResult();

		$continuationManager = new ApiContinuationManager( $this, [], [] );
		$this->setContinuationManager( $continuationManager );

		if ( $params['lang'] === null ) {
			$langCode = false;
		} elseif ( !$services->getLanguageNameUtils()->isValidCode( $params['lang'] ) ) {
			$this->dieWithError( [ 'apierror-invalidlang', 'lang' ] );
		} else {
			$langCode = $params['lang'];
		}

		$pageSet = $this->getPageSet();
		$pageSet->execute();
		$titles = $pageSet->getGoodPages();
		$missingTitles = $pageSet->getMissingPages();

		$includeMissingTitles = $this->getParameter( 'doNotIgnoreMissingTitles' ) ?:
			$this->getParameter( 'includeMissingTitles' );

		if ( !$titles && ( !$includeMissingTitles || !$missingTitles ) ) {
			$result->addValue( null, 'pages', (object)[] );
			$this->setContinuationManager();
			$continuationManager->setContinuationIntoResult( $this->getResult() );
			return;
		}

		$resp = [];

		if ( $includeMissingTitles ) {
			foreach ( $missingTitles as $missingTitleId => $missingTitle ) {
				$resp[ $missingTitleId ] = [ 'title' => $missingTitle, 'missing' => true ];
			}

			foreach ( $titles as $titleId => $title ) {
				$resp[ $titleId ] = [ 'title' => $title, 'notemplatedata' => true ];
			}
		}

		if ( $titles ) {
			$db = $this->getDB();
			$res = $db->newSelectQueryBuilder()
				->from( 'page_props' )
				->fields( [ 'pp_page', 'pp_value' ] )
				->where( [
					'pp_page' => array_keys( $titles ),
					'pp_propname' => 'templatedata'
				] )
				->orderBy( 'pp_page' )
				->caller( __METHOD__ )
				->fetchResultSet();

			foreach ( $res as $row ) {
				$rawData = $row->pp_value;
				$tdb = TemplateDataBlob::newFromDatabase( $db, $rawData );
				$status = $tdb->getStatus();

				if ( !$status->isOK() ) {
					$this->dieWithError(
						[ 'apierror-templatedata-corrupt', intval( $row->pp_page ), $status->getMessage() ]
					);
				}

				if ( $langCode !== false ) {
					$data = $tdb->getDataInLanguage( $langCode );
				} else {
					$data = $tdb->getData();
				}

				// HACK: don't let ApiResult's formatversion=1 compatibility layer mangle our booleans
				// to empty strings / absent properties
				foreach ( $data->params as $param ) {
					$param->{ApiResult::META_BC_BOOLS} = [ 'required', 'suggested', 'deprecated' ];
				}

				$data->params->{ApiResult::META_TYPE} = 'kvp';
				$data->params->{ApiResult::META_KVP_KEY_NAME} = 'key';
				$data->params->{ApiResult::META_INDEXED_TAG_NAME} = 'param';
				if ( isset( $data->paramOrder ) ) {
					ApiResult::setIndexedTagName( $data->paramOrder, 'p' );
				}

				if ( $includeMissingTitles ) {
					unset( $resp[$row->pp_page]['notemplatedata'] );
				} else {
					$resp[ $row->pp_page ] = [ 'title' => $titles[ $row->pp_page ] ];
				}
				$resp[$row->pp_page] += (array)$data;
			}
		}

		$wikiPageFactory = $services->getWikiPageFactory();

		// Now go through all the titles again, and attempt to extract parameter names from the
		// wikitext for templates with no templatedata.
		if ( $includeMissingTitles ) {
			foreach ( $resp as $pageId => $pageInfo ) {
				if ( !isset( $pageInfo['notemplatedata'] ) ) {
					// Ignore pages that already have templatedata or that don't exist.
					continue;
				}

				$content = $wikiPageFactory->newFromTitle( $pageInfo['title'] )->getContent();
				$text = $content instanceof TextContent
					? $content->getText()
					: $content->getTextForSearchIndex();
				$resp[$pageId]['params'] = $this->getRawParams( $text );
			}
		}

		// TODO tracking will only be implemented temporarily to answer questions on
		// template usage for the Technical Wishes topic area see T258917
		if ( ExtensionRegistry::getInstance()->isLoaded( 'EventLogging' ) ) {
			foreach ( $resp as $pageInfo ) {
				EventLogging::submit(
					'eventlogging_TemplateDataApi',
					[
						'$schema' => '/analytics/legacy/templatedataapi/1.0.0',
						'event' => [
							'template_name' => $wikiPageFactory->newFromTitle( $pageInfo['title'] )
								->getTitle()->getDBkey(),
							'has_template_data' => !isset( $pageInfo['notemplatedata'] ),
						],
					]
				);
			}
		}

		$pageSet->populateGeneratorData( $resp );
		ApiResult::setArrayType( $resp, 'kvp', 'id' );
		ApiResult::setIndexedTagName( $resp, 'page' );

		// Set top level element
		$result->addValue( null, 'pages', (object)$resp );

		$values = $pageSet->getNormalizedTitlesAsResult();
		if ( $values ) {
			$result->addValue( null, 'normalized', $values );
		}
		$redirects = $pageSet->getRedirectTitlesAsResult();
		if ( $redirects ) {
			$result->addValue( null, 'redirects', $redirects );
		}

		$this->setContinuationManager();
		$continuationManager->setContinuationIntoResult( $this->getResult() );
	}

	/**
	 * Get parameter descriptions from raw wikitext (used for templates that have no templatedata).
	 * @param string $wikitext The text to extract parameters from.
	 * @return array[] Parameter info in the same format as the templatedata 'params' key.
	 */
	private function getRawParams( string $wikitext ): array {
		// Ignore non-wikitext content in comments and wikitext-escaping tags
		$wikitext = preg_replace( '/<!--.*?-->/s', '', $wikitext );
		$wikitext = preg_replace( '/<nowiki\s*>.*?<\/nowiki\s*>/s', '', $wikitext );
		$wikitext = preg_replace( '/<pre\s*>.*?<\/pre\s*>/s', '', $wikitext );

		// This regex matches the one in ext.TemplateDataGenerator.sourceHandler.js
		if ( !preg_match_all( '/{{{+([^\n#={|}]*?)([<|]|}}})/m', $wikitext, $rawParams ) ) {
			return [];
		}

		$params = [];
		$normalizedParams = [];
		foreach ( $rawParams[1] as $rawParam ) {
			// This normalization process is repeated in JS in ext.TemplateDataGenerator.sourceHandler.js
			$normalizedParam = strtolower( trim( preg_replace( '/[-_ ]+/', ' ', $rawParam ) ) );
			if ( !$normalizedParam || in_array( $normalizedParam, $normalizedParams ) ) {
				// This or a similarly-named parameter has already been found.
				continue;
			}
			$normalizedParams[] = $normalizedParam;
			$params[ trim( $rawParam ) ] = [];
		}
		return $params;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams( $flags = 0 ) {
		$result = [
			'includeMissingTitles' => [
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'doNotIgnoreMissingTitles' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEPRECATED => true,
			],
			'lang' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
		];
		if ( $flags ) {
			$result += $this->getPageSet()->getFinalParams( $flags );
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=templatedata&titles=Template:Foobar&includeMissingTitles=1'
				=> 'apihelp-templatedata-example-1',
			'action=templatedata&titles=Template:Phabricator'
				=> 'apihelp-templatedata-example-2',
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:TemplateData';
	}

}
