<?php

namespace MediaWiki\Extension\AbuseFilter;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutorFactory;
use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Extension\AbuseFilter\Watcher\Watcher;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Psr\Log\LoggerInterface;

/**
 * This class contains the logic for executing abuse filters and their actions. The entry points are
 * run() and runForStash(). Note that run() can only be executed once on a given instance.
 * @internal Not stable yet
 */
class FilterRunner {
	public const CONSTRUCTOR_OPTIONS = [
		'AbuseFilterValidGroups',
		'AbuseFilterCentralDB',
		'AbuseFilterIsCentral',
		'AbuseFilterConditionLimit',
	];

	/** @var AbuseFilterHookRunner */
	private $hookRunner;
	/** @var FilterProfiler */
	private $filterProfiler;
	/** @var ChangeTagger */
	private $changeTagger;
	/** @var FilterLookup */
	private $filterLookup;
	/** @var RuleCheckerFactory */
	private $ruleCheckerFactory;
	/** @var ConsequencesExecutorFactory */
	private $consExecutorFactory;
	/** @var AbuseLoggerFactory */
	private $abuseLoggerFactory;
	/** @var EmergencyCache */
	private $emergencyCache;
	/** @var Watcher[] */
	private $watchers;
	/** @var EditStashCache */
	private $stashCache;
	/** @var LoggerInterface */
	private $logger;
	/** @var VariablesManager */
	private $varManager;
	/** @var VariableGeneratorFactory */
	private $varGeneratorFactory;
	/** @var ServiceOptions */
	private $options;

	/**
	 * @var FilterEvaluator
	 */
	private $ruleChecker;

	/**
	 * @var User The user who performed the action being filtered
	 */
	private $user;
	/**
	 * @var Title The title where the action being filtered was performed
	 */
	private $title;
	/**
	 * @var VariableHolder The variables for the current action
	 */
	private $vars;
	/**
	 * @var string The group of filters to check (as defined in $wgAbuseFilterValidGroups)
	 */
	private $group;
	/**
	 * @var string The action we're filtering
	 */
	private $action;

	/**
	 * @param AbuseFilterHookRunner $hookRunner
	 * @param FilterProfiler $filterProfiler
	 * @param ChangeTagger $changeTagger
	 * @param FilterLookup $filterLookup
	 * @param RuleCheckerFactory $ruleCheckerFactory
	 * @param ConsequencesExecutorFactory $consExecutorFactory
	 * @param AbuseLoggerFactory $abuseLoggerFactory
	 * @param VariablesManager $varManager
	 * @param VariableGeneratorFactory $varGeneratorFactory
	 * @param EmergencyCache $emergencyCache
	 * @param Watcher[] $watchers
	 * @param EditStashCache $stashCache
	 * @param LoggerInterface $logger
	 * @param ServiceOptions $options
	 * @param User $user
	 * @param Title $title
	 * @param VariableHolder $vars
	 * @param string $group
	 * @throws InvalidArgumentException If $group is invalid or the 'action' variable is unset
	 */
	public function __construct(
		AbuseFilterHookRunner $hookRunner,
		FilterProfiler $filterProfiler,
		ChangeTagger $changeTagger,
		FilterLookup $filterLookup,
		RuleCheckerFactory $ruleCheckerFactory,
		ConsequencesExecutorFactory $consExecutorFactory,
		AbuseLoggerFactory $abuseLoggerFactory,
		VariablesManager $varManager,
		VariableGeneratorFactory $varGeneratorFactory,
		EmergencyCache $emergencyCache,
		array $watchers,
		EditStashCache $stashCache,
		LoggerInterface $logger,
		ServiceOptions $options,
		User $user,
		Title $title,
		VariableHolder $vars,
		string $group
	) {
		$this->hookRunner = $hookRunner;
		$this->filterProfiler = $filterProfiler;
		$this->changeTagger = $changeTagger;
		$this->filterLookup = $filterLookup;
		$this->ruleCheckerFactory = $ruleCheckerFactory;
		$this->consExecutorFactory = $consExecutorFactory;
		$this->abuseLoggerFactory = $abuseLoggerFactory;
		$this->varManager = $varManager;
		$this->varGeneratorFactory = $varGeneratorFactory;
		$this->emergencyCache = $emergencyCache;
		$this->watchers = $watchers;
		$this->stashCache = $stashCache;
		$this->logger = $logger;

		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		if ( !in_array( $group, $options->get( 'AbuseFilterValidGroups' ), true ) ) {
			throw new InvalidArgumentException( "Group $group is not a valid group" );
		}
		$this->options = $options;

		if ( !$vars->varIsSet( 'action' ) ) {
			throw new InvalidArgumentException( "The 'action' variable is not set." );
		}
		$this->user = $user;
		$this->title = $title;
		$this->vars = $vars;
		$this->group = $group;
		$this->action = $vars->getComputedVariable( 'action' )->toString();
	}

