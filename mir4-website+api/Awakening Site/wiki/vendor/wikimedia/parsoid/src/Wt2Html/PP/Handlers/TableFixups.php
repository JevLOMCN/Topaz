<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Handlers;

use stdClass;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\NodeData\DataMw;
use Wikimedia\Parsoid\NodeData\TempData;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wt2Html\Frame;
use Wikimedia\Parsoid\Wt2Html\PegTokenizer;

/**
 * Provides DOMTraverser visitors that fix template-induced interrupted table cell parsing
 * by recombining table cells and/or reparsing table cell content as attributes.
 * - stripDoubleTDs
 * - handleTableCellTemplates
 */
class TableFixups {
	/**
	 * @var PegTokenizer
	 */
	private $tokenizer;

	public function __construct( Env $env ) {
		/**
		 * Set up some helper objects for reparseTemplatedAttributes
		 */

		/**
		 * Actually the regular tokenizer, but we'll use
		 * tokenizeTableCellAttributes only.
		 */
		$this->tokenizer = new PegTokenizer( $env );
	}

	/**
	 * DOM visitor that strips the double td for this test case:
	 * ```
	 * |{{1x|{{!}} Foo}}
	 * ```
	 *
	 * @see https://phabricator.wikimedia.org/T52603
	 * @param Element $node
	 * @param Frame $frame
	 * @return bool|Node
	 */
	public function stripDoubleTDs( Element $node, Frame $frame ) {
		$nextNode = $node->nextSibling;
		if ( !WTUtils::isLiteralHTMLNode( $node ) &&
			$nextNode instanceof Element &&
			DOMCompat::nodeName( $nextNode ) === 'td' &&
			!WTUtils::isLiteralHTMLNode( $nextNode ) &&
			DiffDOMUtils::nodeEssentiallyEmpty( $node ) && (
				// FIXME: will not be set for nested templates
				DOMUtils::hasTypeOf( $nextNode, 'mw:Transclusion' ) ||
				// Hacky work-around for nested templates
				preg_match( '/^{{.*?}}$/D', DOMDataUtils::getDataParsoid( $nextNode )->src ?? '' )
			)
		) {
			// Update the dsr. Since we are coalescing the first
			// node with the second (or, more precisely, deleting
			// the first node), we have to update the second DSR's
			// starting point and start tag width.
			$nodeDSR = DOMDataUtils::getDataParsoid( $node )->dsr ?? null;
			$nextNodeDP = DOMDataUtils::getDataParsoid( $nextNode );

			if ( $nodeDSR && !empty( $nextNodeDP->dsr ) ) {
				$nextNodeDP->dsr->start = $nodeDSR->start;
			}

			$dataMW = DOMDataUtils::getDataMw( $nextNode );
			$nodeSrc = WTUtils::getWTSource( $frame, $node );
			$dataMW->parts ??= [];
			array_unshift( $dataMW->parts, $nodeSrc );

			// Delete the duplicated <td> node.
			$node->parentNode->removeChild( $node );
			// This node was deleted, so don't continue processing on it.
			return $nextNode;
		}

		return true;
	}

	private function isSimpleTemplatedSpan( Node $node ): bool {
		return DOMCompat::nodeName( $node ) === 'span' &&
			DOMUtils::hasTypeOf( $node, 'mw:Transclusion' ) &&
			DOMUtils::allChildrenAreTextOrComments( $node );
	}

	/**
	 * @param list<string|stdClass> &$parts
	 * @param Frame $frame
	 * @param int $offset1
	 * @param int $offset2
	 */
	private function fillDSRGap( array &$parts, Frame $frame, int $offset1, int $offset2 ): void {
		if ( $offset1 < $offset2 ) {
			$parts[] = PHPUtils::safeSubstr( $frame->getSrcText(), $offset1, $offset2 - $offset1 );
		}
	}

