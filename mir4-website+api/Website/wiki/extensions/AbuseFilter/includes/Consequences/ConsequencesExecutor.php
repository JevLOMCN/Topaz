<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences;

use MediaWiki\Block\BlockUser;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Block;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Consequence;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\ConsequencesDisablerConsequence;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\HookAborterConsequence;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\GlobalNameUtils;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentityUtils;
use Psr\Log\LoggerInterface;

class ConsequencesExecutor {
	public const CONSTRUCTOR_OPTIONS = [
		'AbuseFilterLocallyDisabledGlobalActions',
		'AbuseFilterBlockDuration',
		'AbuseFilterAnonBlockDuration',
		'AbuseFilterBlockAutopromoteDuration',
	];

	/** @var ConsequencesLookup */
	private $consLookup;
	/** @var ConsequencesFactory */
	private $consFactory;
	/** @var ConsequencesRegistry */
	private $consRegistry;
	/** @var FilterLookup */
	private $filterLookup;
	/** @var LoggerInterface */
	private $logger;
	/** @var UserIdentityUtils */
	private $userIdentityUtils;
	/** @var ServiceOptions */
	private $options;
	/** @var ActionSpecifier */
	private $specifier;
	/** @var VariableHolder */
	private $vars;

	/**
	 * @param ConsequencesLookup $consLookup
	 * @param ConsequencesFactory $consFactory
	 * @param ConsequencesRegistry $consRegistry
	 * @param FilterLookup $filterLookup
	 * @param LoggerInterface $logger
	 * @param UserIdentityUtils $userIdentityUtils
	 * @param ServiceOptions $options
	 * @param ActionSpecifier $specifier
	 * @param VariableHolder $vars
	 */
	public function __construct(
		ConsequencesLookup $consLookup,
		ConsequencesFactory $consFactory,
		ConsequencesRegistry $consRegistry,
		FilterLookup $filterLookup,
		LoggerInterface $logger,
		UserIdentityUtils $userIdentityUtils,
		ServiceOptions $options,
		ActionSpecifier $specifier,
		VariableHolder $vars
	) {
		$this->consLookup = $consLookup;
		$this->consFactory = $consFactory;
		$this->consRegistry = $consRegistry;
		$this->filterLookup = $filterLookup;
		$this->logger = $logger;
		$this->userIdentityUtils = $userIdentityUtils;
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->specifier = $specifier;
		$this->vars = $vars;
	}

	/**
	 * Executes a set of actions.
	 *
	 * @param string[] $filters
	 * @return Status returns the operation's status. $status->isOK() will return true if
	 *         there were no actions taken, false otherwise. $status->getValue() will return
	 *         an array listing the actions taken. $status->getErrors() etc. will provide
	 *         the errors and warnings to be shown to the user to explain the actions.
	 */
	public function executeFilterActions( array $filters ): Status {
		$actionsToTake = $this->getActualConsequencesToExecute( $filters );
		$actionsTaken = array_fill_keys( $filters, [] );

		$messages = [];
		foreach ( $actionsToTake as $filter => $actions ) {
			foreach ( $actions as $action => $info ) {
				[ $executed, $newMsg ] = $this->takeConsequenceAction( $info );

				if ( $newMsg !== null ) {
					$messages[] = $newMsg;
				}
				if ( $executed ) {
					$actionsTaken[$filter][] = $action;
				}
			}
		}

		return $this->buildStatus( $actionsTaken, $messages );
	}

	/**
	 * @param string[] $filters
	 * @return Consequence[][]
	 * @internal
	 */
	public function getActualConsequencesToExecute( array $filters ): array {
		$rawConsParamsByFilter = $this->consLookup->getConsequencesForFilters( $filters );
		$consParamsByFilter = $this->replaceLegacyParameters( $rawConsParamsByFilter );
		$specializedConsParams = $this->specializeParameters( $consParamsByFilter );
		$allowedConsParams = $this->removeForbiddenConsequences( $specializedConsParams );

		$consequences = $this->replaceArraysWithConsequences( $allowedConsParams );
		$actualConsequences = $this->applyConsequenceDisablers( $consequences );
		$deduplicatedConsequences = $this->deduplicateConsequences( $actualConsequences );
		return $this->removeRedundantConsequences( $deduplicatedConsequences );
	}

