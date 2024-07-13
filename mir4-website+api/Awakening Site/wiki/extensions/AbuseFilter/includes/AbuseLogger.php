<?php

namespace MediaWiki\Extension\AbuseFilter;

use ExtensionRegistry;
use InvalidArgumentException;
use ManualLogEntry;
use MediaWiki\CheckUser\Hooks;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentityValue;
use Profiler;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\ScopedCallback;

class AbuseLogger {
	public const CONSTRUCTOR_OPTIONS = [
		'AbuseFilterLogIP',
		'AbuseFilterNotifications',
		'AbuseFilterNotificationsPrivate',
	];

	/** @var Title */
	private $title;
	/** @var User */
	private $user;
	/** @var VariableHolder */
	private $vars;
	/** @var string */
	private $action;

	/** @var CentralDBManager */
	private $centralDBManager;
	/** @var FilterLookup */
	private $filterLookup;
	/** @var VariablesBlobStore */
	private $varBlobStore;
	/** @var VariablesManager */
	private $varManager;
	/** @var EditRevUpdater */
	private $editRevUpdater;
	/** @var LBFactory */
	private $lbFactory;
	/** @var ServiceOptions */
	private $options;
	/** @var string */
	private $wikiID;
	/** @var string */
	private $requestIP;

	/**
	 * @param CentralDBManager $centralDBManager
	 * @param FilterLookup $filterLookup
	 * @param VariablesBlobStore $varBlobStore
	 * @param VariablesManager $varManager
	 * @param EditRevUpdater $editRevUpdater
	 * @param LBFactory $lbFactory
	 * @param ServiceOptions $options
	 * @param string $wikiID
	 * @param string $requestIP
	 * @param Title $title
	 * @param User $user
	 * @param VariableHolder $vars
	 */
	public function __construct(
		CentralDBManager $centralDBManager,
		FilterLookup $filterLookup,
		VariablesBlobStore $varBlobStore,
		VariablesManager $varManager,
		EditRevUpdater $editRevUpdater,
		LBFactory $lbFactory,
		ServiceOptions $options,
		string $wikiID,
		string $requestIP,
		Title $title,
		User $user,
		VariableHolder $vars
	) {
		if ( !$vars->varIsSet( 'action' ) ) {
			throw new InvalidArgumentException( "The 'action' variable is not set." );
		}
		$this->centralDBManager = $centralDBManager;
		$this->filterLookup = $filterLookup;
		$this->varBlobStore = $varBlobStore;
		$this->varManager = $varManager;
		$this->editRevUpdater = $editRevUpdater;
		$this->lbFactory = $lbFactory;
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->wikiID = $wikiID;
		$this->requestIP = $requestIP;
		$this->title = $title;
		$this->user = $user;
		$this->vars = $vars;
		$this->action = $vars->getComputedVariable( 'action' )->toString();
	}

