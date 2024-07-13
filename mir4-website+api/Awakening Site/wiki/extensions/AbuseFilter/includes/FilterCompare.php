<?php

namespace MediaWiki\Extension\AbuseFilter;

use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\Filter\Filter;

/**
 * This service allows comparing two versions of a filter.
 * @todo We might want to expand this to cover the use case of ViewDiff
 * @internal
 */
class FilterCompare {
	public const SERVICE_NAME = 'AbuseFilterFilterCompare';

	/** @var ConsequencesRegistry */
	private $consequencesRegistry;

	/**
	 * @param ConsequencesRegistry $consequencesRegistry
	 */
	public function __construct( ConsequencesRegistry $consequencesRegistry ) {
		$this->consequencesRegistry = $consequencesRegistry;
	}

	/**
	 * @param Filter $firstFilter
	 * @param Filter $secondFilter
	 * @return array Fields that are different
	 */
	public function compareVersions( Filter $firstFilter, Filter $secondFilter ): array {
		// TODO: Avoid DB references here, re-add when saving the filter
		$methods = [
			'af_public_comments' => 'getName',
			'af_pattern' => 'getRules',
			'af_comments' => 'getComments',
			'af_deleted' => 'isDeleted',
			'af_enabled' => 'isEnabled',
			'af_hidden' => 'isHidden',
			'af_global' => 'isGlobal',
			'af_group' => 'getGroup',
		];

		$differences = [];

		foreach ( $methods as $field => $method ) {
			if ( $firstFilter->$method() !== $secondFilter->$method() ) {
				$differences[] = $field;
			}
		}

		$firstActions = $firstFilter->getActions();
		$secondActions = $secondFilter->getActions();
		foreach ( $this->consequencesRegistry->getAllEnabledActionNames() as $action ) {
			if ( !isset( $firstActions[$action] ) && !isset( $secondActions[$action] ) ) {
				// They're both unset
			} elseif ( isset( $firstActions[$action] ) && isset( $secondActions[$action] ) ) {
				// They're both set. Double check needed, e.g. per T180194
				if ( array_diff( $firstActions[$action], $secondActions[$action] ) ||
					array_diff( $secondActions[$action], $firstActions[$action] ) ) {
					// Different parameters
					$differences[] = 'actions';
				}
			} else {
				// One's unset, one's set.
				$differences[] = 'actions';
			}
		}

		return array_unique( $differences );
	}
}
