<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\Parsoid\Wt2Html\Frame;
use Wikimedia\Parsoid\Wt2Html\TT\PreHandler;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

class ComputeDSR implements Wt2HtmlDOMProcessor {
	/**
	 * For an explanation of what TSR is, see ComputeDSR::computeNodeDSR()
	 *
	 * TSR info on all these tags are only valid for the opening tag.
	 *
	 * On other tags, a, hr, br, meta-marker tags, the tsr spans
	 * the entire DOM, not just the tag.
	 *
	 * This code is not in Wikitext\Consts.php because this
	 * information is Parsoid-implementation-specific.
	 */
	private const WT_TAGS_WITH_LIMITED_TSR = [
		"b"  => true,
		"i"  => true,
		"h1" => true,
		"h2" => true,
		"h3" => true,
		"h4" => true,
		"h5" => true,
		"h6" => true,
		"ul" => true,
		"ol" => true,
		"dl" => true,
		"li" => true,
		"dt" => true,
		"dd" => true,
		"table" => true,
		"caption" => true,
		"tr" => true,
		"td" => true,
		"th" => true,
		"hr" => true, // void element
		"br" => true, // void element
		"pre" => true,
	];

	/**
	 * Do $parsoidData->tsr values span the entire DOM subtree rooted at $n?
	 *
	 * @param Element $n
	 * @param DataParsoid $parsoidData
	 * @return bool
	 */
	private function tsrSpansTagDOM( Element $n, DataParsoid $parsoidData ): bool {
		// - tags known to have tag-specific tsr
		// - html tags with 'stx' set
		// - tags with certain typeof properties (Parsoid-generated
		//   constructs: placeholders, lang variants)
		$name = DOMCompat::nodeName( $n );
		return !(
			isset( self::WT_TAGS_WITH_LIMITED_TSR[$name] ) ||
			DOMUtils::matchTypeOf(
				$n,
				'/^mw:(Placeholder|LanguageVariant)$/D'
			) ||
			WTUtils::hasLiteralHTMLMarker( $parsoidData )
		);
	}

