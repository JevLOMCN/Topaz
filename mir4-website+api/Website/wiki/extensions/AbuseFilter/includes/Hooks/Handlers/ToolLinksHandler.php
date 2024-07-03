<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks\Handlers;

use IContextSource;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseLog;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use Wikimedia\IPUtils;

class ToolLinksHandler implements
	\MediaWiki\Hook\ContributionsToolLinksHook,
	\MediaWiki\Hook\HistoryPageToolLinksHook,
	\MediaWiki\Hook\UndeletePageToolLinksHook
{

	/** @var AbuseFilterPermissionManager */
	private $afPermManager;

	/**
	 * ToolLinksHandler constructor.
	 * @param AbuseFilterPermissionManager $afPermManager
	 */
	public function __construct( AbuseFilterPermissionManager $afPermManager ) {
		$this->afPermManager = $afPermManager;
	}

	/**
	 * @param int $id
	 * @param Title $nt
	 * @param array &$tools
	 * @param SpecialPage $sp for context
	 */
	public function onContributionsToolLinks( $id, Title $nt, array &$tools, SpecialPage $sp ) {
		$username = $nt->getText();
		if ( $this->afPermManager->canViewAbuseLog( $sp->getAuthority() )
			&& !IPUtils::isValidRange( $username )
		) {
			$linkRenderer = $sp->getLinkRenderer();
			$tools['abuselog'] = $linkRenderer->makeLink(
				$this->getSpecialPageTitle(),
				$sp->msg( 'abusefilter-log-linkoncontribs' )->text(),
				[ 'title' => $sp->msg( 'abusefilter-log-linkoncontribs-text',
					$username )->text(), 'class' => 'mw-contributions-link-abuse-log' ],
				[ 'wpSearchUser' => $username ]
			);
		}
	}

	/**
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param string[] &$links
	 */
	public function onHistoryPageToolLinks( IContextSource $context, LinkRenderer $linkRenderer, array &$links ) {
		if ( $this->afPermManager->canViewAbuseLog( $context->getAuthority() ) ) {
			$links[] = $linkRenderer->makeLink(
				$this->getSpecialPageTitle(),
				$context->msg( 'abusefilter-log-linkonhistory' )->text(),
				[ 'title' => $context->msg( 'abusefilter-log-linkonhistory-text' )->text() ],
				[ 'wpSearchTitle' => $context->getTitle()->getPrefixedText() ]
			);
		}
	}

	/**
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param string[] &$links
	 */
	public function onUndeletePageToolLinks( IContextSource $context, LinkRenderer $linkRenderer, array &$links ) {
		$show = $this->afPermManager->canViewAbuseLog( $context->getAuthority() );
		$action = $context->getRequest()->getVal( 'action', 'view' );

		// For 'history action', the link would be added by HistoryPageToolLinks hook.
		if ( $show && $action !== 'history' ) {
			$links[] = $linkRenderer->makeLink(
				$this->getSpecialPageTitle(),
				$context->msg( 'abusefilter-log-linkonundelete' )->text(),
				[ 'title' => $context->msg( 'abusefilter-log-linkonundelete-text' )->text() ],
				[ 'wpSearchTitle' => $context->getTitle()->getPrefixedText() ]
			);
		}
	}

	/**
	 * @codeCoverageIgnore Helper for tests
	 * @return LinkTarget
	 */
	private function getSpecialPageTitle(): LinkTarget {
		return defined( 'MW_PHPUNIT_TEST' )
			? new TitleValue( NS_SPECIAL, SpecialAbuseLog::PAGE_NAME )
			: SpecialPage::getTitleFor( SpecialAbuseLog::PAGE_NAME );
	}
}
