<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Error;
use SplObjectStorage;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\UnreachableException;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\Core\ElementRange;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\NodeData\TempData;
use Wikimedia\Parsoid\NodeData\TemplateInfo;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wt2Html\Frame;

/**
 * Template encapsulation happens in three steps.
 *
 * 1. findWrappableTemplateRanges
 *
 *    Locate start and end metas. Walk upwards towards the root from both and
 *    find a common ancestor A. The subtree rooted at A is now effectively the
 *    scope of the dom template ouput.
 *
 * 2. findTopLevelNonOverlappingRanges
 *
 *    Mark all nodes in a range and walk up to root from each range start to
 *    determine overlaps, nesting. Merge overlapping and nested ranges to find
 *    the subset of top-level non-overlapping ranges which will be wrapped as
 *    individual units.
 *
 *    range.startElem, range.endElem are the start/end meta tags for a transclusion
 *    range.start, range.end are the start/end DOM nodes after the range is
 *    expanded, merged with other ranges, etc. In the simple cases, they will
 *    be identical to startElem, endElem.
 *
 * 3. encapsulateTemplates
 *
 *    For each non-overlapping range,
 *    - compute a data-mw according to the DOM spec
 *    - replace the start / end meta markers with transclusion type and data-mw
 *      on the first DOM node
 *    - add about ids on all top-level nodes of the range
 *
 * This is a simple high-level overview of the 3 steps to help understand this
 * code.
 *
 * FIXME: At some point, more of the details should be extracted and documented
 * in pseudo-code as an algorithm.
 * @module
 */
class DOMRangeBuilder {

	private const MAP_TBODY_TR = [
		'tbody' => true,
		'tr' => true
	];

	/** @var Document */
	private $document;

	/** @var Frame */
	private $frame;

	/** @var Env */
	protected $env;

	/** @var SplObjectStorage */
	protected $nodeRanges;

	/** @var array<string|CompoundTemplateInfo>[] */
	private $compoundTpls = [];

	/** @var string */
	protected $traceType;

	public function __construct(
		Document $document, Frame $frame
	) {
		$this->document = $document;
		$this->frame = $frame;
		$this->env = $frame->getEnv();
		$this->nodeRanges = new SplObjectStorage;
		$this->traceType = "tplwrap";
	}

	protected function updateDSRForFirstRangeNode( Element $target, Element $source ): void {
		$srcDP = DOMDataUtils::getDataParsoid( $source );
		$tgtDP = DOMDataUtils::getDataParsoid( $target );

		// Since TSRs on template content tokens are cleared by the
		// template handler, all computed dsr values for template content
		// is always inferred from top-level content values and is safe.
		// So, do not overwrite a bigger end-dsr value.
		if ( isset( $srcDP->dsr->end ) && isset( $tgtDP->dsr->end ) &&
			$tgtDP->dsr->end > $srcDP->dsr->end
		) {
			$tgtDP->dsr->start = $srcDP->dsr->start ?? null;
		} else {
			$tgtDP->dsr = clone $srcDP->dsr;
			$tgtDP->src = $srcDP->src ?? null;
		}
	}

	/**
	 * Get the DSR of the end of a DOMRange
	 *
	 * @param DOMRangeInfo $range
	 * @return DomSourceRange|null
	 */
	private static function getRangeEndDSR( DOMRangeInfo $range ): ?DomSourceRange {
		$endNode = $range->end;
		if ( $endNode instanceof Element ) {
			return DOMDataUtils::getDataParsoid( $endNode )->dsr ?? null;
		} else {
			// In the rare scenario where the last element of a range is not an ELEMENT,
			// extrapolate based on DSR of first leftmost sibling that is an ELEMENT.
			// We don't try any harder than this for now.
			$offset = 0;
			$n = $endNode->previousSibling;
			while ( $n && !( $n instanceof Element ) ) {
				if ( $n instanceof Text ) {
					$offset += strlen( $n->nodeValue );
				} else {
					// A comment
					// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType
					$offset += WTUtils::decodedCommentLength( $n );
				}
				$n = $n->previousSibling;
			}

			$dsr = null;
			if ( $n ) {
				/**
				 * The point of the above loop is to ensure we're working
				 * with a Element if there is an $n.
				 *
				 * @var Element $n
				 */
				'@phan-var Element $n';
				$dsr = DOMDataUtils::getDataParsoid( $n )->dsr ?? null;
			}

			if ( $dsr && is_int( $dsr->end ?? null ) ) {
				$len = $endNode instanceof Text
					? strlen( $endNode->nodeValue )
					: WTUtils::decodedCommentLength( $endNode );
				$dsr = new DomSourceRange( $dsr->end + $offset, $dsr->end + $offset + $len, null, null );
			}

			return $dsr;
		}
	}

	/**
	 * Returns the range ID of a node - in the case of templates, its "about" attribute.
	 * @param Element $node
	 * @return string
	 */
	protected function getRangeId( Element $node ): string {
		return DOMCompat::getAttribute( $node, "about" );
	}

