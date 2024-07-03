<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use Composer\Semver\Semver;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\Core\SelserData;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\Timing;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;

/**
 * This is a Serializer class that will compare two versions of a DOM
 * and re-use the original wikitext for unmodified regions of the DOM.
 * Originally this relied on special change markers inserted by the
 * editor, but we now generate these ourselves using DOMDiff.
 */
class SelectiveSerializer {

	/** @var Env */
	private $env;

	private $wts;
	private $trace;

	/** @var SelserData */
	private $selserData;

	public function __construct( Env $env, array $options ) {
		$this->env = $env;
		$this->wts = new WikitextSerializer( $env, $options );
		$this->selserData = $options['selserData'];

		// Debug options
		$this->trace = $this->env->hasTraceFlag( 'selser' );
	}

	/**
	 * Wrap text node children of nodes with name $nodeName in <span> tags and
	 * compute the DSR values for those span tags.
	 *
	 * This helps DOMDiff mark up diffs of content in these nodes at a more fine-grained level.
	 *
	 * These DSR values rely on availability of information about trimmed leading
	 * and trailing WS in these nodes in the wt->html direction. Given this info,
	 * on the original unedited DOM, the computed DSR values for span tags wrapping
	 * text nodes will be accurate.
	 *
	 * However, for the edited DOM with modified nodes, the computation is necessarily
	 * speculative and as such, the computed DSR values may be bogus. Given this,
	 * we rely on DOMDiff to diff the data-parsoid attribute and mark these nodes as
	 * modified because of the mismatched dsr values. If so, these span tags will never
	 * have selser reuse apply to them and the speculatively computed DSR values will
	 * be discarded.
	 *
	 * @param Element $body
	 * @param string $nodeName
	 */
	private function wrapTextChildrenOfNode( Element $body, string $nodeName ): void {
		// Note that while it might seem that only the first and last child need to be
		// wrapped, when nested list items are added, the previously last child of
		// a list item become an intermediate child in the new DOM. Without the span
		// wrapper, trailing trimmed whitespace gets dropped.
		$inListItem = isset( Consts::$HTML['ListItemTags'][$nodeName] );
		foreach ( DOMCompat::querySelectorAll( $body, $nodeName ) as $elt ) {
			if ( WTUtils::isLiteralHTMLNode( $elt ) ) {
				continue;
			}

			// Skip items with about id => part of templates / extensions like Cite
			// CAVEAT: In some cases, this might be bailing out a little too early.
			// For example, where certain extensions might actually support nested DSR
			// values inside and where <li> items in them might benefit. But, given that
			// so far, such extensions are more the exception than the norm, we will take
			// the easy way out here and revisit this if dirty diffs for those <li> items
			// merit further action in the future.
			if ( $elt->hasAttribute( 'about' ) ) {
				continue;
			}

			// No point wrapping text nodes if there is no usable DSR
			$eltDSR = DOMDataUtils::getDataParsoid( $elt )->dsr ?? null;
			if ( !Utils::isValidDSR( $eltDSR ) ) {
				continue;
			}

			$doc = $body->ownerDocument;
			$firstChild = $c = $elt->firstChild;
			$start = $eltDSR->innerStart();
			while ( $c ) {
				if ( $eltDSR && $c === $firstChild ) {
					if ( $eltDSR->leadingWS < 0 ) {
						// We don't have accurate information about the length of trimmed WS.
						// So, we cannot wrap this text node with a <span>.
						break;
					} else {
						$start += $eltDSR->leadingWS;
					}
				}
				$next = $c->nextSibling;
				if ( $c instanceof Text ) {
					$text = $c->nodeValue;
					$len = strlen( $text );

					// Don't wrap newlines since single-line-context handling will convert these
					// newlines into spaces and introduce dirty-diffs. Leaving nls outside the
					// wrapped text lets it be handled as separator text and emitted appropriately.
					if ( $len > 0 && $text[$len - 1] === "\n" ) {
						$text = rtrim( $text, "\n" );
						$numOfNls = $len - strlen( $text );
						$nl = str_repeat( "\n", $numOfNls );
						$len -= $numOfNls;
					} else {
						$nl = null;

						// Detect last child of "original" item and tack on trailingWS width
						// to the contents of this text node. If this is a list item and
						// we added a nested list, that nested list will be the last item.
						//
						// Note that trailingWS is only captured for the last line, so if
						// the text ends in a newline (the "if" condition), we shouldn't need
						// to do this.
						if ( $eltDSR && (
							!$next || (
								$inListItem && DOMUtils::isList( $next ) && WTUtils::isNewElt( $next )
							)
						) ) {
							$len += $eltDSR->trailingWS;
						}
					}

					$span = $doc->createElement( 'span' );
					$span->setAttribute( 'data-mw-selser-wrapper', '' );
					$dp = DOMDataUtils::getDataParsoid( $span );
					$dp->dsr = new DomSourceRange( $start, $start + $len, 0, 0 );
					$start += $len;

					if ( $nl ) {
						$elt->insertBefore( $span, $c );
						$span->appendChild( $doc->createTextNode( $text ) );
						$c->nodeValue = $nl;
						// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
						$start += $numOfNls;
					} else {
						$elt->replaceChild( $span, $c );
						$span->appendChild( $c );
					}
				} elseif ( $c instanceof Comment ) {
					$start += WTUtils::decodedCommentLength( $c );
				} elseif ( $c instanceof Element ) {
					// No point wrapping following text nodes if there won't be any usable DSR
					$cDSR = DOMDataUtils::getDataParsoid( $c )->dsr ?? null;
					if ( !Utils::isValidDSR( $cDSR ) ) {
						break;
					}
					$start = $cDSR->end;
					$next = $c->hasAttribute( 'about' ) ? WTUtils::skipOverEncapsulatedContent( $c ) : $next;
				}
				$c = $next;
			}
		}
	}