	/**
	 * Hoist transclusion information from cell content / attributes
	 * onto the cell itself.
	 *
	 * @param Frame $frame
	 * @param Element[] $transclusions
	 * @param Element $td
	 */
	private function hoistTransclusionInfo(
		Frame $frame, array $transclusions, Element $td
	): void {
		// Initialize dsr for $td
		// In `handleTableCellTemplates`, we're creating a cell w/o dsr info.
		$tdDp = DOMDataUtils::getDataParsoid( $td );
		if ( !Utils::isValidDSR( $tdDp->dsr ?? null ) ) {
			$tplDp = DOMDataUtils::getDataParsoid( $transclusions[0] );
			Assert::invariant( Utils::isValidDSR( $tplDp->dsr ?? null ), 'Expected valid DSR' );
			$tdDp->dsr = clone $tplDp->dsr;
		}

		// Build up $parts, $pi to set up the combined transclusion info on $td.
		// Note that content for all but the last template has been swallowed into
		// the attributes of $td.
		$parts = [];
		$pi = [];
		$lastTpl = null;
		$prevDp = null;

		$index = 0;
		foreach ( $transclusions as $i => $tpl ) {
			$tplDp = DOMDataUtils::getDataParsoid( $tpl );
			Assert::invariant( Utils::isValidDSR( $tplDp->dsr ?? null ), 'Expected valid DSR' );

			// Plug DSR gaps between transclusions
			if ( !$prevDp ) {
				$this->fillDSRGap( $parts, $frame, $tdDp->dsr->start, $tplDp->dsr->start );
			} else {
				$this->fillDSRGap( $parts, $frame, $prevDp->dsr->end, $tplDp->dsr->start );
			}

			// Assimilate $tpl's data-mw and data-parsoid pi info
			$dmw = DOMDataUtils::getDataMw( $tpl );
			foreach ( $dmw->parts ?? [] as $part ) {
				'@phan-var string|stdClass $part';
				// Template index is relative to other transclusions.
				// This index is used to extract whitespace information from
				// data-parsoid and that array only includes info for templates.
				// So skip over strings here.
				if ( !is_string( $part ) ) {
					// Cloning is strictly not needed here, but mimicing
					// code in WrapSectionsState.php
					$part = clone $part;
					if ( isset( $part->template ) ) {
						$part->template->i = $index++;
					} else {
						$part->templatearg->i = $index++;
					}
				}
				$parts[] = $part;
			}
			PHPUtils::pushArray( $pi, $tplDp->pi ?? [ [] ] );
			DOMDataUtils::setDataMw( $tpl, null );

			$lastTpl = $tpl;
			$prevDp = $tplDp;
		}

		$aboutId = DOMCompat::getAttribute( $lastTpl, 'about' );

		// Hoist transclusion information to $td.
		$td->setAttribute( 'typeof', 'mw:Transclusion' );
		$td->setAttribute( 'about', $aboutId );

		// Add wikitext for the table cell content following $lastTpl
		$this->fillDSRGap( $parts, $frame, $prevDp->dsr->end, $tdDp->dsr->end );

		// Save the new data-mw on the td
		DOMDataUtils::setDataMw( $td, new DataMw( [ 'parts' => $parts ] ) );
		$tdDp->pi = $pi;

		// td wraps everything now.
		// Remove template encapsulation from here on.
		// This simplifies the problem of analyzing the <td>
		// for additional fixups (|| Boo || Baz) by potentially
		// invoking 'reparseTemplatedAttributes' on split cells
		// with some modifications.
		$child = $lastTpl;

		// Transclusions may be nested in elements in some ugly wikitext so
		// make sure we're starting at a direct descendant of td
		while ( $child->parentNode !== $td ) {
			$child = $child->parentNode;
		}

		while ( $child ) {
			if (
				DOMCompat::nodeName( $child ) === 'span' &&
				DOMCompat::getAttribute( $child, 'about' ) === $aboutId
			) {
				// Remove the encapsulation attributes. If there are no more attributes left,
				// the span wrapper is useless and can be removed.
				$child->removeAttribute( 'about' );
				$child->removeAttribute( 'typeof' );
				if ( DOMDataUtils::noAttrs( $child ) ) {
					$next = $child->firstChild ?: $child->nextSibling;
					DOMUtils::migrateChildren( $child, $td, $child );
					$child->parentNode->removeChild( $child );
					$child = $next;
				} else {
					$child = $child->nextSibling;
				}
			} else {
				$child = $child->nextSibling;
			}
		}
	}