	/**
	 * Find the common DOM ancestor of two DOM nodes.
	 *
	 * @param Element $startElem
	 * @param Element $endMeta
	 * @param Element $endElem
	 * @return DOMRangeInfo
	 */
	private function getDOMRange(
		Element $startElem, Element $endMeta, Element $endElem
	) {
		$range = $this->findEnclosingRange( $startElem, $endElem );
		$range->startElem = $startElem;
		$range->endElem = $endMeta;

		$startsInFosterablePosn = DOMUtils::isFosterablePosition( $range->start );
		$next = $range->start->nextSibling;

		// Detect empty content and handle them!
		if ( WTUtils::isTplMarkerMeta( $range->start ) && $next === $endElem ) {
			Assert::invariant( $range->start === $startElem,
				"Expected startElem to be same as range.start" );
			if ( $startsInFosterablePosn ) {
				// Expand range!
				$range->start = $range->end = $range->start->parentNode;
				$startsInFosterablePosn = false;
			} else {
				$emptySpan = $this->document->createElement( 'span' );
				$range->start->parentNode->insertBefore( $emptySpan, $endElem );
			}

			// Handle unwrappable content in fosterable positions
			// and expand template range, if required.
			// NOTE: Template marker meta tags are translated from comments
			// *after* the DOM has been built which is why they can show up in
			// fosterable positions in the DOM.
		} elseif ( $startsInFosterablePosn &&
			( !( $range->start instanceof Element ) ||
				WTUtils::isTplMarkerMeta( $range->start ) &&
				( !( $next instanceof Element ) || WTUtils::isTplMarkerMeta( $next ) )
			)
		) {
			$rangeStartParent = $range->start->parentNode;

			// If we are in a table in a foster-element position, then all non-element
			// nodes will be white-space and comments. Skip over all of them and find
			// the first table content node.
			$noWS = true;
			$nodesToMigrate = [];
			$newStart = $range->start;
			$n = $range->start instanceof Element ? $next : $range->start;
			while ( !( $n instanceof Element ) ) {
				if ( $n instanceof Text ) {
					$noWS = false;
				}
				$nodesToMigrate[] = $n;
				$n = $n->nextSibling;
				$newStart = $n;
			}

			// As long as $newStart is a tr/tbody or we don't have whitespace
			// migrate $nodesToMigrate into $newStart. Pushing whitespace into
			// th/td/caption can change display semantics.
			if ( $newStart && ( $noWS || isset( self::MAP_TBODY_TR[DOMCompat::nodeName( $newStart )] ) ) ) {
				/**
				 * The point of the above loop is to ensure we're working
				 * with a Element if there is a $newStart.
				 *
				 * @var Element $newStart
				 */
				'@phan-var Element $newStart';
				$insertPosition = $newStart->firstChild;
				foreach ( $nodesToMigrate as $n ) {
					$newStart->insertBefore( $n, $insertPosition );
				}
				$range->start = $newStart;
				// Update dsr to point to original start
				$this->updateDSRForFirstRangeNode( $range->start, $startElem );
			} else {
				// If not, we are forced to expand the template range.
				$range->start = $range->end = $rangeStartParent;
			}
		}

		// Ensure range->start is an element node since we want to
		// add/update the data-parsoid attribute to it.
		if ( !( $range->start instanceof Element ) ) {
			$span = $this->document->createElement( 'span' );
			$range->start->parentNode->insertBefore( $span, $range->start );
			$span->appendChild( $range->start );
			$this->updateDSRForFirstRangeNode( $span, $startElem );
			$range->start = $span;
		}

		$range->start = $this->getStartConsideringFosteredContent( $range->start );

		$rangeStartNextSibling = $range->start->nextSibling;
		if ( $range->start === $startElem && $rangeStartNextSibling instanceof Element ) {
			// HACK!
			// The strip-double-tds pass has a HACK that requires DSR and src
			// information being set on this element node. So, this HACK here
			// is supporting that HACK there.
			//
			// (The parser test for T52603 will fail without this fix)
			$this->updateDSRForFirstRangeNode( $rangeStartNextSibling, $startElem );
		}

		// Use the negative test since it doesn't mark the range as flipped
		// if range.start === range.end
		if ( !DOMUtils::inSiblingOrder( $range->start, $range->end ) ) {
			// In foster-parenting situations, the end-meta tag (and hence range.end)
			// can show up before the range.start which would be the table itself.
			// So, we record this info for later analysis.
			$range->flipped = true;
		}

		$this->env->log(
			"trace/{$this->traceType}/findranges",
			static function () use ( &$range ) {
				$msg = '';
				$dp1 = DOMDataUtils::getDataParsoid( $range->start );
				$dp2 = DOMDataUtils::getDataParsoid( $range->end );
				$tmp1 = $dp1->tmp;
				$tmp2 = $dp2->tmp;
				$dp1->tmp = null;
				$dp2->tmp = null;
				$msg .= "\n----------------------------------------------";
				$msg .= "\nFound range : " . $range->id . '; flipped? ' . ( (string)$range->flipped ) .
					'; offset: ' . $range->startOffset;
				$msg .= "\nstart-elem : " . DOMCompat::getOuterHTML( $range->startElem ) . '; DP: ' .
					PHPUtils::jsonEncode( DOMDataUtils::getDataParsoid( $range->startElem ) );
				$msg .= "\nend-elem : " . DOMCompat::getOuterHTML( $range->endElem ) . '; DP: ' .
					PHPUtils::jsonEncode( DOMDataUtils::getDataParsoid( $range->endElem ) );
				$msg .= "\nstart : [TAG_ID " . ( $tmp1->tagId ?? '?' ) . ']: ' .
					DOMCompat::getOuterHTML( $range->start ) .
					'; DP: ' . PHPUtils::jsonEncode( $dp1 );
				$msg .= "\nend : [TAG_ID " . ( $tmp2->tagId ?? '?' ) . ']: ' .
					DOMCompat::getOuterHTML( $range->end ) .
					'; DP: ' . PHPUtils::jsonEncode( $dp2 );
				$msg .= "\n----------------------------------------------";
				$dp1->tmp = $tmp1;
				$dp2->tmp = $tmp2;
				return $msg;
			}
		);

		return $range;
	}

	/**
	 * Returns the current node if it's not just after fostered content, the first node
	 * of fostered content otherwise.
	 * @param Node $node
	 * @return Node
	 */
	protected function getStartConsideringFosteredContent( Node $node ): Node {
		if ( DOMCompat::nodeName( $node ) === 'table' ) {
			// If we have any fostered content, include it as well.
			for ( $previousSibling = $node->previousSibling;
				$previousSibling instanceof Element &&
				!empty( DOMDataUtils::getDataParsoid( $previousSibling )->fostered );
				$previousSibling = $node->previousSibling
			) {
				$node = $previousSibling;
			}
		}
		return $node;
	}

	private static function stripStartMeta( Element $meta ): void {
		if ( DOMCompat::nodeName( $meta ) === 'meta' ) {
			$meta->parentNode->removeChild( $meta );
		} else {
			// Remove mw:* from the typeof.
			$type = DOMCompat::getAttribute( $meta, 'typeof' );
			if ( $type !== null ) {
				$type = preg_replace( '/(?:^|\s)mw:[^\/]*(\/\S+|(?=$|\s))/D', '', $type );
				$meta->setAttribute( 'typeof', $type );
			}
		}
	}

