<?php

namespace MediaWiki\Extension\AbuseFilter\Pager;

use HtmlArmor;
use IContextSource;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\CentralDBNotAvailableException;
use MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseLog;
use MediaWiki\Linker\Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Pager\ReverseChronologicalPager;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\WikiMap\WikiMap;
use stdClass;
use Wikimedia\Rdbms\IResultWrapper;
use Xml;

class AbuseLogPager extends ReverseChronologicalPager {
	/**
	 * @var array
	 */
	private $conds;

	/** @var LinkBatchFactory */
	private $linkBatchFactory;

	/** @var PermissionManager */
	private $permissionManager;

	/** @var AbuseFilterPermissionManager */
	private $afPermissionManager;

	/** @var string */
	private $basePageName;

	/**
	 * @var string[] Map of [ id => show|hide ], for entries that we're currently (un)hiding
	 */
	private $hideEntries;

	/**
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param array $conds
	 * @param LinkBatchFactory $linkBatchFactory
	 * @param PermissionManager $permManager
	 * @param AbuseFilterPermissionManager $afPermissionManager
	 * @param string $basePageName
	 * @param string[] $hideEntries
	 */
	public function __construct(
		IContextSource $context,
		LinkRenderer $linkRenderer,
		array $conds,
		LinkBatchFactory $linkBatchFactory,
		PermissionManager $permManager,
		AbuseFilterPermissionManager $afPermissionManager,
		string $basePageName,
		array $hideEntries = []
	) {
		parent::__construct( $context, $linkRenderer );
		$this->conds = $conds;
		$this->linkBatchFactory = $linkBatchFactory;
		$this->permissionManager = $permManager;
		$this->afPermissionManager = $afPermissionManager;
		$this->basePageName = $basePageName;
		$this->hideEntries = $hideEntries;
	}

	/**
	 * @param stdClass $row
	 * @return string
	 */
	public function formatRow( $row ) {
		return $this->doFormatRow( $row );
	}