	/**
	 * Collect potential attribute content.
	 *
	 * We expect this to be text nodes without a pipe character followed by one or
	 * more nowiki spans, followed by a template encapsulation with pure-text and
	 * nowiki content. Collection stops when encountering a pipe character.
	 *
	 * @param Env $env
	 * @param Element $cell known to be <td> / <th>
	 * @param ?Element $templateWrapper
	 * @return ?array
	 */
	public function collectAttributishContent(
		Env $env, Element $cell, ?Element $templateWrapper
	): ?array {
		$buf = [];
		$nowikis = [];
		$transclusions = $templateWrapper ? [ $templateWrapper ] : [];

		// Some of this logic could be replaced by DSR-based recovery of
		// wikitext that is outside templates. But since we have to walk over
		// templated content in this fashion anyway, we might as well use the
		// same logic uniformly.

		$traverse = static function ( ?Node $child ) use (
			&$traverse, &$buf, &$nowikis, &$transclusions
		): bool {
			while ( $child ) {
				if ( $child instanceof Comment ) {
					// Legacy parser strips comments during parsing => drop them.
				} elseif ( $child instanceof Text ) {
					$text = $child->nodeValue;
					$buf[] = $text;

					// Are we done accumulating?
					if ( preg_match( '/(?:^|[^|])\|(?:[^|]|$)/D', $text ) ) {
						return true;
					}
				} else {
					'@phan-var Element $child';  /** @var Element $child */
					if ( DOMUtils::hasTypeOf( $child, 'mw:Transclusion' ) ) {
						$transclusions[] = $child;
					}

					if ( WTUtils::isFirstExtensionWrapperNode( $child ) ) {
						// "|" chars in extension content don't trigger table-cell parsing
						// since they have higher precedence in tokenization. The extension
						// content will simply be dropped (but any side effects it had will
						// continue to apply. Ex: <ref> tags might leave an orphaned ref in
						// the <references> section).
						$child = WTUtils::skipOverEncapsulatedContent( $child );
						continue;
					} elseif ( DOMUtils::hasTypeOf( $child, 'mw:Entity' ) ) {
						// Get entity's wikitext source, not rendered content.
						// "&#10;" is "\n" which breaks attribute parsing!
						$buf[] = DOMDataUtils::getDataParsoid( $child )->src ?? $child->textContent;
					} elseif ( DOMUtils::hasTypeOf( $child, 'mw:Nowiki' ) ) {
						// Nowiki span were added to protect otherwise
						// meaningful wikitext chars used in attributes.
						// Save the content and add in a marker to splice out later.
						$nowikis[] = $child->textContent;
						$buf[] = '<nowiki-marker>';
					} elseif ( DOMUtils::hasRel( $child, 'mw:WikiLink' ) ||
						WTUtils::isGeneratedFigure( $child )
					) {
						// Wikilinks/images abort attribute parsing
						return true;
					} else {
						if ( $traverse( $child->firstChild ) ) {
							return true;
						}
					}
				}

				$child = $child->nextSibling;
			}

			return false;
		};

		if ( $traverse( $cell->firstChild ) ) {
			return [
				'txt' => implode( '', $buf ),
				'nowikis' => $nowikis,
				'transclusions' => $transclusions,
			];
		} else {
			return null;
		}
	}