	private static function findToplevelEnclosingRange(
		array $nestingInfo, ?string $startId
	): ?string {
		// Walk up the implicit nesting tree to find the
		// top-level range within which rId is nested.
		// No cycles can exist since they have been suppressed.
		$visited = [];
		$rId = $startId;
		while ( isset( $nestingInfo[$rId] ) ) {
			if ( isset( $visited[$rId] ) ) {
				throw new Error( "Found a cycle in tpl-range nesting where there shouldn't have been one." );
			}
			$visited[$rId] = true;
			$rId = $nestingInfo[$rId];
		}
		return $rId;
	}

	/**
	 * Add a template to $this->compoundTpls
	 *
	 * @param string $compoundTplId
	 * @param DOMRangeInfo $range
	 * @param TemplateInfo $templateInfo
	 */
	private function recordTemplateInfo(
		string $compoundTplId, DOMRangeInfo $range, TemplateInfo $templateInfo
	): void {
		$this->compoundTpls[$compoundTplId] ??= [];

		// Record template args info along with any intervening wikitext
		// between templates that are part of the same compound structure.
		/** @var array $tplArray */
		$tplArray = &$this->compoundTpls[$compoundTplId];
		$dp = DOMDataUtils::getDataParsoid( $range->startElem );
		$dsr = $dp->dsr;

		if ( count( $tplArray ) > 0 ) {
			$prevTplInfo = PHPUtils::lastItem( $tplArray );
			if ( $prevTplInfo->dsr->end < $dsr->start ) {
				$width = $dsr->start - $prevTplInfo->dsr->end;
				$tplArray[] = PHPUtils::safeSubstr(
					$this->frame->getSrcText(), $prevTplInfo->dsr->end, $width );
			}
		}

		if ( !empty( $dp->unwrappedWT ) ) {
			$tplArray[] = (string)$dp->unwrappedWT;
		}

		// Get rid of src-offsets since they aren't needed anymore.
		foreach ( $templateInfo->paramInfos as $pi ) {
			$pi->srcOffsets = null;
		}
		$tplArray[] = new CompoundTemplateInfo(
			$dsr, $templateInfo, DOMUtils::hasTypeOf( $range->startElem, 'mw:Param' )
		);
	}

	/**
	 * Determine whether adding the given range would introduce a cycle in the
	 * subsumedRanges graph.
	 *
	 * Nesting cycles with multiple ranges can show up because of foster
	 * parenting scenarios if they are not detected and suppressed.
	 *
	 * @param string $start The ID of the new range
	 * @param string $end The ID of the other range
	 * @param string[] $subsumedRanges The subsumed ranges graph, encoded as an
	 *   array in which each element maps one string range ID to another range ID
	 * @return bool
	 */
	private static function introducesCycle( string $start, string $end, array $subsumedRanges ): bool {
		$visited = [ $start => true ];
		$elt = $subsumedRanges[$end] ?? null;
		while ( $elt ) {
			if ( !empty( $visited[$elt] ) ) {
				return true;
			}
			$elt = $subsumedRanges[$elt] ?? null;
		}
		return false;
	}

	/**
	 * Determine whether DOM ranges overlap.
	 *
	 * The `inSiblingOrder` check here is sufficient to determine overlaps
	 * because the algorithm in `findWrappableTemplateRanges` will put the
	 * start/end elements for intersecting ranges on the same plane and prev/
	 * curr are in textual order (which translates to dom order).
	 *
	 * @param DOMRangeInfo $prev
	 * @param DOMRangeInfo $curr
	 * @return bool
	 */
	private static function rangesOverlap( DOMRangeInfo $prev, DOMRangeInfo $curr ): bool {
		$prevEnd = ( !$prev->flipped ) ? $prev->end : $prev->start;
		$currStart = ( !$curr->flipped ) ? $curr->start : $curr->end;
		return DOMUtils::inSiblingOrder( $currStart, $prevEnd );
	}

