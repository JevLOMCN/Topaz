<?php

namespace MediaWiki\Extension\AbuseFilter;

use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseFilter;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Title\Title;

/**
 * Helper service for EmergencyWatcher to notify filter maintainers of throttled filters
 * @todo DI not possible due to Echo
 */
class EchoNotifier {
	public const SERVICE_NAME = 'AbuseFilterEchoNotifier';
	public const EVENT_TYPE = 'throttled-filter';

	/** @var FilterLookup */
	private $filterLookup;
	/** @var ConsequencesRegistry */
	private $consequencesRegistry;
	/** @var bool */
	private $isEchoLoaded;

	/**
	 * @param FilterLookup $filterLookup
	 * @param ConsequencesRegistry $consequencesRegistry
	 * @param bool $isEchoLoaded
	 */
	public function __construct(
		FilterLookup $filterLookup,
		ConsequencesRegistry $consequencesRegistry,
		bool $isEchoLoaded
	) {
		$this->filterLookup = $filterLookup;
		$this->consequencesRegistry = $consequencesRegistry;
		$this->isEchoLoaded = $isEchoLoaded;
	}

	/**
	 * @param int $filter
	 * @return Title
	 */
	private function getTitleForFilter( int $filter ): Title {
		return SpecialAbuseFilter::getTitleForSubpage( (string)$filter );
	}

	/**
	 * @param int $filter
	 * @return ExistingFilter
	 */
	private function getFilterObject( int $filter ): ExistingFilter {
		return $this->filterLookup->getFilter( $filter, false );
	}

	/**
	 * @internal
	 * @param int $filter
	 * @return array
	 */
	public function getDataForEvent( int $filter ): array {
		$filterObj = $this->getFilterObject( $filter );
		$throttledActionNames = array_intersect(
			$filterObj->getActionsNames(),
			$this->consequencesRegistry->getDangerousActionNames()
		);
		return [
			'type' => self::EVENT_TYPE,
			'title' => $this->getTitleForFilter( $filter ),
			'extra' => [
				'user' => $filterObj->getUserID(),
				'throttled-actions' => $throttledActionNames,
			],
		];
	}

	/**
	 * Send notification about a filter being throttled
	 *
	 * @param int $filter
	 * @return Event|false
	 */
	public function notifyForFilter( int $filter ) {
		if ( $this->isEchoLoaded ) {
			return Event::create( $this->getDataForEvent( $filter ) );
		}
		return false;
	}

}
