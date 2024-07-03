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
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Utils\MWTimestamp;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

/**
 * Query module to list abuse filter details.
 *
 * @copyright 2009 Alex Z. <mrzmanwiki AT gmail DOT com>
 * Based mostly on code by Bryan Tong Minh and Roan Kattouw
 *
 * @ingroup API
 * @ingroup Extensions
 */
class QueryAbuseFilters extends ApiQueryBase {

	/** @var AbuseFilterPermissionManager */
	private $afPermManager;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param AbuseFilterPermissionManager $afPermManager
	 */
	public function __construct(
		ApiQuery $query,
		$moduleName,
		AbuseFilterPermissionManager $afPermManager
	) {
		parent::__construct( $query, $moduleName, 'abf' );
		$this->afPermManager = $afPermManager;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->checkUserRightsAny( 'abusefilter-view' );

		$params = $this->extractRequestParams();

		$prop = array_fill_keys( $params['prop'], true );
		$fld_id = isset( $prop['id'] );
		$fld_desc = isset( $prop['description'] );
		$fld_pattern = isset( $prop['pattern'] );
		$fld_actions = isset( $prop['actions'] );
		$fld_hits = isset( $prop['hits'] );
		$fld_comments = isset( $prop['comments'] );
		$fld_user = isset( $prop['lasteditor'] );
		$fld_time = isset( $prop['lastedittime'] );
		$fld_status = isset( $prop['status'] );
		$fld_private = isset( $prop['private'] );

		$result = $this->getResult();

		$this->addTables( 'abuse_filter' );

		$this->addFields( 'af_id' );
		$this->addFields( 'af_hidden' );
		$this->addFieldsIf( 'af_hit_count', $fld_hits );
		$this->addFieldsIf( 'af_enabled', $fld_status );
		$this->addFieldsIf( 'af_deleted', $fld_status );
		$this->addFieldsIf( 'af_public_comments', $fld_desc );
		$this->addFieldsIf( 'af_pattern', $fld_pattern );
		$this->addFieldsIf( 'af_actions', $fld_actions );
		$this->addFieldsIf( 'af_comments', $fld_comments );
		if ( $fld_user ) {
			$actorQuery = AbuseFilterServices::getActorMigration()->getJoin( 'af_user' );
			$this->addTables( $actorQuery['tables'] );
			$this->addFields( [ 'af_user_text' => $actorQuery['fields']['af_user_text'] ] );
			$this->addJoinConds( $actorQuery['joins'] );
		}
		$this->addFieldsIf( 'af_timestamp', $fld_time );

		$this->addOption( 'LIMIT', $params['limit'] + 1 );

		$this->addWhereRange( 'af_id', $params['dir'], $params['startid'], $params['endid'] );

		if ( $params['show'] !== null ) {
			$show = array_fill_keys( $params['show'], true );

			/* Check for conflicting parameters. */
			if ( ( isset( $show['enabled'] ) && isset( $show['!enabled'] ) )
				|| ( isset( $show['deleted'] ) && isset( $show['!deleted'] ) )
				|| ( isset( $show['private'] ) && isset( $show['!private'] ) )
			) {
				$this->dieWithError( 'apierror-show' );
			}

			$this->addWhereIf( 'af_enabled = 0', isset( $show['!enabled'] ) );
			$this->addWhereIf( 'af_enabled != 0', isset( $show['enabled'] ) );
			$this->addWhereIf( 'af_deleted = 0', isset( $show['!deleted'] ) );
			$this->addWhereIf( 'af_deleted != 0', isset( $show['deleted'] ) );
			$this->addWhereIf( 'af_hidden = 0', isset( $show['!private'] ) );
			$this->addWhereIf( 'af_hidden != 0', isset( $show['private'] ) );
		}

		$res = $this->select( __METHOD__ );

		$showhidden = $this->afPermManager->canViewPrivateFilters( $this->getAuthority() );

		$count = 0;
		foreach ( $res as $row ) {
			$filterId = intval( $row->af_id );
			if ( ++$count > $params['limit'] ) {
				// We've had enough
				$this->setContinueEnumParameter( 'startid', $filterId );
				break;
			}
			$entry = [];
			if ( $fld_id ) {
				$entry['id'] = $filterId;
			}
			if ( $fld_desc ) {
				$entry['description'] = $row->af_public_comments;
			}
			if ( $fld_pattern && ( !$row->af_hidden || $showhidden ) ) {
				$entry['pattern'] = $row->af_pattern;
			}
			if ( $fld_actions ) {
				$entry['actions'] = $row->af_actions;
			}
			if ( $fld_hits ) {
				$entry['hits'] = intval( $row->af_hit_count );
			}
			if ( $fld_comments && ( !$row->af_hidden || $showhidden ) ) {
				$entry['comments'] = $row->af_comments;
			}
			if ( $fld_user ) {
				$entry['lasteditor'] = $row->af_user_text;
			}
			if ( $fld_time ) {
				$ts = new MWTimestamp( $row->af_timestamp );
				$entry['lastedittime'] = $ts->getTimestamp( TS_ISO_8601 );
			}
			if ( $fld_private && $row->af_hidden ) {
				$entry['private'] = '';
			}
			if ( $fld_status ) {
				if ( $row->af_enabled ) {
					$entry['enabled'] = '';
				}
				if ( $row->af_deleted ) {
					$entry['deleted'] = '';
				}
			}
			if ( $entry ) {
				$fit = $result->addValue( [ 'query', $this->getModuleName() ], null, $entry );
				if ( !$fit ) {
					$this->setContinueEnumParameter( 'startid', $filterId );
					break;
				}
			}
		}
		$result->addIndexedTagName( [ 'query', $this->getModuleName() ], 'filter' );
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'startid' => [
				ParamValidator::PARAM_TYPE => 'integer'
			],
			'endid' => [
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'dir' => [
				ParamValidator::PARAM_TYPE => [
					'older',
					'newer'
				],
				ParamValidator::PARAM_DEFAULT => 'newer',
				ApiBase::PARAM_HELP_MSG => 'api-help-param-direction',
			],
			'show' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'enabled',
					'!enabled',
					'deleted',
					'!deleted',
					'private',
					'!private',
				],
			],
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'id|description|actions|status',
				ParamValidator::PARAM_TYPE => [
					'id',
					'description',
					'pattern',
					'actions',
					'hits',
					'comments',
					'lasteditor',
					'lastedittime',
					'status',
					'private',
				],
				ParamValidator::PARAM_ISMULTI => true
			]
		];
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&list=abusefilters&abfshow=enabled|!private'
				=> 'apihelp-query+abusefilters-example-1',
			'action=query&list=abusefilters&abfprop=id|description|pattern'
				=> 'apihelp-query+abusefilters-example-2',
		];
	}
}