	/**
	 * Identify the elements of $tplRanges that are non-overlapping.
	 * Record template info in $this->compoundTpls as we go.
	 *
	 * @param Node $docRoot
	 * @param DOMRangeInfo[] $tplRanges The potentially overlapping ranges
	 * @return DOMRangeInfo[] The non-overlapping ranges
	 */
	public function findTopLevelNonOverlappingRanges( Node $docRoot, array $tplRanges ): array {
		// For each node, assign an attribute that is a record of all
		// tpl ranges it belongs to at the top-level.
		foreach ( $tplRanges as $r ) {
			$n = !$r->flipped ? $r->start : $r->end;
			$e = !$r->flipped ? $r->end : $r->start;

			while ( $n ) {
				if ( $n instanceof Element ) {
					$this->addNodeRange( $n, $r );
					// Done
					if ( $n === $e ) {
						break;
					}
				}

				$n = $n->nextSibling;
			}
		}

		// In the first pass over `numRanges` below, `subsumedRanges` is used to
		// record purely the nested ranges.  However, in the second pass, we also
		// add the relationships between overlapping ranges so that
		// `findToplevelEnclosingRange` can use that information to add `argInfo`
		// to the right `compoundTpls`.  This scenario can come up when you have
		// three ranges, 1 intersecting with 2 but not 3, and 3 nested in 2.
		$subsumedRanges = [];

		// For each range r:(s, e), walk up from s --> docRoot and if any of
		// these nodes have tpl-ranges (besides r itself) assigned to them,
		// then r is nested in those other templates and can be ignored.
		foreach ( $tplRanges as $r ) {
			$n = $r->start;

			while ( $n !== $docRoot ) {
				$ranges = $this->getNodeRanges( $n );
				if ( $ranges ) {
					if ( $n !== $r->start ) {
						// 'r' is nested for sure
						// Record the outermost range in which 'r' is nested.
						$outermostId = null;
						$outermostOffset = null;
						foreach ( $ranges as $rangeId => $range ) {
							if ( $outermostId === null
								|| $range->startOffset < $outermostOffset
							) {
								$outermostId = $rangeId;
								$outermostOffset = $range->startOffset;
							}
						}
						$subsumedRanges[$r->id] = (string)$outermostId;
						break;
					} else {
						// n === r.start
						//
						// We have to make sure this is not an overlap scenario.
						// Find the ranges that r.start and r.end belong to and
						// compute their intersection. If this intersection has
						// another tpl range besides r itself, we have a winner!
						//
						// The code below does the above check efficiently.
						$eTpls = $this->getNodeRanges( $r->end );
						$foundNesting = false;

						foreach ( $ranges as $otherId => $other ) {
							// - Don't record nesting cycles.
							// - Record the outermost range in which 'r' is nested in.
							if ( $otherId !== $r->id &&
								!empty( $eTpls[$otherId] ) &&
								// When we have identical ranges, pick the range with
								// the larger offset to be subsumed.
								( $r->start !== $other->start ||
									$r->end !== $other->end ||
									$other->startOffset < $r->startOffset
								) &&
								!self::introducesCycle( $r->id, (string)$otherId, $subsumedRanges )
							) {
								$foundNesting = true;
								if ( !isset( $subsumedRanges[$r->id] ) ||
									$other->startOffset < $ranges[$subsumedRanges[$r->id]]->startOffset
								) {
									$subsumedRanges[$r->id] = (string)$otherId;
								}
							}
						}

						if ( $foundNesting ) {
							// 'r' is nested
							break;
						}
					}
				}

				// Move up
				$n = $n->parentNode;
			}
		}

		// Sort by start offset in source wikitext
		usort( $tplRanges, static function ( $r1, $r2 ) {
			return $r1->startOffset - $r2->startOffset;
		} );

		// Since the tpl ranges are sorted in textual order (by start offset),
		// it is sufficient to only look at the most recent template to see
		// if the current one overlaps with the previous one.
		//
		// This works because we've already identify nested ranges and can ignore them.

		$newRanges = [];
		$prev = null;

		foreach ( $tplRanges as $r ) {
			$endTagToRemove = null;
			$startTagToStrip = null;

			// Extract tplargInfo
			$tmp = DOMDataUtils::getDataParsoid( $r->startElem )->getTemp();
			$templateInfo = $tmp->tplarginfo ?? null;

			$this->verifyTplInfoExpectation( $templateInfo, $tmp );

			$this->env->log( "trace/{$this->traceType}/merge", static function () use ( &$DOMDataUtils, &$r ) {
				$msg = '';
				$dp1 = DOMDataUtils::getDataParsoid( $r->start );
				$dp2 = DOMDataUtils::getDataParsoid( $r->end );
				$tmp1 = $dp1->tmp;
				$tmp2 = $dp2->tmp;
				$dp1->tmp = null;
				$dp2->tmp = null;
				$msg .= "\n##############################################";
				$msg .= "\nrange " . $r->id . '; r-start-elem: ' . DOMCompat::getOuterHTML( $r->startElem ) .
					'; DP: ' . PHPUtils::jsonEncode( DOMDataUtils::getDataParsoid( $r->startElem ) );
				$msg .= "\nrange " . $r->id . '; r-end-elem: ' . DOMCompat::getOuterHTML( $r->endElem ) .
					'; DP: ' . PHPUtils::jsonEncode( DOMDataUtils::getDataParsoid( $r->endElem ) );
				$msg .= "\nrange " . $r->id . '; r-start: [TAG_ID ' . ( $tmp1->tagId ?? '?' ) . ']: ' .
					DOMCompat::getOuterHTML( $r->start ) . '; DP: ' . PHPUtils::jsonEncode( $dp1 );
				$msg .= "\nrange " . $r->id . '; r-end: [TAG_ID ' . ( $tmp2->tagId ?? '?' ) . ']: ' .
					DOMCompat::getOuterHTML( $r->end ) . '; DP: ' . PHPUtils::jsonEncode( $dp2 );
				$msg .= "\n----------------------------------------------";
				$dp1->tmp = $tmp1;
				$dp2->tmp = $tmp2;
				return $msg;
			} );

			$enclosingRangeId = self::findToplevelEnclosingRange(
				$subsumedRanges,
				$subsumedRanges[$r->id] ?? null
			);
			if ( $enclosingRangeId ) {
				$this->env->log( "trace/{$this->traceType}/merge", '--nested in ', $enclosingRangeId, '--' );

				// Nested -- ignore r
				$startTagToStrip = $r->startElem;
				$endTagToRemove = $r->endElem;
				if ( $templateInfo ) {
					// 'r' is nested in 'enclosingRange' at the top-level
					// So, enclosingRange gets r's argInfo
					$this->recordTemplateInfo( $enclosingRangeId, $r, $templateInfo );
				}
			} elseif ( $prev && self::rangesOverlap( $prev, $r ) ) {
				// In the common case, in overlapping scenarios, r.start is
				// identical to prev.end. However, in fostered content scenarios,
				// there can true overlap of the ranges.
				$this->env->log( "trace/{$this->traceType}/merge", '--overlapped--' );

				// See comment above, where `subsumedRanges` is defined.
				$subsumedRanges[$r->id] = $prev->id;

				// Overlapping ranges.
				// r is the regular kind
				// Merge r with prev

				// Note that if a table comes from a template, a foster box isn't
				// emitted so the enclosure isn't guaranteed.  In pathological
				// cases, like where the table end tag isn't emitted, we can still
				// end up with flipped ranges if the template end marker gets into
				// a fosterable position (which can still happen despite being
				// emitted as a comment).
				Assert::invariant( !$r->flipped,
					'Flipped range should have been enclosed.'
				);

				$startTagToStrip = $r->startElem;
				$endTagToRemove = $prev->endElem;

				$prev->end = $r->end;
				$prev->endElem = $r->endElem;
				if ( WTUtils::isMarkerAnnotation( $r->endElem ) ) {
					$endDataMw = DOMDataUtils::getDataMw( $r->endElem );
					$endDataMw->rangeId = $r->id;
					$prev->extendedByOverlapMerge = true;
				}

				// Update compoundTplInfo
				if ( $templateInfo ) {
					$this->recordTemplateInfo( $prev->id, $r, $templateInfo );
				}
			} else {
				$this->env->log( "trace/{$this->traceType}/merge", '--normal--' );

				// Default -- no overlap
				// Emit the merged range
				$newRanges[] = $r;
				$prev = $r;

				// Update compoundTpls
				if ( $templateInfo ) {
					$this->recordTemplateInfo( $r->id, $r, $templateInfo );
				}
			}

			if ( $endTagToRemove ) {
				// Remove start and end meta-tags
				// Not necessary to remove the start tag, but good to cleanup
				$endTagToRemove->parentNode->removeChild( $endTagToRemove );
				self::stripStartMeta( $startTagToStrip );
			}
		}

		return $newRanges;
	}