	/**
	 * Create and publish log entries for taken actions
	 *
	 * @param array[] $actionsTaken
	 * @return array Shape is [ 'local' => int[], 'global' => int[] ], IDs of logged filters
	 * @phan-return array{local:int[],global:int[]}
	 */
	public function addLogEntries( array $actionsTaken ): array {
		$dbw = $this->lbFactory->getPrimaryDatabase();
		$logTemplate = $this->buildLogTemplate();
		$centralLogTemplate = [
			'afl_wiki' => $this->wikiID,
		];

		$logRows = [];
		$centralLogRows = [];
		$loggedLocalFilters = [];
		$loggedGlobalFilters = [];

		foreach ( $actionsTaken as $filter => $actions ) {
			[ $filterID, $global ] = GlobalNameUtils::splitGlobalName( $filter );
			$thisLog = $logTemplate;
			$thisLog['afl_filter_id'] = $filterID;
			$thisLog['afl_global'] = (int)$global;
			$thisLog['afl_actions'] = implode( ',', $actions );

			// Don't log if we were only throttling.
			// TODO This check should be removed or rewritten using Consequence objects
			if ( $thisLog['afl_actions'] !== 'throttle' ) {
				$logRows[] = $thisLog;
				// Global logging
				if ( $global ) {
					$centralLog = $thisLog + $centralLogTemplate;
					$centralLog['afl_filter_id'] = $filterID;
					$centralLog['afl_global'] = 0;
					$centralLog['afl_title'] = $this->title->getPrefixedText();
					$centralLog['afl_namespace'] = 0;

					$centralLogRows[] = $centralLog;
					$loggedGlobalFilters[] = $filterID;
				} else {
					$loggedLocalFilters[] = $filterID;
				}
			}
		}

		if ( !count( $logRows ) ) {
			return [ 'local' => [], 'global' => [] ];
		}

		$localLogIDs = $this->insertLocalLogEntries( $logRows, $dbw );

		$globalLogIDs = [];
		if ( count( $loggedGlobalFilters ) ) {
			$fdb = $this->centralDBManager->getConnection( DB_PRIMARY );
			$globalLogIDs = $this->insertGlobalLogEntries( $centralLogRows, $fdb );
		}

		$this->editRevUpdater->setLogIdsForTarget(
			$this->title,
			[ 'local' => $localLogIDs, 'global' => $globalLogIDs ]
		);

		return [ 'local' => $loggedLocalFilters, 'global' => $loggedGlobalFilters ];
	}

	/**
	 * Creates a template to use for logging taken actions
	 *
	 * @return array
	 */
	private function buildLogTemplate(): array {
		// If $this->user isn't safe to load (e.g. a failure during
		// AbortAutoAccount), create a dummy anonymous user instead.
		$user = $this->user->isSafeToLoad() ? $this->user : new User;
		// Create a template
		$logTemplate = [
			'afl_user' => $user->getId(),
			'afl_user_text' => $user->getName(),
			'afl_timestamp' => $this->lbFactory->getReplicaDatabase()->timestamp(),
			'afl_namespace' => $this->title->getNamespace(),
			'afl_title' => $this->title->getDBkey(),
			'afl_action' => $this->action,
			'afl_ip' => $this->options->get( 'AbuseFilterLogIP' ) ? $this->requestIP : ''
		];
		// Hack to avoid revealing IPs of people creating accounts
		if ( ( $this->action === 'createaccount' || $this->action === 'autocreateaccount' ) && !$user->getId() ) {
			$logTemplate['afl_user_text'] = $this->vars->getComputedVariable( 'accountname' )->toString();
		}
		return $logTemplate;
	}

	/**
	 * @param array $data
	 * @return ManualLogEntry
	 */
	private function newLocalLogEntryFromData( array $data ): ManualLogEntry {
		// Give grep a chance to find the usages:
		// logentry-abusefilter-hit
		$entry = new ManualLogEntry( 'abusefilter', 'hit' );
		$user = new UserIdentityValue( $data['afl_user'], $data['afl_user_text'] );
		$entry->setPerformer( $user );
		$entry->setTarget( $this->title );
		$filterName = GlobalNameUtils::buildGlobalName(
			$data['afl_filter_id'],
			$data['afl_global'] === 1
		);
		// Additional info
		$entry->setParameters( [
			'action' => $data['afl_action'],
			'filter' => $filterName,
			'actions' => $data['afl_actions'],
			'log' => $data['afl_id'],
		] );
		return $entry;
	}