	/**
	 * Update parameters for all consequences, making sure that they match the currently expected format
	 * (e.g., 'block' didn't use to have expiries).
	 *
	 * @param array[] $consParams
	 * @return array[]
	 */
	private function replaceLegacyParameters( array $consParams ): array {
		$registeredBlockDuration = $this->options->get( 'AbuseFilterBlockDuration' );
		$anonBlockDuration = $this->options->get( 'AbuseFilterAnonBlockDuration' ) ?? $registeredBlockDuration;
		foreach ( $consParams as $filter => $actions ) {
			foreach ( $actions as $name => $parameters ) {
				if ( $name === 'block' && count( $parameters ) !== 3 ) {
					// Old type with fixed expiry
					$blockTalk = in_array( 'blocktalk', $parameters, true );

					$consParams[$filter][$name] = [
						$blockTalk ? 'blocktalk' : 'noTalkBlockSet',
						$anonBlockDuration,
						$registeredBlockDuration
					];
				}
			}
		}

		return $consParams;
	}

	/**
	 * For every consequence, keep only the parameters that are relevant for this specific action being filtered.
	 * For instance, choose between anon expiry and registered expiry for blocks.
	 *
	 * @param array[] $consParams
	 * @return array[]
	 */
	private function specializeParameters( array $consParams ): array {
		$user = $this->specifier->getUser();
		$isNamed = $this->userIdentityUtils->isNamed( $user );
		foreach ( $consParams as $filter => $actions ) {
			foreach ( $actions as $name => $parameters ) {
				if ( $name === 'block' ) {
					$consParams[$filter][$name] = [
						'expiry' => $isNamed ? $parameters[2] : $parameters[1],
						'blocktalk' => $parameters[0] === 'blocktalk'
					];
				}
			}
		}

		return $consParams;
	}

	/**
	 * Removes any consequence that cannot be executed. For instance, remove locally disabled
	 * consequences for global filters.
	 *
	 * @param array[] $consParams
	 * @return array[]
	 */
	private function removeForbiddenConsequences( array $consParams ): array {
		$locallyDisabledActions = $this->options->get( 'AbuseFilterLocallyDisabledGlobalActions' );
		foreach ( $consParams as $filter => $actions ) {
			$isGlobalFilter = GlobalNameUtils::splitGlobalName( $filter )[1];
			if ( $isGlobalFilter ) {
				$consParams[$filter] = array_diff_key(
					$actions,
					array_filter( $locallyDisabledActions )
				);
			}
		}

		return $consParams;
	}

	/**
	 * Converts all consequence specifiers to Consequence objects.
	 *
	 * @param array[] $actionsByFilter
	 * @return Consequence[][]
	 */
	private function replaceArraysWithConsequences( array $actionsByFilter ): array {
		$ret = [];
		foreach ( $actionsByFilter as $filter => $actions ) {
			$ret[$filter] = [];
			foreach ( $actions as $name => $parameters ) {
				$cons = $this->actionsParamsToConsequence( $name, $parameters, $filter );
				if ( $cons !== null ) {
					$ret[$filter][$name] = $cons;
				}
			}
		}

		return $ret;
	}

	/**
	 * Pre-check any consequences-disabler consequence and remove any further actions prevented by them. Specifically:
	 * - For every filter with "throttle" enabled, remove other actions if the throttle counter hasn't been reached
	 * - For every filter with "warn" enabled, remove other actions if the warning hasn't been shown
	 *
	 * @param Consequence[][] $consequencesByFilter
	 * @return Consequence[][]
	 */
	private function applyConsequenceDisablers( array $consequencesByFilter ): array {
		foreach ( $consequencesByFilter as $filter => $actions ) {
			/** @var ConsequencesDisablerConsequence[] $consequenceDisablers */
			$consequenceDisablers = array_filter( $actions, static function ( $el ) {
				return $el instanceof ConsequencesDisablerConsequence;
			} );
			'@phan-var ConsequencesDisablerConsequence[] $consequenceDisablers';
			uasort(
				$consequenceDisablers,
				static function ( ConsequencesDisablerConsequence $x, ConsequencesDisablerConsequence $y ) {
					return $x->getSort() - $y->getSort();
				}
			);
			foreach ( $consequenceDisablers as $name => $consequence ) {
				if ( $consequence->shouldDisableOtherConsequences() ) {
					$consequencesByFilter[$filter] = [ $name => $consequence ];
					continue 2;
				}
			}
		}

		return $consequencesByFilter;
	}

	/**
	 * Removes duplicated consequences. For instance, this only keeps the longest of all blocks.
	 *
	 * @param Consequence[][] $consByFilter
	 * @return Consequence[][]
	 */
	private function deduplicateConsequences( array $consByFilter ): array {
		// Keep track of the longest block
		$maxBlock = [ 'id' => null, 'expiry' => -1, 'cons' => null ];

		foreach ( $consByFilter as $filter => $actions ) {
			foreach ( $actions as $name => $cons ) {
				if ( $name === 'block' ) {
					/** @var Block $cons */
					'@phan-var Block $cons';
					$expiry = $cons->getExpiry();
					$parsedExpiry = BlockUser::parseExpiryInput( $expiry );
					if (
						$maxBlock['expiry'] === -1 ||
						$parsedExpiry > BlockUser::parseExpiryInput( $maxBlock['expiry'] )
					) {
						$maxBlock = [
							'id' => $filter,
							'expiry' => $expiry,
							'cons' => $cons
						];
					}
					// We'll re-add it later
					unset( $consByFilter[$filter]['block'] );
				}
			}
		}

		if ( $maxBlock['id'] !== null ) {
			$consByFilter[$maxBlock['id']]['block'] = $maxBlock['cons'];
		}

		return $consByFilter;
	}