	/**
	 * Note that the case of nodeName varies with DOM implementation.  This
	 * method currently forces the name nodeName to uppercase.  In the future
	 * we can/should switch to using the "native" case of the DOM
	 * implementation; we do a case-insensitive match (by converting the result
	 * to the "native" case of the DOM implementation) in
	 * EncapsulatedContentHandler when this value is used.
	 * @param DOMRangeInfo $range
	 * @return string|null nodeName with an optional "_$stx" suffix.
	 */
	private static function findFirstTemplatedNode( DOMRangeInfo $range ): ?string {
		$firstNode = $range->start;

		// Skip tpl marker meta
		if ( WTUtils::isTplMarkerMeta( $firstNode ) ) {
			$firstNode = $firstNode->nextSibling;
		}

		// Walk past fostered nodes since they came from within a table
		// Note that this is not foolproof because in some scenarios,
		// fostered content is not marked up. Ex: when a table is templated,
		// and content from the table is fostered.
		$dp = DOMDataUtils::getDataParsoid( $firstNode );
		while ( !empty( $dp->fostered ) ) {
			$firstNode = $firstNode->nextSibling;
			/** @var Element $firstNode */
			DOMUtils::assertElt( $firstNode );
			$dp = DOMDataUtils::getDataParsoid( $firstNode );
		}

		// FIXME: It is harder to use META as a node name since this is a generic
		// placeholder for a whole bunch of things each of which has its own
		// newline constraint requirements. So, for now, I am skipping that
		// can of worms to prevent confusing the serializer with an overloaded
		// tag name.
		if ( DOMCompat::nodeName( $firstNode ) === 'meta' ) {
			return null;
		}

		// FIXME spec-compliant values would be upper-case, this is just a workaround
		// for current PHP DOM implementation and could be removed in the future
		// See discussion in the method comment above.
		$nodeName = mb_strtoupper( DOMCompat::nodeName( $firstNode ), "UTF-8" );

		return !empty( $dp->stx ) ? $nodeName . '_' . $dp->stx : $nodeName;
	}

	/**
	 * Encapsulation requires adding about attributes on the top-level
	 * nodes of the range. This requires them to all be Elements.
	 *
	 * @param DOMRangeInfo $range
	 */
	private function ensureElementsInRange( DOMRangeInfo $range ): void {
		$n = $range->start;
		$e = $range->end;
		$about = DOMCompat::getAttribute( $range->startElem, 'about' );
		while ( $n ) {
			$next = $n->nextSibling;
			if ( !( $n instanceof Element ) ) {
				// Don't add span-wrappers in fosterable positions
				//
				// NOTE: there cannot be any non-IEW text in fosterable position
				// since the HTML tree builder would already have fostered it out.
				if ( !DOMUtils::isFosterablePosition( $n ) ) {
					$span = $this->document->createElement( 'span' );
					$span->setAttribute( 'about', $about );
					$dp = new DataParsoid;
					$dp->setTempFlag( TempData::WRAPPER );
					DOMDataUtils::setDataParsoid( $span, $dp );
					$n->parentNode->replaceChild( $span, $n );
					$span->appendChild( $n );
					$n = $span;
				}
			} else {
				$n->setAttribute( 'about', $about );
			}

			if ( $n === $e ) {
				break;
			}

			$n = $next;
		}
	}

	/**
	 * Find the first element to be encapsulated.
	 * Skip past marker metas and non-elements (which will all be IEW
	 * in fosterable positions in a table).
	 *
	 * @param DOMRangeInfo $range
	 * @return Element
	 */
	private static function findEncapTarget( DOMRangeInfo $range ): Element {
		$encapTgt = $range->start;
		'@phan-var Node $encapTgt';

		// Skip template-marker meta-tags.
		while ( WTUtils::isTplMarkerMeta( $encapTgt ) ||
			!( $encapTgt instanceof Element )
		) {
			// Detect unwrappable template and bail out early.
			if ( $encapTgt === $range->end ||
				( !( $encapTgt instanceof Element ) &&
					!DOMUtils::isFosterablePosition( $encapTgt )
				)
			) {
				throw new Error( 'Cannot encapsulate transclusion. Start=' .
					DOMCompat::getOuterHTML( $range->startElem ) );
			}
			$encapTgt = $encapTgt->nextSibling;
		}

		'@phan-var Element $encapTgt';
		return $encapTgt;
	}

