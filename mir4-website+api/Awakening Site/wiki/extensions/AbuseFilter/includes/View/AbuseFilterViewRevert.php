<?php

namespace MediaWiki\Extension\AbuseFilter\View;

use HTMLForm;
use IContextSource;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\ReversibleConsequence;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\SpecsFormatter;
use MediaWiki\Extension\AbuseFilter\Variables\UnsetVariableException;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserFactory;
use Message;
use PermissionsError;
use UnexpectedValueException;
use UserBlockedError;
use Wikimedia\Rdbms\LBFactory;
use Xml;

class AbuseFilterViewRevert extends AbuseFilterView {
	/** @var int */
	private $filter;
	/**
	 * @var string|null The start time of the lookup period
	 */
	private $periodStart;
	/**
	 * @var string|null The end time of the lookup period
	 */
	private $periodEnd;
	/**
	 * @var string|null The reason provided for the revert
	 */
	private $reason;
	/**
	 * @var LBFactory
	 */
	private $lbFactory;
	/**
	 * @var UserFactory
	 */
	private $userFactory;
	/**
	 * @var FilterLookup
	 */
	private $filterLookup;
	/**
	 * @var ConsequencesFactory
	 */
	private $consequencesFactory;
	/**
	 * @var VariablesBlobStore
	 */
	private $varBlobStore;
	/**
	 * @var SpecsFormatter
	 */
	private $specsFormatter;

	/**
	 * @param LBFactory $lbFactory
	 * @param UserFactory $userFactory
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param FilterLookup $filterLookup
	 * @param ConsequencesFactory $consequencesFactory
	 * @param VariablesBlobStore $varBlobStore
	 * @param SpecsFormatter $specsFormatter
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param string $basePageName
	 * @param array $params
	 */
	public function __construct(
		LBFactory $lbFactory,
		UserFactory $userFactory,
		AbuseFilterPermissionManager $afPermManager,
		FilterLookup $filterLookup,
		ConsequencesFactory $consequencesFactory,
		VariablesBlobStore $varBlobStore,
		SpecsFormatter $specsFormatter,
		IContextSource $context,
		LinkRenderer $linkRenderer,
		string $basePageName,
		array $params
	) {
		parent::__construct( $afPermManager, $context, $linkRenderer, $basePageName, $params );
		$this->lbFactory = $lbFactory;
		$this->userFactory = $userFactory;
		$this->filterLookup = $filterLookup;
		$this->consequencesFactory = $consequencesFactory;
		$this->varBlobStore = $varBlobStore;
		$this->specsFormatter = $specsFormatter;
		$this->specsFormatter->setMessageLocalizer( $this->getContext() );
	}

	/**
	 * Shows the page
	 */
	public function show() {
		$lang = $this->getLanguage();

		$performer = $this->getAuthority();
		$out = $this->getOutput();

		if ( !$this->afPermManager->canRevertFilterActions( $performer ) ) {
			throw new PermissionsError( 'abusefilter-revert' );
		}

		$block = $performer->getBlock();
		if ( $block && $block->isSitewide() ) {
			throw new UserBlockedError( $block );
		}

		$this->loadParameters();

		if ( $this->attemptRevert() ) {
			return;
		}

		$filter = $this->filter;

		$out->addWikiMsg( 'abusefilter-revert-intro', Message::numParam( $filter ) );
		// Parse wikitext in this message to allow formatting of numero signs (T343994#9209383)
		$out->setPageTitle( $this->msg( 'abusefilter-revert-title' )->numParams( $filter )->parse() );

		// First, the search form. Limit dates to avoid huge queries
		$RCMaxAge = $this->getConfig()->get( 'RCMaxAge' );
		$min = wfTimestamp( TS_ISO_8601, time() - $RCMaxAge );
		$max = wfTimestampNow();
		$filterLink =
			$this->linkRenderer->makeLink(
				$this->getTitle( $filter ),
				$lang->formatNum( $filter )
			);
		$searchFields = [];
		$searchFields['filterid'] = [
			'type' => 'info',
			'default' => $filterLink,
			'raw' => true,
			'label-message' => 'abusefilter-revert-filter'
		];
		$searchFields['PeriodStart'] = [
			'type' => 'datetime',
			'label-message' => 'abusefilter-revert-periodstart',
			'min' => $min,
			'max' => $max
		];
		$searchFields['PeriodEnd'] = [
			'type' => 'datetime',
			'label-message' => 'abusefilter-revert-periodend',
			'min' => $min,
			'max' => $max
		];

		HTMLForm::factory( 'ooui', $searchFields, $this->getContext() )
			->setTitle( $this->getTitle( "revert/$filter" ) )
			->setWrapperLegendMsg( 'abusefilter-revert-search-legend' )
			->setSubmitTextMsg( 'abusefilter-revert-search' )
			->setMethod( 'get' )
			->setFormIdentifier( 'revert-select-date' )
			->setSubmitCallback( [ $this, 'showRevertableActions' ] )
			->showAlways();
	}

