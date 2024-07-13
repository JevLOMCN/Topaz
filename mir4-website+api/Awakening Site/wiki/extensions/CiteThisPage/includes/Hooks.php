<?php

namespace MediaWiki\Extension\CiteThisPage;

use MediaWiki\Config\Config;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class Hooks implements \MediaWiki\Hook\SidebarBeforeOutputHook {
	/**
	 * Checks, if the "cite this page" link should be added. By default the link is added to all
	 * pages in the main namespace, and additionally to pages, which are in one of the namespaces
	 * named in $wgCiteThisPageAdditionalNamespaces.
	 *
	 * @param Title|null $title
	 * @param Config|null $config
	 * @return bool
	 */
	private static function shouldAddLink( ?Title $title, ?Config $config ) {
		if ( !$title || !$config ) {
			return false;
		}

		$additionalNamespaces = $config->get( 'CiteThisPageAdditionalNamespaces' );

		return $title->isContentPage() ||
			(
				isset( $additionalNamespaces[$title->getNamespace()] ) &&
				$additionalNamespaces[$title->getNamespace()]
			);
	}

	/** @inheritDoc */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		$out = $skin->getOutput();
		$title = $out->getTitle();

		if ( !self::shouldAddLink( $title, $out->getConfig() ) ) {
			return;
		}

		$revid = $out->getRevisionId();

		if ( $revid === 0 || $revid === null ) {
			return;
		}

		$specialPage = SpecialPage::getTitleFor( 'CiteThisPage' );
		$citeURL = $specialPage->getLocalURL( [
				'page' => $title->getPrefixedDBkey(),
				'id' => $revid,
				'wpFormIdentifier' => 'titleform'
			]
		);

		$citeThisPageLink = [
			'id' => 't-cite',
			'href' => $citeURL,
			'icon' => 'quotes',
			'text' => $skin->msg( 'citethispage-link' )->text(),
			// Message keys: 'tooltip-citethispage', 'accesskey-citethispage'
			'single-id' => 'citethispage',
		];

		// Append link
		$sidebar['TOOLBOX']['citethispage'] = $citeThisPageLink;
	}
}