	/**
	 * Add markers to the DOM around the non-overlapping ranges.
	 *
	 * @param DOMRangeInfo[] $nonOverlappingRanges
	 */
	private function encapsulateTemplates( array $nonOverlappingRanges ): void {
		foreach ( $nonOverlappingRanges as $i => $range ) {

			// We should never have flipped overlapping ranges, and indeed that's
			// asserted in `findTopLevelNonOverlappingRanges`.  Flipping results
			// in either completely nested ranges, or non-intersecting ranges.
			//
			// If the table causing the fostering is not transcluded, we emit a
			// foster box and wrap the whole table+fb in metas, producing nested
			// ranges.  For ex,
			//
			// <table>
			// {{1x|<div>}}
			//
			// The tricky part is when the table *is* transcluded, and we omit the
			// foster box.  The common case (for some definition of common) might
			// be like,
			//
			// {{1x|<table>}}
			// {{1x|<div>}}
			//
			// Here, #mwt1 leaves a table open and the end meta from #mwt2 is
			// fostered, since it gets closed into the div.  The range for #mwt1
			// is the entire table, which thankfully contains #mwt2, so we still
			// have the expected entire nesting.  Any tricks to extend the range
			// of #mwt2 beyond the table (so that we have an overlapping range) will
			// inevitably result in the end meta not being fostered, and we avoid
			// this situation altogether.
			//
			// The very edgy case is as follows,
			//
			// {{1x|<table><div>}}</div>
			// {{1x|<div>}}
			//
			// where both end metas are fostered.  Ignoring that we don't even
			// roundtrip the first transclusion properly on its own, here we have
			// a flipped range where, since the end meta for the first range was
			// also fostered, the ranges still don't overlap.

			// FIXME: The code below needs to be aware of flipped ranges.

			$this->ensureElementsInRange( $range );

			$tplArray = $this->compoundTpls[$range->id] ?? null;
			Assert::invariant( (bool)$tplArray, 'No parts for template range!' );

			$encapTgt = self::findEncapTarget( $range );
			$encapValid = false;
			$encapDP = DOMDataUtils::getDataParsoid( $encapTgt );

			// Update type-of (always even if tpl-encap below will fail).
			// This ensures that VE will still "edit-protect" this template
			// and not allow its content to be edited directly.
			$startElem = $range->startElem;
			if ( $startElem !== $encapTgt ) {
				$t1 = DOMCompat::getAttribute( $startElem, 'typeof' );
				if ( $t1 !== null ) {
					foreach ( array_reverse( explode( ' ', $t1 ) ) as $t ) {
						DOMUtils::addTypeOf( $encapTgt, $t, true );
					}
				}
			}

			/* ----------------------------------------------------------------
			 * We'll attempt to update dp1.dsr to reflect the entire range of
			 * the template.  This relies on a couple observations:
			 *
			 * 1. In the common case, dp2.dsr->end will be > dp1.dsr->end
			 *    If so, new range = dp1.dsr->start, dp2.dsr->end
			 *
			 * 2. But, foster parenting can complicate this when range.end is a table
			 *    and range.start has been fostered out of the table (range.end).
			 *    But, we need to verify this assumption.
			 *
			 *    2a. If dp2.dsr->start is smaller than dp1.dsr->start, this is a
			 *        confirmed case of range.start being fostered out of range.end.
			 *
			 *    2b. If dp2.dsr->start is unknown, we rely on fostered flag on
			 *        range.start, if any.
			 * ---------------------------------------------------------------- */
			$dp1 = DOMDataUtils::getDataParsoid( $range->start );
			$dp1DSR = isset( $dp1->dsr ) ? clone $dp1->dsr : null;
			$dp2DSR = self::getRangeEndDSR( $range );

			if ( $dp1DSR ) {
				if ( $dp2DSR ) {
					// Case 1. above
					if ( $dp2DSR->end > $dp1DSR->end ) {
						$dp1DSR->end = $dp2DSR->end;
					}

					// Case 2. above
					$endDsr = $dp2DSR->start;
					if ( DOMCompat::nodeName( $range->end ) === 'table' &&
						$endDsr !== null &&
						( $endDsr < $dp1DSR->start || !empty( $dp1->fostered ) )
					) {
						$dp1DSR->start = $endDsr;
					}
				}

				// encapsulation possible only if dp1.dsr is valid
				$encapValid = Utils::isValidDSR( $dp1DSR ) &&
					$dp1DSR->end >= $dp1DSR->start;
			}

			if ( $encapValid ) {
				// Find transclusion info from the array (skip past a wikitext element)
				/** @var CompoundTemplateInfo $firstTplInfo */
				$firstTplInfo = is_string( $tplArray[0] ) ? $tplArray[1] : $tplArray[0];

				// Add any leading wikitext
				if ( $firstTplInfo->dsr->start > $dp1DSR->start ) {
					// This gap in dsr (between the final encapsulated content, and the
					// content that actually came from a template) is indicative of this
					// being a mixed-template-content-block and/or multi-template-content-block
					// scenario.
					//
					// In this case, record the name of the first node in the encapsulated
					// content. During html -> wt serialization, newline constraints for
					// this entire block has to be determined relative to this node.
					$ftn = self::findFirstTemplatedNode( $range );
					if ( $ftn !== null ) {
						$encapDP->firstWikitextNode = $ftn;
					}
					$width = $firstTplInfo->dsr->start - $dp1DSR->start;
					array_unshift(
						$tplArray,
						PHPUtils::safeSubstr( $this->frame->getSrcText(), $dp1DSR->start, $width )
					);
				}

				// Add any trailing wikitext
				/** @var CompoundTemplateInfo $lastTplInfo */
				$lastTplInfo = PHPUtils::lastItem( $tplArray );
				if ( $lastTplInfo->dsr->end < $dp1DSR->end ) {
					$width = $dp1DSR->end - $lastTplInfo->dsr->end;
					$tplArray[] = PHPUtils::safeSubstr( $this->frame->getSrcText(), $lastTplInfo->dsr->end, $width );
				}

				// Map the array of { dsr: .. , args: .. } objects to just the args property
				$infoIndex = 0;
				$parts = [];
				$pi = [];
				foreach ( $tplArray as $a ) {
					if ( is_string( $a ) ) {
						$parts[] = $a;
					} elseif ( $a instanceof CompoundTemplateInfo ) {
						// Remember the position of the transclusion relative
						// to other transclusions. Should match the index of
						// the corresponding private metadata in $templateInfos.
						$args = $a->info->getDataMw( $infoIndex++ );
						$parts[] = $a->isParam
							? (object)[ 'templatearg' => $args ]
							: (object)[ 'template' => $args ];
						// FIXME: we throw away the array keys and rebuild them
						// again in WikitextSerializer
						$pi[] = array_values( $a->info->paramInfos );
					}
				}

				// Set up dsr->start, dsr->end, and data-mw on the target node
				// Avoid clobbering existing (ex: extension) data-mw information (T214241)
				$encapDataMw = DOMDataUtils::getDataMw( $encapTgt );
				$encapDataMw->parts = $parts;
				DOMDataUtils::setDataMw( $encapTgt, $encapDataMw );
				$encapDP->pi = $pi;

				// Special case when mixed-attribute-and-content templates are
				// involved. This information is reliable and comes from the
				// AttributeExpander and gets around the problem of unmarked
				// fostered content that findFirstTemplatedNode runs into.
				$firstWikitextNode = DOMDataUtils::getDataParsoid(
						$range->startElem
					)->firstWikitextNode ?? null;
				if ( empty( $encapDP->firstWikitextNode ) && $firstWikitextNode ) {
					$encapDP->firstWikitextNode = $firstWikitextNode;
				}
			} else {
				$errors = [ 'Do not have necessary info. to encapsulate Tpl: ' . $i ];
				$errors[] = 'Start Elt : ' . DOMCompat::getOuterHTML( $startElem );
				$errors[] = 'End Elt   : ' . DOMCompat::getOuterHTML( $range->endElem );
				$errors[] = 'Start DSR : ' . PHPUtils::jsonEncode( $dp1DSR ?? 'no-start-dsr' );
				$errors[] = 'End   DSR : ' . PHPUtils::jsonEncode( $dp2DSR ?? [] );
				$this->env->log( 'error', implode( "\n", $errors ) );
			}

			// Make DSR range zero-width for fostered templates after
			// setting up data-mw. However, since template encapsulation
			// sometimes captures both fostered content as well as the table
			// from which it was fostered from, in those scenarios, we should
			// leave DSR info untouched.
			//
			// SSS FIXME:
			// 1. Should we remove the fostered flag from the entire
			// encapsulated block if we dont set dsr width range to zero
			// since only part of the block is fostered, not the entire
			// encapsulated block?
			//
			// 2. In both cases, should we mark these uneditable by adding
			// mw:Placeholder to the typeof?
			if ( !empty( $dp1->fostered ) ) {
				$encapDataMw = DOMDataUtils::getDataMw( $encapTgt );
				if ( !$encapDataMw ||
					!$encapDataMw->parts ||
					count( $encapDataMw->parts ) === 1
				) {
					$dp1DSR->end = $dp1DSR->start;
				}
			}

			// Update DSR after fostering-related fixes are done.
			if ( $encapValid ) {
				// encapInfo.dp points to DOMDataUtils.getDataParsoid(encapInfo.target)
				// and all updates below update properties in that object tree.
				if ( empty( $encapDP->dsr ) ) {
					$encapDP->dsr = $dp1DSR;
				} else {
					$encapDP->dsr->start = $dp1DSR->start;
					$encapDP->dsr->end = $dp1DSR->end;
				}
				$encapDP->src = $encapDP->dsr->substr(
					$this->frame->getSrcText()
				);
			}

			// Remove startElem (=range.startElem) if a meta.  If a meta,
			// it is guaranteed to be a marker meta added to mark the start
			// of the template.
			if ( WTUtils::isTplMarkerMeta( $startElem ) ) {
				$startElem->parentNode->removeChild( $startElem );
			}

			$range->endElem->parentNode->removeChild( $range->endElem );
		}
	}