	/**
	 * @param stdClass $row
	 * @param bool $isListItem
	 * @return string
	 */
	public function doFormatRow( stdClass $row, bool $isListItem = true ): string {
		$performer = $this->getAuthority();
		$visibility = SpecialAbuseLog::getEntryVisibilityForUser( $row, $performer, $this->afPermissionManager );

		if ( $visibility !== SpecialAbuseLog::VISIBILITY_VISIBLE ) {
			return '';
		}

		$linkRenderer = $this->getLinkRenderer();
		$diffLink = false;

		if ( !$row->afl_wiki ) {
			$title = Title::makeTitle( $row->afl_namespace, $row->afl_title );

			$pageLink = $linkRenderer->makeLink(
				$title,
				null,
				[],
				[ 'redirect' => 'no' ]
			);
			if ( $row->rev_id ) {
				$diffLink = $linkRenderer->makeKnownLink(
					$title,
					new HtmlArmor( $this->msg( 'abusefilter-log-diff' )->parse() ),
					[],
					[ 'diff' => 'prev', 'oldid' => $row->rev_id ]
				);
			} elseif (
				isset( $row->ar_timestamp ) && $row->ar_timestamp
				&& $this->canSeeUndeleteDiffForPage( $title )
			) {
				$diffLink = $linkRenderer->makeKnownLink(
					SpecialPage::getTitleFor( 'Undelete' ),
					new HtmlArmor( $this->msg( 'abusefilter-log-diff' )->parse() ),
					[],
					[
						'diff' => 'prev',
						'target' => $title->getPrefixedText(),
						'timestamp' => $row->ar_timestamp,
					]
				);
			}
		} else {
			$pageLink = WikiMap::makeForeignLink( $row->afl_wiki, $row->afl_title );

			if ( $row->afl_rev_id ) {
				$diffUrl = WikiMap::getForeignURL( $row->afl_wiki, $row->afl_title );
				$diffUrl = wfAppendQuery( $diffUrl,
					[ 'diff' => 'prev', 'oldid' => $row->afl_rev_id ] );

				$diffLink = Linker::makeExternalLink( $diffUrl,
					$this->msg( 'abusefilter-log-diff' )->text() );
			}
		}

		if ( !$row->afl_wiki ) {
			// Local user
			$userLink = SpecialAbuseLog::getUserLinks( $row->afl_user, $row->afl_user_text );
		} else {
			$userLink = WikiMap::foreignUserLink( $row->afl_wiki, $row->afl_user_text ) . ' ' .
				$this->msg( 'parentheses' )->params( WikiMap::getWikiName( $row->afl_wiki ) )->escaped();
		}

		$lang = $this->getLanguage();
		$timestamp = htmlspecialchars( $lang->userTimeAndDate( $row->afl_timestamp, $this->getUser() ) );

		$actions_takenRaw = $row->afl_actions;
		if ( !strlen( trim( $actions_takenRaw ) ) ) {
			$actions_taken = $this->msg( 'abusefilter-log-noactions' )->escaped();
		} else {
			$actions = explode( ',', $actions_takenRaw );
			$displayActions = [];

			$specsFormatter = AbuseFilterServices::getSpecsFormatter();
			$specsFormatter->setMessageLocalizer( $this->getContext() );
			foreach ( $actions as $action ) {
				$displayActions[] = $specsFormatter->getActionDisplay( $action );
			}
			$actions_taken = $lang->commaList( $displayActions );
		}

		$filterID = $row->afl_filter_id;
		$global = $row->afl_global;

		if ( $global ) {
			// Pull global filter description
			$lookup = AbuseFilterServices::getFilterLookup();
			try {
				$filterObj = $lookup->getFilter( $filterID, true );
				$globalDesc = $filterObj->getName();
				$escaped_comments = Sanitizer::escapeHtmlAllowEntities( $globalDesc );
				$filter_hidden = $filterObj->isHidden();
			} catch ( CentralDBNotAvailableException $_ ) {
				$escaped_comments = $this->msg( 'abusefilter-log-description-not-available' )->escaped();
				// either hide all filters, including not hidden, or show all, including hidden
				// we choose the former
				$filter_hidden = true;
			}
		} else {
			$escaped_comments = Sanitizer::escapeHtmlAllowEntities(
				$row->af_public_comments ?? '' );
			$filter_hidden = $row->af_hidden;
		}

		if ( $this->afPermissionManager->canSeeLogDetailsForFilter( $performer, $filter_hidden ) ) {
			$actionLinks = [];

			if ( $isListItem ) {
				$detailsLink = $linkRenderer->makeKnownLink(
					SpecialPage::getTitleFor( $this->basePageName, $row->afl_id ),
					$this->msg( 'abusefilter-log-detailslink' )->text()
				);
				$actionLinks[] = $detailsLink;
			}

			$examineTitle = SpecialPage::getTitleFor( 'AbuseFilter', 'examine/log/' . $row->afl_id );
			$examineLink = $linkRenderer->makeKnownLink(
				$examineTitle,
				new HtmlArmor( $this->msg( 'abusefilter-changeslist-examine' )->parse() )
			);
			$actionLinks[] = $examineLink;

			if ( $diffLink ) {
				$actionLinks[] = $diffLink;
			}

			if ( !$isListItem && $this->afPermissionManager->canHideAbuseLog( $performer ) ) {
				// Link for hiding a single entry from the details view
				$hideLink = $linkRenderer->makeKnownLink(
					SpecialPage::getTitleFor( $this->basePageName, 'hide' ),
					$this->msg( 'abusefilter-log-hidelink' )->text(),
					[],
					[ "hideids[$row->afl_id]" => 1 ]
				);

				$actionLinks[] = $hideLink;
			}

			if ( $global ) {
				$centralDb = $this->getConfig()->get( 'AbuseFilterCentralDB' );
				$linkMsg = $this->msg( 'abusefilter-log-detailedentry-global' )
					->numParams( $filterID );
				if ( $centralDb !== null ) {
					$globalURL = WikiMap::getForeignURL(
						$centralDb,
						'Special:AbuseFilter/' . $filterID
					);
					$filterLink = Linker::makeExternalLink( $globalURL, $linkMsg->text() );
				} else {
					$filterLink = $linkMsg->escaped();
				}
			} else {
				$title = SpecialPage::getTitleFor( 'AbuseFilter', (string)$filterID );
				$linkText = $this->msg( 'abusefilter-log-detailedentry-local' )
					->numParams( $filterID )->text();
				$filterLink = $linkRenderer->makeKnownLink( $title, $linkText );
			}
			$description = $this->msg( 'abusefilter-log-detailedentry-meta' )->rawParams(
				$timestamp,
				$userLink,
				$filterLink,
				htmlspecialchars( $row->afl_action ),
				$pageLink,
				$actions_taken,
				$escaped_comments,
				$lang->pipeList( $actionLinks )
			)->params( $row->afl_user_text )->parse();
		} else {
			if ( $diffLink ) {
				$msg = 'abusefilter-log-entry-withdiff';
			} else {
				$msg = 'abusefilter-log-entry';
			}
			$description = $this->msg( $msg )->rawParams(
				$timestamp,
				$userLink,
				htmlspecialchars( $row->afl_action ),
				$pageLink,
				$actions_taken,
				$escaped_comments,
				// Passing $7 to 'abusefilter-log-entry' will do nothing, as it's not used.
				$diffLink
			)->params( $row->afl_user_text )->parse();
		}

		$attribs = null;
		if (
			$this->isHidingEntry( $row ) === true ||
			// If isHidingEntry is false, we've just unhidden the row
			( $this->isHidingEntry( $row ) === null && $row->afl_deleted )
		) {
			$attribs = [ 'class' => 'mw-abusefilter-log-hidden-entry' ];
		}
		if ( self::entryHasAssociatedDeletedRev( $row ) ) {
			$description .= ' ' .
				$this->msg( 'abusefilter-log-hidden-implicit' )->parse();
		}

		if ( $isListItem && !$this->hideEntries && $this->afPermissionManager->canHideAbuseLog( $performer ) ) {
			// Checkbox for hiding multiple entries, single entries are handled above
			$description = Xml::check( 'hideids[' . $row->afl_id . ']' ) . ' ' . $description;
		}

		if ( $isListItem ) {
			return Xml::tags( 'li', $attribs, $description );
		} else {
			return Xml::tags( 'span', $attribs, $description );
		}
	}

