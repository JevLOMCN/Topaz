<?php

namespace MediaWiki\Extension\AbuseFilter\Pager;

use LogicException;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\SpecsFormatter;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewList;
use MediaWiki\Linker\Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\TablePager;
use MediaWiki\SpecialPage\SpecialPage;
use stdClass;
use UnexpectedValueException;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Class to build paginated filter list
 */
class AbuseFilterPager extends TablePager {

	/**
	 * The unique sort fields for the sort options for unique paginate
	 */
	private const INDEX_FIELDS = [
		'af_id' => [ 'af_id' ],
		'af_enabled' => [ 'af_enabled', 'af_deleted', 'af_id' ],
		'af_timestamp' => [ 'af_timestamp', 'af_id' ],
		'af_hidden' => [ 'af_hidden', 'af_id' ],
		'af_group' => [ 'af_group', 'af_id' ],
		'af_hit_count' => [ 'af_hit_count', 'af_id' ],
		'af_public_comments' => [ 'af_public_comments', 'af_id' ],
	];

	/** @var ?LinkBatchFactory */
	private $linkBatchFactory;

	/** @var AbuseFilterPermissionManager */
	private $afPermManager;

	/** @var SpecsFormatter */
	protected $specsFormatter;

	/**
	 * @var AbuseFilterViewList The associated page
	 */
	private $mPage;
	/**
	 * @var array Query WHERE conditions
	 */
	private $conds;
	/**
	 * @var string|null The pattern being searched
	 */
	private $searchPattern;
	/**
	 * @var string|null The pattern search mode (LIKE, RLIKE or IRLIKE)
	 */
	private $searchMode;

	/**
	 * @param AbuseFilterViewList $page
	 * @param LinkRenderer $linkRenderer
	 * @param ?LinkBatchFactory $linkBatchFactory
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param SpecsFormatter $specsFormatter
	 * @param array $conds
	 * @param ?string $searchPattern Null if no pattern was specified
	 * @param ?string $searchMode
	 */
	public function __construct(
		AbuseFilterViewList $page,
		LinkRenderer $linkRenderer,
		?LinkBatchFactory $linkBatchFactory,
		AbuseFilterPermissionManager $afPermManager,
		SpecsFormatter $specsFormatter,
		array $conds,
		?string $searchPattern,
		?string $searchMode
	) {
		// needed by parent's constructor call
		$this->afPermManager = $afPermManager;
		$this->specsFormatter = $specsFormatter;
		parent::__construct( $page->getContext(), $linkRenderer );
		$this->mPage = $page;
		$this->linkBatchFactory = $linkBatchFactory;
		$this->conds = $conds;
		$this->searchPattern = $searchPattern;
		$this->searchMode = $searchMode;
	}

	/**
	 * @return array
	 */
	public function getQueryInfo() {
		$actorQuery = AbuseFilterServices::getActorMigration()->getJoin( 'af_user' );
		return [
			'tables' => [ 'abuse_filter' ] + $actorQuery['tables'],
			'fields' => [
				// All columns but af_comments
				'af_id',
				'af_enabled',
				'af_deleted',
				'af_pattern',
				'af_global',
				'af_public_comments',
				'af_hidden',
				'af_hit_count',
				'af_timestamp',
				'af_actions',
				'af_group',
				'af_throttled'
			] + $actorQuery['fields'],
			'conds' => $this->conds,
			'join_conds' => $actorQuery['joins'],
		];
	}

	/**
	 * @param IResultWrapper $result
	 */
	protected function preprocessResults( $result ) {
		// LinkBatchFactory only provided and needed for local wiki results
		if ( $this->linkBatchFactory === null || $this->getNumRows() === 0 ) {
			return;
		}

		$lb = $this->linkBatchFactory->newLinkBatch();
		$lb->setCaller( __METHOD__ );
		foreach ( $result as $row ) {
			$lb->add( NS_USER, $row->af_user_text );
			$lb->add( NS_USER_TALK, $row->af_user_text );
		}
		$lb->execute();
		$result->seek( 0 );
	}

