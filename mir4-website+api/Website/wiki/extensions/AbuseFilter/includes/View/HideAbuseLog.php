<?php

namespace MediaWiki\Extension\AbuseFilter\View;

use HTMLForm;
use IContextSource;
use LogEventsList;
use LogPage;
use ManualLogEntry;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Pager\AbuseLogPager;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\LBFactory;
use Xml;

class HideAbuseLog extends AbuseFilterView {

	/** @var LBFactory */
	private $lbFactory;

	/** @var int[] */
	private $hideIDs;

	/**
	 * @param LBFactory $lbFactory
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param string $basePageName
	 */
	public function __construct(
		LBFactory $lbFactory,
		AbuseFilterPermissionManager $afPermManager,
		IContextSource $context,
		LinkRenderer $linkRenderer,
		string $basePageName
	) {
		parent::__construct( $afPermManager, $context, $linkRenderer, $basePageName, [] );
		$this->lbFactory = $lbFactory;
		$this->hideIDs = array_keys( $this->getRequest()->getArray( 'hideids', [] ) );
	}

	/**
	 * Shows the page
	 */
	public function show(): void {
		$output = $this->getOutput();
		$output->enableOOUI();

		if ( !$this->afPermManager->canHideAbuseLog( $this->getAuthority() ) ) {
			$output->addWikiMsg( 'abusefilter-log-hide-forbidden' );
			return;
		}

		if ( !$this->hideIDs ) {
			$output->addWikiMsg( 'abusefilter-log-hide-no-selected' );
			return;
		}

		// TODO DI
		$pager = new AbuseLogPager(
			$this->getContext(),
			MediaWikiServices::getInstance()->getLinkRenderer(),
			[ 'afl_id' => $this->hideIDs ],
			MediaWikiServices::getInstance()->getLinkBatchFactory(),
			MediaWikiServices::getInstance()->getPermissionManager(),
			$this->afPermManager,
			$this->basePageName,
			array_fill_keys( $this->hideIDs, $this->getRequest()->getVal( 'wpshoworhide' ) )
		);
		$pager->doQuery();
		if ( $pager->getResult()->numRows() === 0 ) {
			$output->addWikiMsg( 'abusefilter-log-hide-no-selected' );
			return;
		}

		$output->addModuleStyles( 'mediawiki.interface.helpers.styles' );
		$output->wrapWikiMsg(
			"<strong>$1</strong>",
			[
				'abusefilter-log-hide-selected',
				$this->getLanguage()->formatNum( count( $this->hideIDs ) )
			]
		);
		$output->addHTML( Xml::tags( 'ul', [ 'class' => 'plainlinks' ], $pager->getBody() ) );

		$hideReasonsOther = $this->msg( 'revdelete-reasonotherlist' )->text();
		$hideReasons = $this->msg( 'revdelete-reason-dropdown-suppress' )->inContentLanguage()->text();
		$hideReasons = Xml::listDropdownOptions( $hideReasons, [ 'other' => $hideReasonsOther ] );

		$formInfo = [
			'showorhide' => [
				'type' => 'radio',
				'label-message' => 'abusefilter-log-hide-set-visibility',
				'options-messages' => [
					'abusefilter-log-hide-show' => 'show',
					'abusefilter-log-hide-hide' => 'hide'
				],
				'default' => 'hide',
				'flatlist' => true
			],
			'dropdownreason' => [
				'type' => 'select',
				'options' => $hideReasons,
				'label-message' => 'abusefilter-log-hide-reason'
			],
			'reason' => [
				'type' => 'text',
				'label-message' => 'abusefilter-log-hide-reason-other',
			],
		];

		$actionURL = $this->getTitle( 'hide' )->getLocalURL( [ 'hideids' => array_fill_keys( $this->hideIDs, 1 ) ] );
		HTMLForm::factory( 'ooui', $formInfo, $this->getContext() )
			->setAction( $actionURL )
			->setWrapperLegend( $this->msg( 'abusefilter-log-hide-legend' )->text() )
			->setSubmitCallback( [ $this, 'saveHideForm' ] )
			->showAlways();

		// Show suppress log for this entry. Hack: since every suppression is performed on a
		// totally different page (i.e. Special:AbuseLog/xxx), we use showLogExtract without
		// specifying a title and then adding it in conds.
		// This isn't shown if the request was posted because we update visibility in a DeferredUpdate, so it would
		// display outdated info that might confuse the user.
		// TODO Can we improve this somehow?
		if ( !$this->getRequest()->wasPosted() ) {
			$suppressLogPage = new LogPage( 'suppress' );
			$output->addHTML( "<h2>" . $suppressLogPage->getName()->escaped() . "</h2>\n" );
			$searchTitles = [];
			foreach ( $this->hideIDs as $id ) {
				$searchTitles[] = $this->getTitle( (string)$id )->getDBkey();
			}
			$conds = [ 'log_namespace' => NS_SPECIAL, 'log_title' => $searchTitles ];
			LogEventsList::showLogExtract( $output, 'suppress', '', '', [ 'conds' => $conds ] );
		}
	}

	/**
	 * Process the hide form after submission. This performs the actual visibility update. Used as callback by HTMLForm
	 *
	 * @param array $fields
	 * @return bool|array True on success, array of error message keys otherwise
	 */
	public function saveHideForm( array $fields ) {
		// Determine which rows actually have to be changed
		$dbw = $this->lbFactory->getPrimaryDatabase();
		$newValue = $fields['showorhide'] === 'hide' ? 1 : 0;
		$actualIDs = $dbw->selectFieldValues(
			'abuse_filter_log',
			'afl_id',
			[ 'afl_id' => $this->hideIDs, "afl_deleted != $newValue" ],
			__METHOD__
		);
		if ( !count( $actualIDs ) ) {
			return [ 'abusefilter-log-hide-no-change' ];
		}

		$dbw->update(
			'abuse_filter_log',
			[ 'afl_deleted' => $newValue ],
			[ 'afl_id' => $actualIDs ],
			__METHOD__
		);

		// Log in a DeferredUpdates to avoid potential flood
		DeferredUpdates::addCallableUpdate( function () use ( $fields, $actualIDs ) {
			$reason = $fields['dropdownreason'];
			if ( $reason === 'other' ) {
				$reason = $fields['reason'];
			} elseif ( $fields['reason'] !== '' ) {
				$reason .=
					$this->msg( 'colon-separator' )->inContentLanguage()->text() . $fields['reason'];
			}

			$action = $fields['showorhide'] === 'hide' ? 'hide-afl' : 'unhide-afl';
			foreach ( $actualIDs as $logid ) {
				$logEntry = new ManualLogEntry( 'suppress', $action );
				$logEntry->setPerformer( $this->getUser() );
				$logEntry->setTarget( $this->getTitle( $logid ) );
				$logEntry->setComment( $reason );
				$logEntry->insert();
			}
		} );

		$count = count( $actualIDs );
		$this->getOutput()->prependHTML(
			Html::successBox(
				$this->msg( 'abusefilter-log-hide-done' )->params(
					$this->getLanguage()->formatNum( $count ),
					// Messages used: abusefilter-log-hide-done-hide, abusefilter-log-hide-done-show
					$this->msg( 'abusefilter-log-hide-done-' . $fields['showorhide'] )->numParams( $count )->text()
				)->escaped()
			)
		);

		return true;
	}

}