	private function preprocessDOMForSelser( Element $body ): void {
		if ( Semver::satisfies( $this->env->getInputContentVersion(), '>=2.1.2' ) ) {
			// Wrap text node children of <li> elements in dummy spans
			$this->wrapTextChildrenOfNode( $body, 'li' );
			$this->wrapTextChildrenOfNode( $body, 'dd' );
		}
	}

	/**
	 * Selectively serialize an HTML DOM.
	 *
	 * WARNING: You probably want to use WikitextContentModelHandler::fromDOM instead.
	 *
	 * @param Document $doc
	 * @return string
	 */
	public function serializeDOM( Document $doc ): string {
		$serializeStart = null;
		$domDiffStart = null;
		$r = null;

		$body = DOMCompat::getBody( $doc );
		$oldBody = DOMCompat::getBody( $this->selserData->oldDOM );

		// Preprocess DOMs - this is specific to selser
		$this->preprocessDOMForSelser( $oldBody );
		$this->preprocessDOMForSelser( $body );

		// Use provided diff-marked DOM (used during testing)
		// or generate one (used in production)
		if ( $this->env->getDOMDiff() ) {
			$diff = [ 'isEmpty' => false ];
			$body = DOMCompat::getBody( $this->env->getDOMDiff() );
		} else {
			$domDiffTiming = Timing::start( $this->env->getSiteConfig()->metrics() );
			$diff = ( new DOMDiff( $this->env ) )->diff( $oldBody, $body );
			$domDiffTiming->end( 'html2wt.selser.domDiff' );
		}

		if ( $diff['isEmpty'] ) {
			// Nothing was modified, just re-use the original source
			$r = $this->selserData->oldText;
		} else {
			if ( $this->trace || $this->env->hasDumpFlag( 'dom:post-dom-diff' ) ) {
				$options = [ 'storeDiffMark' => true ];
				$this->env->writeDump(
					ContentUtils::dumpDOM( $oldBody, 'OLD DOM ', $options ) . "\n" .
					ContentUtils::dumpDOM( $body, 'DOM after running DOMDiff', $options )
				);
			}

			// Call the WikitextSerializer to do our bidding
			$r = $this->wts->serializeDOM( $doc, true );
		}

		return $r;
	}
}