	/**
	 * Remove redundant consequences, e.g., remove "disallow" if a dangerous action will be executed
	 * TODO: Is this wanted, especially now that we have custom disallow messages?
	 *
	 * @param Consequence[][] $consByFilter
	 * @return Consequence[][]
	 */
	private function removeRedundantConsequences( array $consByFilter ): array {
		$dangerousActions = $this->consRegistry->getDangerousActionNames();

		foreach ( $consByFilter as $filter => $actions ) {
			// Don't show the disallow message if a blocking action is executed
			if (
				isset( $actions['disallow'] ) &&
				array_intersect( array_keys( $actions ), $dangerousActions )
			) {
				unset( $consByFilter[$filter]['disallow'] );
			}
		}

		return $consByFilter;
	}

	/**
	 * @param string $actionName
	 * @param array $rawParams
	 * @param int|string $filter
	 * @return Consequence|null
	 */
	private function actionsParamsToConsequence( string $actionName, array $rawParams, $filter ): ?Consequence {
		[ $filterID, $isGlobalFilter ] = GlobalNameUtils::splitGlobalName( $filter );
		$filterObj = $this->filterLookup->getFilter( $filterID, $isGlobalFilter );

		$baseConsParams = new Parameters(
			$filterObj,
			$isGlobalFilter,
			$this->specifier
		);

		switch ( $actionName ) {
			case 'throttle':
				$throttleId = array_shift( $rawParams );
				[ $rateCount, $ratePeriod ] = explode( ',', array_shift( $rawParams ) );

				$throttleParams = [
					'id' => $throttleId,
					'count' => (int)$rateCount,
					'period' => (int)$ratePeriod,
					'groups' => $rawParams,
					'global' => $isGlobalFilter
				];
				return $this->consFactory->newThrottle( $baseConsParams, $throttleParams );
			case 'warn':
				return $this->consFactory->newWarn( $baseConsParams, $rawParams[0] ?? 'abusefilter-warning' );
			case 'disallow':
				return $this->consFactory->newDisallow( $baseConsParams, $rawParams[0] ?? 'abusefilter-disallowed' );
			case 'rangeblock':
				return $this->consFactory->newRangeBlock( $baseConsParams, '1 week' );
			case 'degroup':
				return $this->consFactory->newDegroup( $baseConsParams, $this->vars );
			case 'blockautopromote':
				$duration = $this->options->get( 'AbuseFilterBlockAutopromoteDuration' ) * 86400;
				return $this->consFactory->newBlockAutopromote( $baseConsParams, $duration );
			case 'block':
				return $this->consFactory->newBlock(
					$baseConsParams,
					$rawParams['expiry'],
					$rawParams['blocktalk']
				);
			case 'tag':
				return $this->consFactory->newTag( $baseConsParams, $rawParams );
			default:
				if ( array_key_exists( $actionName, $this->consRegistry->getCustomActions() ) ) {
					$callback = $this->consRegistry->getCustomActions()[$actionName];
					return $callback( $baseConsParams, $rawParams );
				} else {
					$this->logger->warning( "Unrecognised action $actionName" );
					return null;
				}
		}
	}

	/**
	 * @param Consequence $consequence
	 * @return array [ Executed (bool), Message (?array) ] The message is given as an array
	 *   containing the message key followed by any message parameters.
	 * @todo Improve return value
	 */
	private function takeConsequenceAction( Consequence $consequence ): array {
		$res = $consequence->execute();
		if ( $res && $consequence instanceof HookAborterConsequence ) {
			$message = $consequence->getMessage();
		}

		return [ $res, $message ?? null ];
	}

	/**
	 * Constructs a Status object as returned by executeFilterActions() from the list of
	 * actions taken and the corresponding list of messages.
	 *
	 * @param array[] $actionsTaken associative array mapping each filter to the list if
	 *                actions taken because of that filter.
	 * @param array[] $messages a list of arrays, where each array contains a message key
	 *                followed by any message parameters.
	 * @phan-param non-empty-array[] $messages
	 *
	 * @return Status
	 */
	private function buildStatus( array $actionsTaken, array $messages ): Status {
		$status = Status::newGood( $actionsTaken );

		foreach ( $messages as $msg ) {
			$status->fatal( ...$msg );
		}

		return $status;
	}
}
