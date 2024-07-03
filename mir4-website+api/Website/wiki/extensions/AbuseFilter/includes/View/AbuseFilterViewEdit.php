<?php

namespace MediaWiki\Extension\AbuseFilter\View;

use HtmlArmor;
use IContextSource;
use IDBAccessObject;
use LogicException;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\EditBox\EditBoxBuilderFactory;
use MediaWiki\Extension\AbuseFilter\Filter\Filter;
use MediaWiki\Extension\AbuseFilter\Filter\FilterNotFoundException;
use MediaWiki\Extension\AbuseFilter\Filter\FilterVersionNotFoundException;
use MediaWiki\Extension\AbuseFilter\Filter\MutableFilter;
use MediaWiki\Extension\AbuseFilter\FilterImporter;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\FilterProfiler;
use MediaWiki\Extension\AbuseFilter\FilterStore;
use MediaWiki\Extension\AbuseFilter\InvalidImportDataException;
use MediaWiki\Extension\AbuseFilter\SpecsFormatter;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MainConfigNames;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Specials\SpecialBlock;
use OOUI;
use UnexpectedValueException;
use Wikimedia\Rdbms\LBFactory;
use Xml;

class AbuseFilterViewEdit extends AbuseFilterView {
	/**
	 * @var int|null The history ID of the current filter
	 */
	private $historyID;
	/** @var int|string */
	private $filter;

	/** @var LBFactory */
	private $lbFactory;

	/** @var PermissionManager */
	private $permissionManager;

	/** @var FilterProfiler */
	private $filterProfiler;

	/** @var FilterLookup */
	private $filterLookup;

	/** @var FilterImporter */
	private $filterImporter;

	/** @var FilterStore */
	private $filterStore;

	/** @var EditBoxBuilderFactory */
	private $boxBuilderFactory;

	/** @var ConsequencesRegistry */
	private $consequencesRegistry;

	/** @var SpecsFormatter */
	private $specsFormatter;

	/**
	 * @param LBFactory $lbFactory
	 * @param PermissionManager $permissionManager
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param FilterProfiler $filterProfiler
	 * @param FilterLookup $filterLookup
	 * @param FilterImporter $filterImporter
	 * @param FilterStore $filterStore
	 * @param EditBoxBuilderFactory $boxBuilderFactory
	 * @param ConsequencesRegistry $consequencesRegistry
	 * @param SpecsFormatter $specsFormatter
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param string $basePageName
	 * @param array $params
	 */
	public function __construct(
		LBFactory $lbFactory,
		PermissionManager $permissionManager,
		AbuseFilterPermissionManager $afPermManager,
		FilterProfiler $filterProfiler,
		FilterLookup $filterLookup,
		FilterImporter $filterImporter,
		FilterStore $filterStore,
		EditBoxBuilderFactory $boxBuilderFactory,
		ConsequencesRegistry $consequencesRegistry,
		SpecsFormatter $specsFormatter,
		IContextSource $context,
		LinkRenderer $linkRenderer,
		string $basePageName,
		array $params
	) {
		parent::__construct( $afPermManager, $context, $linkRenderer, $basePageName, $params );
		$this->lbFactory = $lbFactory;
		$this->permissionManager = $permissionManager;
		$this->filterProfiler = $filterProfiler;
		$this->filterLookup = $filterLookup;
		$this->filterImporter = $filterImporter;
		$this->filterStore = $filterStore;
		$this->boxBuilderFactory = $boxBuilderFactory;
		$this->consequencesRegistry = $consequencesRegistry;
		$this->specsFormatter = $specsFormatter;
		$this->specsFormatter->setMessageLocalizer( $this->getContext() );
		$this->filter = $this->mParams['filter'];
		$this->historyID = $this->mParams['history'] ?? null;
	}

	/**
	 * Shows the page
	 */
	public function show() {
		$out = $this->getOutput();
		$out->enableOOUI();
		$request = $this->getRequest();
		$out->setPageTitleMsg( $this->msg( 'abusefilter-edit' ) );
		$out->addHelpLink( 'Extension:AbuseFilter/Rules format' );

		if ( !is_numeric( $this->filter ) && $this->filter !== null ) {
			$this->showUnrecoverableError( 'abusefilter-edit-badfilter' );
			return;
		}
		$filter = $this->filter ? (int)$this->filter : null;
		$history_id = $this->historyID;
		if ( $this->historyID ) {
			$dbr = $this->lbFactory->getReplicaDatabase();
			$lastID = (int)$dbr->selectField(
				'abuse_filter_history',
				'afh_id',
				[
					'afh_filter' => $filter,
				],
				__METHOD__,
				[ 'ORDER BY' => 'afh_id DESC' ]
			);
			// change $history_id to null if it's current version id
			if ( $lastID === $this->historyID ) {
				$history_id = null;
			}
		}

		// Add the default warning and disallow messages in a JS variable
		$this->exposeMessages();

		$canEdit = $this->afPermManager->canEdit( $this->getAuthority() );

		if ( $filter === null && !$canEdit ) {
			// Special case: Special:AbuseFilter/new is certainly not usable if the user cannot edit
			$this->showUnrecoverableError( 'abusefilter-edit-notallowed' );
			return;
		}

		$isImport = $request->wasPosted() && $request->getRawVal( 'wpImportText' ) !== null;

		if ( !$isImport && $request->wasPosted() && $canEdit ) {
			$this->attemptSave( $filter, $history_id );
			return;
		}

		if ( $isImport ) {
			$filterObj = $this->loadImportRequest();
			if ( $filterObj === null ) {
				$this->showUnrecoverableError( 'abusefilter-import-invalid-data' );
				return;
			}
		} else {
			// The request wasn't posted (i.e. just viewing the filter) or the user cannot edit
			try {
				$filterObj = $this->loadFromDatabase( $filter, $history_id );
			} catch ( FilterNotFoundException $_ ) {
				$filterObj = null;
			}
			if ( $filterObj === null || ( $history_id && (int)$filterObj->getID() !== $filter ) ) {
				$this->showUnrecoverableError( 'abusefilter-edit-badfilter' );
				return;
			}
		}

		$this->buildFilterEditor( null, $filterObj, $filter, $history_id );
	}