	/**
	 * T46498, second part of T52603
	 *
	 * Handle wikitext like
	 * ```
	 * {|
	 * |{{nom|Bar}}
	 * |}
	 * ```
	 * where nom expands to `style="foo" class="bar"|Bar`. The attributes are
	 * tokenized and stripped from the table contents.
	 *
	 * This method works well for the templates documented in
	 * https://en.wikipedia.org/wiki/Template:Table_cell_templates/doc
	 *
	 * Nevertheless, there are some limitations:
	 * - We assume that attributes don't contain wiki markup (apart from <nowiki>)
	 *   and end up in text or nowiki nodes.
	 * - Only a single table cell is produced / opened by the template that
	 *   contains the attributes. This limitation could be lifted with more
	 *   aggressive re-parsing if really needed in practice.
	 * - There is only a single transclusion in the table cell content. This
	 *   limitation can be lifted with more advanced data-mw construction.
	 *
	 * @param Frame $frame
	 * @param Element $cell known to be <td> / <th>
	 * @param ?Element $templateWrapper
	 */
	public function reparseTemplatedAttributes(
		Frame $frame, Element $cell, ?Element $templateWrapper
	): void {
		$env = $frame->getEnv();
		// Collect attribute content and examine it
		$attributishContent = $this->collectAttributishContent( $env, $cell, $templateWrapper );
		if ( !$attributishContent ) {
			return;
		}

		/**
		 * FIXME: These checks are insufficient.
		 * Previous rounds of table fixups might have created this cell without
		 * any templated content (the while loop in handleTableCellTemplates).
		 * Till we figure out a reliable test for this, we'll reparse attributes always.
		 *
		 * // This DOM pass is trying to bridge broken parses across
		 * // template boundaries. so, if templates aren't involved,
		 * // no reason to reparse.
		 * if ( count( $attributishContent['transclusions'] ) === 0 &&
		 * 	!WTUtils::fromEncapsulatedContent( $cell )
		 * ) {
		 * 	return;
		 * }
		 */

		$attrText = $attributishContent['txt'];
		if ( !preg_match( '/(^[^|]+\|)([^|]|$)/D', $attrText, $matches ) ) {
			return;
		}
		$attributishPrefix = $matches[1];

		// Splice in nowiki content.  We added in <nowiki> markers to prevent the
		// above regexps from matching on nowiki-protected chars.
		if ( str_contains( $attributishPrefix, '<nowiki-marker>' ) ) {
			$attributishPrefix = preg_replace_callback(
				'/<nowiki-marker>/',
				static function ( $unused ) use ( &$attributishContent ) {
					// This is a little tricky. We want to use the content from the
					// nowikis to reparse the string to key/val pairs but the rule,
					// single_cell_table_args, will invariably get tripped up on
					// newlines which, to this point, were shuttled through in the
					// nowiki. Core sanitizer will do this replacement in attr vals
					// so it's a safe normalization to do here.
					return preg_replace( '/\s+/', ' ', array_shift( $attributishContent['nowikis'] ) );
				},
				$attributishPrefix
			);
		}

		// re-parse the attributish prefix
		$attributeTokens = $this->tokenizer->tokenizeTableCellAttributes( $attributishPrefix, false );

		// No attributes => nothing more to do!
		if ( !$attributeTokens ) {
			return;
		}

		// Note that `row_syntax_table_args` (the rule used for tokenizing above)
		// returns an array consisting of [table_attributes, spaces, pipe]
		$attrs = $attributeTokens[0];

		// Sanitize attrs and transfer them to the td node
		Sanitizer::applySanitizedArgs( $env->getSiteConfig(), $cell, $attrs );
		$cellDp = DOMDataUtils::getDataParsoid( $cell );
		$cellDp->setTempFlag( TempData::NO_ATTRS, false );

		// If the transclusion node was embedded within the td node,
		// lift up the about group to the td node.
		$transclusions = $attributishContent['transclusions'];
		if ( $transclusions && ( $cell !== $transclusions[0] || count( $transclusions ) > 1 ) ) {
			$this->hoistTransclusionInfo( $frame, $transclusions, $cell );
		}

		// Drop content that has been consumed by the reparsed attribute content.
		// NOTE: We serialize and reparse data-object-id attributes as well which
		// ensures stashed data-* attributes continue to be usable.
		// FIXME: This is too naive.  What about all the care we showed in `collectAttributishContent`?
		DOMCompat::setInnerHTML( $cell,
			preg_replace( '/^[^|]*\|/', '', DOMCompat::getInnerHTML( $cell ) ) );
	}