	/**
	 * Show revertable actions, called as submit callback by HTMLForm
	 * @param array $formData
	 * @param HTMLForm $dateForm
	 * @return bool
	 */
	public function showRevertableActions( array $formData, HTMLForm $dateForm ): bool {
		$lang = $this->getLanguage();
		$user = $this->getUser();
		$filter = $this->filter;

		// Look up all of them.
		$results = $this->doLookup();
		if ( $results === [] ) {
			$dateForm->addPostHtml( $this->msg( 'abusefilter-revert-preview-no-results' )->escaped() );
			return true;
		}

		// Add a summary of everything that will be reversed.
		$dateForm->addPostHtml( $this->msg( 'abusefilter-revert-preview-intro' )->parseAsBlock() );
		$list = [];

		foreach ( $results as $result ) {
			$displayActions = [];
			foreach ( $result['actions'] as $action ) {
				$displayActions[] = $this->specsFormatter->getActionDisplay( $action );
			}

			/** @var ActionSpecifier $spec */
			$spec = $result['spec'];
			$msg = $this->msg( 'abusefilter-revert-preview-item' )
				->params(
					$lang->userTimeAndDate( $result['timestamp'], $user )
				)->rawParams(
					Linker::userLink( $spec->getUser()->getId(), $spec->getUser()->getName() )
				)->params(
					$spec->getAction()
				)->rawParams(
					$this->linkRenderer->makeLink( $spec->getTitle() )
				)->params(
					$lang->commaList( $displayActions )
				)->rawParams(
					$this->linkRenderer->makeLink(
						SpecialPage::getTitleFor( 'AbuseLog' ),
						$this->msg( 'abusefilter-log-detailslink' )->text(),
						[],
						[ 'details' => $result['id'] ]
					)
				)->params(
					$spec->getUser()->getName()
				)->parse();
			$list[] = Xml::tags( 'li', null, $msg );
		}

		$dateForm->addPostHtml( Xml::tags( 'ul', null, implode( "\n", $list ) ) );

		// Add a button down the bottom.
		$confirmForm = [];
		$confirmForm['PeriodStart'] = [
			'type' => 'hidden',
		];
		$confirmForm['PeriodEnd'] = [
			'type' => 'hidden',
		];
		$confirmForm['Reason'] = [
			'type' => 'text',
			'label-message' => 'abusefilter-revert-reasonfield',
			'id' => 'wpReason',
		];

		$revertForm = HTMLForm::factory( 'ooui', $confirmForm, $this->getContext() )
			->setTitle( $this->getTitle( "revert/$filter" ) )
			->setTokenSalt( "abusefilter-revert-$filter" )
			->setWrapperLegendMsg( 'abusefilter-revert-confirm-legend' )
			->setSubmitTextMsg( 'abusefilter-revert-confirm' )
			->prepareForm()
			->getHTML( true );
		$dateForm->addPostHtml( $revertForm );

		return true;
	}