	/**
	 * @inheritDoc
	 * This is the same as the parent implementation if no search pattern was specified.
	 * Otherwise, it does a query with no limit and then slices the results à la ContribsPager.
	 */
	public function reallyDoQuery( $offset, $limit, $order ) {
		if ( $this->searchMode === null ) {
			return parent::reallyDoQuery( $offset, $limit, $order );
		}

		[ $tables, $fields, $conds, $fname, $options, $join_conds ] =
			$this->buildQueryInfo( $offset, $limit, $order );

		unset( $options['LIMIT'] );
		$res = $this->mDb->select( $tables, $fields, $conds, $fname, $options, $join_conds );

		$filtered = [];
		foreach ( $res as $row ) {
			if ( $this->matchesPattern( $row->af_pattern ) ) {
				$filtered[$row->af_id] = $row;
			}
		}

		// sort results and enforce limit like ContribsPager
		if ( $order === self::QUERY_ASCENDING ) {
			ksort( $filtered );
		} else {
			krsort( $filtered );
		}
		$filtered = array_slice( $filtered, 0, $limit );
		$filtered = array_values( $filtered );
		return new FakeResultWrapper( $filtered );
	}

	/**
	 * Check whether $subject matches the given $pattern.
	 *
	 * @param string $subject
	 * @return bool
	 * @throws LogicException
	 */
	private function matchesPattern( $subject ) {
		$pattern = $this->searchPattern;
		switch ( $this->searchMode ) {
			case 'RLIKE':
				return (bool)preg_match( "/$pattern/u", $subject );
			case 'IRLIKE':
				return (bool)preg_match( "/$pattern/ui", $subject );
			case 'LIKE':
				return mb_stripos( $subject, $pattern ) !== false;
			default:
				throw new LogicException( "Unknown search type {$this->searchMode}" );
		}
	}

	/**
	 * Note: this method is called by parent::__construct
	 * @return array
	 * @see MediaWiki\Pager\Pager::getFieldNames()
	 */
	public function getFieldNames() {
		$headers = [
			'af_id' => 'abusefilter-list-id',
			'af_public_comments' => 'abusefilter-list-public',
			'af_actions' => 'abusefilter-list-consequences',
			'af_enabled' => 'abusefilter-list-status',
			'af_timestamp' => 'abusefilter-list-lastmodified',
			'af_hidden' => 'abusefilter-list-visibility',
		];

		$performer = $this->getAuthority();
		if ( $this->afPermManager->canSeeLogDetails( $performer ) ) {
			$headers['af_hit_count'] = 'abusefilter-list-hitcount';
		}

		if ( $this->afPermManager->canViewPrivateFilters( $performer ) && $this->searchMode !== null ) {
			// This is also excluded in the default view
			$headers['af_pattern'] = 'abusefilter-list-pattern';
		}

		if ( count( $this->getConfig()->get( 'AbuseFilterValidGroups' ) ) > 1 ) {
			$headers['af_group'] = 'abusefilter-list-group';
		}

		foreach ( $headers as &$msg ) {
			$msg = $this->msg( $msg )->text();
		}

		return $headers;
	}

	/**
	 * @param string $name
	 * @param string|null $value
	 * @return string
	 */
	public function formatValue( $name, $value ) {
		$lang = $this->getLanguage();
		$user = $this->getUser();
		$linkRenderer = $this->getLinkRenderer();
		$row = $this->mCurrentRow;

		switch ( $name ) {
			case 'af_id':
				return $linkRenderer->makeLink(
					SpecialPage::getTitleFor( 'AbuseFilter', $value ),
					$lang->formatNum( intval( $value ) )
				);
			case 'af_pattern':
				return $this->getHighlightedPattern( $row );
			case 'af_public_comments':
				return $linkRenderer->makeLink(
					SpecialPage::getTitleFor( 'AbuseFilter', $row->af_id ),
					$value
				);
			case 'af_actions':
				$actions = explode( ',', $value );
				$displayActions = [];
				foreach ( $actions as $action ) {
					$displayActions[] = $this->specsFormatter->getActionDisplay( $action );
				}
				return $lang->commaList( $displayActions );
			case 'af_enabled':
				$statuses = [];
				if ( $row->af_deleted ) {
					$statuses[] = $this->msg( 'abusefilter-deleted' )->parse();
				} elseif ( $row->af_enabled ) {
					$statuses[] = $this->msg( 'abusefilter-enabled' )->parse();
					if ( $row->af_throttled ) {
						$statuses[] = $this->msg( 'abusefilter-throttled' )->parse();
					}
				} else {
					$statuses[] = $this->msg( 'abusefilter-disabled' )->parse();
				}

				if ( $row->af_global && $this->getConfig()->get( 'AbuseFilterIsCentral' ) ) {
					$statuses[] = $this->msg( 'abusefilter-status-global' )->parse();
				}

				return $lang->commaList( $statuses );
			case 'af_hidden':
				$msg = $value ? 'abusefilter-hidden' : 'abusefilter-unhidden';
				return $this->msg( $msg )->parse();
			case 'af_hit_count':
				if ( $this->afPermManager->canSeeLogDetailsForFilter( $user, $row->af_hidden ) ) {
					$count_display = $this->msg( 'abusefilter-hitcount' )
						->numParams( $value )->text();
					$link = $linkRenderer->makeKnownLink(
						SpecialPage::getTitleFor( 'AbuseLog' ),
						$count_display,
						[],
						[ 'wpSearchFilter' => $row->af_id ]
					);
				} else {
					$link = "";
				}
				return $link;
			case 'af_timestamp':
				$userLink =
					Linker::userLink(
						$row->af_user,
						$row->af_user_text
					) .
					Linker::userToolLinks(
						$row->af_user,
						$row->af_user_text
					);

				return $this->msg( 'abusefilter-edit-lastmod-text' )
					->rawParams(
						$this->mPage->getLinkToLatestDiff(
							$row->af_id,
							$lang->userTimeAndDate( $value, $user )
						),
						$userLink,
						$this->mPage->getLinkToLatestDiff(
							$row->af_id,
							$lang->userDate( $value, $user )
						),
						$this->mPage->getLinkToLatestDiff(
							$row->af_id,
							$lang->userTime( $value, $user )
						)
					)->params(
						wfEscapeWikiText( $row->af_user_text )
					)->parse();
			case 'af_group':
				return $this->specsFormatter->nameGroup( $value );
			default:
				throw new UnexpectedValueException( "Unknown row type $name!" );
		}
	}

