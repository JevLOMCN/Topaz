<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\Extension\AbuseFilter\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use InvalidArgumentException;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\CentralDBNotAvailableException;
use MediaWiki\Extension\AbuseFilter\Filter\FilterNotFoundException;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\GlobalNameUtils;
use MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseLog;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\Utils\MWTimestamp;
use Wikimedia\IPUtils;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

/**
 * Query module to list abuse log entries.
 *
 * @copyright 2009 Alex Z. <mrzmanwiki AT gmail DOT com>
 * Based mostly on code by Bryan Tong Minh and Roan Kattouw
 *
 * @ingroup API
 * @ingroup Extensions
 */
class QueryAbuseLog extends ApiQueryBase {

	/** @var FilterLookup */
	private $afFilterLookup;

	/** @var AbuseFilterPermissionManager */
	private $afPermManager;

	/** @var VariablesBlobStore */
	private $afVariablesBlobStore;

	/** @var VariablesManager */
	private $afVariablesManager;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param FilterLookup $afFilterLookup
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param VariablesBlobStore $afVariablesBlobStore
	 * @param VariablesManager $afVariablesManager
	 */
	public function __construct(
		ApiQuery $query,
		$moduleName,
		FilterLookup $afFilterLookup,
		AbuseFilterPermissionManager $afPermManager,
		VariablesBlobStore $afVariablesBlobStore,
		VariablesManager $afVariablesManager
	) {
		parent::__construct( $query, $moduleName, 'afl' );
		$this->afFilterLookup = $afFilterLookup;
		$this->afPermManager = $afPermManager;
		$this->afVariablesBlobStore = $afVariablesBlobStore;
		$this->afVariablesManager = $afVariablesManager;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$lookup = $this->afFilterLookup;

		// Same check as in SpecialAbuseLog
		$this->checkUserRightsAny( 'abusefilter-log' );

		$performer = $this->getAuthority();
		$params = $this->extractRequestParams();

		$prop = array_fill_keys( $params['prop'], true );
		$fld_ids = isset( $prop['ids'] );
		$fld_filter = isset( $prop['filter'] );
		$fld_user = isset( $prop['user'] );
		$fld_title = isset( $prop['title'] );
		$fld_action = isset( $prop['action'] );
		$fld_details = isset( $prop['details'] );
		$fld_result = isset( $prop['result'] );
		$fld_timestamp = isset( $prop['timestamp'] );
		$fld_hidden = isset( $prop['hidden'] );
		$fld_revid = isset( $prop['revid'] );
		$isCentral = $this->getConfig()->get( 'AbuseFilterIsCentral' );
		$fld_wiki = $isCentral && isset( $prop['wiki'] );

		if ( $fld_details ) {
			$this->checkUserRightsAny( 'abusefilter-log-detail' );
		}

		// Map of [ [ id, global ], ... ]
		$searchFilters = [];
		// Match permissions for viewing events on private filters to SpecialAbuseLog (bug 42814)
		// @todo Avoid code duplication with SpecialAbuseLog::showList, make it so that, if hidden
		// filters are specified, we only filter them out instead of failing.
		if ( $params['filter'] ) {
			if ( !is_array( $params['filter'] ) ) {
				$params['filter'] = [ $params['filter'] ];
			}

			$foundInvalid = false;
			foreach ( $params['filter'] as $filter ) {
				try {
					$searchFilters[] = GlobalNameUtils::splitGlobalName( $filter );
				} catch ( InvalidArgumentException $e ) {
					$foundInvalid = true;
					continue;
				}
			}
			if ( !$this->afPermManager->canViewPrivateFiltersLogs( $performer ) ) {
				foreach ( $searchFilters as [ $filterID, $global ] ) {
					try {
						$isHidden = $lookup->getFilter( $filterID, $global )->isHidden();
					} catch ( CentralDBNotAvailableException $_ ) {
						// Conservatively assume it's hidden, like in SpecialAbuseLog
						$isHidden = true;
					} catch ( FilterNotFoundException $_ ) {
						$isHidden = false;
						$foundInvalid = true;
					}
					if ( $isHidden ) {
						$this->dieWithError(
							[ 'apierror-permissiondenied', $this->msg( 'action-abusefilter-log-private' ) ]
						);
					}
				}
			}

			if ( $foundInvalid ) {
				// @todo Tell what the invalid IDs are
				$this->addWarning( 'abusefilter-log-invalid-filter' );
			}
		}

		$result = $this->getResult();

		$this->addTables( 'abuse_filter_log' );
		$this->addFields( 'afl_timestamp' );
		$this->addFields( 'afl_rev_id' );
		$this->addFields( 'afl_deleted' );
		$this->addFields( 'afl_filter_id' );
		$this->addFields( 'afl_global' );
		$this->addFieldsIf( 'afl_id', $fld_ids );
		$this->addFieldsIf( 'afl_user_text', $fld_user );
		$this->addFieldsIf( [ 'afl_namespace', 'afl_title' ], $fld_title );
		$this->addFieldsIf( 'afl_action', $fld_action );
		$this->addFieldsIf( 'afl_var_dump', $fld_details );
		$this->addFieldsIf( 'afl_actions', $fld_result );
		$this->addFieldsIf( 'afl_wiki', $fld_wiki );

		if ( $fld_filter ) {
			$this->addTables( 'abuse_filter' );
			$this->addFields( 'af_public_comments' );

			$this->addJoinConds( [
				'abuse_filter' => [
					'LEFT JOIN',
					[
						'af_id=afl_filter_id',
						'afl_global' => 0
					]
				]
			] );
		}

		$this->addOption( 'LIMIT', $params['limit'] + 1 );

		$this->addWhereIf( [ 'afl_id' => $params['logid'] ], isset( $params['logid'] ) );

		$this->addWhereRange( 'afl_timestamp', $params['dir'], $params['start'], $params['end'] );

		if ( isset( $params['user'] ) ) {
			$u = User::newFromName( $params['user'] );
			if ( $u ) {
				// Username normalisation
				$params['user'] = $u->getName();
				$userId = $u->getId();
			} elseif ( IPUtils::isIPAddress( $params['user'] ) ) {
				// It's an IP, sanitize it
				$params['user'] = IPUtils::sanitizeIP( $params['user'] );
				$userId = 0;
			}

			if ( isset( $userId ) ) {
				// Only add the WHERE for user in case it's either a valid user
				// (but not necessary an existing one) or an IP.
				$this->addWhere(
					[
						'afl_user' => $userId,
						'afl_user_text' => $params['user']
					]
				);
			}
		}

		$this->addWhereIf( [ 'afl_deleted' => 0 ], !$this->afPermManager->canSeeHiddenLogEntries( $performer ) );

		if ( $searchFilters ) {
			// @todo Avoid code duplication with SpecialAbuseLog::showList
			$filterConds = [ 'local' => [], 'global' => [] ];
			foreach ( $searchFilters as $filter ) {
				$isGlobal = $filter[1];
				$key = $isGlobal ? 'global' : 'local';
				$filterConds[$key][] = $filter[0];
			}
			$conds = [];
			if ( $filterConds['local'] ) {
				$conds[] = $this->getDB()->makeList(
					[ 'afl_global' => 0, 'afl_filter_id' => $filterConds['local'] ],
					LIST_AND
				);
			}
			if ( $filterConds['global'] ) {
				$conds[] = $this->getDB()->makeList(
					[ 'afl_global' => 1, 'afl_filter_id' => $filterConds['global'] ],
					LIST_AND
				);
			}
			$conds = $this->getDB()->makeList( $conds, LIST_OR );

			$this->addWhere( $conds );
		}

		if ( isset( $params['wiki'] ) ) {
			// 'wiki' won't be set if $wgAbuseFilterIsCentral = false
			$this->addWhereIf( [ 'afl_wiki' => $params['wiki'] ], $isCentral );
		}

		$title = $params['title'];
		if ( $title !== null ) {
			$titleObj = Title::newFromText( $title );
			if ( $titleObj === null ) {
				$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $title ) ] );
			}
			$this->addWhereFld( 'afl_namespace', $titleObj->getNamespace() );
			$this->addWhereFld( 'afl_title', $titleObj->getDBkey() );
		}
		$res = $this->select( __METHOD__ );

		$count = 0;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've had enough
				$ts = new MWTimestamp( $row->afl_timestamp );
				$this->setContinueEnumParameter( 'start', $ts->getTimestamp( TS_ISO_8601 ) );
				break;
			}
			$visibility = SpecialAbuseLog::getEntryVisibilityForUser( $row, $performer, $this->afPermManager );
			if ( $visibility !== SpecialAbuseLog::VISIBILITY_VISIBLE ) {
				continue;
			}

			$filterID = $row->afl_filter_id;
			$global = $row->afl_global;
			$fullName = GlobalNameUtils::buildGlobalName( $filterID, $global );
			$isHidden = $lookup->getFilter( $filterID, $global )->isHidden();
			$canSeeDetails = $this->afPermManager->canSeeLogDetailsForFilter( $performer, $isHidden );

			$entry = [];
			if ( $fld_ids ) {
				$entry['id'] = intval( $row->afl_id );
				$entry['filter_id'] = $canSeeDetails ? $fullName : '';
			}
			if ( $fld_filter ) {
				if ( $global ) {
					$entry['filter'] = $lookup->getFilter( $filterID, true )->getName();
				} else {
					$entry['filter'] = $row->af_public_comments;
				}
			}
			if ( $fld_user ) {
				$entry['user'] = $row->afl_user_text;
			}
			if ( $fld_wiki ) {
				$entry['wiki'] = $row->afl_wiki;
			}
			if ( $fld_title ) {
				$title = Title::makeTitle( $row->afl_namespace, $row->afl_title );
				ApiQueryBase::addTitleInfo( $entry, $title );
			}
			if ( $fld_action ) {
				$entry['action'] = $row->afl_action;
			}
			if ( $fld_result ) {
				$entry['result'] = $row->afl_actions;
			}
			if ( $fld_revid && $row->afl_rev_id !== null ) {
				$entry['revid'] = $canSeeDetails ? (int)$row->afl_rev_id : '';
			}
			if ( $fld_timestamp ) {
				$ts = new MWTimestamp( $row->afl_timestamp );
				$entry['timestamp'] = $ts->getTimestamp( TS_ISO_8601 );
			}
			if ( $fld_details ) {
				$entry['details'] = [];
				if ( $canSeeDetails ) {
					$vars = $this->afVariablesBlobStore->loadVarDump( $row->afl_var_dump );
					$varManager = $this->afVariablesManager;
					$entry['details'] = $varManager->exportAllVars( $vars );
				}
			}

			if ( $fld_hidden ) {
				$entry['hidden'] = (bool)$row->afl_deleted;
			}

			if ( $entry ) {
				$fit = $result->addValue( [ 'query', $this->getModuleName() ], null, $entry );
				if ( !$fit ) {
					$ts = new MWTimestamp( $row->afl_timestamp );
					$this->setContinueEnumParameter( 'start', $ts->getTimestamp( TS_ISO_8601 ) );
					break;
				}
			}
		}
		$result->addIndexedTagName( [ 'query', $this->getModuleName() ], 'item' );
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		$params = [
			'logid' => [
				ParamValidator::PARAM_TYPE => 'integer'
			],
			'start' => [
				ParamValidator::PARAM_TYPE => 'timestamp'
			],
			'end' => [
				ParamValidator::PARAM_TYPE => 'timestamp'
			],
			'dir' => [
				ParamValidator::PARAM_TYPE => [
					'newer',
					'older'
				],
				ParamValidator::PARAM_DEFAULT => 'older',
				ApiBase::PARAM_HELP_MSG => 'api-help-param-direction',
			],
			'user' => null,
			'title' => null,
			'filter' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => true,
				ApiBase::PARAM_HELP_MSG => [
					'apihelp-query+abuselog-param-filter',
					GlobalNameUtils::GLOBAL_FILTER_PREFIX
				]
			],
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'ids|user|title|action|result|timestamp|hidden|revid',
				ParamValidator::PARAM_TYPE => [
					'ids',
					'filter',
					'user',
					'title',
					'action',
					'details',
					'result',
					'timestamp',
					'hidden',
					'revid',
				],
				ParamValidator::PARAM_ISMULTI => true
			]
		];
		if ( $this->getConfig()->get( 'AbuseFilterIsCentral' ) ) {
			$params['wiki'] = [
				ParamValidator::PARAM_TYPE => 'string',
			];
			$params['prop'][ParamValidator::PARAM_DEFAULT] .= '|wiki';
			$params['prop'][ParamValidator::PARAM_TYPE][] = 'wiki';
			$params['filter'][ApiBase::PARAM_HELP_MSG] = 'apihelp-query+abuselog-param-filter-central';
		}
		return $params;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&list=abuselog'
				=> 'apihelp-query+abuselog-example-1',
			'action=query&list=abuselog&afltitle=API'
				=> 'apihelp-query+abuselog-example-2',
		];
	}
}