	/**
	 * Can this user see diffs generated by Special:Undelete for the page?
	 * @see MediaWiki\Specials\SpecialUndelete
	 * @param LinkTarget $page
	 *
	 * @return bool
	 */
	private function canSeeUndeleteDiffForPage( LinkTarget $page ): bool {
		if ( !$this->canSeeUndeleteDiffs() ) {
			return false;
		}

		foreach ( [ 'deletedtext', 'undelete' ] as $action ) {
			if ( $this->permissionManager->userCan(
				$action, $this->getUser(), $page, PermissionManager::RIGOR_QUICK
			) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Can this user see diffs generated by Special:Undelete?
	 * @see MediaWiki\Specials\SpecialUndelete
	 *
	 * @return bool
	 */
	private function canSeeUndeleteDiffs(): bool {
		if ( !$this->permissionManager->userHasRight( $this->getUser(), 'deletedhistory' ) ) {
			return false;
		}

		return $this->permissionManager->userHasAnyRight(
			$this->getUser(), 'deletedtext', 'undelete' );
	}

	/**
	 * @return array
	 */
	public function getQueryInfo() {
		$info = [
			'tables' => [ 'abuse_filter_log', 'abuse_filter', 'revision' ],
			'fields' => [
				$this->mDb->tableName( 'abuse_filter_log' ) . '.*',
				$this->mDb->tableName( 'abuse_filter' ) . '.*',
				'rev_id',
			],
			'conds' => $this->conds,
			'join_conds' => [
				'abuse_filter' => [
					'LEFT JOIN',
					[ 'af_id=afl_filter_id', 'afl_global' => 0 ],
				],
				'revision' => [
					'LEFT JOIN',
					[
						'afl_wiki IS NULL',
						'afl_rev_id IS NOT NULL',
						'rev_id=afl_rev_id',
					]
				],
			],
		];

		if ( $this->canSeeUndeleteDiffs() ) {
			$info['tables'][] = 'archive';
			$info['fields'][] = 'ar_timestamp';
			$info['join_conds']['archive'] = [
				'LEFT JOIN',
				[
					'afl_wiki IS NULL',
					'afl_rev_id IS NOT NULL',
					'rev_id IS NULL',
					'ar_rev_id=afl_rev_id',
				]
			];
		}

		if ( !$this->afPermissionManager->canSeeHiddenLogEntries( $this->getAuthority() ) ) {
			$info['conds']['afl_deleted'] = 0;
		}

		return $info;
	}

	/**
	 * @param IResultWrapper $result
	 */
	protected function preprocessResults( $result ) {
		if ( $this->getNumRows() === 0 ) {
			return;
		}

		$lb = $this->linkBatchFactory->newLinkBatch();
		$lb->setCaller( __METHOD__ );
		foreach ( $result as $row ) {
			// Only for local wiki results
			if ( !$row->afl_wiki ) {
				$lb->add( $row->afl_namespace, $row->afl_title );
				$lb->add( NS_USER, $row->afl_user_text );
				$lb->add( NS_USER_TALK, $row->afl_user_text );
			}
		}
		$lb->execute();
		$result->seek( 0 );
	}

	/**
	 * @param stdClass $row
	 * @return bool
	 * @todo This should be moved elsewhere
	 */
	private static function entryHasAssociatedDeletedRev( stdClass $row ): bool {
		if ( !$row->afl_rev_id ) {
			return false;
		}
		$revision = MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getRevisionById( $row->afl_rev_id );
		return $revision && $revision->getVisibility() !== 0;
	}

	/**
	 * Check whether the entry passed in is being currently hidden/unhidden.
	 * This is used to format the entries list shown when updating visibility, and is necessary because
	 * when we decide whether to display the entry as hidden the DB hasn't been updated yet.
	 *
	 * @param stdClass $row
	 * @return bool|null True if just hidden, false if just unhidden, null if untouched
	 */
	private function isHidingEntry( stdClass $row ): ?bool {
		if ( isset( $this->hideEntries[ $row->afl_id ] ) ) {
			return $this->hideEntries[ $row->afl_id ] === 'hide';
		}
		return null;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function getIndexField() {
		return 'afl_timestamp';
	}
}
