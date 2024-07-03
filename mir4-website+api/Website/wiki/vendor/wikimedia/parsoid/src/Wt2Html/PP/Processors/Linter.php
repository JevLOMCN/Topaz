<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use stdClass;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\NodeData\TempData;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Timing;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

/**
 * DOM pass that walks the DOM tree, detects specific wikitext patterns,
 * and emits them as linter events.
 */
class Linter implements Wt2HtmlDOMProcessor {
	/** @var ParsoidExtensionAPI */
	private $extApi = null;

	/** @var array<string,bool>|null */
	private $tagsWithChangedMisnestingBehavior = null;

	/** @var string|null */
	private $obsoleteTagsRE = null;

	/**
	 * We are trying to find HTML5 tags that have different behavior compared to HTML4
	 * in some misnesting scenarios around wikitext paragraphs.
	 *
	 * Ex: Input: <p><small>a</p><p>b</small></p>
	 *     Tidy  output: <p><small>a</small></p><p><small>b</small></p>
	 *     HTML5 output: <p><small>a</small></p><p><small>b</small></p>
	 *
	 * So, all good here.
	 * But, see how output changes when we use <span> instead
	 *
	 * Ex: Input: <p><span>a</p><p>b</span></p>
	 *     Tidy  output: <p><span>a</span></p><p><span>b</span></p>
	 *     HTML5 output: <p><span>a</span></p><p>b</p>
	 *
	 * The source wikitext is "<span>a\n\nb</span>". The difference persists even
	 * when you have "<span>a\n\n<div>b</div>" or "<span>a\n\n{|\n|x\n|}\nbar".
	 *
	 * This is because Tidy seems to be doing the equivalent of HTM5-treebuilder's
	 * active formatting element reconstruction step on all *inline* elements.
	 * However, HTML5 parsers only do that on formatting elements. So, we need
	 * to compute which HTML5 tags are subject to this differential behavior.
	 *
	 * We compute that by excluding the following tags from the list of all HTML5 tags
	 * - If our sanitizer doesn't allow them, they will be escaped => ignore them
	 * - HTML4 block tags are excluded (obviously)
	 * - Void tags don't matter since they cannot wrap anything (obviously)
	 * - Active formatting elements have special handling in the HTML5 tree building
	 *   algorithm where they are reconstructed to wrap all originally intended content.
	 *   (ex: <small> above)
	 *
	 * Here is the list of 22 HTML5 tags that are affected:
	 *    ABBR, BDI, BDO, CITE, DATA, DEL, DFN, INS, KBD, MARK,
	 *    Q, RB, RP, RT, RTC, RUBY, SAMP, SPAN, SUB, SUP, TIME, VAR
	 *
	 * https://phabricator.wikimedia.org/T176363#3628173 verifies that this list of
	 * tags all demonstrate this behavior.
	 *
	 * @return array
	 * @phan-return array<string,bool>
	 */
	private function getTagsWithChangedMisnestingBehavior(): array {
		if ( $this->tagsWithChangedMisnestingBehavior === null ) {
			// This set is frozen in time.  It gets us down to the requisite
			// 22 HTML5 tags above, but shouldn't be used for anything other
			// than that.
			$HTML4TidyBlockTags = PHPUtils::makeSet( [
				'div', 'p',
				# tables
				'table', 'tbody', 'thead', 'tfoot', 'caption', 'th', 'tr', 'td',
				# lists
				'ul', 'ol', 'li', 'dl', 'dt', 'dd',
				# HTML5 heading content
				'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hgroup',
				# HTML5 sectioning content
				'article', 'aside', 'nav', 'section', 'footer', 'header',
				'figure', 'figcaption', 'fieldset', 'details', 'blockquote',
				# other
				'hr', 'button', 'canvas', 'center', 'col', 'colgroup', 'embed',
				'map', 'object', 'pre', 'progress', 'video',
			] );
			$this->tagsWithChangedMisnestingBehavior = [];
			foreach ( Consts::$HTML['HTML5Tags'] as $tag => $dummy ) {
				if ( isset( Consts::$Sanitizer['AllowedLiteralTags'][$tag] ) &&
					!isset( $HTML4TidyBlockTags[$tag] ) &&
					!isset( Consts::$HTML['FormattingTags'][$tag] ) &&
					!isset( Consts::$HTML['VoidTags'][$tag] )
				) {
					$this->tagsWithChangedMisnestingBehavior[$tag] = true;
				}
			}
		}

		return $this->tagsWithChangedMisnestingBehavior;
	}

	/**
	 * Finds a matching node at the "start" of this node.
	 */
	private function leftMostMisnestedDescendent( ?Node $node, Element $match ): ?Element {
		if ( !$node instanceof Element ) {
			return null;
		}

		if ( DOMUtils::isMarkerMeta( $node, 'mw:Placeholder/StrippedTag' ) ) {
			$name = DOMDataUtils::getDataParsoid( $node )->name ?? null;
			return $name === DOMCompat::nodeName( $match ) ? $node : null;
		}

		if ( DOMCompat::nodeName( $node ) === DOMCompat::nodeName( $match ) ) {
			$dp = DOMDataUtils::getDataParsoid( $node );
			if ( ( DOMDataUtils::getDataParsoid( $match )->stx ?? null ) === ( $dp->stx ?? null ) &&
				!empty( $dp->autoInsertedStart )
			) {
				if ( !empty( $dp->autoInsertedEnd ) ) {
					return $this->getMatchingMisnestedNode( $node, $match );
				} else {
					return $node;
				}
			}
		}

		return $this->leftMostMisnestedDescendent( $node->firstChild, $match );
	}

	/**
	 * $node has an 'autoInsertedEnd' flag set on it. We are looking for
	 * its matching node that has an 'autoInsertedStart' flag set on it.
	 * This happens when the tree-builder fixes up misnested tags.
	 * This "adjacency" is wrt the HTML string. In a DOM, this can either
	 * be the next sibling OR, it might be the left-most-descendent of
	 * of $node's parent's sibling (and so on up the ancestor chain).
	 */
	private function getMatchingMisnestedNode( Node $node, Element $match ): ?Element {
		if ( DOMUtils::atTheTop( $node ) ) {
			return null;
		}

		if ( DiffDOMUtils::nextNonSepSibling( $node ) ) {
			return $this->leftMostMisnestedDescendent( DiffDOMUtils::nextNonSepSibling( $node ), $match );
		}

		return $this->getMatchingMisnestedNode( $node->parentNode, $match );
	}