	/**
	 * Inits variables and parser right before running
	 */
	private function init() {
		// Add vars from extensions
		$this->hookRunner->onAbuseFilter_filterAction(
			$this->vars,
			$this->title
		);
		$this->hookRunner->onAbuseFilterAlterVariables(
			$this->vars,
			$this->title,
			$this->user
		);
		$generator = $this->varGeneratorFactory->newGenerator( $this->vars );
		$this->vars = $generator->addGenericVars()->getVariableHolder();
		$this->ruleChecker = $this->ruleCheckerFactory->newRuleChecker( $this->vars );
	}

	/**
	 * The main entry point of this class. This method runs all filters and takes their consequences.
	 *
	 * @param bool $allowStash Whether we are allowed to check the cache to see if there's a cached
	 *  result of a previous execution for the same edit.
	 * @return Status Good if no action has been taken, a fatal otherwise.
	 */
	public function run( $allowStash = true ): Status {
		$this->init();

		$skipReasons = [];
		$shouldFilter = $this->hookRunner->onAbuseFilterShouldFilterAction(
			$this->vars, $this->title, $this->user, $skipReasons
		);
		if ( !$shouldFilter ) {
			$this->logger->info(
				'Skipping action {action}. Reasons provided: {reasons}',
				[ 'action' => $this->action, 'reasons' => implode( ', ', $skipReasons ) ]
			);
			return Status::newGood();
		}

		$useStash = $allowStash && $this->action === 'edit';

		$runnerData = null;
		if ( $useStash ) {
			$cacheData = $this->stashCache->seek( $this->vars );
			if ( $cacheData !== false ) {
				// Use cached vars (T176291) and profiling data (T191430)
				$this->vars = VariableHolder::newFromArray( $cacheData['vars'] );
				$runnerData = RunnerData::fromArray( $cacheData['data'] );
			}
		}

		$runnerData ??= $this->checkAllFiltersInternal();

		DeferredUpdates::addCallableUpdate( function () use ( $runnerData ) {
			$this->profileExecution( $runnerData );
			$this->updateEmergencyCache( $runnerData->getMatchesMap() );
		} );

		// TODO: inject the action specifier to avoid this
		$accountname = $this->varManager->getVar(
			$this->vars,
			'accountname',
			VariablesManager::GET_BC
		)->toNative();
		$spec = new ActionSpecifier(
			$this->action,
			$this->title,
			$this->user,
			$this->user->getRequest()->getIP(),
			$accountname
		);

		// Tag the action if the condition limit was hit
		if ( $runnerData->getTotalConditions() > $this->options->get( 'AbuseFilterConditionLimit' ) ) {
			$this->changeTagger->addConditionsLimitTag( $spec );
		}

		$matchedFilters = $runnerData->getMatchedFilters();

		if ( count( $matchedFilters ) === 0 ) {
			return Status::newGood();
		}

		$executor = $this->consExecutorFactory->newExecutor( $spec, $this->vars );
		$status = $executor->executeFilterActions( $matchedFilters );
		$actionsTaken = $status->getValue();

		// Note, it's important that we create an AbuseLogger now, after all lazy-loaded variables
		// requested by active filters have been computed
		$abuseLogger = $this->abuseLoggerFactory->newLogger( $this->title, $this->user, $this->vars );
		[
			'local' => $loggedLocalFilters,
			'global' => $loggedGlobalFilters
		] = $abuseLogger->addLogEntries( $actionsTaken );

		foreach ( $this->watchers as $watcher ) {
			$watcher->run( $loggedLocalFilters, $loggedGlobalFilters, $this->group );
		}

		return $status;
	}

