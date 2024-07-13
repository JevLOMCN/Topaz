<?php

namespace MediaWiki\Extension\AbuseFilter;

use FormatJson;
use LogicException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\Filter\Filter;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\LastEditInfo;
use MediaWiki\Extension\AbuseFilter\Filter\MutableFilter;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;

/**
 * This class allows encoding filters to (and decoding from) a string format that can be used
 * to export them to another wiki.
 *
 * @internal
 * @note Callers should NOT rely on the output format, as it may vary
 */
class FilterImporter {
	public const SERVICE_NAME = 'AbuseFilterFilterImporter';

	public const CONSTRUCTOR_OPTIONS = [
		'AbuseFilterValidGroups',
		'AbuseFilterIsCentral',
	];

	private const TEMPLATE_KEYS = [
		'rules',
		'name',
		'comments',
		'group',
		'actions',
		'enabled',
		'deleted',
		'hidden',
		'global'
	];

	/** @var ServiceOptions */
	private $options;

	/** @var ConsequencesRegistry */
	private $consequencesRegistry;

	/**
	 * @param ServiceOptions $options
	 * @param ConsequencesRegistry $consequencesRegistry
	 */
	public function __construct( ServiceOptions $options, ConsequencesRegistry $consequencesRegistry ) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->consequencesRegistry = $consequencesRegistry;
	}

	/**
	 * @param Filter $filter
	 * @param array $actions
	 * @return string
	 */
	public function encodeData( Filter $filter, array $actions ): string {
		$data = [
			'rules' => $filter->getRules(),
			'name' => $filter->getName(),
			'comments' => $filter->getComments(),
			'group' => $filter->getGroup(),
			'actions' => $filter->getActions(),
			'enabled' => $filter->isEnabled(),
			'deleted' => $filter->isDeleted(),
			'hidden' => $filter->isHidden(),
			'global' => $filter->isGlobal()
		];
		// @codeCoverageIgnoreStart
		if ( array_keys( $data ) !== self::TEMPLATE_KEYS ) {
			// Sanity
			throw new LogicException( 'Bad keys' );
		}
		// @codeCoverageIgnoreEnd
		return FormatJson::encode( [ 'data' => $data, 'actions' => $actions ] );
	}

	/**
	 * @param string $rawData
	 * @return Filter
	 * @throws InvalidImportDataException
	 */
	public function decodeData( string $rawData ): Filter {
		$validGroups = $this->options->get( 'AbuseFilterValidGroups' );
		$globalFiltersEnabled = $this->options->get( 'AbuseFilterIsCentral' );

		$data = FormatJson::decode( $rawData );
		if ( !$this->isValidImportData( $data ) ) {
			throw new InvalidImportDataException( $rawData );
		}
		[ 'data' => $filterData, 'actions' => $actions ] = wfObjectToArray( $data );

		return new MutableFilter(
			new Specs(
				$filterData['rules'],
				$filterData['comments'],
				$filterData['name'],
				array_keys( $actions ),
				// Keep the group only if it exists on this wiki
				in_array( $filterData['group'], $validGroups, true ) ? $filterData['group'] : 'default'
			),
			new Flags(
				(bool)$filterData['enabled'],
				(bool)$filterData['deleted'],
				(bool)$filterData['hidden'],
				// And also make it global only if global filters are enabled here
				$filterData['global'] && $globalFiltersEnabled
			),
			$actions,
			new LastEditInfo(
				0,
				'',
				''
			)
		);
	}

	/**
	 * Note: this doesn't check if parameters are valid etc., but only if the shape of the object is right.
	 *
	 * @param mixed $data Already decoded
	 * @return bool
	 */
	private function isValidImportData( $data ): bool {
		if ( !is_object( $data ) ) {
			return false;
		}

		$arr = get_object_vars( $data );

		$expectedKeys = [ 'data' => true, 'actions' => true ];
		if ( count( $arr ) !== count( $expectedKeys ) || array_diff_key( $arr, $expectedKeys ) ) {
			return false;
		}

		if ( !is_object( $arr['data'] ) || !( is_object( $arr['actions'] ) || $arr['actions'] === [] ) ) {
			return false;
		}

		if ( array_keys( get_object_vars( $arr['data'] ) ) !== self::TEMPLATE_KEYS ) {
			return false;
		}

		$allActions = $this->consequencesRegistry->getAllActionNames();
		foreach ( $arr['actions'] as $action => $params ) {
			if ( !in_array( $action, $allActions, true ) || !is_array( $params ) ) {
				return false;
			}
		}

		return true;
	}
}