	/**
	 * Attach a range to a node.
	 *
	 * @param Element $node
	 * @param DOMRangeInfo $range
	 */
	private function addNodeRange( Element $node, DOMRangeInfo $range ): void {
		// With the native DOM extension, normally you assume that DOMNode
		// objects are temporary -- you get a new DOMNode every time you
		// traverse the DOM. But by retaining a reference in the
		// SplObjectStorage, we ensure that the DOMNode object stays live while
		// the pass is active. Then its address can be used as an index.
		if ( !isset( $this->nodeRanges[$node] ) ) {
			// We have to use an object as the data because
			// SplObjectStorage::offsetGet() does not provide an lval.
			$this->nodeRanges[$node] = new DOMRangeInfoArray;
		}
		$this->nodeRanges[$node]->ranges[$range->id] = $range;
	}

	/**
	 * Get the ranges attached to this node, indexed by range ID.
	 *
	 * @param Element $node
	 * @return DOMRangeInfo[]|null
	 */
	private function getNodeRanges( Element $node ): ?array {
		return $this->nodeRanges[$node]->ranges ?? null;
	}

	/**
	 * Recursively walk the DOM tree. Find wrappable template ranges and return them.
	 *
	 * @param Node $rootNode
	 * @return DOMRangeInfo[]
	 */
	protected function findWrappableMetaRanges( Node $rootNode ): array {
		$tpls = [];
		$tplRanges = [];
		$this->findWrappableTemplateRangesRecursive( $rootNode, $tpls, $tplRanges );
		return $tplRanges;
	}