	/**
	 * @param int|null $filter The filter ID or null for a new filter
	 * @param int|null $history_id The history ID of the filter, if applicable. Otherwise null
	 */
	private function attemptSave( ?int $filter, $history_id ): void {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		[ $newFilter, $origFilter ] = $this->loadRequest( $filter );

		$tokenFilter = $filter === null ? 'new' : (string)$filter;
		$editToken = $request->getVal( 'wpEditToken' );
		$tokenMatches = $user->matchEditToken(
			$editToken, [ 'abusefilter', $tokenFilter ], $request );

		if ( !$tokenMatches ) {
			// Token invalid or expired while the page was open, warn to retry
			$error = Html::warningBox( $this->msg( 'abusefilter-edit-token-not-match' )->parseAsBlock() );
			$this->buildFilterEditor( $error, $newFilter, $filter, $history_id );
			return;
		}

		$status = $this->filterStore->saveFilter( $user, $filter, $newFilter, $origFilter );

		if ( !$status->isGood() ) {
			$errors = $status->getErrors();
			[ 'message' => $msg, 'params' => $params ] = $errors[0];
			if ( $status->isOK() ) {
				// Fixable error, show the editing interface
				$error = Html::errorBox( $this->msg( $msg, $params )->parseAsBlock() );
				$this->buildFilterEditor( $error, $newFilter, $filter, $history_id );
			} else {
				$this->showUnrecoverableError( $msg );
			}
		} elseif ( $status->getValue() === false ) {
			// No change
			$out->redirect( $this->getTitle()->getLocalURL() );
		} else {
			// Everything went fine!
			[ $new_id, $history_id ] = $status->getValue();
			$out->redirect(
				$this->getTitle()->getLocalURL(
					[
						'result' => 'success',
						'changedfilter' => $new_id,
						'changeid' => $history_id,
					]
				)
			);
		}
	}

	/**
	 * @param string $msgKey
	 */
	private function showUnrecoverableError( string $msgKey ): void {
		$out = $this->getOutput();

		$out->addHTML( Html::errorBox( $this->msg( $msgKey )->parseAsBlock() ) );
		$href = $this->getTitle()->getFullURL();
		$btn = new OOUI\ButtonWidget( [
			'label' => $this->msg( 'abusefilter-return' )->text(),
			'href' => $href
		] );
		$out->addHTML( $btn );
	}

	/**
	 * Builds the full form for edit filters, adding it to the OutputPage. This method can be called in 5 different
	 * situations, for a total of 5 different data sources for $filterObj and $actions:
	 *  1 - View the result of importing a filter
	 *  2 - Create a new filter
	 *  3 - Load the current version of an existing filter
	 *  4 - Load an old version of an existing filter
	 *  5 - Show the user input again if saving fails after one of the steps above
	 *
	 * @param string|null $error An error message to show above the filter box (HTML).
	 * @param Filter $filterObj
	 * @param int|null $filter The filter ID, or null for a new filter
	 * @param int|null $history_id The history ID of the filter, if applicable. Otherwise null
	 */
	private function buildFilterEditor(
		$error,
		Filter $filterObj,
		?int $filter,
		$history_id
	) {
		$out = $this->getOutput();
		$out->addJsConfigVars( 'isFilterEditor', true );
		$lang = $this->getLanguage();
		$user = $this->getUser();
		$actions = $filterObj->getActions();

		$isCreatingNewFilter = $filter === null;
		$out->addSubtitle( $this->msg(
			$isCreatingNewFilter ? 'abusefilter-edit-subtitle-new' : 'abusefilter-edit-subtitle',
			$isCreatingNewFilter ? $filter : $this->getLanguage()->formatNum( $filter ),
			$history_id
		)->parse() );

		// We use filterHidden() to ensure that if a public filter is made private, the public
		// revision is also hidden.
		if (
			( $filterObj->isHidden() || (
				$filter !== null && $this->filterLookup->getFilter( $filter, false )->isHidden() )
			) && !$this->afPermManager->canViewPrivateFilters( $user )
		) {
			$out->addHTML( $this->msg( 'abusefilter-edit-denied' )->escaped() );
			return;
		}

		if ( $isCreatingNewFilter ) {
			$title = $this->msg( 'abusefilter-add' );
		} elseif ( $this->afPermManager->canEditFilter( $user, $filterObj ) ) {
			$title = $this->msg( 'abusefilter-edit-specific' )
				->numParams( $this->filter )
				->params( $filterObj->getName() );
		} else {
			$title = $this->msg( 'abusefilter-view-specific' )
				->numParams( $this->filter )
				->params( $filterObj->getName() );
		}
		$out->setPageTitleMsg( $title );

		$readOnly = !$this->afPermManager->canEditFilter( $user, $filterObj );

		if ( $history_id ) {
			$oldWarningMessage = $readOnly
				? 'abusefilter-edit-oldwarning-view'
				: 'abusefilter-edit-oldwarning';
			$out->addWikiMsg( $oldWarningMessage, $history_id, $filter );
		}

		if ( $error !== null ) {
			$out->addHTML( $error );
		}

		$fields = [];

		$fields['abusefilter-edit-id'] =
			$isCreatingNewFilter ?
				$this->msg( 'abusefilter-edit-new' )->escaped() :
				htmlspecialchars( $lang->formatNum( (string)$filter ) );
		$fields['abusefilter-edit-description'] =
			new OOUI\TextInputWidget( [
				'name' => 'wpFilterDescription',
				'id' => 'mw-abusefilter-edit-description-input',
				'value' => $filterObj->getName(),
				'readOnly' => $readOnly
				]
			);

		$validGroups = $this->getConfig()->get( 'AbuseFilterValidGroups' );
		if ( count( $validGroups ) > 1 ) {
			$groupSelector =
				new OOUI\DropdownInputWidget( [
					'name' => 'wpFilterGroup',
					'id' => 'mw-abusefilter-edit-group-input',
					'value' => $filterObj->getGroup(),
					'disabled' => $readOnly
				] );

			$options = [];
			foreach ( $validGroups as $group ) {
				$options += [ $this->specsFormatter->nameGroup( $group ) => $group ];
			}

			$options = Xml::listDropdownOptionsOoui( $options );
			$groupSelector->setOptions( $options );

			$fields['abusefilter-edit-group'] = $groupSelector;
		}

		// Hit count display
		if ( $filterObj->getHitCount() !== null && $this->afPermManager->canSeeLogDetails( $user ) ) {
			$count_display = $this->msg( 'abusefilter-hitcount' )
				->numParams( $filterObj->getHitCount() )->text();
			$hitCount = $this->linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'AbuseLog' ),
				$count_display,
				[],
				[ 'wpSearchFilter' => $filterObj->getID() ]
			);