	/**
	 * Given a tplInfo object, determine whether we are:
	 * - Not processing template content (could be extension or top level page)
	 * - Processing encapsulated content that is produced by a single template.
	 *   If so, return the name of that template.
	 * - Processing encapsulated content that comes from multiple templates.
	 *   If so, return a flag indicating this.
	 *
	 * FIXME: We might potentially be computing this information redundantly
	 * for every lint we find within this template's content. It could probably
	 * be cached in tplInfo after it is computed once.
	 */
	private function findEnclosingTemplateName( Env $env, ?stdClass $tplInfo ): ?array {
		if ( !$tplInfo ) {
			return null;
		}

		if ( !DOMUtils::hasTypeOf( $tplInfo->first, 'mw:Transclusion' ) ) {
			return null;
		}
		$dmw = DOMDataUtils::getDataMw( $tplInfo->first );
		// This count check is conservative in that link suffixes and prefixes
		// could artifically add an extra element to the parts array but we
		// don't have a good way of distinguishing that right now. It will require
		// a non-string representation for them and a change in spec along with
		// a version bump and all that song and dance. If linting accuracy in these
		// scenarios become a problem, we can revisit this.
		if ( !empty( $dmw->parts ) && count( $dmw->parts ) === 1 ) {
			$p0 = $dmw->parts[0];
			// If just a single part (guaranteed with count above), it will be stdclass
			'@phan-var \stdClass $p0';
			$name = null;
			if ( !empty( $p0->template->target->href ) ) { // Could be "function"
				// PORT-FIXME: Should that be SiteConfig::relativeLinkPrefix() rather than './'?
				$name = PHPUtils::stripPrefix( $p0->template->target->href, './' );
			} elseif ( !empty( $p0->template ) ) {
				$name = trim( $p0->template->target->wt );
			} else {
				$name = trim( $p0->templatearg->target->wt );
			}
			return [ 'name' => $name ];
		} else {
			return [ 'multiPartTemplateBlock' => true ];
		}
	}

	/**
	 * Compute the DSR information for the lint object.
	 * - In the common case, this is simply the DSR value of the node
	 *   that generated the lint. But, occasionally, for some lints,
	 *   we might have to post-process the node's DSR.
	 * - If the lint is found in template content, then the DSR spans
	 *   the transclusion markup in the toplevel page source.
	 */
	private function findLintDSR(
		?array $tplLintInfo, ?stdClass $tplInfo, ?DomSourceRange $nodeDSR,
		?callable $updateNodeDSR = null
	): ?DomSourceRange {
		if ( $tplLintInfo !== null || ( $tplInfo && !Utils::isValidDSR( $nodeDSR ) ) ) {
			return DOMDataUtils::getDataParsoid( $tplInfo->first )->dsr ?? null;
		} else {
			return $updateNodeDSR ? $updateNodeDSR( $nodeDSR ) : $nodeDSR;
		}
	}

	/**
	 * Determine if a node has an identical nested tag (?)
	 */
	private function hasIdenticalNestedTag( Element $node, string $name ): bool {
		$c = $node->firstChild;
		while ( $c ) {
			if ( $c instanceof Element ) {
				if (
					DOMCompat::nodeName( $c ) === $name &&
					empty( DOMDataUtils::getDataParsoid( $c )->autoInsertedEnd )
				) {
					return true;
				}

				return $this->hasIdenticalNestedTag( $c, $name );
			}

			$c = $c->nextSibling;
		}

		return false;
	}

	/**
	 * Determine if a node has misnestable content
	 */
	private function hasMisnestableContent( Node $node, string $name ): bool {
		// For A, TD, TH, H* tags, Tidy doesn't seem to propagate
		// the unclosed tag outside these tags.
		// No need to check for tr/table since content cannot show up there
		if ( DOMUtils::atTheTop( $node ) || preg_match( '/^(?:a|td|th|h\d)$/D', DOMCompat::nodeName( $node ) ) ) {
			return false;
		}

		$next = DiffDOMUtils::nextNonSepSibling( $node );
		if ( !$next ) {
			return $this->hasMisnestableContent( $node->parentNode, $name );
		}

		$contentNode = null;
		if ( DOMCompat::nodeName( $next ) === 'p' && !WTUtils::isLiteralHTMLNode( $next ) ) {
			$contentNode = DiffDOMUtils::firstNonSepChild( $next );
		} else {
			$contentNode = $next;
		}

		// If the first "content" node we find is a matching
		// stripped tag, we have nothing that can get misnested
		return $contentNode && !(
			$contentNode instanceof Element &&
			DOMUtils::isMarkerMeta( $contentNode, 'mw:Placeholder/StrippedTag' ) &&
			isset( DOMDataUtils::getDataParsoid( $contentNode )->name ) &&
			DOMDataUtils::getDataParsoid( $contentNode )->name === $name
		);
	}

	/**
	 * Indicate whether an end tag is optional for this node
	 *
	 * See https://www.w3.org/TR/html5/syntax.html#optional-tags
	 *
	 * End tags for tr/td/th/li are entirely optional since they
	 * require a parent container and can only be followed by like
	 * kind.
	 *
	 * Caveat: <li>foo</li><ol>..</ol> and <li>foo<ol>..</ol>
	 * generate different DOM trees, so explicit </li> tag
	 * is required to specify which of the two was intended.
	 *
	 * With that one caveat around nesting, the parse with/without
	 * the end tag is identical. For now, ignoring that caveat
	 * since they aren't like to show up in our corpus much.
	 *
	 * For the other tags in that w3c spec section, I haven't reasoned
	 * through when exactly they are optional. Not handling that complexity
	 * for now since those are likely uncommon use cases in our corpus.
	 */
	private function endTagOptional( Node $node ): bool {
		static $tagNames = [ 'tr', 'td', 'th', 'li' ];
		return in_array( DOMCompat::nodeName( $node ), $tagNames, true );
	}

	/**
	 * Find the nearest ancestor heading tag
	 */
	private function getHeadingAncestor( Node $node ): ?Node {
		while ( $node && !DOMUtils::isHeading( $node ) ) {
			$node = $node->parentNode;
		}
		return $node;
	}