	/**
	 * @return array[]
	 */
	public function doLookup() {
		$periodStart = $this->periodStart;
		$periodEnd = $this->periodEnd;
		$filter = $this->filter;
		$dbr = $this->lbFactory->getReplicaDatabase();

		// Only hits from local filters can be reverted
		$conds = [ 'afl_filter_id' => $filter, 'afl_global' => 0 ];

		if ( $periodStart !== null ) {
			$conds[] = 'afl_timestamp >= ' . $dbr->addQuotes( $dbr->timestamp( $periodStart ) );
		}
		if ( $periodEnd !== null ) {
			$conds[] = 'afl_timestamp <= ' . $dbr->addQuotes( $dbr->timestamp( $periodEnd ) );
		}

		// Don't revert if there was no action, or the action was global
		$conds[] = 'afl_actions != ' . $dbr->addQuotes( '' );
		$conds[] = 'afl_wiki IS NULL';

		$selectFields = [
			'afl_id',
			'afl_user',
			'afl_user_text',
			'afl_ip',
			'afl_action',
			'afl_actions',
			'afl_var_dump',
			'afl_timestamp',
			'afl_namespace',
			'afl_title',
		];
		$res = $dbr->select(
			'abuse_filter_log',
			$selectFields,
			$conds,
			__METHOD__,
			[ 'ORDER BY' => 'afl_timestamp DESC' ]
		);

		// TODO: get the following from ConsequencesRegistry or sth else
		static $reversibleActions = [ 'block', 'blockautopromote', 'degroup' ];

		$results = [];
		foreach ( $res as $row ) {
			$actions = explode( ',', $row->afl_actions );
			$currentReversibleActions = array_intersect( $actions, $reversibleActions );
			if ( count( $currentReversibleActions ) ) {
				$vars = $this->varBlobStore->loadVarDump( $row->afl_var_dump );
				try {
					// The variable is not lazy-loaded
					$accountName = $vars->getComputedVariable( 'accountname' )->toNative();
				} catch ( UnsetVariableException $_ ) {
					$accountName = null;
				}
				$results[] = [
					'id' => $row->afl_id,
					'actions' => $currentReversibleActions,
					'vars' => $vars,
					'spec' => new ActionSpecifier(
						$row->afl_action,
						new TitleValue( (int)$row->afl_namespace, $row->afl_title ),
						$this->userFactory->newFromAnyId( (int)$row->afl_user, $row->afl_user_text ),
						$row->afl_ip,
						$accountName
					),
					'timestamp' => $row->afl_timestamp
				];
			}
		}

		return $results;
	}

	/**
	 * Loads parameters from request
	 */
	public function loadParameters() {
		$request = $this->getRequest();

		$this->filter = (int)$this->mParams[1];
		$this->periodStart = strtotime( $request->getText( 'wpPeriodStart' ) ) ?: null;
		$this->periodEnd = strtotime( $request->getText( 'wpPeriodEnd' ) ) ?: null;
		$this->reason = $request->getVal( 'wpReason' );
	}

	/**
	 * @return bool
	 */
	public function attemptRevert() {
		$filter = $this->filter;
		$token = $this->getRequest()->getVal( 'wpEditToken' );
		if ( !$this->getUser()->matchEditToken( $token, "abusefilter-revert-$filter" ) ) {
			return false;
		}

		$results = $this->doLookup();
		foreach ( $results as $result ) {
			foreach ( $result['actions'] as $action ) {
				$this->revertAction( $action, $result );
			}
		}
		$this->getOutput()->addHTML( Html::successBox(
			$this->msg(
				'abusefilter-revert-success',
				$filter,
				$this->getLanguage()->formatNum( $filter )
			)->parse()
		) );

		return true;
	}

	/**
	 * Helper method for typing
	 * @param string $action
	 * @param array $result
	 * @return ReversibleConsequence
	 */
	private function getConsequence( string $action, array $result ): ReversibleConsequence {
		$params = new Parameters(
			$this->filterLookup->getFilter( $this->filter, false ),
			false,
			$result['spec']
		);

		switch ( $action ) {
			case 'block':
				return $this->consequencesFactory->newBlock( $params, '', false );
			case 'blockautopromote':
				$duration = $this->getConfig()->get( 'AbuseFilterBlockAutopromoteDuration' ) * 86400;
				return $this->consequencesFactory->newBlockAutopromote( $params, $duration );
			case 'degroup':
				return $this->consequencesFactory->newDegroup( $params, $result['vars'] );
			default:
				throw new UnexpectedValueException( "Invalid action $action" );
		}
	}

	/**
	 * @param string $action
	 * @param array $result
	 * @return bool
	 */
	public function revertAction( string $action, array $result ): bool {
		$message = $this->msg(
			'abusefilter-revert-reason', $this->filter, $this->reason
		)->inContentLanguage()->text();

		$consequence = $this->getConsequence( $action, $result );
		return $consequence->revert( $this->getUser(), $message );
	}
}