	/**
	 * Get the filter pattern with <b> elements surrounding the searched pattern
	 *
	 * @param stdClass $row
	 * @return string
	 */
	private function getHighlightedPattern( stdClass $row ) {
		if ( $this->searchMode === null ) {
			throw new LogicException( 'Cannot search without a mode.' );
		}
		$maxLen = 50;
		if ( $this->searchMode === 'LIKE' ) {
			$position = mb_stripos( $row->af_pattern, $this->searchPattern );
			$length = mb_strlen( $this->searchPattern );
		} else {
			$regex = '/' . $this->searchPattern . '/u';
			if ( $this->searchMode === 'IRLIKE' ) {
				$regex .= 'i';
			}

			$matches = [];
			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			$check = @preg_match(
				$regex,
				$row->af_pattern,
				$matches
			);
			// This may happen in case of catastrophic backtracking, or regexps matching
			// the empty string.
			if ( $check === false || strlen( $matches[0] ) === 0 ) {
				return htmlspecialchars( mb_substr( $row->af_pattern, 0, 50 ) );
			}

			$length = mb_strlen( $matches[0] );
			$position = mb_strpos( $row->af_pattern, $matches[0] );
		}

		$remaining = $maxLen - $length;
		if ( $remaining <= 0 ) {
			$pattern = '<b>' .
				htmlspecialchars( mb_substr( $row->af_pattern, $position, $maxLen ) ) .
				'</b>';
		} else {
			// Center the snippet on the matched string
			$minoffset = max( $position - round( $remaining / 2 ), 0 );
			$pattern = mb_substr( $row->af_pattern, $minoffset, $maxLen );
			$pattern =
				htmlspecialchars( mb_substr( $pattern, 0, $position - $minoffset ) ) .
				'<b>' .
				htmlspecialchars( mb_substr( $pattern, $position - $minoffset, $length ) ) .
				'</b>' .
				htmlspecialchars( mb_substr(
						$pattern,
						$position - $minoffset + $length,
						$remaining - ( $position - $minoffset + $length )
					)
				);
		}
		return $pattern;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function getDefaultSort() {
		return 'af_id';
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function getTableClass() {
		return parent::getTableClass() . ' mw-abusefilter-list-scrollable';
	}

	/**
	 * @param stdClass $row
	 * @return string
	 * @see TablePager::getRowClass()
	 */
	public function getRowClass( $row ) {
		if ( $row->af_enabled ) {
			return $row->af_throttled ? 'mw-abusefilter-list-throttled' : 'mw-abusefilter-list-enabled';
		} elseif ( $row->af_deleted ) {
			return 'mw-abusefilter-list-deleted';
		} else {
			return 'mw-abusefilter-list-disabled';
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getIndexField() {
		return [ self::INDEX_FIELDS[$this->mSort] ];
	}

	/**
	 * @param string $field
	 *
	 * @return bool
	 */
	public function isFieldSortable( $field ) {
		if ( ( $field === 'af_hit_count' || $field === 'af_public_comments' )
			&& !$this->afPermManager->canSeeLogDetails( $this->getAuthority() )
		) {
			return false;
		}
		return isset( self::INDEX_FIELDS[$field] );
	}
}
