<?php

namespace MediaWiki\Extension\AbuseFilter\Special;

use DifferenceEngine;
use ExtensionRegistry;
use HTMLForm;
use InvalidArgumentException;
use ManualLogEntry;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\CentralDBNotAvailableException;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\Filter\FilterNotFoundException;
use MediaWiki\Extension\AbuseFilter\GlobalNameUtils;
use MediaWiki\Extension\AbuseFilter\Pager\AbuseLogPager;
use MediaWiki\Extension\AbuseFilter\SpecsFormatter;
use MediaWiki\Extension\AbuseFilter\Variables\UnsetVariableException;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesFormatter;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Extension\AbuseFilter\View\HideAbuseLog;
use MediaWiki\Html\Html;
use MediaWiki\Html\ListToggle;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\WikiMap\WikiMap;
use OOUI\ButtonInputWidget;
use stdClass;
use Wikimedia\Rdbms\LBFactory;
use Xml;

class SpecialAbuseLog extends AbuseFilterSpecialPage {
	public const PAGE_NAME = 'AbuseLog';

	/** Visible entry */
	public const VISIBILITY_VISIBLE = 'visible';
	/** Explicitly hidden entry */
	public const VISIBILITY_HIDDEN = 'hidden';
	/** Visible entry but the associated revision is hidden */
	public const VISIBILITY_HIDDEN_IMPLICIT = 'implicit';

	/**
	 * @var string|null The user whose AbuseLog entries are being searched
	 */
	private $mSearchUser;

	/**
	 * @var string The start time of the search period
	 */
	private $mSearchPeriodStart;

	/**
	 * @var string The end time of the search period
	 */
	private $mSearchPeriodEnd;

	/**
	 * @var string The page of which AbuseLog entries are being searched
	 */
	private $mSearchTitle;

	/**
	 * @var string The action performed by the user
	 */
	private $mSearchAction;

	/**
	 * @var string The action taken by AbuseFilter
	 */
	private $mSearchActionTaken;

	/**
	 * @var string The wiki name where we're performing the search
	 */
	private $mSearchWiki;

	/**
	 * @var string|null The filter IDs we're looking for. Either a single one, or a pipe-separated list
	 */
	private $mSearchFilter;

	/**
	 * @var string The visibility of entries we're interested in
	 */
	private $mSearchEntries;

	/**
	 * @var string The impact of the user action, i.e. if the change has been saved
	 */
	private $mSearchImpact;

	/** @var string|null The filter group to search, as defined in $wgAbuseFilterValidGroups */
	private $mSearchGroup;

	/** @var LBFactory */
	private $lbFactory;

	/** @var LinkBatchFactory */
	private $linkBatchFactory;

	/** @var PermissionManager */
	private $permissionManager;

	/** @var UserIdentityLookup */
	private $userIdentityLookup;

	/** @var ConsequencesRegistry */
	private $consequencesRegistry;

	/** @var VariablesBlobStore */
	private $varBlobStore;

	/** @var SpecsFormatter */
	private $specsFormatter;

	/** @var VariablesFormatter */
	private $variablesFormatter;

	/** @var VariablesManager */
	private $varManager;

	/**
	 * @param LBFactory $lbFactory
	 * @param LinkBatchFactory $linkBatchFactory
	 * @param PermissionManager $permissionManager
	 * @param UserIdentityLookup $userIdentityLookup
	 * @param AbuseFilterPermissionManager $afPermissionManager
	 * @param ConsequencesRegistry $consequencesRegistry
	 * @param VariablesBlobStore $varBlobStore
	 * @param SpecsFormatter $specsFormatter
	 * @param VariablesFormatter $variablesFormatter
	 * @param VariablesManager $varManager
	 */
	public function __construct(
		LBFactory $lbFactory,
		LinkBatchFactory $linkBatchFactory,
		PermissionManager $permissionManager,
		UserIdentityLookup $userIdentityLookup,
		AbuseFilterPermissionManager $afPermissionManager,
		ConsequencesRegistry $consequencesRegistry,
		VariablesBlobStore $varBlobStore,
		SpecsFormatter $specsFormatter,
		VariablesFormatter $variablesFormatter,
		VariablesManager $varManager
	) {
		parent::__construct( self::PAGE_NAME, 'abusefilter-log', $afPermissionManager );
		$this->lbFactory = $lbFactory;
		$this->linkBatchFactory = $linkBatchFactory;
		$this->permissionManager = $permissionManager;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->consequencesRegistry = $consequencesRegistry;
		$this->varBlobStore = $varBlobStore;
		$this->specsFormatter = $specsFormatter;
		$this->specsFormatter->setMessageLocalizer( $this );
		$this->variablesFormatter = $variablesFormatter;
		$this->variablesFormatter->setMessageLocalizer( $this );
		$this->varManager = $varManager;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'changes';
	}