	/**
	 * For formatting tags, Tidy seems to be doing this "smart" fixup of
	 * unclosed tags by looking for matching unclosed pairs of identical tags
	 * and if the content ends in non-whitespace text, it treats the second
	 * unclosed opening tag as a closing tag. But, a HTML5 parser won't do this.
	 * So, detect this pattern and flag for linter fixup.
	 */
	private function matchedOpenTagPairExists( Node $c, DataParsoid $dp ): bool {
		$lc = $c->lastChild;
		if ( !$lc instanceof Element || DOMCompat::nodeName( $lc ) !== DOMCompat::nodeName( $c ) ) {
			return false;
		}

		$lcDP = DOMDataUtils::getDataParsoid( $lc );
		if ( empty( $lcDP->autoInsertedEnd ) || ( $lcDP->stx ?? null ) !== ( $dp->stx ?? null ) ) {
			return false;
		}

		$prev = $lc->previousSibling;
		// PORT-FIXME: Do we care about non-ASCII whitespace here?
		if ( $prev instanceof Text && !preg_match( '/\s$/D', $prev->nodeValue ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Lint Treebuilder fixups marked by dom.markTreeBuilderFixup.js
	 *
	 * It handles the following scenarios:
	 *
	 * 1. Unclosed end tags (`missing-end-tag`, `missing-end-tag-in-heading`)
	 * 2. Invalid self-closed tags (`self-closed-tag`)
	 * 3. Stripped tags (`stripped-tag`)
	 *
	 * In addition, we have specialized categories for some patterns
	 * where we encounter unclosed end tags.
	 *
	 * 4. misnested-tag
	 * 5. html5-misnesting
	 * 6. multiple-unclosed-formatting-tags
	 * 7. unclosed-quotes-in-heading
	 */
	private function lintTreeBuilderFixup(
		Env $env, Element $c, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		// This might have been processed as part of
		// misnested-tag category identification.
		if ( $dp->getTempFlag( TempData::LINTED ) ) {
			return;
		}

		$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
		// During DSR computation, stripped meta tags
		// surrender their width to its previous sibling.
		// We record the original DSR in the tmp attribute
		// for that reason.
		$dsr = $this->findLintDSR( $tplLintInfo, $tplInfo, $dp->tmp->origDSR ?? $dp->dsr ?? null );
		$lintObj = null;
		if ( DOMUtils::isMarkerMeta( $c, 'mw:Placeholder/StrippedTag' ) ) {
			$lintObj = [
				'dsr' => $dsr,
				'templateInfo' => $tplLintInfo,
				'params' => [ 'name' => $dp->name ?? null ],
			];
			$env->recordLint( 'stripped-tag', $lintObj );
		}

		// Dont bother linting for auto-inserted start/end or self-closing-tag if:
		// 1. c is a void element
		//    Void elements won't have auto-inserted start/end tags
		//    and self-closing versions are valid for them.
		//
		// 2. c is tbody (FIXME: don't remember why we have this exception)
		//
		// 3. c is not an HTML element (unless they are i/b quotes or tables)
		//
		// 4. c doesn't have DSR info and doesn't come from a template either
		$cNodeName = DOMCompat::nodeName( $c );
		$ancestor = null;
		$isHtmlElement = WTUtils::hasLiteralHTMLMarker( $dp );
		if ( !Utils::isVoidElement( $cNodeName ) &&
			$cNodeName !== 'tbody' &&
			( $isHtmlElement || DOMUtils::isQuoteElt( $c ) || $cNodeName === 'table' ) &&
			( $tplInfo !== null || $dsr !== null )
		) {
			if ( !empty( $dp->selfClose ) && $cNodeName !== 'meta' ) {
				$lintObj = [
					'dsr' => $dsr,
					'templateInfo' => $tplLintInfo,
					'params' => [ 'name' => $cNodeName ],
				];
				$env->recordLint( 'self-closed-tag', $lintObj );
				// The other checks won't pass - no need to test them.
				return;
			}

			if (
				( $dp->autoInsertedEnd ?? null ) === true &&
				( $tplInfo || ( $dsr->openWidth ?? 0 ) > 0 )
			) {
				$lintObj = [
					'dsr' => $dsr,
					'templateInfo' => $tplLintInfo,
					'params' => [ 'name' => $cNodeName ],
				];

				// FIXME: This literal html marker check is strictly not required
				// (a) we've already checked that above and know that isQuoteElt is
				//     not one of our tags.
				// (b) none of the tags in the list have native wikitext syntax =>
				//     they will show up as literal html tags.
				// But, in the interest of long-term maintenance in the face of
				// changes (to wikitext or html specs), let us make it explicit.
				if ( $isHtmlElement &&
					isset( $this->getTagsWithChangedMisnestingBehavior()[DOMCompat::nodeName( $c )] ) &&
					$this->hasMisnestableContent( $c, DOMCompat::nodeName( $c ) ) &&
					// Tidy WTF moment here!
					// I don't know why Tidy does something very different
					// when there is an identical nested tag here.
					//
					// <p><span id='1'>a<span>X</span></p><p>b</span></p>
					// vs.
					// <p><span id='1'>a</p><p>b</span></p>  OR
					// <p><span id='1'>a<del>X</del></p><p>b</span></p>
					//
					// For the first snippet, Tidy only wraps "a" with the id='1' span
					// For the second and third snippets, Tidy wraps "b" with the id='1' span as well.
					//
					// For the corresponding wikitext that generates the above token stream,
					// Parsoid (and Remex) won't wrap 'b' with the id=1' span at all.
					!$this->hasIdenticalNestedTag( $c, DOMCompat::nodeName( $c ) )
				) {
					$env->recordLint( 'html5-misnesting', $lintObj );
				} elseif (
					!$isHtmlElement && DOMUtils::isQuoteElt( $c ) &&
					// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found
					( $ancestor = $this->getHeadingAncestor( $c->parentNode ) )
				) {
					$lintObj['params']['ancestorName'] = DOMCompat::nodeName( $ancestor );
					$env->recordLint( 'unclosed-quotes-in-heading', $lintObj );
				} else {
					$adjNode = $this->getMatchingMisnestedNode( $c, $c );
					if ( $adjNode ) {
						$adjDp = DOMDataUtils::getDataParsoid( $adjNode );
						$adjDp->setTempFlag( TempData::LINTED );
						$env->recordLint( 'misnested-tag', $lintObj );
					} elseif ( !$this->endTagOptional( $c ) && empty( $dp->autoInsertedStart ) ) {
						$lintObj['params']['inTable'] = DOMUtils::hasNameOrHasAncestorOfName( $c, 'table' );
						$category = $this->getHeadingAncestor( $c ) ?
							'missing-end-tag-in-heading' : 'missing-end-tag';
						$next = DiffDOMUtils::nextNonSepSibling( $c );
						if (
							// Skip if covered by deletable-table-tag
							!( $cNodeName === 'table' && $next &&
							( DOMCompat::nodeName( $c ) === 'table' ) )
						) {
							$env->recordLint( $category, $lintObj );
						}
						if ( isset( Consts::$HTML['FormattingTags'][DOMCompat::nodeName( $c )] ) &&
							$this->matchedOpenTagPairExists( $c, $dp )
						) {
							$env->recordLint( 'multiple-unclosed-formatting-tags', $lintObj );
						}
					}
				}
			}
		}
	}

	/**
	 * Lint fostered content marked by MarkFosteredContent.
	 *
	 * Lint category: `fostered`
	 *
	 * This will log cases like:
	 *
	 * {|
	 * foo
	 * |-
	 * | bar
	 * |}
	 *
	 * Here 'foo' gets fostered out.
	 */
	private function lintFostered(
		Env $env, Element $node, DataParsoid $dp, ?stdClass $tplInfo
	): ?Element {
		$maybeTable = $node->nextSibling;
		$clear = false;

		while ( $maybeTable && DOMCompat::nodeName( $maybeTable ) !== 'table' ) {
			if ( $tplInfo && $maybeTable === $tplInfo->last ) {
				$clear = true;
			}
			$maybeTable = $maybeTable->nextSibling;
		}

		if ( !$maybeTable instanceof Element ) {
			return null;
		} elseif ( $clear && $tplInfo ) {
			$tplInfo->clear = true;
		}

		// In pathological cases, we might walk past fostered nodes
		// that carry templating information. This then triggers
		// other errors downstream. So, walk back to that first node
		// and ignore this fostered content error. The new node will
		// trigger fostered content lint error.
		if ( !$tplInfo && WTUtils::isEncapsulatedDOMForestRoot( $maybeTable ) &&
			!WTUtils::isFirstEncapsulationWrapperNode( $maybeTable )
		) {
			$tplNode = WTUtils::findFirstEncapsulationWrapperNode( $maybeTable );
			if ( $tplNode !== null ) {
				return $tplNode;
			}

			// We got misled by the about id on 'maybeTable'.
			// Let us carry on with regularly scheduled programming.
		}

		$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
		$lintObj = [
			'dsr' => $this->findLintDSR(
				$tplLintInfo, $tplInfo, DOMDataUtils::getDataParsoid( $maybeTable )->dsr ?? null
			),
			'templateInfo' => $tplLintInfo,
		];
		$env->recordLint( 'fostered', $lintObj );

		return $maybeTable;
	}

	/**
	 * Lint obsolete HTML tags.
	 *
	 * Lint category: `obsolete-tag`, `tidy-font-bug`
	 */
	private function lintObsoleteTag(
		Env $env, Element $c, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		if ( !$this->obsoleteTagsRE ) {
			$elts = [];
			foreach ( Consts::$HTML['OlderHTMLTags'] as $tag => $dummy ) {
				// Looks like all existing editors let editors add the <big> tag.
				// VE has a button to add <big>, it seems so does the WikiEditor
				// and JS wikitext editor. So, don't flag BIG as an obsolete tag.
				if ( $tag !== 'big' ) {
					$elts[] = preg_quote( $tag, '/' );
				}
			}
			$this->obsoleteTagsRE = '/^(?:' . implode( '|', $elts ) . ')$/D';
		}

		$tplLintInfo = null;
		if ( ( empty( $dp->autoInsertedStart ) || empty( $dp->autoInsertedEnd ) ) &&
			preg_match( $this->obsoleteTagsRE, DOMCompat::nodeName( $c ) )
		) {
			$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
			$lintObj = [
				'dsr' => $this->findLintDSR( $tplLintInfo, $tplInfo, $dp->dsr ?? null ),
				'templateInfo' => $tplLintInfo,
				'params' => [ 'name' => DOMCompat::nodeName( $c ) ],
			];
			$env->recordLint( 'obsolete-tag', $lintObj );
		}

		if ( DOMCompat::nodeName( $c ) === 'font' && $c->hasAttribute( 'color' ) ) {
			/* ----------------------------------------------------------
			 * Tidy migrates <font> into the link in these cases
			 *     <font>[[Foo]]</font>
			 *     <font>[[Foo]]l</font> (link-trail)
			 *     <font><!--boo-->[[Foo]]</font>
			 *     <font>__NOTOC__[[Foo]]</font>
			 *     <font>[[Category:Foo]][[Foo]]</font>
			 *     <font>{{1x|[[Foo]]}}</font>
			 *
			 * Tidy does not migrate <font> into the link in these cases
			 *     <font> [[Foo]]</font>
			 *     <font>[[Foo]] </font>
			 *     <font>[[Foo]]L</font> (not a link-trail)
			 *     <font>[[Foo]][[Bar]]</font>
			 *     <font>[[Foo]][[Bar]]</font>
			 *
			 * <font> is special.
			 * This behavior is not seen with other formatting tags.
			 *
			 * Remex/parsoid won't do any of this.
			 * This difference in behavior only matters when the font tag
			 * specifies a link colour because the link no longer renders
			 * as blue/red but in the font-specified colour.
			 * ---------------------------------------------------------- */
			$tidyFontBug = $c->firstChild !== null;
			$haveLink = false;
			for ( $n = $c->firstChild;  $n;  $n = $n->nextSibling ) {
				$nodeName = DOMCompat::nodeName( $n );
				if ( $nodeName !== 'a' &&
					!WTUtils::isRenderingTransparentNode( $n ) &&
					!WTUtils::isTplMarkerMeta( $n )
				) {
					$tidyFontBug = false;
					break;
				}

				if ( $nodeName === 'a' || $nodeName === 'figure' ) {
					if ( !$haveLink ) {
						$haveLink = true;
					} else {
						$tidyFontBug = false;
						break;
					}
				}
			}

			if ( $tidyFontBug ) {
				$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
				$env->recordLint( 'tidy-font-bug', [
					'dsr' => $this->findLintDSR( $tplLintInfo, $tplInfo, $dp->dsr ?? null ),
					'templateInfo' => $tplLintInfo,
					'params' => [ 'name' => 'font' ]
				] );
			}
		}
	}

	/**
	 * Log bogus (=unrecognized) media options.
	 *
	 * See - https://www.mediawiki.org/wiki/Help:Images#Syntax
	 *
	 * Lint category: `bogus-image-options`
	 */
	private function lintBogusImageOptions(
		Env $env, Node $c, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		// Despite the lint category name, this checks all media, not just images
		if ( WTUtils::isGeneratedFigure( $c ) && !empty( $dp->optList ) ) {
			$items = [];
			$bogusPx = $dp->getTempFlag( TempData::BOGUS_PX );
			foreach ( $dp->optList as $item ) {
				if (
					$item['ck'] === 'bogus' ||
					( $bogusPx && $item['ck'] === 'width' )
				) {
					$items[] = $item['ak'];
				}
			}
			if ( $items ) {
				$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
				$env->recordLint( 'bogus-image-options', [
					'dsr' => $this->findLintDSR( $tplLintInfo, $tplInfo, $dp->dsr ?? null ),
					'templateInfo' => $tplLintInfo,
					'params' => [ 'items' => $items ]
				] );
			}
		}
	}

	/**
	 * Lint tables Tidy deletes.
	 *
	 * Lint category: `deletable-table-tag`
	 *
	 * In this example below, the second table is in a fosterable position
	 * (inside a <tr>). The tree builder closes the first table at that point
	 * and starts a new table there. We are detecting this pattern because
	 * Tidy does something very different here. It strips the inner table
	 * and retains the outer table. So, for preserving rendering of pages
	 * that are tailored for Tidy, editors have to fix up this wikitext
	 * to strip the inner table (to mimic what Tidy does).
	 *
	 *   {| style='border:1px solid red;'
	 *   |a
	 *   |-
	 *   {| style='border:1px solid blue;'
	 *   |b
	 *   |c
	 *   |}
	 *   |}
	 */
	private function lintDeletableTableTag(
		Env $env, Node $c, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		if ( DOMCompat::nodeName( $c ) === 'table' ) {
			$prev = DiffDOMUtils::previousNonSepSibling( $c );
			if ( $prev instanceof Element && DOMCompat::nodeName( $prev ) === 'table' &&
				!empty( DOMDataUtils::getDataParsoid( $prev )->autoInsertedEnd )
			) {
				$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
				$dsr = $this->findLintDSR(
					$tplLintInfo,
					$tplInfo,
					$dp->dsr ?? null,
					static function ( ?DomSourceRange $nodeDSR ): ?DomSourceRange {
						// Identify the dsr-span of the opening tag
						// of the table that needs to be deleted
						$x = $nodeDSR === null ? null : ( clone $nodeDSR );
						if ( !empty( $x->openWidth ) ) {
							$x->end = $x->innerStart();
							$x->openWidth = 0;
							$x->closeWidth = 0;
						}
						return $x;
					}
				);
				$lintObj = [
					'dsr' => $dsr,
					'templateInfo' => $tplLintInfo,
					'params' => [ 'name' => 'table' ],
				];
				$env->recordLint( 'deletable-table-tag', $lintObj );
			}
		}
	}

	/**
	 * Find the first child passing the filter.
	 */
	private function findMatchingChild( Node $node, callable $filter ): ?Node {
		$c = $node->firstChild;
		while ( $c && !$filter( $c ) ) {
			$c = $c->nextSibling;
		}

		return $c;
	}

	/**
	 * Test if the node has a 'nowrap' CSS rule
	 *
	 * In the general case, this CSS can come from a class,
	 * or from a <style> tag or a stylesheet or even from JS code.
	 * But, for now, we are restricting this inspection to inline CSS
	 * since the intent is to aid editors in fixing patterns that
	 * can be automatically detected.
	 *
	 * Special case for enwiki that has Template:nowrap which
	 * assigns class='nowrap' with CSS white-space:nowrap in
	 * MediaWiki:Common.css
	 */
	private function hasNoWrapCSS( Node $node ): bool {
		return $node instanceof Element && (
			str_contains( DOMCompat::getAttribute( $node, 'style' ) ?? '', 'nowrap' ) ||
			DOMUtils::hasClass( $node, 'nowrap' )
		);
	}

	/**
	 * Lint bad P wrapping.
	 *
	 * Lint category: `pwrap-bug-workaround`
	 */
	private function lintPWrapBugWorkaround(
		Env $env, Element $node, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		if (
			!DOMUtils::isWikitextBlockNode( $node ) &&
			DOMUtils::isWikitextBlockNode( $node->parentNode ) &&
			$this->hasNoWrapCSS( $node )
		) {
			$p = $this->findMatchingChild( $node, static function ( $e ) {
				return DOMCompat::nodeName( $e ) === 'p';
			} );
			if ( $p ) {
				$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
				$lintObj = [
					'dsr' => $this->findLintDSR( $tplLintInfo, $tplInfo, $dp->dsr ?? null ),
					'templateInfo' => $tplLintInfo,
					'params' => [
						'root' => DOMCompat::nodeName( $node->parentNode ),
						'child' => DOMCompat::nodeName( $node ),
					]
				];
				$env->recordLint( 'pwrap-bug-workaround', $lintObj );
			}
		}
	}

	/**
	 * Lint Tidy div span flip.
	 *
	 * Lint category: `misc-tidy-replacement-issues`
	 */
	private function lintMiscTidyReplacementIssues(
		Env $env, Element $node, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		if ( DOMCompat::nodeName( $node ) !== 'span' ) {
			return;
		}

		$fc = DiffDOMUtils::firstNonSepChild( $node );
		if ( !$fc instanceof Element || DOMCompat::nodeName( $fc ) !== 'div' ) {
			return;
		}

		// No style/class attributes -- so, this won't affect rendering
		if ( !$node->hasAttribute( 'class' ) && !$node->hasAttribute( 'style' ) &&
			!$fc->hasAttribute( 'class' ) && !$fc->hasAttribute( 'style' )
		) {
			return;
		}

		$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
		$lintObj = [
			'dsr' => $this->findLintDSR( $tplLintInfo, $tplInfo, $dp->dsr ?? null ),
			'templateInfo' => $tplLintInfo,
			'params' => [ 'subtype' => 'div-span-flip' ]
		];
		$env->recordLint( 'misc-tidy-replacement-issues', $lintObj );
	}

	/**
	 * Lint tidy whitespace bug.
	 *
	 * Lint category: `tidy-whitespace-bug`
	 */
	private function lintTidyWhitespaceBug(
		Env $env, Node $node, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		// We handle a run of nodes in one shot.
		// No need to reprocess repeatedly.
		if ( $dp->getTempFlag( TempData::PROCESSED_TIDY_WS_BUG ) ) {
			return;
		}

		// Find the longest run of nodes that are affected by white-space:nowrap CSS
		// in a way that leads to unsightly rendering in HTML5 compliant browsers.
		//
		// Check if Tidy does buggy whitespace hoisting there to provide the browser
		// opportunities to split the content in short segments.
		//
		// If so, editors would need to edit this run of nodes to introduce
		// whitespace breaks as necessary so that HTML5 browsers get that
		// same opportunity when Tidy is removed.
		$s = null;
		$nowrapNodes = [];
		'@phan-var array<array{node:Node,tidybug:bool,hasLeadingWS:bool}> $nowrapNodes';
		$startNode = $node;
		$haveTidyBug = false;
		$runLength = 0;

		// <br>, <wbr>, <hr> break a line
		while ( $node && !DOMUtils::isRemexBlockNode( $node ) &&
			!in_array( DOMCompat::nodeName( $node ), [ 'hr', 'br', 'wbr' ], true )
		) {
			if ( $node instanceof Text || !$this->hasNoWrapCSS( $node ) ) {
				// No CSS property that affects whitespace.
				$s = $node->textContent;
				if ( preg_match( '/^(\S*)\s/', $s, $m ) ) { // PORT-FIXME: non-ASCII whitespace?
					$runLength += strlen( $m[1] );
					$nowrapNodes[] = [
						'node' => $node,
						'tidybug' => false,
						'hasLeadingWS' => ( preg_match( '/^\s/', $s ) === 1 ), // PORT-FIXME: non-ASCII whitespace?
					];
					break;
				} else {
					$nowrapNodes[] = [ 'node' => $node, 'tidybug' => false ];
					$runLength += strlen( $s );
				}
			} else {
				// Find last non-comment child of node
				$last = $node->lastChild;
				while ( $last instanceof Comment ) {
					$last = $last->previousSibling;
				}

				$bug = false;
				if ( $last instanceof Text &&
					preg_match( '/\s$/D', $last->nodeValue ) // PORT-FIXME: non-ASCII whitespace?
				) {
					// In this scenario, when Tidy hoists the whitespace to
					// after the node, that whitespace is not subject to the
					// nowrap CSS => browsers can break content there.
					//
					// But, non-Tidy libraries won't hoist the whitespace.
					// So, browsers don't have a place to break content.
					$bug = true;
					$haveTidyBug = true;
				}

				$nowrapNodes[] = [ 'node' => $node, 'tidybug' => $bug ];
				$runLength += strlen( $node->textContent );
			}

			// Don't cross template boundaries at the top-level
			if ( $tplInfo && $tplInfo->last === $node ) {
				// Exiting a top-level template
				break;
			} elseif ( !$tplInfo && WTUtils::findFirstEncapsulationWrapperNode( $node ) ) {
				// Entering a top-level template
				break;
			}

			// Move to the next non-comment sibling
			$node = $node->nextSibling;
			while ( $node instanceof Comment ) {
				$node = $node->nextSibling;
			}
		}

		$markProcessedNodes = static function () use ( &$nowrapNodes ) { // Helper
			foreach ( $nowrapNodes as $o ) {
				// Phan fails at applying the instanceof type restriction to the array member when analyzing the
				// following call, but is fine when it's copied to a local variable.
				$node = $o['node'];
				if ( $node instanceof Element ) {
					DOMDataUtils::getDataParsoid( $node )->setTempFlag( TempData::PROCESSED_TIDY_WS_BUG );
				}
			}
		};

		if ( !$haveTidyBug ) {
			// Mark processed nodes and bail
			$markProcessedNodes();
			return;
		}

		// Find run before startNode that doesn't have a whitespace break
		$prev = $startNode->previousSibling;
		while ( $prev && !DOMUtils::isRemexBlockNode( $prev ) ) {
			if ( !( $prev instanceof Comment ) ) {
				$s = $prev->textContent;
				// Find the last \s in the string
				if ( preg_match( '/\s(\S*)$/D', $s, $m ) ) { // PORT-FIXME: non-ASCII whitespace here?
					$runLength += strlen( $m[1] );
					break;
				} else {
					$runLength += strlen( $s );
				}
			}
			$prev = $prev->previousSibling;
		}

		$lintConfig = $env->getSiteConfig()->getLinterConfig();
		$tidyWhitespaceBugMaxLength = $lintConfig['tidyWhitespaceBugMaxLength'] ?? 100;

		if ( $runLength < $tidyWhitespaceBugMaxLength ) {
			// Mark processed nodes and bail
			$markProcessedNodes();
			return;
		}

		// For every node where Tidy hoists whitespace,
		// emit an event to flag a whitespace fixup opportunity.
		$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
		$n = count( $nowrapNodes ) - 1;
		foreach ( $nowrapNodes as $i => $o ) {
			if ( $o['tidybug'] && $i < $n && empty( $nowrapNodes[$i + 1]['hasLeadingWS'] ) ) {
				$nowrapNode = $o['node']; // (see above)
				$lintObj = [
					'dsr' => $this->findLintDSR(
						$tplLintInfo,
						$tplInfo,
						$nowrapNode instanceof Element
							? DOMDataUtils::getDataParsoid( $nowrapNode )->dsr ?? null
							: null
					),
					'templateInfo' => $tplLintInfo,
					'params' => [
						'node' => DOMCompat::nodeName( $o['node'] ),
						'sibling' => DOMCompat::nodeName( $o['node']->nextSibling )
					]
				];

				$env->recordLint( 'tidy-whitespace-bug', $lintObj );
			}
		}

		$markProcessedNodes();
	}

	/**
	 * Detect multiple-unclosed-formatting-tags errors.
	 *
	 * Since unclosed <small> and <big> tags accumulate their effects
	 * in HTML5 parsers (unlike in Tidy where it seems to suppress
	 * multiple unclosed elements of the same name), such pages will
	 * break pretty spectacularly with Remex.
	 *
	 * Ex: https://it.wikipedia.org/wiki/Hubert_H._Humphrey_Metrodome?oldid=93017491#Note
	 *
	 * Lint category: `multiple-unclosed-formatting-tags`
	 */
	private function lintMultipleUnclosedFormattingTags( array $lints, Env $env ): void {
		$firstUnclosedTag = [
			'small' => null,
			'big' => null
		];
		$multiUnclosedTagName = null;
		foreach ( $lints as $item ) {
			// Unclosed tags in tables don't leak out of the table
			if ( $item['type'] === 'missing-end-tag' && !$item['params']['inTable'] ) {
				if ( $item['params']['name'] === 'small' || $item['params']['name'] === 'big' ) {
					$tagName = $item['params']['name'];
					// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
					if ( !$firstUnclosedTag[$tagName] ) {
						$firstUnclosedTag[$tagName] = $item;
					} else {
						$multiUnclosedTagName = $tagName;
						break;
					}
				}
			}
		}

		if ( $multiUnclosedTagName ) {
			$item = $firstUnclosedTag[$multiUnclosedTagName];
			if ( isset( $item['dsr'] ) ) {
				$item['dsr'] = DomSourceRange::fromArray( $item['dsr'] );
			}
			$env->recordLint( 'multiple-unclosed-formatting-tags', [
				'params' => $item['params'] ?? [],
				'dsr' => $item['dsr'] ?? null,
				'templateInfo' => $item['tplLintInfo'] ?? null
			] );
		}
	}

	/**
	 * Post-process an array of lints
	 */
	private function postProcessLints( array $lints, Env $env ): void {
		$this->lintMultipleUnclosedFormattingTags( $lints, $env );
	}

	/**
	 * Get wikitext list item ancestor
	 */
	private function getWikitextListItemAncestor( ?Node $node ): ?Node {
		while ( $node && !DOMUtils::isListItem( $node ) ) {
			$node = $node->parentNode;
		}

		if ( $node && !WTUtils::isLiteralHTMLNode( $node ) &&
			!WTUtils::fromExtensionContent( $node, 'references' )
		) {
			return $node;
		}

		return null;
	}

	/**
	 * Lint a PHP parser bug.
	 *
	 * Lint category: `multiline-html-table-in-list`
	 */
	private function lintMultilineHtmlTableInList(
		Env $env, Element $node, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		$li = null;
		if ( !WTUtils::isLiteralHTMLNode( $node ) ||
			DOMCompat::nodeName( $node ) !== 'table' ||
			// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found
			!( $li = $this->getWikitextListItemAncestor( $node ) ) ||
			!str_contains( DOMCompat::getOuterHTML( $node ), "\n" )
		) {
			return;
		}

		// We have an HTML table nested inside a list
		// that has a newline break in its outer HTML
		// => we are in trouble with the PHP Parser + Remex combo
		$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
		$lintObj = [
			'dsr' => $this->findLintDSR(
				$tplLintInfo, $tplInfo, DOMDataUtils::getDataParsoid( $node )->dsr ?? null
			),
			'templateInfo' => $tplLintInfo,
			'params' => [
				'name' => 'table',
				'ancestorName' => DOMCompat::nodeName( $li ),
			],
		];
		$env->recordLint( 'multiline-html-table-in-list', $lintObj );
	}

	/**
	 * Log wikilinks or media in external links.
	 *
	 * HTML tags can be nested but this is not the case for <a> tags
	 * which when nested outputs the <a> tags adjacent to each other
	 * In the example below, [[Google]] is a wikilink that is nested
	 * in the outer external link
	 * [http://google.com This is [[Google]]'s search page]
	 *
	 * Linter category: `wikilink-in-extlink`
	 */
	private function lintWikilinksInExtlink(
		Env $env, Element $c, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		if ( DOMCompat::nodeName( $c ) === 'a' &&
			DOMUtils::hasRel( $c, "mw:ExtLink" ) &&
			// Images in extlinks will end up with broken up extlinks inside the
			// <figure> DOM. Those have 'misnested' flag set on them. Ignore those.
			empty( DOMDataUtils::getDataParsoid( $c )->misnested )
		) {
			$next = $c->nextSibling;
			$lintError = $next instanceof Element &&
				!empty( DOMDataUtils::getDataParsoid( $next )->misnested ) &&
				// This check may not be necessary but ensures that we are
				// really in a link-in-link misnested scenario.
				DOMUtils::treeHasElement( $next, 'a', true );

			// Media as opposed to most instances of img (barring the link= trick), don't result
			// in misnesting according the html5 spec since we're actively suppressing links in
			// their structure. However, since timed media is inherently clickable, being nested
			// in an extlink could surprise a user clicking on it by navigating away from the page.
			if ( !$lintError ) {
				DOMUtils::visitDOM( $c, static function ( $element ) use ( &$lintError ) {
					if ( $element instanceof Element &&
						( DOMCompat::nodeName( $element ) === 'audio' ||
							DOMCompat::nodeName( $element ) === 'video' )
					) {
						$lintError = true;
					}
				} );
			}
			if ( $lintError ) {
				$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
				$lintObj = [
					'dsr' => $this->findLintDSR(
						$tplLintInfo, $tplInfo, DOMDataUtils::getDataParsoid( $c )->dsr ?? null
					),
					'templateInfo' => $tplLintInfo,
				];
				$env->recordLint( 'wikilink-in-extlink', $lintObj );
			}
		}
	}

	private function recordLargeTablesLint(
		Env $env, ?stdClass $tplInfo, Element $node, int $numColumns, int $columnsMax
	): void {
		$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
		$lintObj = [
			'dsr' => $this->findLintDSR(
				$tplLintInfo, $tplInfo, DOMDataUtils::getDataParsoid( $node )->dsr ?? null
			),
			'templateInfo' => $tplLintInfo,
			'params' => [
				'name' => 'table',
				'columns' => $numColumns,
				'columnsMax' => $columnsMax,
			],
		];
		$env->recordLint( 'large-tables', $lintObj );
	}

	/**
	 * TODO: In the future, this may merit being moved to DOMUtils
	 * along with its "previous" variant.
	 */
	private function skipNonElementNodes( ?Node $n ): ?Element {
		while ( $n && !( $n instanceof Element ) ) {
			$n = $n->nextSibling;
		}
		return $n;
	}

	/**
	 * Lint large tables.
	 *
	 * Identify articles having overly-large tables
	 * to help editors optimize their articles.
	 *
	 * Linter category: `large-tables`
	 */
	private function lintLargeTables(
		Env $env, Element $node, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		if ( DOMCompat::nodeName( $node ) !== 'table' ) {
			return;
		}

		// Skip tables that have nested tables in them as they are likely
		// to be used for layout and not for data representation.
		// We may check nested tables in the next iteration of this lint.
		$nestedTables = $node->getElementsByTagName( 'table' );
		if ( $nestedTables->length > 0 ) {
			return;
		}

		$lintConfig = $env->getSiteConfig()->getLinterConfig();
		$maxColumns = $lintConfig['maxTableColumnHeuristic'] ?? 5;
		$maxRowsToCheck = $lintConfig['maxTableRowsToCheck'] ?? 10;

		$trCount = 0;
		$tbody = DOMCompat::querySelector( $node, 'tbody' );
		// empty table
		if ( !$tbody ) {
			return;
		}
		$tr = self::skipNonElementNodes( $tbody->firstChild );
		while ( $tr && $trCount < $maxRowsToCheck ) {
			$numTh = $tr->getElementsByTagName( 'th' )->length;
			if ( $numTh > $maxColumns ) {
				$this->recordLargeTablesLint( $env, $tplInfo, $node, $numTh, $maxColumns );
				return;
			}

			$numTd = $tr->getElementsByTagName( 'td' )->length;
			if ( $numTd > $maxColumns ) {
				$this->recordLargeTablesLint( $env, $tplInfo, $node, $numTd, $maxColumns );
				return;
			}

			$tr = self::skipNonElementNodes( $tr->nextSibling );
			$trCount++;
		}
	}

	/**
	 * Log inline background color style rules without a color style rule.
	 *
	 * This function identifies elements with inline style attributes
	 * that have background color set but don't have a color style rule.
	 * It records linter events for such elements to help editors make
	 * their articles comply with WCAG color contrast rules.
	 *
	 * Linter category: `night-mode-unaware-background-color`
	 */
	private function lintNightModeUnawareBackgroundColor(
		Env $env, Element $node, DataParsoid $dp, ?stdClass $tplInfo
	): void {
		// Get inline style attribute value
		$styleAttrValue = DOMCompat::getAttribute( $node, 'style' );

		// Check if background color is set but font color is not
		if (
			( $styleAttrValue !== null ) &&
			preg_match( '/(^|;)\s*background(-color)?\s*:/i', $styleAttrValue ) &&
			!preg_match( '/(^|;)\s*color\s*:/i', $styleAttrValue )
		) {
			$tplLintInfo = $this->findEnclosingTemplateName( $env, $tplInfo );
			$lintObj = [
				'dsr' => $this->findLintDSR(
					$tplLintInfo, $tplInfo, DOMDataUtils::getDataParsoid( $node )->dsr ?? null
				),
				'templateInfo' => $tplLintInfo,
			];
			$env->recordLint( 'night-mode-unaware-background-color', $lintObj );
		}
	}

	/**
	 * Log wikitext fixups
	 */
	private function logWikitextFixups(
		Element $node, Env $env, ?stdClass $tplInfo
	): ?Element {
		$dp = DOMDataUtils::getDataParsoid( $node );

		$this->lintTreeBuilderFixup( $env, $node, $dp, $tplInfo );
		$this->lintDeletableTableTag( $env, $node, $dp, $tplInfo ); // For T161341
		$this->lintPWrapBugWorkaround( $env, $node, $dp, $tplInfo ); // For T161306
		$this->lintObsoleteTag( $env, $node, $dp, $tplInfo );
		$this->lintBogusImageOptions( $env, $node, $dp, $tplInfo );
		$this->lintTidyWhitespaceBug( $env, $node, $dp, $tplInfo );
		$this->lintMiscTidyReplacementIssues( $env, $node, $dp, $tplInfo );

		// When an HTML table is nested inside a list and if any part of the table
		// is on a new line, the PHP parser misnests the list and the table.
		// Tidy fixes the misnesting one way (puts table inside/outside the list)
		// HTML5 parser fix it another way (list expands to rest of the page!)
		$this->lintMultilineHtmlTableInList( $env, $node, $dp, $tplInfo );
		$this->lintWikilinksInExtlink( $env, $node, $dp, $tplInfo );
		$this->lintLargeTables( $env, $node, $dp, $tplInfo );

		$this->lintNightModeUnawareBackgroundColor( $env, $node, $dp, $tplInfo );

		// Log fostered content, but skip rendering-transparent nodes
		if (
			!empty( $dp->fostered ) &&
			!WTUtils::isRenderingTransparentNode( $node ) &&
			// TODO: Section tags are rendering transparent but not sol transparent,
			// and that method only considers WTUtils::isSolTransparentLink, though
			// there is a FIXME to consider all link nodes.
			!( DOMCompat::nodeName( $node ) === 'link' &&
				DOMUtils::hasTypeOf( $node, 'mw:Extension/section' ) )
		) {
			return $this->lintFostered( $env, $node, $dp, $tplInfo );
		} else {
			return null;
		}
	}

	/**
	 * Walk the DOM and compute lints for the entire tree.
	 * - When we enter encapsulated content (templates or extensions),
	 *   compute "tplInfo" (misnamed given that it can be an extension)
	 *   so that lints from the templates' content can be mapped back
	 *   to the transclusion that generated them.
	 * - When we process extensions, if we have a lint handler for the
	 *   extension, let the extension's lint handler compute lints.
	 */
	private function findLints(
		Node $root, Env $env, ?stdClass $tplInfo = null
	): void {
		$node = $root->firstChild;
		while ( $node !== null ) {
			if ( !$node instanceof Element ) {
				$node = $node->nextSibling;
				continue;
			}

			// !tplInfo check is to protect against templated content in
			// extensions which might in turn be nested in templated content.
			if ( !$tplInfo && WTUtils::isFirstEncapsulationWrapperNode( $node ) ) {
				$aboutSibs = WTUtils::getAboutSiblings( $node, DOMCompat::getAttribute( $node, 'about' ) );
				$tplInfo = (object)[
					'first' => $node,
					'last' => end( $aboutSibs ),
					'dsr' => DOMDataUtils::getDataParsoid( $node )->dsr ?? null,
					// FIXME: This is not being used. Instead the code is recomputing
					// this info in findEnclosingTemplateName.
					'isTemplated' => DOMUtils::hasTypeOf( $node, 'mw:Transclusion' ),
					'clear' => false,
				];
			}

			$nextNode = false;
			// Let native extensions lint their content
			$nativeExt = WTUtils::getNativeExt( $env, $node );
			if ( $nativeExt ) {
				if ( !$this->extApi ) {
					$this->extApi = new ParsoidExtensionAPI( $env );
				}
				$nextNode = $nativeExt->lintHandler(
					$this->extApi,
					$node,
					function ( $extRootNode ) use ( $env, $tplInfo ) {
						$this->findLints(
							$extRootNode, $env,
							empty( $tplInfo->isTemplated ) ? null : $tplInfo
						);
					}
				);
			}
			if ( $nextNode === false ) {
				// Default node handler
				// Lint this node
				$nextNode = $this->logWikitextFixups( $node, $env, $tplInfo );
				if ( $tplInfo && $tplInfo->clear ) {
					$tplInfo = null;
				}

				// Lint subtree
				if ( !$nextNode ) {
					$this->findLints( $node, $env, $tplInfo );
				}
			}

			if ( $tplInfo && $tplInfo->last === $node ) {
				$tplInfo = null;
			}

			$node = $nextNode ?: $node->nextSibling;
		}
	}

	/**
	 * This is only invoked on the top-level document
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		// Track time spent linting so we can evaluate benefits
		// of migrating this code off the critical path to its own
		// post processor.
		$metrics = $env->getSiteConfig()->metrics();
		$timer = null;
		if ( $metrics ) {
			$timer = Timing::start( $metrics );
		}

		$this->findLints( $root, $env );
		$this->postProcessLints( $env->getLints(), $env );

		if ( $metrics ) {
			$timer->end( "linting" );
		}
	}

}