	/**
	 * Possibilities:
	 * - $cell and $prev are both <td>s (or both <th>s)
	 *   - Common case
	 *   - Ex: "|align=left {{tpl returning | foobar}}"
	 *     So, "|align=left |foobar" is the combined string
	 *   - Combined cell is a <td> (will be <th> if both were <th>s)
	 *   - We assign new attributes to $cell and drop $prev
	 * - $cell is <td> and $prev is <th>
	 *   - Ex: "!align=left {{tpl returning | foobar}}"
	 *     So, "!align=left |foobar" is the combined string
	 *   - The combined cell will be a <th> with attributes "align=left"
	 *     and content "foobar"
	 * - $cell is <th> and $prev is <td>
	 *    - Ex: "|align=left {{tpl returning !scope=row | foobar}}"
	 *      So "|align=left !scope=row | foobar" is the combined string
	 *      and we need to drop the th-attributes entirely after combining
	 *   - The combined cell will be a <td>
	 *   - $cell's attribute is dropped
	 *   - $prev's content is dropped
	 *
	 * @param Frame $frame
	 * @param Element $cell
	 * @return bool
	 */
	private function combineWithPreviousCell( Frame $frame, Element $cell ): bool {
		// UNSUPPORTED SCENARIO 1:
		// While in the general case, we should look for combinability no matter
		// whether $cell has attributes or not,  we are currently restricting
		// our support to use cases where $cell doesn't have attributes since that
		// is the common scenario and use case for this kind of markup.
		//
		//     Ex: |class="foo"{{1x|1={{!}}title="x"{{!}}foo}}
		//         should parse as <td class="foo">title="x"|foo</td>
		$cellIsTd = DOMCompat::nodeName( $cell ) === 'td';
		$cellDp = DOMDataUtils::getDataParsoid( $cell );
		if ( $cellIsTd && !$cellDp->getTempFlag( TempData::NO_ATTRS ) ) {
			return false;
		}

		// UNSUPPORTED SCENARIO 2:
		// In this cell-combining scenario, $prev can have attributes only if it
		// also had content. See example below:
		//     Ex: |class="foo"|bar{{1x|1={{!}}foo}}
		//         should parse as <td class="foo">bar|foo</td>
		// In this case, the attributes/content of $cell would end up as the
		// content of the combined cell.
		//
		// UNSUPPORTED SCENARIO 3:
		// The template produced attributes as well as maybe a new cell.
		//     Ex: |class="foo"{{1x| foo}} and |class="foo"{{1x|&nbsp;foo}}
		// We let the more general 'reparseTemplatedAttributes' code handle
		// this scenario for now.
		$prev = $cell->previousSibling;
		DOMUtils::assertElt( $prev );
		$prevDp = DOMDataUtils::getDataParsoid( $prev );

		// Build the attribute string
		$prevCellSrc = PHPUtils::safeSubstr(
			$frame->getSrcText(), $prevDp->dsr->start, $prevDp->dsr->length() );

		$cellAttrSrc = substr( $prevCellSrc, $prevDp->dsr->openWidth );
		$reparseSrc = $cellAttrSrc . "|"; // "|" or "!", but doesn't matter since we discard that anyway

		// Reparse the attributish prefix
		$env = $frame->getEnv();
		$attributeTokens = $this->tokenizer->tokenizeTableCellAttributes( $reparseSrc, false );
		if ( !is_array( $attributeTokens ) ) {
			$env->log( "error/wt2html",
				"TableFixups: Failed to successfully reparse $reparseSrc as table cell attributes" );
			return false;
		}

		// Update data-mw, DSR if $cell is an encapsulation wrapper
		$dataMW = null;
		$cellIsTplWrapper = WTUtils::isFirstEncapsulationWrapperNode( $cell );
		if ( $cellIsTplWrapper ) {
			$dataMW = DOMDataUtils::getDataMw( $cell );
			array_unshift( $dataMW->parts, $prevCellSrc );
			$cellDSR = $cellDp->dsr ?? null;
			if ( $cellDSR && $cellDSR->start ) {
				$cellDSR->start -= strlen( $prevCellSrc );
			}
		}

		$parent = $cell->parentNode;
		if ( DOMCompat::nodeName( $cell ) === DOMCompat::nodeName( $prev ) ) {
			// Matching cell types
			$combinedCell = $cell;
			$combinedCellDp = $cellDp;
			$parent->removeChild( $prev );
		} else {
			// Different cell types
			$combinedCell = $prev;

			// Remove all content on $prev which will
			// become the new combined cell
			while ( $prev->firstChild ) {
				$prev->removeChild( $prev->firstChild );
			}
			// Note that this implicitly migrates data-mw and data-parsoid
			foreach ( DOMUtils::attributes( $cell ) as $k => $v ) {
				$combinedCell->setAttribute( $k, $v );
			}
			DOMUtils::migrateChildren( $cell, $combinedCell );
			$parent->removeChild( $cell );

			$combinedCellDp = DOMDataUtils::getDataParsoid( $combinedCell );
		}

		// Note that `row_syntax_table_args` (the rule used for tokenizing above)
		// returns an array consisting of [table_attributes, spaces, pipe]
		$attrs = $attributeTokens[0];
		Sanitizer::applySanitizedArgs( $env->getSiteConfig(), $combinedCell, $attrs );
		$combinedCellDp->setTempFlag( TempData::NO_ATTRS, false );

		return true;
	}

