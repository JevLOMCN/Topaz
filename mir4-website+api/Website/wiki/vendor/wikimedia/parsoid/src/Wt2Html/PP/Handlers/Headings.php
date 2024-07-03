<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Handlers;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\TitleException;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;

class Headings {
	/**
	 * See the safe-heading transform code in Parser::finalizeHeadings in core
	 *
	 * Allowed HTML tags are:
	 * - <sup> and <sub> (T10393)
	 * - <i> (T28375)
	 * - <b> (r105284)
	 * - <bdi> (T74884)
	 * - <span dir="rtl"> and <span dir="ltr"> (T37167)
	 *   (handled separately in code below)
	 * - <s> and <strike> (T35715)
	 * - <q> (T251672)
	 */
	private const ALLOWED_NODES_IN_ANCHOR = [ 'span', 'sup', 'sub', 'i', 'b', 'bdi', 's', 'strike', 'q' ];

	/**
	 * This method implements the equivalent of the regexp-based safe-headline
	 * transform in Parser::finalizeHeadings in core.
	 *
	 * @param Node $node
	 */
	private static function processHeadingContent( Node $node ): void {
		$c = $node->firstChild;
		while ( $c ) {
			$next = $c->nextSibling;
			if ( $c instanceof Element ) {
				$cName = DOMCompat::nodeName( $c );
				if ( DOMUtils::hasTypeOf( $c, 'mw:LanguageVariant' ) ) {
					// Special case for -{...}-
					$dp = DOMDataUtils::getDataParsoid( $c );
					$node->replaceChild(
						$node->ownerDocument->createTextNode( $dp->src ?? '' ), $c
					);
				} elseif ( in_array( $cName, [ 'style', 'script' ], true ) ) {
					# Remove any <style> or <script> tags (T198618)
					$node->removeChild( $c );
				} else {
					self::processHeadingContent( $c );
					if ( !$c->firstChild ) {
						// Empty now - strip it!
						$node->removeChild( $c );
					} elseif (
						!in_array( $cName, self::ALLOWED_NODES_IN_ANCHOR, true ) ||
						( $cName === 'span' && DOMUtils::hasTypeOf( $c, 'mw:Entity' ) )
					) {
						# Strip all unallowed tag wrappers
						DOMUtils::migrateChildren( $c, $node, $next );
						$next = $c->nextSibling;
						$node->removeChild( $c );
					} else {
						# We strip any parameter from accepted tags except dir="rtl|ltr" from <span>,
						# to allow setting directionality in toc items.
						foreach ( DOMUtils::attributes( $c ) as $key => $val ) {
							if ( $cName === 'span' ) {
								if ( $key !== 'dir' || ( $val !== 'ltr' && $val !== 'rtl' ) ) {
									$c->removeAttribute( $key );
								}
							} else {
								$c->removeAttribute( $key );
							}
						}
					}
				}
			} elseif ( !( $c instanceof Text ) ) {
				// Strip everying else but text nodes
				$node->removeChild( $c );
			}

			$c = $next;
		}
	}