	/**
	 * @param array[] $logRows
	 * @param IDatabase $dbw
	 * @return int[]
	 */
	private function insertLocalLogEntries( array $logRows, IDatabase $dbw ): array {
		$varDump = $this->varBlobStore->storeVarDump( $this->vars );

		$loggedIDs = [];
		foreach ( $logRows as $data ) {
			$data['afl_var_dump'] = $varDump;
			$dbw->insert( 'abuse_filter_log', $data, __METHOD__ );
			$loggedIDs[] = $data['afl_id'] = $dbw->insertId();

			// Send data to CheckUser if installed and we
			// aren't already sending a notification to recentchanges
			if ( ExtensionRegistry::getInstance()->isLoaded( 'CheckUser' )
				&& strpos( $this->options->get( 'AbuseFilterNotifications' ), 'rc' ) === false
			) {
				global $wgCheckUserLogAdditionalRights;
				$wgCheckUserLogAdditionalRights[] = 'abusefilter-view';
				$entry = $this->newLocalLogEntryFromData( $data );
				$user = $entry->getPerformerIdentity();
				// Invert the hack from ::buildLogTemplate because CheckUser attempts
				// to assign an actor id to the non-existing user
				if (
					( $this->action === 'createaccount' || $this->action === 'autocreateaccount' )
					&& !$user->getId()
				) {
					$entry->setPerformer( new UserIdentityValue( 0, $this->requestIP ) );
				}
				$rc = $entry->getRecentChange();
				// We need to send the entries on POSTSEND to ensure that the user definitely exists, as a temporary
				// account being created by this edit may not exist until after AbuseFilter processes the edit.
				DeferredUpdates::addCallableUpdate( static function () use ( $rc ) {
					// Silence the TransactionProfiler warnings for performing write queries (T359648).
					$trxProfiler = Profiler::instance()->getTransactionProfiler();
					$scope = $trxProfiler->silenceForScope( $trxProfiler::EXPECTATION_REPLICAS_ONLY );
					Hooks::updateCheckUserData( $rc );
					ScopedCallback::consume( $scope );
				} );
			}

			if ( $this->options->get( 'AbuseFilterNotifications' ) !== false ) {
				$filterID = $data['afl_filter_id'];
				$global = $data['afl_global'];
				if (
					!$this->options->get( 'AbuseFilterNotificationsPrivate' ) &&
					$this->filterLookup->getFilter( $filterID, $global )->isHidden()
				) {
					continue;
				}
				$entry = $this->newLocalLogEntryFromData( $data );
				$this->publishEntry( $dbw, $entry );
			}
		}
		return $loggedIDs;
	}

	/**
	 * @param array[] $centralLogRows
	 * @param IDatabase $fdb
	 * @return int[]
	 */
	private function insertGlobalLogEntries( array $centralLogRows, IDatabase $fdb ): array {
		$this->varManager->computeDBVars( $this->vars );
		$globalVarDump = $this->varBlobStore->storeVarDump( $this->vars, true );
		foreach ( $centralLogRows as $index => $data ) {
			$centralLogRows[$index]['afl_var_dump'] = $globalVarDump;
		}

		$loggedIDs = [];
		foreach ( $centralLogRows as $row ) {
			$fdb->insert( 'abuse_filter_log', $row, __METHOD__ );
			$loggedIDs[] = $fdb->insertId();
		}
		return $loggedIDs;
	}

	/**
	 * Like ManualLogEntry::publish, but doesn't require an ID (which we don't have) and skips the
	 * tagging part
	 *
	 * @param IDatabase $dbw To cancel the callback if the log insertion fails
	 * @param ManualLogEntry $entry
	 */
	private function publishEntry( IDatabase $dbw, ManualLogEntry $entry ): void {
		DeferredUpdates::addCallableUpdate(
			function () use ( $entry ) {
				$rc = $entry->getRecentChange();
				$to = $this->options->get( 'AbuseFilterNotifications' );

				if ( $to === 'rc' || $to === 'rcandudp' ) {
					$rc->save( $rc::SEND_NONE );
				}
				if ( $to === 'udp' || $to === 'rcandudp' ) {
					$rc->notifyRCFeeds();
				}
			},
			DeferredUpdates::POSTSEND,
			$dbw
		);
	}

}