	/**
	 * Similar to run(), but runs in "stash" mode, which means filters are executed, no actions are
	 *  taken, and the result is saved in cache to be later reused. This can only be used for edits,
	 *  and not doing so will throw.
	 *
	 * @throws InvalidArgumentException
	 * @return Status Always a good status, since we're only saving data.
	 */
	public function runForStash(): Status {
		if ( $this->action !== 'edit' ) {
			throw new InvalidArgumentException(
				__METHOD__ . " can only be called for edits, called for action {$this->action}."
			);
		}

		$this->init();

		$skipReasons = [];
		$shouldFilter = $this->hookRunner->onAbuseFilterShouldFilterAction(
			$this->vars, $this->title, $this->user, $skipReasons
		);
		if ( !$shouldFilter ) {
			// Don't log it yet
			return Status::newGood();
		}

		// XXX: We need a copy here because the cache key is computed
		// from the variables, but some variables can be loaded lazily
		// which would store the data with a key distinct from that
		// computed by seek() in ::run().
		// TODO: Find better way to generate the cache key.
		$origVars = clone $this->vars;

		$runnerData = $this->checkAllFiltersInternal();
		// Save the filter stash result and do nothing further
		$cacheData = [
			'vars' => $this->varManager->dumpAllVars( $this->vars ),
			'data' => $runnerData->toArray(),
		];

		$this->stashCache->store( $origVars, $cacheData );

		return Status::newGood();
	}

	/**
	 * Run all filters and return information about matches and profiling
	 *
	 * @return RunnerData
	 */
	private function checkAllFiltersInternal(): RunnerData {
		// Ensure there's no extra time leftover
		LazyVariableComputer::$profilingExtraTime = 0;

		$data = new RunnerData();

		foreach ( $this->filterLookup->getAllActiveFiltersInGroup( $this->group, false ) as $filter ) {
			[ $status, $timeTaken ] = $this->checkFilter( $filter );
			$data->record( $filter->getID(), false, $status, $timeTaken );
		}

		if ( $this->options->get( 'AbuseFilterCentralDB' ) && !$this->options->get( 'AbuseFilterIsCentral' ) ) {
			foreach ( $this->filterLookup->getAllActiveFiltersInGroup( $this->group, true ) as $filter ) {
				[ $status, $timeTaken ] = $this->checkFilter( $filter, true );
				$data->record( $filter->getID(), true, $status, $timeTaken );
			}
		}

		return $data;
	}

	/**
	 * Returns an associative array of filters which were tripped
	 *
	 * @internal BC method
	 * @return bool[] Map of (filter ID => bool)
	 */
	public function checkAllFilters(): array {
		$this->init();
		return $this->checkAllFiltersInternal()->getMatchesMap();
	}

	/**
	 * Check the conditions of a single filter, and profile it
	 *
	 * @param ExistingFilter $filter
	 * @param bool $global
	 * @return array [ status, time taken ]
	 * @phan-return array{0:\MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerStatus,1:float}
	 */
	private function checkFilter( ExistingFilter $filter, bool $global = false ): array {
		$filterName = GlobalNameUtils::buildGlobalName( $filter->getID(), $global );

		$startTime = microtime( true );
		$origExtraTime = LazyVariableComputer::$profilingExtraTime;

		$status = $this->ruleChecker->checkConditions( $filter->getRules(), $filterName );

		$actualExtra = LazyVariableComputer::$profilingExtraTime - $origExtraTime;
		$timeTaken = 1000 * ( microtime( true ) - $startTime - $actualExtra );

		return [ $status, $timeTaken ];
	}

	/**
	 * @param RunnerData $data
	 */
	private function profileExecution( RunnerData $data ) {
		$allFilters = $data->getAllFilters();
		$matchedFilters = $data->getMatchedFilters();
		$this->filterProfiler->recordRuntimeProfilingResult(
			count( $allFilters ),
			$data->getTotalConditions(),
			$data->getTotalRuntime()
		);
		$this->filterProfiler->recordPerFilterProfiling( $this->title, $data->getProfilingData() );
		$this->filterProfiler->recordStats(
			$this->group,
			$data->getTotalConditions(),
			$data->getTotalRuntime(),
			(bool)$matchedFilters
		);
	}

	/**
	 * @param bool[] $matches
	 */
	private function updateEmergencyCache( array $matches ): void {
		$filters = $this->emergencyCache->getFiltersToCheckInGroup( $this->group );
		foreach ( $filters as $filter ) {
			if ( array_key_exists( "$filter", $matches ) ) {
				$this->emergencyCache->incrementForFilter( $filter, $matches["$filter"] );
			}
		}
	}
}