	/**
	 * Recursive helper for findWrappableTemplateRanges()
	 *
	 * @param Node $rootNode
	 * @param ElementRange[] &$tpls Template start and end elements by ID
	 * @param DOMRangeInfo[] &$tplRanges Template range info
	 */
	private function findWrappableTemplateRangesRecursive(
		Node $rootNode, array &$tpls, array &$tplRanges
	): void {
		$elem = $rootNode->firstChild;

		while ( $elem ) {
			// get the next sibling before doing anything since
			// we may delete elem as part of encapsulation
			$nextSibling = $elem->nextSibling;

			if ( $elem instanceof Element ) {
				$metaType = $this->matchMetaType( $elem );

				// Ignore templates without tsr.
				//
				// These are definitely nested in other templates / extensions
				// and need not be wrapped themselves since they
				// can never be edited directly.
				//
				// NOTE: We are only testing for tsr presence on the start-elem
				// because wikitext errors can lead to parse failures and no tsr
				// on end-meta-tags.
				//
				// Ex: "<ref>{{1x|bar}}<!--bad-></ref>"
				if ( $metaType !== null &&
					( !empty( DOMDataUtils::getDataParsoid( $elem )->tsr ) ||
						str_ends_with( $metaType, '/End' )
					)
				) {
					$about = $this->getRangeId( $elem );
					$tpl = $tpls[$about] ?? null;
					// Is this a start marker?
					if ( !str_ends_with( $metaType, '/End' ) ) {
						if ( $tpl ) {
							$tpl->startElem = $elem;
							// content or end marker existed already
							if ( !empty( $tpl->endElem ) ) {
								// End marker was foster-parented.
								// Found actual start tag.
								$tplRanges[] = $this->getDOMRange(
									$elem, $tpl->endElem, $tpl->endElem );
							} else {
								// should not happen!
								throw new UnreachableException( "start found after content for $about." );
							}
						} else {
							$tpl = new ElementRange;
							$tpl->startElem = $elem;
							$tpls[$about] = $tpl;
						}
					} else {
						// elem is the end-meta tag
						if ( $tpl ) {
							/* ------------------------------------------------------------
							 * Special case: In some cases, the entire template content can
							 * get fostered out of a table, not just the start/end marker.
							 *
							 * Simplest example:
							 *
							 *   {|
							 *   {{1x|foo}}
							 *   |}
							 *
							 * More complex example:
							 *
							 *   {|
							 *   {{1x|
							 *   a
							 *    b
							 *
							 *     c
							 *   }}
							 *   |}
							 *
							 * Since meta-tags don't normally get fostered out, this scenario
							 * only arises when the entire content including meta-tags was
							 * wrapped in p-tags.  So, we look to see if:
							 * 1. the end-meta-tag's parent has a table sibling,
							 * 2. the start meta's parent is marked as fostered.
							 * If so, we recognize this as an adoption scenario and fix up
							 * DSR of start-meta-tag's parent to include the table's DSR.
							 * ------------------------------------------------------------*/
							$sm = $tpl->startElem;

							// TODO: this should only happen in fairly specific cases of the
							// annotation processing and should eventually be handled properly.
							// In the meantime, we create and log an exception to have an idea
							// of the amplitude of the problem.
							if ( $sm === null ) {
								throw new RangeBuilderException( 'No start tag found for the range' );
							}
							$em = $elem;
							$ee = $em;
							$tbl = $em->parentNode->nextSibling;

							// Dont get distracted by a newline node -- skip over it
							// Unsure why it shows up occasionally
							if ( $tbl && $tbl instanceof Text && $tbl->nodeValue === "\n" ) {
								$tbl = $tbl->nextSibling;
							}

							$dp = !DOMUtils::atTheTop( $sm->parentNode ) ?
								DOMDataUtils::getDataParsoid( $sm->parentNode ) : null;
							if ( $tbl && DOMCompat::nodeName( $tbl ) === 'table' && !empty( $dp->fostered ) ) {
								'@phan-var Element $tbl';  /** @var Element $tbl */
								$tblDP = DOMDataUtils::getDataParsoid( $tbl );
								if ( isset( $dp->tsr->start ) && $dp->tsr->start !== null &&
									isset( $tblDP->dsr->start ) && $tblDP->dsr->start === null
								) {
									$tblDP->dsr->start = $dp->tsr->start;
								}
								$tbl->setAttribute( 'about', $about ); // set about on elem
								$ee = $tbl;
							}
							$tplRanges[] = $this->getDOMRange( $sm, $em, $ee );
						} else {
							// The end tag can appear before the start tag if it is fostered out
							// of the table and the start tag is not.
							// It can even technically happen that both tags are fostered out of
							// a table and that the range is flipped: while the fostered content of
							// single table is fostered in-order, the ordering might change
							// across tables if the tags are not initially fostered by the same
							// table.
							$tpl = new ElementRange;
							$tpl->endElem = $elem;
							$tpls[$about] = $tpl;
						}
					}
				} else {
					$this->findWrappableTemplateRangesRecursive( $elem, $tpls, $tplRanges );
				}
			}

			$elem = $nextSibling;
		}
	}

	/**
	 * Returns the meta type of the element if it exists and matches the type expected by the
	 * current class, null otherwise
	 * @param Element $elem the element to check
	 * @return string|null
	 */
	protected function matchMetaType( Element $elem ): ?string {
		// for this class we're interested in the template type
		return WTUtils::matchTplType( $elem );
	}

	protected function verifyTplInfoExpectation( ?TemplateInfo $templateInfo, TempData $tmp ): void {
		if ( !$templateInfo ) {
			// An assertion here is probably an indication that we're
			// mistakenly doing template wrapping in a nested context.
			Assert::invariant( $tmp->getFlag( TempData::FROM_FOSTER ), 'Template range without arginfo.' );
		}
	}

	public function execute( Node $root ): void {
		$tplRanges = $this->findWrappableMetaRanges( $root );
		if ( count( $tplRanges ) > 0 ) {
			$nonOverlappingRanges = $this->findTopLevelNonOverlappingRanges( $root, $tplRanges );
			$this->encapsulateTemplates( $nonOverlappingRanges );
		}
	}

	/**
	 * Creates a range that encloses $startElem and $endElem
	 * @param Element $startElem
	 * @param Element $endElem
	 * @return DOMRangeInfo
	 */
	protected function findEnclosingRange( Element $startElem, Element $endElem ): DOMRangeInfo {
		$range = new DOMRangeInfo;
		$range->id = Utils::stripParsoidIdPrefix( $this->getRangeId( $startElem ) );
		$range->startOffset = DOMDataUtils::getDataParsoid( $startElem )->tsr->start;

		// Find common ancestor of startElem and endElem
		$startAncestors = DOMUtils::pathToRoot( $startElem );
		$elem = $endElem;
		$parentNode = $endElem->parentNode;
		while ( $parentNode && $parentNode->nodeType !== XML_DOCUMENT_NODE ) {
			$i = array_search( $parentNode, $startAncestors, true );
			if ( $i === 0 ) {
				// the common ancestor is startElem
				// widen the scope to include the full subtree
				$range->start = $startElem->firstChild;
				$range->end = $startElem->lastChild;
				break;
			} elseif ( $i > 0 ) {
				$range->start = $startAncestors[$i - 1];
				$range->end = $elem;
				break;
			}
			$elem = $parentNode;
			$parentNode = $elem->parentNode;
		}

		return $range;
	}
}
