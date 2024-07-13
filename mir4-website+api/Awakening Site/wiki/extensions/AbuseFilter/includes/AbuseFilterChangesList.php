<?php

namespace MediaWiki\Extension\AbuseFilter;

use HtmlArmor;
use IContextSource;
use LogFormatter;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\SpecialPage\SpecialPage;
use OldChangesList;
use RecentChange;

class AbuseFilterChangesList extends OldChangesList {

	/**
	 * @var string
	 */
	private $testFilter;

	/**
	 * @param IContextSource $context
	 * @param string $testFilter
	 */
	public function __construct( IContextSource $context, $testFilter ) {
		parent::__construct( $context );
		$this->testFilter = $testFilter;
	}

	/**
	 * @param string &$s
	 * @param RecentChange &$rc
	 * @param string[] &$classes
	 */
	public function insertExtra( &$s, &$rc, &$classes ) {
		if ( (int)$rc->getAttribute( 'rc_deleted' ) !== 0 ) {
			$s .= ' ' . $this->msg( 'abusefilter-log-hidden-implicit' )->parse();
			if ( !$this->userCan( $rc, RevisionRecord::SUPPRESSED_ALL ) ) {
				// Remember to keep this in sync with the CheckMatch API
				return;
			}
		}

		$examineParams = [];
		if ( $this->testFilter && strlen( $this->testFilter ) < 2000 ) {
			// Since this is GETed, don't send it if it's too long to prevent broken URLs 2000 is taken from
			// https://stackoverflow.com/questions/417142/what-is-the-maximum-length-of-a-url-
			// in-different-browsers/417184#417184
			$examineParams['testfilter'] = $this->testFilter;
		}

		$title = SpecialPage::getTitleFor( 'AbuseFilter', 'examine/' . $rc->getAttribute( 'rc_id' ) );
		$examineLink = $this->linkRenderer->makeLink(
			$title,
			new HtmlArmor( $this->msg( 'abusefilter-changeslist-examine' )->parse() ),
			[],
			$examineParams
		);

		$s .= ' ' . $this->msg( 'parentheses' )->rawParams( $examineLink )->escaped();

		// Add CSS classes for match and not match
		if ( isset( $rc->filterResult ) ) {
			$class = $rc->filterResult ?
				'mw-abusefilter-changeslist-match' :
				'mw-abusefilter-changeslist-nomatch';

			$classes[] = $class;
		}
	}

	/**
	 * Overridden as a hacky workaround for T273387. Yuck!
	 * @inheritDoc
	 */
	public function recentChangesLine( &$rc, $watched = false, $linenumber = null ) {
		$par = parent::recentChangesLine( $rc, $watched, $linenumber );
		if ( $par === false || $par === '' ) {
			return $par;
		}
		$ret = preg_replace( '/<\/li>$/', '', $par );
		if ( $rc->getAttribute( 'rc_source' ) === 'flow' ) {
			$classes = [];
			$this->insertExtra( $ret, $rc, $classes );
		}
		return $ret . '</li>';
	}

	/**
	 * Insert links to user page, user talk page and eventually a blocking link.
	 *   Like the parent, but don't hide details if user can see them.
	 *
	 * @param string &$s HTML to update
	 * @param RecentChange &$rc
	 */
	public function insertUserRelatedLinks( &$s, &$rc ) {
		$links = $this->getLanguage()->getDirMark() . Linker::userLink( $rc->getAttribute( 'rc_user' ),
				$rc->getAttribute( 'rc_user_text' ) ) .
				Linker::userToolLinks( $rc->getAttribute( 'rc_user' ), $rc->getAttribute( 'rc_user_text' ) );

		if ( $this->isDeleted( $rc, RevisionRecord::DELETED_USER ) ) {
			if ( $this->userCan( $rc, RevisionRecord::DELETED_USER ) ) {
				$s .= ' <span class="history-deleted">' . $links . '</span>';
			} else {
				$s .= ' <span class="history-deleted">' .
					$this->msg( 'rev-deleted-user' )->escaped() . '</span>';
			}
		} else {
			$s .= $links;
		}
	}

	/**
	 * Insert a formatted comment. Like the parent, but don't hide details if user can see them.
	 * @param RecentChange $rc
	 * @return string
	 */
	public function insertComment( $rc ) {
		if ( $this->isDeleted( $rc, RevisionRecord::DELETED_COMMENT ) ) {
			if ( $this->userCan( $rc, RevisionRecord::DELETED_COMMENT ) ) {
				return ' <span class="history-deleted">' .
					MediaWikiServices::getInstance()->getCommentFormatter()
						->formatBlock( $rc->getAttribute( 'rc_comment' ), $rc->getTitle() ) . '</span>';
			} else {
				return ' <span class="history-deleted">' .
					$this->msg( 'rev-deleted-comment' )->escaped() . '</span>';
			}
		} else {
			return MediaWikiServices::getInstance()->getCommentFormatter()
				->formatBlock( $rc->getAttribute( 'rc_comment' ), $rc->getTitle() );
		}
	}

	/**
	 * Insert a formatted action. The same as parent, but with a different audience in LogFormatter
	 *
	 * @param RecentChange $rc
	 * @return string
	 */
	public function insertLogEntry( $rc ) {
		$formatter = LogFormatter::newFromRow( $rc->getAttributes() );
		$formatter->setContext( $this->getContext() );
		$formatter->setAudience( LogFormatter::FOR_THIS_USER );
		$formatter->setShowUserToolLinks( true );
		$mark = $this->getLanguage()->getDirMark();
		return $formatter->getActionText() . " $mark" . $formatter->getComment();
	}

	/**
	 * @param string &$s
	 * @param RecentChange &$rc
	 */
	public function insertRollback( &$s, &$rc ) {
		// Kill rollback links.
	}
}
