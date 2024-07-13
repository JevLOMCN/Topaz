<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\UrlUtils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

class AddRedLinks implements Wt2HtmlDOMProcessor {
	/**
	 * Add red links to a document.
	 *
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		'@phan-var Element|DocumentFragment $root';  // @var Element|DocumentFragment $root
		$allLinks = PHPUtils::iterable_to_array(
			DOMCompat::querySelectorAll( $root, 'a[rel~="mw:WikiLink"]' )
		);

		// Split up processing into chunks of 1000 so that we don't exceed LinkCache::MAX_SIZE
		$chunks = array_chunk( $allLinks, 1000 );
		foreach ( $chunks as $links ) {
			$titles = [];
			foreach ( $links as $a ) {
				$t = DOMCompat::getAttribute( $a, 'title' );
				if ( $t !== null ) {
					$titles[$t] = true;
				}
			}

			if ( !$titles ) {
				return;
			}

			$start = microtime( true );
			$titleMap = $env->getDataAccess()->getPageInfo( $env->getPageConfig(), array_keys( $titles ) );
			if ( $env->profiling() ) {
				$profile = $env->getCurrentProfile();
				$profile->bumpMWTime( "RedLinks", 1000 * ( microtime( true ) - $start ), "api" );
				$profile->bumpCount( "RedLinks" );
			}

			$prefixedTitleText = $env->getContextTitle()->getPrefixedText();

			foreach ( $links as $a ) {
				$k = DOMCompat::getAttribute( $a, 'title' );
				if ( $k === null ) {
					continue;
				}
				if ( empty( $titleMap[$k] ) ) {
					// Likely a consequence of T237535; can be removed once
					// that is fixed.
					$env->log( 'warn', 'We should have data for the title: ' . $k );
					continue;
				}
				$data = $titleMap[$k];
				$a->removeAttribute( 'class' ); // Clear all, if we're doing a pb2pb refresh

				$href = DOMCompat::getAttribute( $a, 'href' );
				$parsedURL = UrlUtils::parseUrl( $href ?? '' );

				$queryElts = [];
				if ( isset( $parsedURL['query'] ) ) {
					parse_str( $parsedURL['query'], $queryElts );
				}

				if (
					!empty( $data['missing'] ) && empty( $data['known'] ) &&
					$k !== $prefixedTitleText
				) {
					DOMCompat::getClassList( $a )->add( 'new' );
					WTUtils::addPageContentI18nAttribute( $a, 'title', 'red-link-title', [ $k ] );
					$queryElts['action'] = 'edit';
					$queryElts['redlink'] = '1';
				} else {
					if ( $k === $prefixedTitleText ) {
						if ( isset( $parsedURL['fragment'] ) ) {
							DOMCompat::getClassList( $a )->add( 'mw-selflink-fragment' );
						} else {
							DOMCompat::getClassList( $a )->add( 'mw-selflink', 'selflink' );
						}
						$a->removeAttribute( 'title' );
					}
					// Clear a potential redlink, if we're doing a pb2pb refresh
					// This is similar to what's happening in Html2Wt/RemoveRedLinks
					// and maybe that pass should just run before this one.
					if ( isset( $queryElts['action'] ) && $queryElts['action'] === 'edit' ) {
						unset( $queryElts['action'] );
					}
					if ( isset( $queryElts['redlink'] ) && $queryElts['redlink'] === '1' ) {
						unset( $queryElts['redlink'] );
					}
				}

				if ( count( $queryElts ) === 0 ) {
					// avoids the insertion of ? on empty query string
					$parsedURL['query'] = null;
				} else {
					$parsedURL['query'] = http_build_query( $queryElts );
				}
				$newHref = UrlUtils::assembleUrl( $parsedURL );

				$a->setAttribute( 'href', $newHref );

				if ( !empty( $data['redirect'] ) ) {
					DOMCompat::getClassList( $a )->add( 'mw-redirect' );
				}
				foreach ( $data['linkclasses'] ?? [] as $extraClass ) {
					DOMCompat::getClassList( $a )->add( $extraClass );
				}
			}
		}
	}
}
