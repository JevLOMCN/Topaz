<?php

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace PageImages\Hooks;

use IContextSource;
use MediaWiki\Title\Title;
use MobileFrontend\Hooks\SpecialMobileEditWatchlistImagesHook;
use PageImages\PageImages;

/**
 * Hooks from MobileFrontend extension,
 * which is optional to use with this extension.
 */
class MobileFrontendHookHandlers implements SpecialMobileEditWatchlistImagesHook {

	/**
	 * SpecialMobileEditWatchlist::images hook handler, adds images to mobile watchlist A-Z view
	 *
	 * @param IContextSource $context Context object. Ignored
	 * @param array[] &$watchlist Array of relevant pages on the watchlist, sorted by namespace
	 * @param array[] &$images Array of images to populate
	 */
	public function onSpecialMobileEditWatchlist__images(
		IContextSource $context, array &$watchlist, array &$images
	) {
		$ids = [];
		foreach ( $watchlist as $ns => $pages ) {
			foreach ( array_keys( $pages ) as $dbKey ) {
				$title = Title::makeTitle( $ns, $dbKey );
				// Getting page ID here is safe because SpecialEditWatchlist::getWatchlistInfo()
				// uses LinkBatch
				$id = $title->getArticleID();
				if ( $id ) {
					$ids[$id] = $dbKey;
				}
			}
		}

		$data = PageImages::getImages( array_keys( $ids ) );
		foreach ( $data as $id => $page ) {
			if ( isset( $page['pageimage'] ) ) {
				$images[ $page['ns'] ][ $ids[$id] ] = $page['pageimage'];
			}
		}
	}

}