			$fields['abusefilter-edit-hitcount'] = $hitCount;
		}

		if ( $filter !== null && $filterObj->isEnabled() ) {
			// Statistics
			[
				'count' => $totalCount,
				'matches' => $matchesCount,
				'total-time' => $curTotalTime,
				'total-cond' => $curTotalConds,
			] = $this->filterProfiler->getFilterProfile( $filter );

			if ( $totalCount > 0 ) {
				$matchesPercent = round( 100 * $matchesCount / $totalCount, 2 );
				$avgTime = round( $curTotalTime / $totalCount, 2 );
				$avgCond = round( $curTotalConds / $totalCount, 1 );

				$fields['abusefilter-edit-status-label'] = $this->msg( 'abusefilter-edit-status' )
					->numParams( $totalCount, $matchesCount, $matchesPercent, $avgTime, $avgCond )
					->parse();
			}
		}

		$boxBuilder = $this->boxBuilderFactory->newEditBoxBuilder( $this, $user, $out );

		$fields['abusefilter-edit-rules'] = $boxBuilder->buildEditBox(
			$filterObj->getRules(),
			true
		);
		$fields['abusefilter-edit-notes'] =
			new OOUI\MultilineTextInputWidget( [
				'name' => 'wpFilterNotes',
				'value' => $filterObj->getComments() . "\n",
				'rows' => 15,
				'readOnly' => $readOnly,
				'id' => 'mw-abusefilter-notes-editor'
			] );

		// Build checkboxes
		$checkboxes = [ 'hidden', 'enabled', 'deleted' ];
		$flags = '';

		if ( $this->getConfig()->get( 'AbuseFilterIsCentral' ) ) {
			$checkboxes[] = 'global';
		}

		if ( $filterObj->isThrottled() ) {
			$throttledActionNames = array_intersect(
				$filterObj->getActionsNames(),
				$this->consequencesRegistry->getDangerousActionNames()
			);

			if ( $throttledActionNames ) {
				$throttledActionsLocalized = [];
				foreach ( $throttledActionNames as $actionName ) {
					$throttledActionsLocalized[] = $this->specsFormatter->getActionMessage( $actionName )->text();
				}

				$throttledMsg = $this->msg( 'abusefilter-edit-throttled-warning' )
					->plaintextParams( $lang->commaList( $throttledActionsLocalized ) )
					->params( count( $throttledActionsLocalized ) )
					->parseAsBlock();
			} else {
				$throttledMsg = $this->msg( 'abusefilter-edit-throttled-warning-no-actions' )
					->parseAsBlock();
			}
			$flags .= Html::warningBox( $throttledMsg );
		}

		foreach ( $checkboxes as $checkboxId ) {
			// Messages that can be used here:
			// * abusefilter-edit-enabled
			// * abusefilter-edit-deleted
			// * abusefilter-edit-hidden
			// * abusefilter-edit-global
			$message = "abusefilter-edit-$checkboxId";
			// isEnabled(), isDeleted(), isHidden(), isGlobal()
			$method = 'is' . ucfirst( $checkboxId );
			$postVar = 'wpFilter' . ucfirst( $checkboxId );

			$checkboxAttribs = [
				'name' => $postVar,
				'id' => $postVar,
				'selected' => $filterObj->$method(),
				'disabled' => $readOnly
			];
			$labelAttribs = [
				'label' => $this->msg( $message )->text(),
				'align' => 'inline',
			];

			if ( $checkboxId === 'global' && !$this->afPermManager->canEditGlobal( $user ) ) {
				$checkboxAttribs['disabled'] = 'disabled';
			}

			// Set readonly on deleted if the filter isn't disabled
			if ( $checkboxId === 'deleted' && $filterObj->isEnabled() ) {
				$checkboxAttribs['disabled'] = 'disabled';
			}

			// Add infusable where needed
			if ( $checkboxId === 'deleted' || $checkboxId === 'enabled' ) {
				$checkboxAttribs['infusable'] = true;
				if ( $checkboxId === 'deleted' ) {
					$labelAttribs['id'] = $postVar . 'Label';
					$labelAttribs['infusable'] = true;
				}
			}

			$checkbox =
				new OOUI\FieldLayout(
					new OOUI\CheckboxInputWidget( $checkboxAttribs ),
					$labelAttribs
				);
			$flags .= $checkbox;
		}

		$fields['abusefilter-edit-flags'] = $flags;

		if ( $filter !== null ) {
			$tools = '';
			if ( $this->afPermManager->canRevertFilterActions( $user ) ) {
				$tools .= Xml::tags(
					'p', null,
					$this->linkRenderer->makeLink(
						$this->getTitle( "revert/$filter" ),
						new HtmlArmor( $this->msg( 'abusefilter-edit-revert' )->parse() )
					)
				);
			}

			if ( $this->afPermManager->canUseTestTools( $user ) ) {
				// Test link
				$tools .= Xml::tags(
					'p', null,
					$this->linkRenderer->makeLink(
						$this->getTitle( "test/$filter" ),
						new HtmlArmor( $this->msg( 'abusefilter-edit-test-link' )->parse() )
					)
				);
			}
			// Last modification details
			$userLink =
				Linker::userLink( $filterObj->getUserID(), $filterObj->getUserName() ) .
				Linker::userToolLinks( $filterObj->getUserID(), $filterObj->getUserName() );
			$fields['abusefilter-edit-lastmod'] =
				$this->msg( 'abusefilter-edit-lastmod-text' )
				->rawParams(
					$this->getLinkToLatestDiff(
						$filter,
						$lang->userTimeAndDate( $filterObj->getTimestamp(), $user )
					),
					$userLink,
					$this->getLinkToLatestDiff(
						$filter,
						$lang->userDate( $filterObj->getTimestamp(), $user )
					),
					$this->getLinkToLatestDiff(
						$filter,
						$lang->userTime( $filterObj->getTimestamp(), $user )
					)
				)->params(
					wfEscapeWikiText( $filterObj->getUserName() )
				)->parse();
			$history_display = new HtmlArmor( $this->msg( 'abusefilter-edit-viewhistory' )->parse() );
			$fields['abusefilter-edit-history'] =
				$this->linkRenderer->makeKnownLink( $this->getTitle( 'history/' . $filter ), $history_display );

			$exportText = $this->filterImporter->encodeData( $filterObj, $actions );
			$tools .= Xml::tags( 'a', [ 'href' => '#', 'id' => 'mw-abusefilter-export-link' ],
				$this->msg( 'abusefilter-edit-export' )->parse() );
			$tools .=
				new OOUI\MultilineTextInputWidget( [
					'id' => 'mw-abusefilter-export',
					'readOnly' => true,
					'value' => $exportText,
					'rows' => 10
				] );

			$fields['abusefilter-edit-tools'] = $tools;
		}

		$form = Xml::buildForm( $fields );
		$form = Xml::fieldset( $this->msg( 'abusefilter-edit-main' )->text(), $form );
		$form .= Xml::fieldset(
			$this->msg( 'abusefilter-edit-consequences' )->text(),
			$this->buildConsequenceEditor( $filterObj, $actions )
		);

		$urlFilter = $filter === null ? 'new' : (string)$filter;
		if ( !$readOnly ) {
			$form .=
				new OOUI\ButtonInputWidget( [
					'type' => 'submit',
					'label' => $this->msg( 'abusefilter-edit-save' )->text(),
					'useInputTag' => true,
					'accesskey' => 's',
					'flags' => [ 'progressive', 'primary' ]
				] );
			$form .= Html::hidden(
				'wpEditToken',
				$user->getEditToken( [ 'abusefilter', $urlFilter ] )
			);
		}

		$form = Xml::tags( 'form',
			[
				'action' => $this->getTitle( $urlFilter )->getFullURL(),
				'method' => 'post',
				'id' => 'mw-abusefilter-editing-form'
			],
			$form
		);

		$out->addHTML( $form );

		if ( $history_id ) {
			// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
			$out->addWikiMsg( $oldWarningMessage, $history_id, $filter );
		}
	}

	/**
	 * Builds the "actions" editor for a given filter.
	 * @param Filter $filterObj
	 * @param array[] $actions Array of rows from the abuse_filter_action table
	 *  corresponding to the filter object
	 * @return string HTML text for an action editor.
	 */
	private function buildConsequenceEditor( Filter $filterObj, array $actions ) {
		$enabledActions = $this->consequencesRegistry->getAllEnabledActionNames();

		$setActions = [];
		foreach ( $enabledActions as $action ) {
			$setActions[$action] = array_key_exists( $action, $actions );
		}

		$output = '';

		foreach ( $enabledActions as $action ) {
			$params = $actions[$action] ?? null;
			$output .= $this->buildConsequenceSelector(
				$action, $setActions[$action], $filterObj, $params );
		}

		return $output;
	}

	/**
	 * @param string $action The action to build an editor for
	 * @param bool $set Whether or not the action is activated
	 * @param Filter $filterObj
	 * @param string[]|null $parameters Action parameters. Null iff $set is false.
	 * @return string|\OOUI\FieldLayout
	 */
	private function buildConsequenceSelector( $action, $set, $filterObj, ?array $parameters ) {
		$config = $this->getConfig();
		$user = $this->getUser();
		$actions = $this->consequencesRegistry->getAllEnabledActionNames();
		if ( !in_array( $action, $actions, true ) ) {
			return '';
		}

		$readOnly = !$this->afPermManager->canEditFilter( $user, $filterObj );

		switch ( $action ) {
			case 'throttle':
				// Throttling is only available via object caching
				if ( $config->get( MainConfigNames::MainCacheType ) === CACHE_NONE ) {
					return '';
				}
				$throttleSettings =
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'wpFilterActionThrottle',
							'id' => 'mw-abusefilter-action-checkbox-throttle',
							'selected' => $set,
							'classes' => [ 'mw-abusefilter-action-checkbox' ],
							'disabled' => $readOnly
						]
						),
						[
							'label' => $this->msg( 'abusefilter-edit-action-throttle' )->text(),
							'align' => 'inline'
						]
					);
				$throttleFields = [];

				if ( $set ) {
					// @phan-suppress-next-line PhanTypeArraySuspiciousNullable $parameters is array here
					[ $throttleCount, $throttlePeriod ] = explode( ',', $parameters[1], 2 );

					$throttleGroups = array_slice( $parameters, 2 );
				} else {
					$throttleCount = 3;
					$throttlePeriod = 60;

					$throttleGroups = [ 'user' ];
				}

				$throttleFields[] =
					new OOUI\FieldLayout(
						new OOUI\TextInputWidget( [
							'type' => 'number',
							'name' => 'wpFilterThrottleCount',
							'value' => $throttleCount,
							'readOnly' => $readOnly
							]
						),
						[
							'label' => $this->msg( 'abusefilter-edit-throttle-count' )->text()
						]
					);
				$throttleFields[] =
					new OOUI\FieldLayout(
						new OOUI\TextInputWidget( [
							'type' => 'number',
							'name' => 'wpFilterThrottlePeriod',
							'value' => $throttlePeriod,
							'readOnly' => $readOnly
							]
						),
						[
							'label' => $this->msg( 'abusefilter-edit-throttle-period' )->text()
						]
					);

				$groupsHelpLink = Html::element(
					'a',
					[
						'href' => 'https://www.mediawiki.org/wiki/Special:MyLanguage/' .
							'Extension:AbuseFilter/Actions#Throttling',
						'target' => '_blank'
					],
					$this->msg( 'abusefilter-edit-throttle-groups-help-text' )->text()
				);
				$groupsHelp = $this->msg( 'abusefilter-edit-throttle-groups-help' )
						->rawParams( $groupsHelpLink )->escaped();
				$hiddenGroups =
					new OOUI\FieldLayout(
						new OOUI\MultilineTextInputWidget( [
							'name' => 'wpFilterThrottleGroups',
							'value' => implode( "\n", $throttleGroups ),
							'rows' => 5,
							'placeholder' => $this->msg( 'abusefilter-edit-throttle-hidden-placeholder' )->text(),
							'infusable' => true,
							'id' => 'mw-abusefilter-hidden-throttle-field',
							'readOnly' => $readOnly
						]
						),
						[
							'label' => new OOUI\HtmlSnippet(
								$this->msg( 'abusefilter-edit-throttle-groups' )->parse()
							),
							'align' => 'top',
							'id' => 'mw-abusefilter-hidden-throttle',
							'help' => new OOUI\HtmlSnippet( $groupsHelp ),
							'helpInline' => true
						]
					);

				$throttleFields[] = $hiddenGroups;

				$throttleConfig = [
					'values' => $throttleGroups,
					'label' => $this->msg( 'abusefilter-edit-throttle-groups' )->parse(),
					'disabled' => $readOnly,
					'help' => $groupsHelp
				];
				$this->getOutput()->addJsConfigVars( 'throttleConfig', $throttleConfig );

				$throttleSettings .=
					Xml::tags(
						'div',
						[ 'id' => 'mw-abusefilter-throttle-parameters' ],
						new OOUI\FieldsetLayout( [ 'items' => $throttleFields ] )
					);
				return $throttleSettings;
			case 'disallow':
			case 'warn':
				$output = '';
				$formName = $action === 'warn' ? 'wpFilterActionWarn' : 'wpFilterActionDisallow';
				$checkbox =
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => $formName,
							// mw-abusefilter-action-checkbox-warn, mw-abusefilter-action-checkbox-disallow
							'id' => "mw-abusefilter-action-checkbox-$action",
							'selected' => $set,
							'classes' => [ 'mw-abusefilter-action-checkbox' ],
							'disabled' => $readOnly
						]
						),
						[
							// abusefilter-edit-action-warn, abusefilter-edit-action-disallow
							'label' => $this->msg( "abusefilter-edit-action-$action" )->text(),
							'align' => 'inline'
						]
					);
				$output .= $checkbox;
				$defaultWarnMsg = $config->get( 'AbuseFilterDefaultWarningMessage' );
				$defaultDisallowMsg = $config->get( 'AbuseFilterDefaultDisallowMessage' );

				if ( $set && isset( $parameters[0] ) ) {
					$msg = $parameters[0];
				} elseif (
					( $action === 'warn' && isset( $defaultWarnMsg[$filterObj->getGroup()] ) ) ||
					( $action === 'disallow' && isset( $defaultDisallowMsg[$filterObj->getGroup()] ) )
				) {
					$msg = $action === 'warn' ? $defaultWarnMsg[$filterObj->getGroup()] :
						$defaultDisallowMsg[$filterObj->getGroup()];
				} else {
					$msg = $action === 'warn' ? 'abusefilter-warning' : 'abusefilter-disallowed';
				}

				$fields = [];
				$fields[] =
					$this->getExistingSelector( $msg, $readOnly, $action );
				$otherFieldName = $action === 'warn' ? 'wpFilterWarnMessageOther'
					: 'wpFilterDisallowMessageOther';

				$fields[] =
					new OOUI\FieldLayout(
						new OOUI\TextInputWidget( [
							'name' => $otherFieldName,
							'value' => $msg,
							// mw-abusefilter-warn-message-other, mw-abusefilter-disallow-message-other
							'id' => "mw-abusefilter-$action-message-other",
							'infusable' => true,
							'readOnly' => $readOnly
							]
						),
						[
							'label' => new OOUI\HtmlSnippet(
								// abusefilter-edit-warn-other-label, abusefilter-edit-disallow-other-label
								$this->msg( "abusefilter-edit-$action-other-label" )->parse()
							)
						]
					);

				$previewButton =
					new OOUI\ButtonInputWidget( [
						// abusefilter-edit-warn-preview, abusefilter-edit-disallow-preview
						'label' => $this->msg( "abusefilter-edit-$action-preview" )->text(),
						// mw-abusefilter-warn-preview-button, mw-abusefilter-disallow-preview-button
						'id' => "mw-abusefilter-$action-preview-button",
						'infusable' => true,
						'flags' => 'progressive'
						]
					);

				$buttonGroup = $previewButton;
				if ( $this->permissionManager->userHasRight( $user, 'editinterface' ) ) {
					$editButton =
						new OOUI\ButtonInputWidget( [
							// abusefilter-edit-warn-edit, abusefilter-edit-disallow-edit
							'label' => $this->msg( "abusefilter-edit-$action-edit" )->text(),
							// mw-abusefilter-warn-edit-button, mw-abusefilter-disallow-edit-button
							'id' => "mw-abusefilter-$action-edit-button"
							]
						);
					$buttonGroup =
						new OOUI\Widget( [
							'content' =>
								new OOUI\HorizontalLayout( [
									'items' => [ $previewButton, $editButton ],
									'classes' => [
										'mw-abusefilter-preview-buttons',
										'mw-abusefilter-javascript-tools'
									]
								] )
						] );
				}
				$previewHolder = Xml::tags(
					'div',
					[
						// mw-abusefilter-warn-preview, mw-abusefilter-disallow-preview
						'id' => "mw-abusefilter-$action-preview",
						'style' => 'display:none'
					],
					''
				);
				$fields[] = $buttonGroup;
				$output .=
					Xml::tags(
						'div',
						// mw-abusefilter-warn-parameters, mw-abusefilter-disallow-parameters
						[ 'id' => "mw-abusefilter-$action-parameters" ],
						new OOUI\FieldsetLayout( [ 'items' => $fields ] )
					) . $previewHolder;

				return $output;
			case 'tag':
				$tags = $set ? $parameters : [];
				'@phan-var string[] $parameters';
				$output = '';

				$checkbox =
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'wpFilterActionTag',
							'id' => 'mw-abusefilter-action-checkbox-tag',
							'selected' => $set,
							'classes' => [ 'mw-abusefilter-action-checkbox' ],
							'disabled' => $readOnly
						]
						),
						[
							'label' => $this->msg( 'abusefilter-edit-action-tag' )->text(),
							'align' => 'inline'
						]
					);
				$output .= $checkbox;

				$tagConfig = [
					'values' => $tags,
					'label' => $this->msg( 'abusefilter-edit-tag-tag' )->parse(),
					'disabled' => $readOnly
				];
				$this->getOutput()->addJsConfigVars( 'tagConfig', $tagConfig );

				$hiddenTags =
					new OOUI\FieldLayout(
						new OOUI\MultilineTextInputWidget( [
							'name' => 'wpFilterTags',
							'value' => implode( ',', $tags ),
							'rows' => 5,
							'placeholder' => $this->msg( 'abusefilter-edit-tag-hidden-placeholder' )->text(),
							'infusable' => true,
							'id' => 'mw-abusefilter-hidden-tag-field',
							'readOnly' => $readOnly
						]
						),
						[
							'label' => new OOUI\HtmlSnippet(
								$this->msg( 'abusefilter-edit-tag-tag' )->parse()
							),
							'align' => 'top',
							'id' => 'mw-abusefilter-hidden-tag'
						]
					);
				$output .=
					Xml::tags( 'div',
						[ 'id' => 'mw-abusefilter-tag-parameters' ],
						$hiddenTags
					);
				return $output;
			case 'block':
				if ( $set && count( $parameters ) === 3 ) {
					// Both blocktalk and custom block durations available
					[ $blockTalk, $defaultAnonDuration, $defaultUserDuration ] = $parameters;
				} else {
					if ( $set && count( $parameters ) === 1 ) {
						// Only blocktalk available
						$blockTalk = $parameters[0];
					}
					$defaultAnonDuration = $config->get( 'AbuseFilterAnonBlockDuration' ) ??
						$config->get( 'AbuseFilterBlockDuration' );
					$defaultUserDuration = $config->get( 'AbuseFilterBlockDuration' );
				}
				$suggestedBlocks = SpecialBlock::getSuggestedDurations( null, false );
				$suggestedBlocks = self::normalizeBlocks( $suggestedBlocks );

				$output = '';
				$checkbox =
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'wpFilterActionBlock',
							'id' => 'mw-abusefilter-action-checkbox-block',
							'selected' => $set,
							'classes' => [ 'mw-abusefilter-action-checkbox' ],
							'disabled' => $readOnly
						]
						),
						[
							'label' => $this->msg( 'abusefilter-edit-action-block' )->text(),
							'align' => 'inline'
						]
					);
				$output .= $checkbox;

				$suggestedBlocks = Xml::listDropdownOptionsOoui( $suggestedBlocks );

				$anonDuration =
					new OOUI\DropdownInputWidget( [
						'name' => 'wpBlockAnonDuration',
						'options' => $suggestedBlocks,
						'value' => $defaultAnonDuration,
						'disabled' => $readOnly
					] );

				$userDuration =
					new OOUI\DropdownInputWidget( [
						'name' => 'wpBlockUserDuration',
						'options' => $suggestedBlocks,
						'value' => $defaultUserDuration,
						'disabled' => $readOnly
					] );

				$blockOptions = [];
				if ( $config->get( MainConfigNames::BlockAllowsUTEdit ) === true ) {
					$talkCheckbox =
						new OOUI\FieldLayout(
							new OOUI\CheckboxInputWidget( [
								'name' => 'wpFilterBlockTalk',
								'id' => 'mw-abusefilter-action-checkbox-blocktalk',
								'selected' => isset( $blockTalk ) && $blockTalk === 'blocktalk',
								'classes' => [ 'mw-abusefilter-action-checkbox' ],
								'disabled' => $readOnly
							]
							),
							[
								'label' => $this->msg( 'abusefilter-edit-action-blocktalk' )->text(),
								'align' => 'left'
							]
						);

					$blockOptions[] = $talkCheckbox;
				}
				$blockOptions[] =
					new OOUI\FieldLayout(
						$anonDuration,
						[
							'label' => $this->msg( 'abusefilter-edit-block-anon-durations' )->text()
						]
					);
				$blockOptions[] =
					new OOUI\FieldLayout(
						$userDuration,
						[
							'label' => $this->msg( 'abusefilter-edit-block-user-durations' )->text()
						]
					);

				$output .= Xml::tags(
						'div',
						[ 'id' => 'mw-abusefilter-block-parameters' ],
						new OOUI\FieldsetLayout( [ 'items' => $blockOptions ] )
					);

				return $output;

			default:
				// Give grep a chance to find the usages:
				// abusefilter-edit-action-disallow,
				// abusefilter-edit-action-blockautopromote,
				// abusefilter-edit-action-degroup,
				// abusefilter-edit-action-rangeblock,
				$message = 'abusefilter-edit-action-' . $action;
				$form_field = 'wpFilterAction' . ucfirst( $action );
				$status = $set;

				$thisAction =
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => $form_field,
							'id' => "mw-abusefilter-action-checkbox-$action",
							'selected' => $status,
							'classes' => [ 'mw-abusefilter-action-checkbox' ],
							'disabled' => $readOnly
						]
						),
						[
							'label' => $this->msg( $message )->text(),
							'align' => 'inline'
						]
					);
				return $thisAction;
		}
	}

	/**
	 * @param string $warnMsg
	 * @param bool $readOnly
	 * @param string $action
	 * @return \OOUI\FieldLayout
	 */
	public function getExistingSelector( $warnMsg, $readOnly = false, $action = 'warn' ) {
		if ( $action === 'warn' ) {
			$action = 'warning';
			$formId = 'warn';
			$inputName = 'wpFilterWarnMessage';
		} elseif ( $action === 'disallow' ) {
			$action = 'disallowed';
			$formId = 'disallow';
			$inputName = 'wpFilterDisallowMessage';
		} else {
			throw new UnexpectedValueException( "Unexpected action value $action" );
		}

		$existingSelector =
			new OOUI\DropdownInputWidget( [
				'name' => $inputName,
				// mw-abusefilter-warn-message-existing, mw-abusefilter-disallow-message-existing
				'id' => "mw-abusefilter-$formId-message-existing",
				// abusefilter-warning, abusefilter-disallowed
				'value' => $warnMsg === "abusefilter-$action" ? "abusefilter-$action" : 'other',
				'infusable' => true
			] );

		// abusefilter-warning, abusefilter-disallowed
		$options = [ "abusefilter-$action" => "abusefilter-$action" ];

		if ( $readOnly ) {
			$existingSelector->setDisabled( true );
		} else {
			// Find other messages.
			$dbr = $this->lbFactory->getReplicaDatabase();
			$pageTitlePrefix = "Abusefilter-$action";
			$titles = $dbr->selectFieldValues(
				'page',
				'page_title',
				[
					'page_namespace' => 8,
					'page_title LIKE ' . $dbr->addQuotes( $pageTitlePrefix . '%' )
				],
				__METHOD__
			);

			$lang = $this->getLanguage();
			foreach ( $titles as $title ) {
				if ( $lang->lcfirst( $title ) === $lang->lcfirst( $warnMsg ) ) {
					$existingSelector->setValue( $lang->lcfirst( $warnMsg ) );
				}

				if ( $title !== "Abusefilter-$action" ) {
					$options[ $lang->lcfirst( $title ) ] = $lang->lcfirst( $title );
				}
			}
		}

		// abusefilter-edit-warn-other, abusefilter-edit-disallow-other
		$options[ $this->msg( "abusefilter-edit-$formId-other" )->text() ] = 'other';

		$options = Xml::listDropdownOptionsOoui( $options );
		$existingSelector->setOptions( $options );

		$existingSelector =
			new OOUI\FieldLayout(
				$existingSelector,
				[
					// abusefilter-edit-warn-message, abusefilter-edit-disallow-message
					'label' => $this->msg( "abusefilter-edit-$formId-message" )->text()
				]
			);

		return $existingSelector;
	}

	/**
	 * @todo Maybe we should also check if global values belong to $durations
	 * and determine the right point to add them if missing.
	 *
	 * @param string[] $durations
	 * @return string[]
	 */
	private static function normalizeBlocks( array $durations ) {
		global $wgAbuseFilterBlockDuration, $wgAbuseFilterAnonBlockDuration;
		// We need to have same values since it may happen that ipblocklist
		// and one (or both) of the global variables use different wording
		// for the same duration. In such case, when setting the default of
		// the dropdowns it would fail.
		$anonDuration = self::getAbsoluteBlockDuration( $wgAbuseFilterAnonBlockDuration ??
			$wgAbuseFilterBlockDuration );
		$userDuration = self::getAbsoluteBlockDuration( $wgAbuseFilterBlockDuration );
		foreach ( $durations as &$duration ) {
			$currentDuration = self::getAbsoluteBlockDuration( $duration );

			if ( $duration !== $wgAbuseFilterBlockDuration &&
				$currentDuration === $userDuration ) {
				$duration = $wgAbuseFilterBlockDuration;

			} elseif ( $duration !== $wgAbuseFilterAnonBlockDuration &&
				$currentDuration === $anonDuration ) {
				$duration = $wgAbuseFilterAnonBlockDuration;
			}
		}

		return $durations;
	}

	/**
	 * Converts a string duration to an absolute timestamp, i.e. unrelated to the current
	 * time, taking into account infinity durations as well. The second parameter of
	 * strtotime is set to 0 in order to convert the duration in seconds (instead of
	 * a timestamp), thus making it unaffected by the execution time of the code.
	 *
	 * @param string $duration
	 * @return string|int
	 */
	private static function getAbsoluteBlockDuration( $duration ) {
		if ( wfIsInfinity( $duration ) ) {
			return 'infinity';
		}
		return strtotime( $duration, 0 );
	}

	/**
	 * Loads filter data from the database by ID.
	 * @param int|null $id The filter's ID number, or null for a new filter
	 * @return Filter
	 * @throws FilterNotFoundException
	 */
	private function loadFilterData( ?int $id ): Filter {
		if ( $id === null ) {
			return MutableFilter::newDefault();
		}

		$flags = $this->getRequest()->wasPosted()
			// Load from primary database to avoid unintended reversions where there's replication lag.
			? IDBAccessObject::READ_LATEST
			: IDBAccessObject::READ_NORMAL;

		return $this->filterLookup->getFilter( $id, false, $flags );
	}

	/**
	 * Load filter data to show in the edit view from the DB.
	 * @param int|null $filter The filter ID being requested or null for a new filter
	 * @param int|null $history_id If any, the history ID being requested.
	 * @return Filter|null Null if the filter does not exist.
	 */
	private function loadFromDatabase( ?int $filter, $history_id = null ): ?Filter {
		if ( $history_id ) {
			try {
				return $this->filterLookup->getFilterVersion( $history_id );
			} catch ( FilterVersionNotFoundException $_ ) {
				return null;
			}
		} else {
			return $this->loadFilterData( $filter );
		}
	}

	/**
	 * Load data from the HTTP request. Used for saving the filter, and when the token doesn't match
	 * @param int|null $filter
	 * @return Filter[]
	 */
	private function loadRequest( ?int $filter ): array {
		$request = $this->getRequest();
		if ( !$request->wasPosted() ) {
			// Sanity
			throw new LogicException( __METHOD__ . ' called without the request being POSTed.' );
		}

		$origFilter = $this->loadFilterData( $filter );

		/** @var MutableFilter $newFilter */
		$newFilter = $origFilter instanceof MutableFilter
			? clone $origFilter
			: MutableFilter::newFromParentFilter( $origFilter );

		if ( $filter !== null ) {
			// Unchangeable values
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$newFilter->setThrottled( $origFilter->isThrottled() );
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$newFilter->setHitCount( $origFilter->getHitCount() );
			// These are needed if the save fails and the filter is not new
			$newFilter->setID( $origFilter->getID() );
			$newFilter->setUserID( $origFilter->getUserID() );
			$newFilter->setUserName( $origFilter->getUserName() );
			$newFilter->setTimestamp( $origFilter->getTimestamp() );
		}

		$newFilter->setName( trim( $request->getVal( 'wpFilterDescription' ) ) );
		$newFilter->setRules( trim( $request->getVal( 'wpFilterRules' ) ) );
		$newFilter->setComments( trim( $request->getVal( 'wpFilterNotes' ) ) );

		$newFilter->setGroup( $request->getVal( 'wpFilterGroup', 'default' ) );

		$newFilter->setDeleted( $request->getCheck( 'wpFilterDeleted' ) );
		$newFilter->setEnabled( $request->getCheck( 'wpFilterEnabled' ) );
		$newFilter->setHidden( $request->getCheck( 'wpFilterHidden' ) );
		$newFilter->setGlobal( $request->getCheck( 'wpFilterGlobal' )
			&& $this->getConfig()->get( 'AbuseFilterIsCentral' ) );

		$actions = $this->loadActions();

		$newFilter->setActions( $actions );

		return [ $newFilter, $origFilter ];
	}

	/**
	 * @return Filter|null
	 */
	private function loadImportRequest(): ?Filter {
		$request = $this->getRequest();
		if ( !$request->wasPosted() ) {
			// Sanity
			throw new LogicException( __METHOD__ . ' called without the request being POSTed.' );
		}

		try {
			$filter = $this->filterImporter->decodeData( $request->getVal( 'wpImportText' ) );
		} catch ( InvalidImportDataException $_ ) {
			return null;
		}

		return $filter;
	}

	/**
	 * @return array[]
	 */
	private function loadActions(): array {
		$request = $this->getRequest();
		$allActions = $this->consequencesRegistry->getAllEnabledActionNames();
		$actions = [];
		foreach ( $allActions as $action ) {
			// Check if it's set
			$enabled = $request->getCheck( 'wpFilterAction' . ucfirst( $action ) );

			if ( $enabled ) {
				$parameters = [];

				if ( $action === 'throttle' ) {
					// We need to load the parameters
					$throttleCount = $request->getIntOrNull( 'wpFilterThrottleCount' );
					$throttlePeriod = $request->getIntOrNull( 'wpFilterThrottlePeriod' );
					// First explode with \n, which is the delimiter used in the textarea
					$rawGroups = explode( "\n", $request->getText( 'wpFilterThrottleGroups' ) );
					// Trim any space, both as an actual group and inside subgroups
					$throttleGroups = [];
					foreach ( $rawGroups as $group ) {
						if ( strpos( $group, ',' ) !== false ) {
							$subGroups = explode( ',', $group );
							$throttleGroups[] = implode( ',', array_map( 'trim', $subGroups ) );
						} elseif ( trim( $group ) !== '' ) {
							$throttleGroups[] = trim( $group );
						}
					}

					$parameters[0] = $this->filter;
					$parameters[1] = "$throttleCount,$throttlePeriod";
					$parameters = array_merge( $parameters, $throttleGroups );
				} elseif ( $action === 'warn' ) {
					$specMsg = $request->getVal( 'wpFilterWarnMessage' );

					if ( $specMsg === 'other' ) {
						$specMsg = $request->getVal( 'wpFilterWarnMessageOther' );
					}

					$parameters[0] = $specMsg;
				} elseif ( $action === 'block' ) {
					// TODO: Should save a boolean
					$parameters[0] = $request->getCheck( 'wpFilterBlockTalk' ) ?
						'blocktalk' : 'noTalkBlockSet';
					$parameters[1] = $request->getVal( 'wpBlockAnonDuration' );
					$parameters[2] = $request->getVal( 'wpBlockUserDuration' );
				} elseif ( $action === 'disallow' ) {
					$specMsg = $request->getVal( 'wpFilterDisallowMessage' );

					if ( $specMsg === 'other' ) {
						$specMsg = $request->getVal( 'wpFilterDisallowMessageOther' );
					}

					$parameters[0] = $specMsg;
				} elseif ( $action === 'tag' ) {
					$parameters = explode( ',', trim( $request->getText( 'wpFilterTags' ) ) );
					if ( $parameters === [ '' ] ) {
						// Since it's not possible to manually add an empty tag, this only happens
						// if the form is submitted without touching the tag input field.
						// We pass an empty array so that the widget won't show an empty tag in the topbar
						$parameters = [];
					}
				}

				$actions[$action] = $parameters;
			}
		}
		return $actions;
	}

	/**
	 * Exports the default warning and disallow messages to a JS variable
	 */
	private function exposeMessages() {
		$this->getOutput()->addJsConfigVars(
			'wgAbuseFilterDefaultWarningMessage',
			$this->getConfig()->get( 'AbuseFilterDefaultWarningMessage' )
		);
		$this->getOutput()->addJsConfigVars(
			'wgAbuseFilterDefaultDisallowMessage',
			$this->getConfig()->get( 'AbuseFilterDefaultDisallowMessage' )
		);
	}
}