	/**
	 * Main routine
	 *
	 * $parameter string is converted into the $args array, which can come in
	 * three shapes:
	 *
	 * An array of size 2: only if the URL is like Special:AbuseLog/private/id
	 * where id is the log identifier. In this case, the private details of the
	 * log (e.g. IP address) will be shown.
	 *
	 * An array of size 1: either the URL is like Special:AbuseLog/id where
	 * the id is log identifier, in which case the details of the log except for
	 * private bits (e.g. IP address) are shown, or Special:AbuseLog/hide for hiding entries,
	 * or the URL is incomplete as in Special:AbuseLog/private (without specifying id),
	 * in which case a warning is shown to the user
	 *
	 * An array of size 0 when URL is like Special:AbuseLog or an array of size
	 * 1 when the URL is like Special:AbuseFilter/ (i.e. without anything after
	 * the slash). Otherwise, the abuse logs are shown as a list, with a search form above the list.
	 *
	 * @param string|null $parameter URL parameters
	 */
	public function execute( $parameter ) {
		$out = $this->getOutput();

		$this->addNavigationLinks( 'log' );

		$this->setHeaders();
		$this->addHelpLink( 'Extension:AbuseFilter' );
		$this->loadParameters();

		$out->disableClientCache();

		$out->addModuleStyles( 'ext.abuseFilter' );

		$this->checkPermissions();

		$args = $parameter !== null ? explode( '/', $parameter ) : [];

		if ( count( $args ) === 2 && $args[0] === 'private' ) {
			$this->showPrivateDetails( (int)$args[1] );
		} elseif ( count( $args ) === 1 && $args[0] !== '' ) {
			if ( $args[0] === 'private' ) {
				$out->addWikiMsg( 'abusefilter-invalid-request-noid' );
			} elseif ( $args[0] === 'hide' ) {
				$this->showHideView();
			} else {
				$this->showDetails( $args[0] );
			}
		} else {
			$this->outputHeader( 'abusefilter-log-summary' );
			$this->searchForm();
			$this->showList();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getShortDescription( string $path = '' ): string {
		return $this->msg( 'abusefilter-topnav-log' )->text();
	}

	/**
	 * Loads parameters from request
	 */
	public function loadParameters() {
		$request = $this->getRequest();

		$searchUsername = trim( $request->getText( 'wpSearchUser' ) );
		$userTitle = Title::newFromText( $searchUsername, NS_USER );
		$this->mSearchUser = $userTitle ? $userTitle->getText() : null;
		if ( $this->getConfig()->get( 'AbuseFilterIsCentral' ) ) {
			$this->mSearchWiki = $request->getText( 'wpSearchWiki' );
		}

		$this->mSearchPeriodStart = $request->getText( 'wpSearchPeriodStart' );
		$this->mSearchPeriodEnd = $request->getText( 'wpSearchPeriodEnd' );
		$this->mSearchTitle = $request->getText( 'wpSearchTitle' );

		$this->mSearchFilter = null;
		$this->mSearchGroup = null;
		if ( $this->afPermissionManager->canSeeLogDetails( $this->getAuthority() ) ) {
			$this->mSearchFilter = $request->getText( 'wpSearchFilter' );
			if ( count( $this->getConfig()->get( 'AbuseFilterValidGroups' ) ) > 1 ) {
				$this->mSearchGroup = $request->getText( 'wpSearchGroup' );
			}
		}

		$this->mSearchAction = $request->getText( 'wpSearchAction' );
		$this->mSearchActionTaken = $request->getText( 'wpSearchActionTaken' );
		$this->mSearchEntries = $request->getText( 'wpSearchEntries' );
		$this->mSearchImpact = $request->getText( 'wpSearchImpact' );
	}

	/**
	 * @return string[]
	 */
	private function getAllFilterableActions() {
		return [
			'edit',
			'move',
			'upload',
			'stashupload',
			'delete',
			'createaccount',
			'autocreateaccount',
		];
	}

	/**
	 * Builds the search form
	 */
	public function searchForm() {
		$performer = $this->getAuthority();
		$formDescriptor = [
			'SearchUser' => [
				'label-message' => 'abusefilter-log-search-user',
				'type' => 'user',
				'ipallowed' => true,
				'default' => $this->mSearchUser,
			],
			'SearchPeriodStart' => [
				'label-message' => 'abusefilter-test-period-start',
				'type' => 'datetime',
				'default' => $this->mSearchPeriodStart
			],
			'SearchPeriodEnd' => [
				'label-message' => 'abusefilter-test-period-end',
				'type' => 'datetime',
				'default' => $this->mSearchPeriodEnd
			],
			'SearchTitle' => [
				'label-message' => 'abusefilter-log-search-title',
				'type' => 'title',
				'interwiki' => false,
				'default' => $this->mSearchTitle,
				'required' => false
			],
			'SearchImpact' => [
				'label-message' => 'abusefilter-log-search-impact',
				'type' => 'select',
				'options' => [
					$this->msg( 'abusefilter-log-search-impact-all' )->text() => 0,
					$this->msg( 'abusefilter-log-search-impact-saved' )->text() => 1,
					$this->msg( 'abusefilter-log-search-impact-not-saved' )->text() => 2,
				],
			],
		];
		$filterableActions = $this->getAllFilterableActions();
		$actions = array_combine( $filterableActions, $filterableActions );
		ksort( $actions );
		$actions = array_merge(
			[ $this->msg( 'abusefilter-log-search-action-any' )->text() => 'any' ],
			$actions,
			[ $this->msg( 'abusefilter-log-search-action-other' )->text() => 'other' ]
		);
		$formDescriptor['SearchAction'] = [
			'label-message' => 'abusefilter-log-search-action-label',
			'type' => 'select',
			'options' => $actions,
			'default' => 'any',
		];
		$options = [];
		foreach ( $this->consequencesRegistry->getAllActionNames() as $action ) {
			$key = $this->specsFormatter->getActionDisplay( $action );
			$options[$key] = $action;
		}
		ksort( $options );
		$options = array_merge(
			[ $this->msg( 'abusefilter-log-search-action-taken-any' )->text() => '' ],
			$options,
			[ $this->msg( 'abusefilter-log-noactions-filter' )->text() => 'noactions' ]
		);
		$formDescriptor['SearchActionTaken'] = [
			'label-message' => 'abusefilter-log-search-action-taken-label',
			'type' => 'select',
			'options' => $options,
		];
		if ( $this->afPermissionManager->canSeeHiddenLogEntries( $performer ) ) {
			$formDescriptor['SearchEntries'] = [
				'type' => 'select',
				'label-message' => 'abusefilter-log-search-entries-label',
				'options' => [
					$this->msg( 'abusefilter-log-search-entries-all' )->text() => 0,
					$this->msg( 'abusefilter-log-search-entries-hidden' )->text() => 1,
					$this->msg( 'abusefilter-log-search-entries-visible' )->text() => 2,
				],
			];
		}

		if ( $this->afPermissionManager->canSeeLogDetails( $performer ) ) {
			$groups = $this->getConfig()->get( 'AbuseFilterValidGroups' );
			if ( count( $groups ) > 1 ) {
				$options = array_merge(
					[ $this->msg( 'abusefilter-log-search-group-any' )->text() => 0 ],
					array_combine( $groups, $groups )
				);
				$formDescriptor['SearchGroup'] = [
					'label-message' => 'abusefilter-log-search-group',
					'type' => 'select',
					'options' => $options
				];
			}
			$helpmsg = $this->getConfig()->get( 'AbuseFilterIsCentral' )
				? $this->msg( 'abusefilter-log-search-filter-help-central' )->escaped()
				: $this->msg( 'abusefilter-log-search-filter-help' )
					->params( GlobalNameUtils::GLOBAL_FILTER_PREFIX )->escaped();
			$formDescriptor['SearchFilter'] = [
				'label-message' => 'abusefilter-log-search-filter',
				'type' => 'text',
				'default' => $this->mSearchFilter,
				'help' => $helpmsg
			];
		}
		if ( $this->getConfig()->get( 'AbuseFilterIsCentral' ) ) {
			// @todo Add free form input for wiki name. Would be nice to generate
			// a select with unique names in the db at some point.
			$formDescriptor['SearchWiki'] = [
				'label-message' => 'abusefilter-log-search-wiki',
				'type' => 'text',
				'default' => $this->mSearchWiki,
			];
		}

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setWrapperLegendMsg( 'abusefilter-log-search' )
			->setSubmitTextMsg( 'abusefilter-log-search-submit' )
			->setMethod( 'get' )
			->setCollapsibleOptions( true )
			->prepareForm()
			->displayForm( false );
	}

	private function showHideView() {
		$view = new HideAbuseLog(
			$this->lbFactory,
			$this->afPermissionManager,
			$this->getContext(),
			$this->getLinkRenderer(),
			self::PAGE_NAME
		);
		$view->show();
	}

	/**
	 * Shows the results list
	 */
	public function showList() {
		$out = $this->getOutput();
		$performer = $this->getAuthority();

		// Generate conditions list.
		$conds = [];

		if ( $this->mSearchUser !== null ) {
			$searchedUser = $this->userIdentityLookup->getUserIdentityByName( $this->mSearchUser );

			if ( !$searchedUser ) {
				$conds['afl_user'] = 0;
				$conds['afl_user_text'] = $this->mSearchUser;
			} else {
				$conds['afl_user'] = $searchedUser->getId();
				$conds['afl_user_text'] = $searchedUser->getName();
			}
		}

		$dbr = $this->lbFactory->getReplicaDatabase();
		if ( $this->mSearchPeriodStart ) {
			$conds[] = 'afl_timestamp >= ' .
				$dbr->addQuotes( $dbr->timestamp( strtotime( $this->mSearchPeriodStart ) ) );
		}

		if ( $this->mSearchPeriodEnd ) {
			$conds[] = 'afl_timestamp <= ' .
				$dbr->addQuotes( $dbr->timestamp( strtotime( $this->mSearchPeriodEnd ) ) );
		}

		if ( $this->mSearchWiki ) {
			if ( $this->mSearchWiki === WikiMap::getCurrentWikiDbDomain()->getId() ) {
				$conds['afl_wiki'] = null;
			} else {
				$conds['afl_wiki'] = $this->mSearchWiki;
			}
		}

		$groupFilters = [];
		if ( $this->mSearchGroup ) {
			$groupFilters = $dbr->selectFieldValues(
				'abuse_filter',
				'af_id',
				[ 'af_group' => $this->mSearchGroup ],
				__METHOD__
			);
		}

		$searchFilters = [];
		if ( $this->mSearchFilter ) {
			$rawFilters = array_map( 'trim', explode( '|', $this->mSearchFilter ) );
			// Map of [ [ id, global ], ... ]
			$filtersList = [];
			$foundInvalid = false;
			foreach ( $rawFilters as $filter ) {
				try {
					$filtersList[] = GlobalNameUtils::splitGlobalName( $filter );
				} catch ( InvalidArgumentException $e ) {
					$foundInvalid = true;
					continue;
				}
			}

			// if a filter is hidden, users who can't view private filters should
			// not be able to find log entries generated by it.
			if ( !$this->afPermissionManager->canViewPrivateFiltersLogs( $performer ) ) {
				$searchedForPrivate = false;
				foreach ( $filtersList as $index => $filterData ) {
					try {
						$filter = AbuseFilterServices::getFilterLookup()->getFilter( ...$filterData );
					} catch ( FilterNotFoundException $_ ) {
						unset( $filtersList[$index] );
						$foundInvalid = true;
						continue;
					}
					if ( $filter->isHidden() ) {
						unset( $filtersList[$index] );
						$searchedForPrivate = true;
					}
				}
				if ( $searchedForPrivate ) {
					$out->addWikiMsg( 'abusefilter-log-private-not-included' );
				}
			}

			if ( $foundInvalid ) {
				// @todo Tell what the invalid IDs are
				$out->addHTML(
					Html::rawElement(
						'p',
						[],
						Html::warningBox( $this->msg( 'abusefilter-log-invalid-filter' )->escaped() )
					)
				);
			}

			foreach ( $filtersList as $filterData ) {
				$searchFilters[] = GlobalNameUtils::buildGlobalName( ...$filterData );
			}
		}

		$searchIDs = null;
		if ( $this->mSearchGroup && !$this->mSearchFilter ) {
			$searchIDs = $groupFilters;
		} elseif ( !$this->mSearchGroup && $this->mSearchFilter ) {
			$searchIDs = $searchFilters;
		} elseif ( $this->mSearchGroup && $this->mSearchFilter ) {
			$searchIDs = array_intersect( $groupFilters, $searchFilters );
		}

		if ( $searchIDs !== null ) {
			if ( !count( $searchIDs ) ) {
				$out->addWikiMsg( 'abusefilter-log-noresults' );
				return;
			}

			$filterConds = [ 'local' => [], 'global' => [] ];
			foreach ( $searchIDs as $filter ) {
				[ $filterID, $isGlobal ] = GlobalNameUtils::splitGlobalName( $filter );
				$key = $isGlobal ? 'global' : 'local';
				$filterConds[$key][] = $filterID;
			}
			$filterWhere = [];
			if ( $filterConds['local'] ) {
				$filterWhere[] = $dbr->makeList(
					[ 'afl_global' => 0, 'afl_filter_id' => $filterConds['local'] ],
					LIST_AND
				);
			}
			if ( $filterConds['global'] ) {
				$filterWhere[] = $dbr->makeList(
					[ 'afl_global' => 1, 'afl_filter_id' => $filterConds['global'] ],
					LIST_AND
				);
			}
			$conds[] = $dbr->makeList( $filterWhere, LIST_OR );
		}

		$searchTitle = Title::newFromText( $this->mSearchTitle );
		if ( $searchTitle ) {
			$conds['afl_namespace'] = $searchTitle->getNamespace();
			$conds['afl_title'] = $searchTitle->getDBkey();
		}

		if ( $this->afPermissionManager->canSeeHiddenLogEntries( $performer ) ) {
			if ( $this->mSearchEntries === '1' ) {
				$conds['afl_deleted'] = 1;
			} elseif ( $this->mSearchEntries === '2' ) {
				$conds['afl_deleted'] = 0;
			}
		}

		if ( in_array( $this->mSearchImpact, [ '1', '2' ] ) ) {
			$unsuccessfulActionConds = 'afl_rev_id IS NULL';
			if ( $this->mSearchImpact === '1' ) {
				$conds[] = "NOT ( $unsuccessfulActionConds )";
			} else {
				$conds[] = $unsuccessfulActionConds;
			}
		}

		if ( $this->mSearchActionTaken ) {
			if ( in_array( $this->mSearchActionTaken, $this->consequencesRegistry->getAllActionNames() ) ) {
				$list = [ 'afl_actions' => $this->mSearchActionTaken ];
				$list[] = 'afl_actions' . $dbr->buildLike(
					$this->mSearchActionTaken, ',', $dbr->anyString() );
				$list[] = 'afl_actions' . $dbr->buildLike(
					$dbr->anyString(), ',', $this->mSearchActionTaken );
				$list[] = 'afl_actions' . $dbr->buildLike(
					$dbr->anyString(),
					',', $this->mSearchActionTaken, ',',
					$dbr->anyString()
				);
				$conds[] = $dbr->makeList( $list, LIST_OR );
			} elseif ( $this->mSearchActionTaken === 'noactions' ) {
				$conds['afl_actions'] = '';
			}
		}

		if ( $this->mSearchAction ) {
			$filterableActions = $this->getAllFilterableActions();
			if ( in_array( $this->mSearchAction, $filterableActions ) ) {
				$conds['afl_action'] = $this->mSearchAction;
			} elseif ( $this->mSearchAction === 'other' ) {
				$list = $dbr->makeList( [ 'afl_action' => $filterableActions ], LIST_OR );
				$conds[] = "NOT ( $list )";
			}
		}

		$pager = new AbuseLogPager(
			$this->getContext(),
			$this->getLinkRenderer(),
			$conds,
			$this->linkBatchFactory,
			$this->permissionManager,
			$this->afPermissionManager,
			$this->getName()
		);
		$pager->doQuery();
		$result = $pager->getResult();

		$form = Xml::tags(
			'form',
			[
				'method' => 'GET',
				'action' => $this->getPageTitle( 'hide' )->getLocalURL()
			],
			$this->getDeleteButton() . $this->getListToggle() .
				Xml::tags( 'ul', [ 'class' => 'plainlinks' ], $pager->getBody() ) .
				$this->getListToggle() . $this->getDeleteButton()
		);

		if ( $result && $result->numRows() !== 0 ) {
			$out->addModuleStyles( 'mediawiki.interface.helpers.styles' );
			$out->addHTML( $pager->getNavigationBar() . $form . $pager->getNavigationBar() );
		} else {
			$out->addWikiMsg( 'abusefilter-log-noresults' );
		}
	}

	/**
	 * Returns the HTML for a button to hide selected entries
	 *
	 * @return string|ButtonInputWidget
	 */
	private function getDeleteButton() {
		if ( !$this->afPermissionManager->canHideAbuseLog( $this->getAuthority() ) ) {
			return '';
		}
		return new ButtonInputWidget( [
			'label' => $this->msg( 'abusefilter-log-hide-entries' )->text(),
			'type' => 'submit'
		] );
	}

	/**
	 * Get the All / Invert / None options provided by
	 * ToggleList.php to mass select the checkboxes.
	 *
	 * @return string
	 */
	private function getListToggle() {
		if ( !$this->afPermissionManager->canHideAbuseLog( $this->getUser() ) ) {
			return '';
		}
		return ( new ListToggle( $this->getOutput() ) )->getHtml();
	}

	/**
	 * @param string|int $id
	 * @suppress SecurityCheck-SQLInjection
	 */
	public function showDetails( $id ) {
		$out = $this->getOutput();
		$performer = $this->getAuthority();

		$pager = new AbuseLogPager(
			$this->getContext(),
			$this->getLinkRenderer(),
			[],
			$this->linkBatchFactory,
			$this->permissionManager,
			$this->afPermissionManager,
			$this->getName()
		);

		[
			'tables' => $tables,
			'fields' => $fields,
			'join_conds' => $join_conds,
		] = $pager->getQueryInfo();

		$dbr = $this->lbFactory->getReplicaDatabase();
		$row = $dbr->selectRow(
			$tables,
			$fields,
			[ 'afl_id' => $id ],
			__METHOD__,
			[],
			$join_conds
		);

		$error = null;
		if ( !$row ) {
			$error = 'abusefilter-log-nonexistent';
		} else {
			$filterID = $row->afl_filter_id;
			$global = $row->afl_global;

			if ( $global ) {
				try {
					$filter_hidden = AbuseFilterServices::getFilterLookup()->getFilter( $filterID, $global )
						->isHidden();
				} catch ( CentralDBNotAvailableException $_ ) {
					// Conservatively assume that it's hidden, like in formatRow
					$filter_hidden = true;
				}
			} else {
				$filter_hidden = $row->af_hidden;
			}

			if ( !$this->afPermissionManager->canSeeLogDetailsForFilter( $performer, $filter_hidden ) ) {
				$error = 'abusefilter-log-cannot-see-details';
			} else {
				$visibility = self::getEntryVisibilityForUser( $row, $performer, $this->afPermissionManager );
				if ( $visibility === self::VISIBILITY_HIDDEN ) {
					$error = 'abusefilter-log-details-hidden';
				} elseif ( $visibility === self::VISIBILITY_HIDDEN_IMPLICIT ) {
					$error = 'abusefilter-log-details-hidden-implicit';
				}
			}
		}

		if ( $error ) {
			$out->addWikiMsg( $error );
			return;
		}

		$output = Xml::element(
			'legend',
			null,
			$this->msg( 'abusefilter-log-details-legend' )
				->numParams( $id )
				->text()
		);
		$output .= Xml::tags( 'p', null, $pager->doFormatRow( $row, false ) );

		// Load data
		$vars = $this->varBlobStore->loadVarDump( $row->afl_var_dump );
		$out->addJsConfigVars( 'wgAbuseFilterVariables', $this->varManager->dumpAllVars( $vars, true ) );
		$out->addModuleStyles( 'mediawiki.interface.helpers.styles' );

		// Diff, if available
		if ( $row->afl_action === 'edit' ) {
			// Guard for exception because these variables may be unset in case of data corruption (T264513)
			// No need to lazy-load as these come from a DB dump.
			try {
				$old_wikitext = $vars->getComputedVariable( 'old_wikitext' )->toString();
			} catch ( UnsetVariableException $_ ) {
				$old_wikitext = '';
			}
			try {
				$new_wikitext = $vars->getComputedVariable( 'new_wikitext' )->toString();
			} catch ( UnsetVariableException $_ ) {
				$new_wikitext = '';
			}

			$diffEngine = new DifferenceEngine( $this->getContext() );

			$diffEngine->showDiffStyle();

			$formattedDiff = $diffEngine->addHeader(
				$diffEngine->generateTextDiffBody( $old_wikitext, $new_wikitext ),
				'', ''
			);

			$output .=
				Xml::tags(
					'h3',
					null,
					$this->msg( 'abusefilter-log-details-diff' )->parse()
				);

			$output .= $formattedDiff;
		}

		$output .= Xml::element( 'h3', null, $this->msg( 'abusefilter-log-details-vars' )->text() );

		// Build a table.
		$output .= $this->variablesFormatter->buildVarDumpTable( $vars );

		if ( $this->afPermissionManager->canSeePrivateDetails( $performer ) ) {
			$formDescriptor = [
				'Reason' => [
					'label-message' => 'abusefilter-view-privatedetails-reason',
					'type' => 'text',
					'size' => 45,
				],
			];

			$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
			$htmlForm->setTitle( $this->getPageTitle( 'private/' . $id ) )
				->setWrapperLegendMsg( 'abusefilter-view-privatedetails-legend' )
				->setSubmitTextMsg( 'abusefilter-view-privatedetails-submit' )
				->prepareForm();

			$output .= $htmlForm->getHTML( false );
		}

		$out->addHTML( Xml::tags( 'fieldset', null, $output ) );
	}

	/**
	 * Helper function to select a row with private details and some more context
	 * for an AbuseLog entry.
	 * @todo Create a service for this
	 *
	 * @param Authority $authority The user who's trying to view the row
	 * @param int $id The ID of the log entry
	 * @return Status A status object with the requested row stored in the value property,
	 *  or an error and no row.
	 */
	public static function getPrivateDetailsRow( Authority $authority, $id ) {
		$afPermManager = AbuseFilterServices::getPermissionManager();
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getReplicaDatabase();

		$row = $dbr->selectRow(
			[ 'abuse_filter_log', 'abuse_filter' ],
			[ 'afl_id', 'afl_user_text', 'afl_filter_id', 'afl_global', 'afl_timestamp', 'afl_ip',
				'af_id', 'af_public_comments', 'af_hidden' ],
			[ 'afl_id' => $id ],
			__METHOD__,
			[],
			[ 'abuse_filter' => [ 'LEFT JOIN', [ 'af_id=afl_filter_id', 'afl_global' => 0 ] ] ]
		);

		$status = Status::newGood();
		if ( !$row ) {
			$status->fatal( 'abusefilter-log-nonexistent' );
			return $status;
		}

		$filterID = $row->afl_filter_id;
		$global = $row->afl_global;

		if ( $global ) {
			$lookup = AbuseFilterServices::getFilterLookup();
			$filterHidden = $lookup->getFilter( $filterID, $global )->isHidden();
		} else {
			$filterHidden = $row->af_hidden;
		}

		if ( !$afPermManager->canSeeLogDetailsForFilter( $authority, $filterHidden ) ) {
			$status->fatal( 'abusefilter-log-cannot-see-details' );
			return $status;
		}
		$status->setResult( true, $row );
		return $status;
	}

	/**
	 * Builds an HTML table with the private details for a given abuseLog entry.
	 *
	 * @param stdClass $row The row, as returned by self::getPrivateDetailsRow()
	 * @return string The HTML output
	 */
	private function buildPrivateDetailsTable( $row ) {
		$output = Xml::element(
			'legend',
			null,
			$this->msg( 'abusefilter-log-details-privatedetails' )->text()
		);

		$header =
			Xml::element( 'th', null, $this->msg( 'abusefilter-log-details-var' )->text() ) .
			Xml::element( 'th', null, $this->msg( 'abusefilter-log-details-val' )->text() );

		$output .=
			Xml::openElement( 'table',
				[
					'class' => 'wikitable mw-abuselog-private',
					'style' => 'width: 80%;'
				]
			) .
			Xml::openElement( 'tbody' );
		$output .= $header;

		// Log ID
		$linkRenderer = $this->getLinkRenderer();
		$output .=
			Xml::tags( 'tr', null,
				Xml::element( 'td',
					[ 'style' => 'width: 30%;' ],
					$this->msg( 'abusefilter-log-details-id' )->text()
				) .
				Xml::openElement( 'td' ) .
				$linkRenderer->makeKnownLink(
					$this->getPageTitle( $row->afl_id ),
					$this->getLanguage()->formatNum( $row->afl_id )
				) .
				Xml::closeElement( 'td' )
			);

		// Timestamp
		$output .=
			Xml::tags( 'tr', null,
				Xml::element( 'td',
					[ 'style' => 'width: 30%;' ],
					$this->msg( 'abusefilter-edit-builder-vars-timestamp-expanded' )->text()
				) .
				Xml::element( 'td',
					null,
					$this->getLanguage()->userTimeAndDate( $row->afl_timestamp, $this->getUser() )
				)
			);

		// User
		$output .=
			Xml::tags( 'tr', null,
				Xml::element( 'td',
					[ 'style' => 'width: 30%;' ],
					$this->msg( 'abusefilter-edit-builder-vars-user-name' )->text()
				) .
				Xml::element( 'td',
					null,
					$row->afl_user_text
				)
			);

		// Filter ID
		$output .=
			Xml::tags( 'tr', null,
				Xml::element( 'td',
					[ 'style' => 'width: 30%;' ],
					$this->msg( 'abusefilter-list-id' )->text()
				) .
				Xml::openElement( 'td' ) .
				$linkRenderer->makeKnownLink(
					SpecialPage::getTitleFor( 'AbuseFilter', $row->af_id ),
					$this->getLanguage()->formatNum( $row->af_id )
				) .
				Xml::closeElement( 'td' )
			);

		// Filter description
		$output .=
			Xml::tags( 'tr', null,
				Xml::element( 'td',
					[ 'style' => 'width: 30%;' ],
					$this->msg( 'abusefilter-list-public' )->text()
				) .
				Xml::element( 'td',
					null,
					$row->af_public_comments
				)
			);

		// IP address
		if ( $row->afl_ip !== '' ) {
			if ( ExtensionRegistry::getInstance()->isLoaded( 'CheckUser' ) &&
				$this->permissionManager->userHasRight( $this->getUser(), 'checkuser' )
			) {
				$CULink = '&nbsp;&middot;&nbsp;' . $linkRenderer->makeKnownLink(
					SpecialPage::getTitleFor(
						'CheckUser',
						$row->afl_ip
					),
					$this->msg( 'abusefilter-log-details-checkuser' )->text()
				);
			} else {
				$CULink = '';
			}
			$output .=
				Xml::tags( 'tr', null,
					Xml::element( 'td',
						[ 'style' => 'width: 30%;' ],
						$this->msg( 'abusefilter-log-details-ip' )->text()
					) .
					Xml::tags(
						'td',
						null,
						self::getUserLinks( 0, $row->afl_ip ) . $CULink
					)
				);
		} else {
			$output .=
				Xml::tags( 'tr', null,
					Xml::element( 'td',
						[ 'style' => 'width: 30%;' ],
						$this->msg( 'abusefilter-log-details-ip' )->text()
					) .
					Xml::element(
						'td',
						null,
						$this->msg( 'abusefilter-log-ip-not-available' )->text()
					)
				);
		}

		$output .= Xml::closeElement( 'tbody' ) . Xml::closeElement( 'table' );

		return Xml::tags( 'fieldset', null, $output );
	}

	/**
	 * @param int $id
	 * @return void
	 */
	public function showPrivateDetails( $id ) {
		$out = $this->getOutput();
		$user = $this->getUser();

		if ( !$this->afPermissionManager->canSeePrivateDetails( $user ) ) {
			$out->addWikiMsg( 'abusefilter-log-cannot-see-privatedetails' );

			return;
		}
		$request = $this->getRequest();

		// Make sure it is a valid request
		$token = $request->getVal( 'wpEditToken' );
		if ( !$request->wasPosted() || !$user->matchEditToken( $token ) ) {
			$out->addHTML(
				Xml::tags(
					'p',
					null,
					Html::errorBox( $this->msg( 'abusefilter-invalid-request' )->params( $id )->parse() )
				)
			);

			return;
		}

		$reason = $request->getText( 'wpReason' );
		if ( !self::checkPrivateDetailsAccessReason( $reason ) ) {
			$out->addWikiMsg( 'abusefilter-noreason' );
			$this->showDetails( $id );
			return;
		}

		$status = self::getPrivateDetailsRow( $user, $id );
		if ( !$status->isGood() ) {
			$out->addWikiMsg( $status->getErrors()[0] );
			return;
		}
		$row = $status->getValue();

		// Log accessing private details
		if ( $this->getConfig()->get( 'AbuseFilterLogPrivateDetailsAccess' ) ) {
			self::addPrivateDetailsAccessLogEntry( $id, $reason, $user );
		}

		// Show private details (IP).
		$table = $this->buildPrivateDetailsTable( $row );
		$out->addHTML( $table );
	}

	/**
	 * If specifying a reason for viewing private details of abuse log is required
	 * then it makes sure that a reason is provided.
	 *
	 * @param string $reason
	 * @return bool
	 */
	public static function checkPrivateDetailsAccessReason( $reason ) {
		global $wgAbuseFilterPrivateDetailsForceReason;
		return ( !$wgAbuseFilterPrivateDetailsForceReason || strlen( $reason ) > 0 );
	}

	/**
	 * @param int $logID int The ID of the AbuseFilter log that was accessed
	 * @param string $reason The reason provided for accessing private details
	 * @param UserIdentity $userIdentity The user who accessed the private details
	 * @return void
	 */
	public static function addPrivateDetailsAccessLogEntry( $logID, $reason, UserIdentity $userIdentity ) {
		$target = self::getTitleFor( self::PAGE_NAME, (string)$logID );

		$logEntry = new ManualLogEntry( 'abusefilterprivatedetails', 'access' );
		$logEntry->setPerformer( $userIdentity );
		$logEntry->setTarget( $target );
		$logEntry->setParameters( [
			'4::logid' => $logID,
		] );
		$logEntry->setComment( $reason );

		$logEntry->insert();
	}

	/**
	 * @param int $userId
	 * @param string $userName
	 * @return string
	 */
	public static function getUserLinks( $userId, $userName ) {
		static $cache = [];

		if ( !isset( $cache[$userName][$userId] ) ) {
			$cache[$userName][$userId] = Linker::userLink( $userId, $userName ) .
				Linker::userToolLinks( $userId, $userName, true );
		}

		return $cache[$userName][$userId];
	}

	/**
	 * @param stdClass $row
	 * @param Authority $authority
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @return string One of the self::VISIBILITY_* constants
	 */
	public static function getEntryVisibilityForUser(
		stdClass $row,
		Authority $authority,
		AbuseFilterPermissionManager $afPermManager
	): string {
		if ( $row->afl_deleted && !$afPermManager->canSeeHiddenLogEntries( $authority ) ) {
			return self::VISIBILITY_HIDDEN;
		}
		if ( !$row->afl_rev_id ) {
			return self::VISIBILITY_VISIBLE;
		}
		$revRec = MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getRevisionById( (int)$row->afl_rev_id );
		if ( !$revRec || $revRec->getVisibility() === 0 ) {
			return self::VISIBILITY_VISIBLE;
		}
		return $revRec->audienceCan( RevisionRecord::SUPPRESSED_ALL, RevisionRecord::FOR_THIS_USER, $authority )
			? self::VISIBILITY_VISIBLE
			: self::VISIBILITY_HIDDEN_IMPLICIT;
	}
}
