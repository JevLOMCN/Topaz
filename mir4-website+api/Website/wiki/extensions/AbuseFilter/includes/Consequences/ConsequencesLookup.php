<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences;

use MediaWiki\Extension\AbuseFilter\CentralDBManager;
use MediaWiki\Extension\AbuseFilter\GlobalNameUtils;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\LBFactory;

/**
 * Class for retrieving actions and parameters from the database
 * @todo Can we better integrate this with FilterLookup?
 */
class ConsequencesLookup {
	public const SERVICE_NAME = 'AbuseFilterConsequencesLookup';

	/** @var LBFactory */
	private $lbFactory;
	/** @var CentralDBManager */
	private $centralDBManager;
	/** @var ConsequencesRegistry */
	private $consequencesRegistry;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param LBFactory $lbFactory
	 * @param CentralDBManager $centralDBManager
	 * @param ConsequencesRegistry $consequencesRegistry
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		LBFactory $lbFactory,
		CentralDBManager $centralDBManager,
		ConsequencesRegistry $consequencesRegistry,
		LoggerInterface $logger
	) {
		$this->lbFactory = $lbFactory;
		$this->centralDBManager = $centralDBManager;
		$this->consequencesRegistry = $consequencesRegistry;
		$this->logger = $logger;
	}

	/**
	 * @param array<int|string> $filters
	 * @return array[][]
	 */
	public function getConsequencesForFilters( array $filters ): array {
		$globalFilters = [];
		$localFilters = [];

		foreach ( $filters as $filter ) {
			[ $filterID, $global ] = GlobalNameUtils::splitGlobalName( $filter );

			if ( $global ) {
				$globalFilters[] = $filterID;
			} else {
				$localFilters[] = (int)$filter;
			}
		}

		// Load local filter info
		$dbr = $this->lbFactory->getReplicaDatabase();
		// Retrieve the consequences.
		$consequences = [];

		if ( count( $localFilters ) ) {
			$consequences = $this->loadConsequencesFromDB( $dbr, $localFilters );
		}

		if ( count( $globalFilters ) ) {
			$consequences += $this->loadConsequencesFromDB(
				$this->centralDBManager->getConnection( DB_REPLICA ),
				$globalFilters,
				GlobalNameUtils::GLOBAL_FILTER_PREFIX
			);
		}

		return $consequences;
	}

	/**
	 * @param IReadableDatabase $dbr
	 * @param int[] $filters
	 * @param string $prefix
	 * @return array[][]
	 */
	private function loadConsequencesFromDB( IReadableDatabase $dbr, array $filters, string $prefix = '' ): array {
		$actionsByFilter = [];
		foreach ( $filters as $filter ) {
			$actionsByFilter[$prefix . $filter] = [];
		}

		$res = $dbr->select(
			[ 'abuse_filter_action', 'abuse_filter' ],
			'*',
			[ 'af_id' => $filters ],
			__METHOD__,
			[],
			[ 'abuse_filter_action' => [ 'LEFT JOIN', 'afa_filter=af_id' ] ]
		);

		$dangerousActions = $this->consequencesRegistry->getDangerousActionNames();
		// Categorise consequences by filter.
		foreach ( $res as $row ) {
			if ( $row->af_throttled
				&& in_array( $row->afa_consequence, $dangerousActions )
			) {
				// Don't do the action, just log
				$this->logger->info(
					'Filter {filter_id} is throttled, skipping action: {action}',
					[
						'filter_id' => $row->af_id,
						'action' => $row->afa_consequence
					]
				);
			} elseif ( $row->afa_filter !== $row->af_id ) {
				// We probably got a NULL, as it's a LEFT JOIN. Don't add it.
				continue;
			} else {
				$actionsByFilter[$prefix . $row->afa_filter][$row->afa_consequence] =
					$row->afa_parameters !== '' ? explode( "\n", $row->afa_parameters ) : [];
			}
		}

		return $actionsByFilter;
	}

}