	/**
	 * Is the inconsistency between two different ways of computing
	 * start offset ($cs, $s) explainable and acceptable?
	 * If so, we can suppress warnings.
	 *
	 * @param array $opts
	 * @param Node $node
	 * @param int $cs
	 * @param int $s
	 * @return bool
	 */
	private function acceptableInconsistency( array $opts, Node $node, int $cs, int $s ): bool {
		/**
		 * 1. For wikitext URL links, suppress cs-s diff warnings because
		 *    the diffs can come about because of various reasons since the
		 *    canonicalized/decoded href will become the a-link text whose width
		 *    will not match the tsr width of source wikitext
		 *
		 *    (a) urls with encoded chars (ex: 'http://example.com/?foo&#61;bar')
		 *    (b) non-canonical spaces (ex: 'RFC  123' instead of 'RFC 123')
		 *
		 * 2. We currently don't have source offsets for attributes.
		 *    So, we get a lot of spurious complaints about cs/s mismatch
		 *    when DSR computation hit the <body> tag on this attribute.
		 *    $opts['attrExpansion'] tell us when we are processing an attribute
		 *    and let us suppress the mismatch warning on the <body> tag.
		 *
		 * 3. Other scenarios .. to be added
		 */
		if ( $node instanceof Element && (
				WTUtils::isATagFromURLLinkSyntax( $node ) ||
				WTUtils::isATagFromMagicLinkSyntax( $node )
		) ) {
			return true;
		} elseif ( isset( $opts['attrExpansion'] ) && DOMUtils::atTheTop( $node ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Compute wikitext string length that contributes to this
	 * list item's open tag. Closing tag width is always 0 for lists.
	 *
	 * @param Element $li
	 * @return int
	 */
	private function computeListEltWidth( Element $li ): int {
		if ( !$li->previousSibling && $li->firstChild ) {
			if ( DOMUtils::isList( $li->firstChild ) ) {
				// Special case!!
				// First child of a list that is on a chain
				// of nested lists doesn't get a width.
				return 0;
			}
		}

		// count nest listing depth and assign
		// that to the opening tag width.
		$depth = 0;

		// This is the crux of the algorithm in DOMHandler::getListBullets()
		while ( !DOMUtils::atTheTop( $li ) ) {
			$dp = DOMDataUtils::getDataParsoid( $li );
			if ( DOMUtils::isListOrListItem( $li ) ) {
				if ( DOMUtils::isListItem( $li ) ) {
					$depth++;
				}
			} elseif (
				!WTUtils::isLiteralHTMLNode( $li ) ||
				empty( $dp->autoInsertedStart ) || empty( $dp->autoInsertedEnd )
			) {
				break;
			}
			$li = $li->parentNode;
		}

		return $depth;
	}

	/**
	 * Compute wikitext string lengths that contribute to this
	 * anchor's opening (<a>) and closing (</a>) tags.
	 *
	 * @param Element $node
	 * @param ?DataParsoid $dp
	 * @return int[]|null
	 */
	private function computeATagWidth(
		Element $node, ?DataParsoid $dp
	): ?array {
		/* -------------------------------------------------------------
		 * Tag widths are computed as per this logic here:
		 *
		 * 1. [[Foo|bar]] <-- piped mw:WikiLink
		 *     -> start-tag: "[[Foo|"
		 *     -> content  : "bar"
		 *     -> end-tag  : "]]"
		 *
		 * 2. [[Foo]] <-- non-piped mw:WikiLink
		 *     -> start-tag: "[["
		 *     -> content  : "Foo"
		 *     -> end-tag  : "]]"
		 *
		 * 3. [[{{1x|Foo}}|Foo]] <-- tpl-attr mw:WikiLink
		 *    Don't bother setting tag widths since dp->sa['href'] will be
		 *    the expanded target and won't correspond to original source.
		 *
		 * 4. [http://wp.org foo] <-- mw:ExtLink
		 *     -> start-tag: "[http://wp.org "
		 *     -> content  : "foo"
		 *     -> end-tag  : "]"
		 * -------------------------------------------------------------- */
		if ( !$dp ) {
			return null;
		} else {
			if ( WTUtils::isATagFromWikiLinkSyntax( $node ) && !WTUtils::hasExpandedAttrsType( $node ) ) {
				if ( isset( $dp->stx ) && $dp->stx === "piped" ) {
					// this seems like some kind of a phan bug
					$href = $dp->sa['href'] ?? null;
					if ( $href ) {
						return [ strlen( $href ) + 3, 2 ];
					} else {
						return null;
					}
				} else {
					return [ 2, 2 ];
				}
			} elseif ( isset( $dp->tsr ) && WTUtils::isATagFromExtLinkSyntax( $node ) ) {
				return [ $dp->extLinkContentOffsets->start - $dp->tsr->start, 1 ];
			} elseif ( WTUtils::isATagFromURLLinkSyntax( $node ) ||
				WTUtils::isATagFromMagicLinkSyntax( $node )
			) {
				return [ 0, 0 ];
			} else {
				return null;
			}
		}
	}

	/**
	 * Compute wikitext string lengths that contribute to this
	 * node's opening and closing tags.
	 *
	 * @param int|null $stWidth Start tag width
	 * @param int|null $etWidth End tag width
	 * @param Element $node
	 * @param DataParsoid $dp
	 * @return int[] Start and end tag widths
	 */
	private function computeTagWidths( $stWidth, $etWidth, Element $node, DataParsoid $dp ): array {
		if ( isset( $dp->extTagOffsets ) ) {
			return [
				$dp->extTagOffsets->openWidth,
				$dp->extTagOffsets->closeWidth
			];
		}

		if ( WTUtils::hasLiteralHTMLMarker( $dp ) ) {
			if ( !empty( $dp->selfClose ) ) {
				$etWidth = 0;
			}
		} elseif ( DOMUtils::hasTypeOf( $node, 'mw:LanguageVariant' ) ) {
			$stWidth = 2; // -{
			$etWidth = 2; // }-
		} else {
			$nodeName = DOMCompat::nodeName( $node );
			// 'tr' tags not in the original source have zero width
			if ( $nodeName === 'tr' && !isset( $dp->startTagSrc ) ) {
				$stWidth = 0;
				$etWidth = 0;
			} else {
				$wtTagWidth = Consts::$WtTagWidths[$nodeName] ?? null;
				if ( $stWidth === null ) {
					// we didn't have a tsr to tell us how wide this tag was.
					if ( $nodeName === 'a' ) {
						$wtTagWidth = $this->computeATagWidth( $node, $dp );
						$stWidth = $wtTagWidth ? $wtTagWidth[0] : null;
					} elseif ( $nodeName === 'li' || $nodeName === 'dd' ) {
						$stWidth = $this->computeListEltWidth( $node );
					} elseif ( $wtTagWidth ) {
						$stWidth = $wtTagWidth[0];
					}
				}

				if ( $etWidth === null && $wtTagWidth ) {
					$etWidth = $wtTagWidth[1];
				}
			}
		}

		return [ $stWidth, $etWidth ];
	}

	/**
	 * @param Env $env
	 * @param mixed ...$args
	 */
	private function trace( Env $env, ...$args ): void {
		$env->log( "trace/dsr", static function () use ( $args ) {
			$buf = '';
			foreach ( $args as $arg ) {
				$buf .= is_string( $arg ) ? $arg : PHPUtils::jsonEncode( $arg );
			}
			return $buf;
		} );
	}

	/**
	 * TSR = "Tag Source Range".  Start and end offsets giving the location
	 * where the tag showed up in the original source.
	 *
	 * DSR = "DOM Source Range".  dsr->start and dsr->end are open and end,
	 * dsr->openWidth and dsr->closeWidth are widths of the container tag.
	 *
	 * TSR is set by the tokenizer. In most cases, it only applies to the
	 * specific tag (opening or closing).  However, for self-closing
	 * tags that the tokenizer generates, the TSR values applies to the entire
	 * DOM subtree (opening tag + content + closing tag).
	 *
	 * Ex: So [[foo]] will get tokenized to a SelfClosingTagTk(...) with a TSR
	 * value of [0,7].  The DSR algorithm will then use that info and assign
	 * the a-tag rooted at the <a href='...'>foo</a> DOM subtree a DSR value of
	 * [0,7,2,2], where 2 and 2 refer to the opening and closing tag widths.
	 *
	 * [s,e) -- if defined, start/end position of wikitext source that generated
	 *          node's subtree
	 *
	 * @param Frame $frame
	 * @param Node $node node to process
	 * @param ?int $s start position, inclusive
	 * @param ?int $e end position, exclusive
	 * @param int $dsrCorrection
	 * @param array $opts
	 * @return array
	 */
	private function computeNodeDSR(
		Frame $frame, Node $node, ?int $s, ?int $e, int $dsrCorrection,
		array $opts
	): array {
		$env = $frame->getEnv();
		if ( $e === null && !$node->hasChildNodes() ) {
			$e = $s;
		}

		$this->trace( $env, "BEG: ", DOMCompat::nodeName( $node ), " with [s, e]=", [ $s, $e ] );

		/** @var int|null $ce Child end */
		$ce = $e;
		// Initialize $cs to $ce to handle the zero-children case properly
		// if this $node has no child content, then the start and end for
		// the child dom are indeed identical.  Alternatively, we could
		// explicitly code this check before everything and bypass this.
		/** @var int|null $cs Child start */
		$cs = $ce;

		$child = $node->lastChild;
		while ( $child !== null ) {
			$prevChild = $child->previousSibling;
			$origCE = $ce;
			$cType = $child->nodeType;
			$fosteredNode = false;
			$cs = null;

			if ( $child instanceof Element ) {
				$dp = DOMDataUtils::getDataParsoid( $child );
				$endTSR = $dp->tmp->endTSR ?? null;
				if ( $endTSR ) {
					$ce = $endTSR->end;
				}
			} else {
				$endTSR = null;
			}

			// StrippedTag marker tags will be removed and won't
			// be around to fill in the missing gap.  So, absorb its width into
			// the DSR of its previous sibling.  Currently, this fix is only for
			// B and I tags where the fix is clear-cut and obvious.
			$next = $child->nextSibling;
			if ( $next instanceof Element ) {
				$ndp = DOMDataUtils::getDataParsoid( $next );
				if (
					isset( $ndp->src ) &&
					DOMUtils::hasTypeOf( $next, 'mw:Placeholder/StrippedTag' ) &&
					// NOTE: This inlist check matches the case in CleanUp where
					// the placeholders are not removed from the DOM.  We don't want
					// to move the width into the sibling here and then leave around a
					// a zero width placeholder because serializeDOMNode only handles
					// a few cases of zero width nodes, so we'll end up duplicating
					// it from ->src.
					!DOMUtils::isNestedInListItem( $next )
				) {
					if ( isset( Consts::$WTQuoteTags[$ndp->name] ) &&
						isset( Consts::$WTQuoteTags[DOMCompat::nodeName( $child )] ) ) {
						$correction = strlen( $ndp->src );
						$ce += $correction;
						$dsrCorrection = $correction;
						if ( Utils::isValidDSR( $ndp->dsr ?? null ) ) {
							// Record original DSR for the meta tag
							// since it will now get corrected to zero width
							// since child acquires its width->
							$ndp->getTemp()->origDSR = new DomSourceRange(
								$ndp->dsr->start, $ndp->dsr->end, null, null );
						}
					}
				}
			}

			$env->log( "trace/dsr", static function () use ( $child, $cs, $ce ) {
				// slow, for debugging only
				$i = 0;
				foreach ( $child->parentNode->childNodes as $x ) {
					if ( $x === $child ) {
						break;
					}
					$i++;
				}
				return "     CHILD: <" . DOMCompat::nodeName( $child->parentNode ) . ":" . $i .
					">=" .
					( $child instanceof Element ? '' : ( $child instanceof Text ? '#' : '!' ) ) .
					( ( $child instanceof Element ) ?
						( DOMCompat::nodeName( $child ) === 'meta' ?
							DOMCompat::getOuterHTML( $child ) : DOMCompat::nodeName( $child ) ) :
							PHPUtils::jsonEncode( $child->nodeValue ) ) .
					" with " . PHPUtils::jsonEncode( [ $cs, $ce ] );
			} );

			if ( $cType === XML_TEXT_NODE ) {
				if ( $ce !== null ) {
					$cs = $ce - strlen( $child->textContent );
				}
			} elseif ( $cType === XML_COMMENT_NODE ) {
				'@phan-var Comment $child'; // @var Comment $child
				if ( $ce !== null ) {
					// Decode HTML entities & re-encode as wikitext to find length
					$cs = $ce - WTUtils::decodedCommentLength( $child );
				}
			} elseif ( $cType === XML_ELEMENT_NODE ) {
				DOMUtils::assertElt( $child );
				$dp = DOMDataUtils::getDataParsoid( $child );
				$tsr = $dp->tsr ?? null;
				$oldCE = $tsr ? $tsr->end : null;
				$propagateRight = false;
				$stWidth = null;
				$etWidth = null;

				$fosteredNode = $dp->fostered ?? false;

				// We are making dsr corrections to account for
				// stripped tags (end tags usually). When stripping happens,
				// in most common use cases, a corresponding end tag is added
				// back elsewhere in the DOM.
				//
				// So, when an autoInsertedEnd tag is encountered and a matching
				// dsr-correction is found, make a 1-time correction in the
				// other direction.
				//
				// Currently, this fix is only for
				// B and I tags where the fix is clear-cut and obvious.
				if ( $ce !== null && !empty( $dp->autoInsertedEnd ) &&
					DOMUtils::isQuoteElt( $child )
				) {
					$correction = 3 + strlen( DOMCompat::nodeName( $child ) );
					if ( $correction === $dsrCorrection ) {
						$ce -= $correction;
						$dsrCorrection = 0;
					}
				}

				if ( DOMCompat::nodeName( $child ) === "meta" ) {
					if ( $tsr ) {
						if ( WTUtils::isTplMarkerMeta( $child ) ) {
							// If this is a meta-marker tag (for templates, extensions),
							// we have a new valid '$cs'. This marker also effectively resets tsr
							// back to the top-level wikitext source range from nested template
							// source range.
							$cs = $tsr->start;
							$ce = $tsr->end;
							$propagateRight = true;
						} else {
							// All other meta-tags: <includeonly>, <noinclude>, etc.
							$cs = $tsr->start;
							$ce = $tsr->end;
						}
					} elseif ( PreHandler::isIndentPreWS( $child ) ) {
						// Adjust start DSR; see PreHandler::newIndentPreWS()
						$cs = $ce - 1;
					} elseif ( DOMUtils::matchTypeOf( $child, '#^mw:Placeholder(/\w*)?$#D' ) &&
						$ce !== null && $dp->src
					) {
						$cs = $ce - strlen( $dp->src );
					}
					if ( isset( $dp->extTagOffsets ) ) {
						$stWidth = $dp->extTagOffsets->openWidth;
						$etWidth = $dp->extTagOffsets->closeWidth;
						unset( $dp->extTagOffsets );
					}
				} elseif ( DOMUtils::hasTypeOf( $child, "mw:Entity" ) && $ce !== null && $dp->src ) {
					$cs = $ce - strlen( $dp->src );
				} else {
					if ( DOMUtils::matchTypeOf( $child, '#^mw:Placeholder(/\w*)?$#D' ) &&
						$ce !== null && $dp->src
					) {
						$cs = $ce - strlen( $dp->src );
					} else {
						// Non-meta tags
						if ( $endTSR ) {
							$etWidth = $endTSR->length();
						}
						if ( $tsr && empty( $dp->autoInsertedStart ) ) {
							$cs = $tsr->start;
							if ( $this->tsrSpansTagDOM( $child, $dp ) ) {
								if ( $tsr->end !== null && $tsr->end > 0 ) {
									$ce = $tsr->end;
									$propagateRight = true;
								}
							} else {
								$stWidth = $tsr->end - $tsr->start;
							}

							$this->trace( $env, "     TSR: ", $tsr, "; cs: ", $cs, "; ce: ", $ce );
						} elseif ( $s && $child->previousSibling === null ) {
							$cs = $s;
						}
					}

					// Compute width of opening/closing tags for this dom $node
					[ $stWidth, $etWidth ] =
						$this->computeTagWidths( $stWidth, $etWidth, $child, $dp );

					if ( !empty( $dp->autoInsertedStart ) ) {
						$stWidth = 0;
					}
					if ( !empty( $dp->autoInsertedEnd ) ) {
						$etWidth = 0;
					}

					$ccs = $cs !== null && $stWidth !== null ? $cs + $stWidth : null;
					$cce = $ce !== null && $etWidth !== null ? $ce - $etWidth : null;

					/* -----------------------------------------------------------------
					 * Process DOM rooted at '$child'.
					 *
					 * NOTE: You might wonder why we are not checking for the zero-$children
					 * case. It is strictly not necessary and you can set newDsr directly.
					 *
					 * But, you have 2 options: [$ccs, $ccs] or [$cce, $cce]. Setting it to
					 * [$cce, $cce] would be consistent with the RTL approach. We should
					 * then compare $ccs and $cce and verify that they are identical.
					 *
					 * But, if we handled the zero-child case like the other scenarios,
					 * we don't have to worry about the above decisions and checks.
					 * ----------------------------------------------------------------- */

					if ( WTUtils::isDOMFragmentWrapper( $child ) ||
						 DOMUtils::hasTypeOf( $child, 'mw:LanguageVariant' )
					) {
						// Eliminate artificial $cs/s mismatch warnings since this is
						// just a wrapper token with the right DSR but without any
						// nested subtree that could account for the DSR span.
						$newDsr = [ $ccs, $cce ];
					} elseif ( $child instanceof Element
						&& WTUtils::isATagFromWikiLinkSyntax( $child )
						&& ( !isset( $dp->stx ) || $dp->stx !== "piped" ) ) {
						/* -------------------------------------------------------------
						 * This check here eliminates artificial DSR mismatches on content
						 * text of the A-node because of entity expansion, etc.
						 *
						 * Ex: [[7%25 solution]] will be rendered as:
						 *    <a href=....>7% solution</a>
						 * If we descend into the text for the a-node, we'll have a 2-char
						 * DSR mismatch which will trigger artificial error warnings.
						 *
						 * In the non-piped link scenario, all dsr info is already present
						 * in the link target and so we get nothing new by processing
						 * content.
						 * ------------------------------------------------------------- */
						$newDsr = [ $ccs, $cce ];
					} else {
						$env->log( "trace/dsr", static function () use (
							$env, $cs, $ce, $stWidth, $etWidth, $ccs, $cce
						) {
							return "     before-recursing:" .
								"[cs,ce]=" . PHPUtils::jsonEncode( [ $cs, $ce ] ) .
								"; [sw,ew]=" . PHPUtils::jsonEncode( [ $stWidth, $etWidth ] ) .
								"; subtree-[cs,ce]=" . PHPUtils::jsonEncode( [ $ccs, $cce ] );
						} );

						$this->trace( $env, "<recursion>" );
						$newDsr = $this->computeNodeDSR( $frame, $child, $ccs, $cce, $dsrCorrection, $opts );
						$this->trace( $env, "</recursion>" );
					}

					// $cs = min($child-dom-tree dsr->start - tag-width, current dsr->start)
					if ( $stWidth !== null && $newDsr[0] !== null ) {
						$newCs = $newDsr[0] - $stWidth;
						if ( $cs === null || ( !$tsr && $newCs < $cs ) ) {
							$cs = $newCs;
						}
					}

					// $ce = max($child-dom-tree dsr->end + tag-width, current dsr->end)
					if ( $etWidth !== null && $newDsr[1] !== null ) {
						$newCe = $newDsr[1] + $etWidth;
						if ( $newCe > $ce ) {
							$ce = $newCe;
						}
					}
				}

				if ( $cs !== null || $ce !== null ) {
					if ( $ce < 0 ) {
						if ( !$fosteredNode ) {
							$env->log( "info/dsr/negative",
								"Negative DSR for node: " . DOMCompat::nodeName( $node ) . "; resetting to zero" );
						}
						$ce = 0;
					}

					// Fostered $nodes get a zero-dsr width range.
					if ( $fosteredNode ) {
						// Reset to 0, if necessary.
						// This is critical to avoid duplication of fostered content in selser mode.
						if ( $origCE < 0 ) {
							$origCE = 0;
						}
						$dp->dsr = new DomSourceRange( $origCE, $origCE, null, null );
					} else {
						$dp->dsr = new DomSourceRange( $cs, $ce, $stWidth, $etWidth );
					}

					$env->log( "trace/dsr", static function () use ( $frame, $child, $cs, $ce, $dp ) {
						return "     UPDATING " . DOMCompat::nodeName( $child ) .
							" with " . PHPUtils::jsonEncode( [ $cs, $ce ] ) .
							"; typeof: " . ( DOMCompat::getAttribute( $child, "typeof" ) ?? '' );
					} );
				}

				// Propagate any required changes to the right
				// taking care not to cross-over into template content
				if ( $ce !== null &&
					( $propagateRight || $oldCE !== $ce || $e === null ) &&
					!WTUtils::isTplStartMarkerMeta( $child )
				) {
					$sibling = $child->nextSibling;
					$newCE = $ce;
					while ( $newCE !== null && $sibling && !WTUtils::isTplStartMarkerMeta( $sibling ) ) {
						$nType = $sibling->nodeType;
						if ( $nType === XML_TEXT_NODE ) {
							$newCE += strlen( $sibling->textContent );
						} elseif ( $nType === XML_COMMENT_NODE ) {
							'@phan-var Comment $sibling'; // @var Comment $sibling
							$newCE += WTUtils::decodedCommentLength( $sibling );
						} elseif ( $nType === XML_ELEMENT_NODE ) {
							DOMUtils::assertElt( $sibling );
							$siblingDP = DOMDataUtils::getDataParsoid( $sibling );
							$siblingDP->dsr ??= new DomSourceRange( null, null, null, null );
							$sdsrStart = $siblingDP->dsr->start;
							if ( !empty( $siblingDP->fostered ) ||
								( $sdsrStart !== null && $sdsrStart === $newCE ) ||
								( $sdsrStart !== null && $sdsrStart < $newCE && isset( $siblingDP->tsr ) )
							) {
								// $sibling is fostered
								// => nothing to propagate past it
								// $sibling's dsr->start matches what we might propagate
								// => nothing will change
								// $sibling's dsr value came from tsr and it is not outside expected range
								// => stop propagation so you don't overwrite it
								break;
							}

							// Update and move right
							$env->log( "trace/dsr", static function () use ( $frame, $newCE, $sibling, $siblingDP ) {
								return "     CHANGING ce.start of " . DOMCompat::nodeName( $sibling ) .
									" from " . $siblingDP->dsr->start . " to " . $newCE;
							} );

							$siblingDP->dsr->start = $newCE;
							// If we have a dsr->end as well and since we updated
							// dsr->start, we have to ensure that the two values don't
							// introduce an inconsistency where dsr->start > dsr->end.
							// Since we are in a LTR pass and are pushing updates
							// forward, we are resolving it by updating dsr->end as
							// well. There could be scenarios where this would be
							// incorrect, but there is no universal fix here.
							if ( $siblingDP->dsr->end !== null && $newCE > $siblingDP->dsr->end ) {
								$siblingDP->dsr->end = $newCE;
							}
							$newCE = $siblingDP->dsr->end;

						} else {
							break;
						}
						$sibling = $sibling->nextSibling;
					}

					// Propagate new end information
					if ( !$sibling ) {
						$e = $newCE;
					}
				}
			}

			// Don't change state if we processed a fostered $node
			if ( $fosteredNode ) {
				$ce = $origCE;
			} else {
				// $ce for next $child = $cs of current $child
				$ce = $cs;
			}

			$child = $prevChild;
		}

		if ( $cs === null ) {
			$cs = $s;
		}

		// Detect errors
		if ( $s !== null && $cs !== $s && !$this->acceptableInconsistency( $opts, $node, $cs, $s ) ) {
			$env->log( "info/dsr/inconsistent", "DSR inconsistency: cs/s mismatch for node:",
				DOMCompat::nodeName( $node ), "s:", $s, "; cs:", $cs );
		}

		$this->trace( $env, "END: ", DOMCompat::nodeName( $node ), ", returning: ", $cs, ", ", $e );

		return [ $cs, $e ];
	}

	/**
	 * Computes DSR ranges for every node of a DOM tree.
	 * This pass is only invoked on the top-level page.
	 *
	 * @param Env $env The environment/context for the parse pipeline
	 * @param Node $root The root of the tree for which DSR has to be computed
	 * @param array $options Options governing DSR computation
	 * - sourceOffsets: [start, end] source offset. If missing, this defaults to
	 *                  [0, strlen($frame->getSrcText())]
	 * - attrExpansion: Is this an attribute expansion pipeline?
	 * @param bool $atTopLevel Are we running this on the top level?
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		$frame = $options['frame'] ?? $env->topFrame;
		$startOffset = $options['sourceOffsets']->start ?? 0;
		$endOffset = $options['sourceOffsets']->end ?? strlen( $frame->getSrcText() );
		$env->log( "trace/dsr", "------- tracing DSR computation -------" );

		// The actual computation buried in trace/debug stmts.
		$opts = [ 'attrExpansion' => $options['attrExpansion'] ?? false ];
		$this->computeNodeDSR( $frame, $root, $startOffset, $endOffset, 0, $opts );

		if ( $atTopLevel ) {
			'@phan-var Element $root';  // @var Element $root
			$dp = DOMDataUtils::getDataParsoid( $root );
			$dp->dsr = new DomSourceRange( $startOffset, $endOffset, 0, 0 );
		}
		$env->log( "trace/dsr", "------- done tracing computation -------" );
	}
}