	private const NO_REPARSING = 0;
	private const COMBINE_WITH_PREV_CELL = 1;
	private const OTHER_REPARSE = 2;

	/**
	 * @param Element $cell $cell is known to be <td>/<th>
	 * @return int
	 */
	private function getReparseType( Element $cell ): int {
		$prev = $cell->previousSibling;
		$dp = DOMDataUtils::getDataParsoid( $cell );
		if ( WTUtils::fromEncapsulatedContent( $cell ) && // See long comment below
			!$dp->getTempFlag( TempData::FAILED_REPARSE ) &&
			!isset( $dp->stx ) // has to be first cell of the row
		) {
			// Parsoid parses content of templates independent of top-level content.
			// But, this breaks legacy-parser-supported use-cases where template
			// content combines with top-level content to yield a table cell whose
			// source straddles the template boundary.
			//
			// In Parsoid, we handle this by looking for opportunities where
			// table cells could combine. This obviously requires $cell to be
			// a templated cell. But, we don't support combining templated cells
			// with other templated cells.  So, previous sibling cannot be templated.
			//
			// So, bail out of scenarios where prevDp comes from a template (the checks
			// for isValidDSR( $prevDp-> dsr ) and valid opening tag width catch this.
			$prevDp = $prev instanceof Element ? DOMDataUtils::getDataParsoid( $prev ) : null;
			if ( $prevDp &&
				!WTUtils::hasLiteralHTMLMarker( $prevDp ) &&
				$prevDp->getTempFlag( TempData::NO_ATTRS ) &&
				Utils::isValidDSR( $prevDp->dsr ?? null, true ) &&
				!DOMUtils::hasTypeOf( $prev, 'mw:Transclusion' ) &&
				!str_contains( DOMCompat::getInnerHTML( $prev ), "\n" )
			) {
				return self::COMBINE_WITH_PREV_CELL;
			}
		}

		$cellIsTd = DOMCompat::nodeName( $cell ) === 'td';
		$testRE = $cellIsTd ? '/[|]/' : '/[!|]/';
		$child = $cell->firstChild;
		while ( $child ) {
			if ( $child instanceof Text && preg_match( $testRE, $child->textContent ) ) {
				return self::OTHER_REPARSE;
			}

			if ( WTUtils::isFirstExtensionWrapperNode( $child ) ) {
				// "|" chars in extension content don't trigger table-cell parsing
				// since they have higher precedence in tokenization
				$child = WTUtils::skipOverEncapsulatedContent( $child );
			} else {
				if ( $child instanceof Element ) {
					if ( DOMUtils::hasRel( $child, 'mw:WikiLink' ) ||
						WTUtils::isGeneratedFigure( $child )
					) {
						// Wikilinks/images abort attribute parsing
						return self::NO_REPARSING;
					}
					if ( preg_match( $testRE, DOMCompat::getOuterHTML( $child ) ) ) {
						// A "|" char in the HTML will trigger table cell tokenization.
						// Ex: "| foobar <div> x | y </div>" will split the <div>
						// in table-cell tokenization context.
						return self::OTHER_REPARSE;
					}
				}
				$child = $child->nextSibling;
			}
		}

		return self::NO_REPARSING;
	}

