<?php

namespace MediaWiki\Extension\AbuseFilter\Special;

use HtmlArmor;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\TitleValue;
use Xml;

/**
 * Parent class for AbuseFilter special pages.
 */
abstract class AbuseFilterSpecialPage extends SpecialPage {

	/** @var AbuseFilterPermissionManager */
	protected $afPermissionManager;

	/**
	 * @param string $name
	 * @param string $restriction
	 * @param AbuseFilterPermissionManager $afPermissionManager
	 */
	public function __construct(
		$name,
		$restriction,
		AbuseFilterPermissionManager $afPermissionManager
	) {
		parent::__construct( $name, $restriction );
		$this->afPermissionManager = $afPermissionManager;
	}

	/**
	 * @inheritDoc
	 */
	public function getShortDescription( string $path = '' ): string {
		switch ( $path ) {
			case 'AbuseFilter':
				return $this->msg( 'abusefilter-topnav-home' )->text();
			case 'AbuseFilter/history':
				return $this->msg( 'abusefilter-topnav-recentchanges' )->text();
			case 'AbuseFilter/examine':
				return $this->msg( 'abusefilter-topnav-examine' )->text();
			case 'AbuseFilter/test':
				return $this->msg( 'abusefilter-topnav-test' )->text();
			case 'AbuseFilter/tools':
				return $this->msg( 'abusefilter-topnav-tools' )->text();
			default:
				return parent::getShortDescription( $path );
		}
	}

	/**
	 * Get topbar navigation links definitions
	 *
	 * @return array
	 */
	private function getNavigationLinksInternal(): array {
		$performer = $this->getAuthority();

		$linkDefs = [
			'home' => 'AbuseFilter',
			'recentchanges' => 'AbuseFilter/history',
			'examine' => 'AbuseFilter/examine',
		];

		if ( $this->afPermissionManager->canViewAbuseLog( $performer ) ) {
			$linkDefs += [
				'log' => 'AbuseLog'
			];
		}

		if ( $this->afPermissionManager->canUseTestTools( $performer ) ) {
			$linkDefs += [
				'test' => 'AbuseFilter/test',
				'tools' => 'AbuseFilter/tools'
			];
		}

		return $linkDefs;
	}

	/**
	 * Return an array of strings representing page titles that are discoverable to end users via UI.
	 *
	 * @inheritDoc
	 */
	public function getAssociatedNavigationLinks(): array {
		$links = $this->getNavigationLinksInternal();
		return array_map( static function ( $name ) {
			return 'Special:' . $name;
		}, array_values( $links ) );
	}

	/**
	 * Add topbar navigation links
	 *
	 * @param string $pageType
	 */
	protected function addNavigationLinks( $pageType ) {
		// If the current skin supports sub menus nothing to do here.
		if ( $this->getSkin()->supportsMenu( 'associated-pages' ) ) {
			return;
		}
		$linkDefs = $this->getNavigationLinksInternal();
		$links = [];
		foreach ( $linkDefs as $name => $page ) {
			// Give grep a chance to find the usages:
			// abusefilter-topnav-home, abusefilter-topnav-recentchanges, abusefilter-topnav-test,
			// abusefilter-topnav-log, abusefilter-topnav-tools, abusefilter-topnav-examine
			$msgName = "abusefilter-topnav-$name";

			$msg = $this->msg( $msgName )->parse();

			if ( $name === $pageType ) {
				$links[] = Xml::tags( 'strong', null, $msg );
			} else {
				$links[] = $this->getLinkRenderer()->makeLink(
					new TitleValue( NS_SPECIAL, $page ),
					new HtmlArmor( $msg )
				);
			}
		}

		$linkStr = $this->msg( 'parentheses' )
			->rawParams( $this->getLanguage()->pipeList( $links ) )
			->escaped();
		$linkStr = $this->msg( 'abusefilter-topnav' )->parse() . " $linkStr";

		$linkStr = Xml::tags( 'div', [ 'class' => 'mw-abusefilter-navigation' ], $linkStr );

		$this->getOutput()->setSubtitle( $linkStr );
	}
}
