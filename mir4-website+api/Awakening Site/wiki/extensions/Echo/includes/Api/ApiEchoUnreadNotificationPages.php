<?php

namespace MediaWiki\Extension\Notifications\Api;

use ApiQuery;
use ApiQueryBase;
use ApiUsageException;
use MediaWiki\Extension\Notifications\DbFactory;
use MediaWiki\Extension\Notifications\NotifUser;
use MediaWiki\Extension\Notifications\Services;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Page\PageRecord;
use MediaWiki\Page\PageStore;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\WikiMap\WikiMap;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

class ApiEchoUnreadNotificationPages extends ApiQueryBase {
	use ApiCrossWiki;

	/**
	 * @var bool
	 */
	protected $crossWikiSummary = false;

	/**
	 * @var PageStore
	 */
	private $pageStore;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param PageStore $pageStore
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( $query, $moduleName, PageStore $pageStore, TitleFactory $titleFactory ) {
		parent::__construct( $query, $moduleName, 'unp' );
		$this->pageStore = $pageStore;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @throws ApiUsageException
	 */
	public function execute() {
		// To avoid API warning, register the parameter used to bust browser cache
		$this->getMain()->getVal( '_' );

		if ( !$this->getUser()->isRegistered() ) {
			$this->dieWithError( 'apierror-mustbeloggedin-generic', 'login-required' );
		}

		$params = $this->extractRequestParams();

		$result = [];
		if ( in_array( WikiMap::getCurrentWikiId(), $this->getRequestedWikis() ) ) {
			$result[WikiMap::getCurrentWikiId()] = $this->getFromLocal( $params['limit'], $params['grouppages'] );
		}

		if ( $this->getRequestedForeignWikis() ) {
			$result += $this->getUnreadNotificationPagesFromForeign();
		}

		$apis = $this->getForeignNotifications()->getApiEndpoints( $this->getRequestedWikis() );
		foreach ( $result as $wiki => $data ) {
			$result[$wiki]['source'] = $apis[$wiki];
			$result[$wiki]['pages'] = $data['pages'] ?: [];
		}

		$this->getResult()->addValue( 'query', $this->getModuleName(), $result );
	}

	/**
	 * @param int $limit
	 * @param bool $groupPages
	 * @return array
	 * @phan-return array{pages:array[],totalCount:int}
	 */
	protected function getFromLocal( $limit, $groupPages ) {
		$attributeManager = Services::getInstance()->getAttributeManager();
		$enabledTypes = $attributeManager->getUserEnabledEvents( $this->getUser(), 'web' );

		$dbr = DbFactory::newFromDefault()->getEchoDb( DB_REPLICA );
		// If $groupPages is true, we need to fetch all pages and apply the ORDER BY and LIMIT ourselves
		// after grouping.
		$extraOptions = $groupPages ? [] : [ 'ORDER BY' => 'count DESC', 'LIMIT' => $limit ];
		$rows = $dbr->select(
			[ 'echo_event', 'echo_notification' ],
			[ 'event_page_id', 'count' => 'COUNT(*)' ],
			[
				'notification_user' => $this->getUser()->getId(),
				'notification_read_timestamp' => null,
				'event_deleted' => 0,
				'event_type' => $enabledTypes,
			],
			__METHOD__,
			[
				'GROUP BY' => 'event_page_id',
			] + $extraOptions,
			[ 'echo_notification' => [ 'INNER JOIN', 'notification_event = event_id' ] ]
		);

		if ( $rows === false ) {
			return [
				'pages' => [],
				'totalCount' => 0,
			];
		}

		$nullCount = 0;
		$pageCounts = [];
		foreach ( $rows as $row ) {
			if ( $row->event_page_id !== null ) {
				$pageCounts[(int)$row->event_page_id] = intval( $row->count );
			} else {
				$nullCount = intval( $row->count );
			}
		}

		$titles = $this->pageStore
			->newSelectQueryBuilder()
			->wherePageIds( array_keys( $pageCounts ) )
			->caller( __METHOD__ )
			->fetchPageRecords();

		$groupCounts = [];
		/** @var PageRecord $title */
		foreach ( $titles as $title ) {
			$title = $this->titleFactory->castFromPageIdentity( $title );
			if ( $groupPages ) {
				// If $title is a talk page, add its count to its subject page's count
				$pageName = $title->getSubjectPage()->getPrefixedText();
			} else {
				$pageName = $title->getPrefixedText();
			}

			$count = $pageCounts[$title->getArticleID()] ?? 0;
			if ( isset( $groupCounts[$pageName] ) ) {
				$groupCounts[$pageName] += $count;
			} else {
				$groupCounts[$pageName] = $count;
			}
		}

		$userPageName = $this->getUser()->getUserPage()->getPrefixedText();
		if ( $nullCount > 0 && $groupPages ) {
			// Add the count for NULL (not associated with any page) to the count for the user page
			if ( isset( $groupCounts[$userPageName] ) ) {
				$groupCounts[$userPageName] += $nullCount;
			} else {
				$groupCounts[$userPageName] = $nullCount;
			}
		}

		arsort( $groupCounts );
		if ( $groupPages ) {
			$groupCounts = array_slice( $groupCounts, 0, $limit );
		}

		$result = [];
		foreach ( $groupCounts as $pageName => $count ) {
			if ( $groupPages ) {
				$title = Title::newFromText( $pageName );
				$pages = [ $title->getSubjectPage()->getPrefixedText() ];
				if ( $title->canHaveTalkPage() ) {
					$pages[] = $title->getTalkPage()->getPrefixedText();
				}
				if ( $pageName === $userPageName ) {
					$pages[] = null;
				}
				$pageDescription = [
					'ns' => $title->getNamespace(),
					'title' => $title->getPrefixedText(),
					'unprefixed' => $title->getText(),
					'pages' => $pages,
				];
			} else {
				$pageDescription = [ 'title' => $pageName ];
			}
			$result[] = $pageDescription + [
				'count' => $count,
			];
		}
		if ( !$groupPages && $nullCount > 0 ) {
			$result[] = [
				'title' => null,
				'count' => $nullCount,
			];
		}

		return [
			'pages' => $result,
			'totalCount' => NotifUser::newFromUser( $this->getUser() )->getLocalNotificationCount(),
		];
	}

	/**
	 * @return array[]
	 */
	protected function getUnreadNotificationPagesFromForeign() {
		$result = [];
		foreach ( $this->getFromForeign() as $wiki => $data ) {
			if ( isset( $data['query'][$this->getModuleName()][$wiki] ) ) {
				$result[$wiki] = $data['query'][$this->getModuleName()][$wiki];
			} else {
				# Usually an error or it is some malformed response
				# T273479
				LoggerFactory::getInstance( 'Echo' )->warning(
					__METHOD__ . ': Unexpected API response from {wiki}',
					[
						'wiki' => $wiki,
						'data' => $data,
					]
				);
			}
		}

		return $result;
	}

	/**
	 * @return array[]
	 */
	public function getAllowedParams() {
		$maxUpdateCount = $this->getConfig()->get( 'EchoMaxUpdateCount' );

		return $this->getCrossWikiParams() + [
			'grouppages' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false,
			],
			'limit' => [
				ParamValidator::PARAM_TYPE => 'limit',
				ParamValidator::PARAM_DEFAULT => 10,
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => $maxUpdateCount,
				IntegerDef::PARAM_MAX2 => $maxUpdateCount,
			],
			// there is no `offset` or `continue` value: the set of possible
			// notifications is small enough to allow fetching all of them at
			// once, and any sort of fetching would be unreliable because
			// they're sorted based on count of notifications, which could
			// change in between requests
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return string[]
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&meta=unreadnotificationpages' => 'apihelp-query+unreadnotificationpages-example-1',
		];
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Echo_(Notifications)/API';
	}
}