	/**
	 * @param Element $cell $cell is known to be <td>/<th>
	 * @param Frame $frame
	 * @return mixed
	 */
	public function handleTableCellTemplates( Element $cell, Frame $frame ) {
		if ( WTUtils::isLiteralHTMLNode( $cell ) ) {
			return true;
		}

		$reparseType = $this->getReparseType( $cell );
		if ( $reparseType === self::NO_REPARSING ) {
			return true;
		}

		if ( $reparseType === self::COMBINE_WITH_PREV_CELL ) {
			if ( $this->combineWithPreviousCell( $frame, $cell ) ) {
				return true;
			} else {
				// Clear property and retry $cell for other reparses
				// The DOMTraverser will resume the handler on the
				// returned $cell.
				DOMDataUtils::getDataParsoid( $cell )->setTempFlag( TempData::FAILED_REPARSE );
				return $cell;
			}
		}

		// If the cell didn't have attrs, extract and reparse templated attrs
		$dp = DOMDataUtils::getDataParsoid( $cell );
		if ( $dp->getTempFlag( TempData::NO_ATTRS ) ) {
			$templateWrapper = DOMUtils::hasTypeOf( $cell, 'mw:Transclusion' ) ? $cell : null;
			$this->reparseTemplatedAttributes( $frame, $cell, $templateWrapper );
		}

		// Now, examine the <td> to see if it hides additional <td>s
		// and split it up if required.
		//
		// DOMTraverser will process the new cell and invoke
		// handleTableCellTemplates on it which ensures that
		// if any addition attribute fixup or splits are required,
		// they will get done.
		$newCell = null;
		$isTd = DOMCompat::nodeName( $cell ) === 'td';
		$ownerDoc = $cell->ownerDocument;
		$child = $cell->firstChild;
		while ( $child ) {
			$next = $child->nextSibling;

			if ( $newCell ) {
				$newCell->appendChild( $child );
			} elseif ( $child instanceof Text || $this->isSimpleTemplatedSpan( $child ) ) {
				// FIXME: This skips over scenarios like <div>foo||bar</div>.
				$cellName = DOMCompat::nodeName( $cell );
				$hasSpanWrapper = !( $child instanceof Text );
				$match = $match1 = $match2 = null;

				// Find the first match of ||
				preg_match( '/^((?:[^|]*(?:\|[^|])?)*)\|\|([^|].*)?$/D', $child->textContent, $match1 );
				if ( $isTd ) {
					$match = $match1;
				} else {
					// Find the first match !!
					preg_match( '/^((?:[^!]*(?:\![^!])?)*)\!\!([^!].*)?$/D', $child->textContent, $match2 );

					// Pick the shortest match
					if ( $match1 && $match2 ) {
						$match = strlen( $match1[1] ?? '' ) < strlen( $match2[1] ?? '' )
							? $match1
							: $match2;
					} else {
						$match = $match1 ?: $match2;
					}
				}

				if ( $match ) {
					$child->textContent = $match[1] ?? '';

					$newCell = $ownerDoc->createElement( $cellName );
					if ( $hasSpanWrapper ) {
						/**
						 * $hasSpanWrapper above ensures $child is a span.
						 *
						 * @var Element $child
						 */
						'@phan-var Element $child';
						// Fix up transclusion wrapping
						$about = DOMCompat::getAttribute( $child, 'about' );
						$this->hoistTransclusionInfo( $frame, [ $child ], $cell );
					} else {
						// Refetch the about attribute since 'reparseTemplatedAttributes'
						// might have added one to it.
						$about = DOMCompat::getAttribute( $cell, 'about' );
					}

					// about may not be present if the cell was inside
					// wrapped template content rather than being part
					// of the outermost wrapper.
					if ( $about !== null ) {
						$newCell->setAttribute( 'about', $about );
					}
					$newCell->appendChild( $ownerDoc->createTextNode( $match[2] ?? '' ) );
					$cell->parentNode->insertBefore( $newCell, $cell->nextSibling );

					// Set data-parsoid noAttrs flag
					$newCellDP = DOMDataUtils::getDataParsoid( $newCell );
					$newCellDP->setTempFlag( TempData::NO_ATTRS );
					// This new cell has 'row' stx (would be set if the tokenizer had parsed it)
					// It is important to set this so that when $newCell is processed by this pass,
					// it won't accidentally recombine again with the previous cell!
					$newCellDP->stx = 'row';
				}
			}

			$child = $next;
		}

		return true;
	}
}