	/**
	 * Generate anchor ids that the PHP parser assigns to headings.
	 * This is to ensure that links that are out there in the wild
	 * continue to be valid links into Parsoid HTML.
	 * @param Node $node
	 * @param Env $env
	 * @return bool
	 */
	public static function genAnchors( Node $node, Env $env ): bool {
		if ( !DOMUtils::isHeading( $node ) ) {
			return true;
		}
		'@phan-var Element $node';  /** @var Element $node */

		// Deep clone the heading to mutate it to strip unwanted tags and attributes.
		$clone = DOMDataUtils::cloneNode( $node, true );
		'@phan-var Element $clone'; // @var Element $clone
		// Don't bother storing data-attribs on $clone,
		// processHeadingContent is about to strip them

		self::processHeadingContent( $clone );
		$buf = DOMCompat::getInnerHTML( $clone );
		$line = trim( $buf );

		$dp = DOMDataUtils::getDataParsoid( $node );
		$tmp = $dp->getTemp();

		// Cannot generate an anchor id if the heading already has an id!
		//
		// NOTE: Divergence from PHP parser behavior.
		//
		// The PHP parser generates a <h*><span id="anchor-id-here-">..</span><h*>
		// So, it can preserve the existing id if any. However, in Parsoid, we are
		// generating a <h* id="anchor-id-here"> ..</h*> => we either overwrite or
		// preserve the existing id and use it for TOC, etc. We choose to preserve it.
		if ( $node->hasAttribute( 'id' ) ) {
			$linkAnchorId = DOMCompat::getAttribute( $node, 'id' );
			$dp->reusedId = true;
			$tmp->section = [
				'line' => $line,
				'linkAnchor' => $linkAnchorId,
			];
			return true;
		}

		// Additional processing for $anchor
		$anchorText = $clone->textContent; // strip all tags
		$anchorText = Sanitizer::normalizeSectionNameWhiteSpace( $anchorText );
		$anchorText = self::normalizeSectionName( $anchorText, $env );

		# NOTE: Parsoid defaults to html5 mode. So, if we want to replicate
		# legacy output, we should handle that explicitly.
		$anchorId = Sanitizer::escapeIdForAttribute( $anchorText );
		$linkAnchorId = Sanitizer::escapeIdForLink( $anchorText );
		$fallbackId = Sanitizer::escapeIdForAttribute( $anchorText, Sanitizer::ID_FALLBACK );
		if ( $anchorId === $fallbackId ) {
			$fallbackId = null; /* not needed */
		}

		// The ids need to be unique, but we'll enforce this in a post-processing
		// step.

		$node->setAttribute( 'id', $anchorId );
		$tmp->section = [
			'line' => $line,
			'linkAnchor' => $linkAnchorId,
		];

		if ( $fallbackId ) {
			$span = $node->ownerDocument->createElement( 'span' );
			$span->setAttribute( 'id', $fallbackId );
			DOMUtils::addTypeOf( $span, 'mw:FallbackId' );
			$nodeDsr = DOMDataUtils::getDataParsoid( $node )->dsr ?? null;
			// Set a zero-width dsr range for the fallback id
			if ( Utils::isValidDSR( $nodeDsr ) ) {
				$offset = $nodeDsr->innerStart();
				DOMDataUtils::getDataParsoid( $span )->dsr = new DomSourceRange( $offset, $offset, null, null );
			}
			$node->insertBefore( $span, $node->firstChild );
		}

		return true;
	}

	/**
	 * see Parser::normalizeSectionName in Parser.php and T90902
	 * @param string $text
	 * @param Env $env
	 * @return string
	 */
	private static function normalizeSectionName( string $text, Env $env ): string {
		try {
			$title = $env->makeTitleFromURLDecodedStr( "#{$text}" );
			return $title->getFragment();
		} catch ( TitleException $e ) {
			return $text;
		}
	}

	public static function dedupeHeadingIds( array &$seenIds, Node $node ): bool {
		// NOTE: This is not completely compliant with how PHP parser does it.
		// If there is an id in the doc elsewhere, this will assign
		// the heading a suffixed id, whereas the PHP parser processes
		// headings in textual order and can introduce duplicate ids
		// in a document in the process.
		//
		// However, we believe this implementation behavior is more
		// consistent when handling this edge case, and in the common
		// case (where heading ids won't conflict with ids elsewhere),
		// matches PHP parser behavior.
		if ( !$node instanceof Element ) {
			// Not an Element
			return true;
		}

		$origKey = DOMCompat::getAttribute( $node, 'id' );
		if ( $origKey === null ) {
			return true;
		}
		// IE 7 required attributes to be case-insensitively unique (T12721)
		// but it did not support non-ASCII IDs. We don't support IE 7 anymore,
		// but changing the algorithm would change the relevant fragment URLs.
		// This case folding and matching algorithm has to stay exactly the
		// same to preserve external links to the page.
		$key = strtolower( $origKey );
		if ( !isset( $seenIds[$key] ) ) {
			$seenIds[$key] = 1;
			return true;
		}
		// Only update headings and legacy links (first children of heading)
		$isHeading = DOMUtils::isHeading( $node );
		if ( $isHeading || WTUtils::isFallbackIdSpan( $node ) ) {
			$suffix = ++$seenIds[$key];
			while ( !empty( $seenIds[$key . '_' . $suffix] ) ) {
				$suffix++;
				$seenIds[$key]++;
			}
			$node->setAttribute( 'id', $origKey . '_' . $suffix );
			if ( $isHeading ) {
				$tmp = DOMDataUtils::getDataParsoid( $node )->getTemp();
				$linkAnchorId = $tmp->section['linkAnchor'];
				$tmp->section['linkAnchor'] = $linkAnchorId . '_' . $suffix;
			}
			$seenIds[$key . '_' . $suffix] = 1;
		}
		return true;
	}
}
