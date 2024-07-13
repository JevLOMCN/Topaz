<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\DOMHandlers\DOMHandler;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;

class Separators {
	/*
	 * This regexp looks for leading whitespace on the last line of a separator string.
	 * So, only comments (single or multi-line) or other newlines can precede that
	 * whitespace-of-interest. But, also account for any whitespace preceding newlines
	 * since that needs to be skipped over (Ex: "   \n  ").
	 */
	private const INDENT_PRE_WS_IN_SEP_REGEXP =
		'/^((?: *\n|(?:' . Utils::COMMENT_REGEXP_FRAGMENT . '))*)( +)([^\n]*)$/D';

	/**
	 * @var SerializerState
	 */
	private $state;

	/**
	 * @var Env
	 */
	private $env;

	/**
	 * Clean up the constraints object to prevent excessively verbose output
	 * and clog up log files / test runs.
	 *
	 * @param array $constraints
	 * @return array
	 */
	private static function loggableConstraints( array $constraints ): array {
		$c = [
			'a' => $constraints['a'] ?? null,
			'b' => $constraints['b'] ?? null,
			'min' => $constraints['min'] ?? null,
			'max' => $constraints['max'] ?? null,
		];
		if ( !empty( $constraints['constraintInfo'] ) ) {
			$constraintInfo = $constraints['constraintInfo'];
			$c['constraintInfo'] = [
				'onSOL' => $constraintInfo['onSOL'] ?? false,
				'sepType' => $constraintInfo['sepType'] ?? null,
				'nodeA' => DOMCompat::nodeName( $constraintInfo['nodeA'] ),
				'nodeB' => DOMCompat::nodeName( $constraintInfo['nodeB'] ),
			];
		}
		return $c;
	}

	private static function precedingSeparatorTextLen( Node $n ): ?int {
		// Given the CSS white-space property and specifically,
		// "pre" and "pre-line" values for this property, it seems that any
		// sensible HTML editor would have to preserve IEW in HTML documents
		// to preserve rendering. One use-case where an editor might change
		// IEW drastically would be when the user explicitly requests it
		// (Ex: pretty-printing of raw source code).
		//
		// For now, we are going to exploit this. This information is
		// only used to extrapolate DSR values and extract a separator
		// string from source, and is only used locally. In addition,
		// the extracted text is verified for being a valid separator.
		//
		// So, at worst, this can create a local dirty diff around separators
		// and at best, it gets us a clean diff.

		$len = 0;
		$orig = $n;
		while ( $n ) {
			if ( DOMUtils::isIEW( $n ) ) {
				$len += strlen( $n->nodeValue );
			} elseif ( $n instanceof Comment ) {
				$len += WTUtils::decodedCommentLength( $n );
			} elseif ( $n !== $orig ) { // dont return if input node!
				return null;
			}

			$n = $n->previousSibling;
		}

		return $len;
	}

	/**
	 * Helper for updateSeparatorConstraints.
	 *
	 * Collects, checks and integrates separator newline requirements to a simple
	 * min, max structure.
	 *
	 * @param Node $nodeA
	 * @param array $aCons
	 * @param Node $nodeB
	 * @param array $bCons
	 * @return array
	 */
	private function getSepNlConstraints(
		Node $nodeA, array $aCons, Node $nodeB, array $bCons
	): array {
		$env = $this->state->getEnv();

		$nlConstraints = [
			'min' => $aCons['min'] ?? null,
			'max' => $aCons['max'] ?? null,
			'constraintInfo' => [],
		];

		if ( isset( $bCons['min'] ) ) {
			if ( $nlConstraints['max'] !== null && $nlConstraints['max'] < $bCons['min'] ) {
				// Conflict, warn and let nodeB win.
				$env->log(
					'info/html2wt',
					'Incompatible constraints 1:',
					DOMCompat::nodeName( $nodeA ),
					DOMCompat::nodeName( $nodeB ),
					self::loggableConstraints( $nlConstraints )
				);
				$nlConstraints['min'] = $bCons['min'];
				$nlConstraints['max'] = $bCons['min'];
			} else {
				$nlConstraints['min'] = max( $nlConstraints['min'] ?? 0, $bCons['min'] );
			}
		}

		if ( isset( $bCons['max'] ) ) {
			if ( ( $nlConstraints['min'] ?? 0 ) > $bCons['max'] ) {
				// Conflict, warn and let nodeB win.
				$env->log(
					'info/html2wt',
					'Incompatible constraints 2:',
					DOMCompat::nodeName( $nodeA ),
					DOMCompat::nodeName( $nodeB ),
					self::loggableConstraints( $nlConstraints )
				);
				$nlConstraints['min'] = $bCons['max'];
				$nlConstraints['max'] = $bCons['max'];
			} else {
				$nlConstraints['max'] = min( $nlConstraints['max'] ?? $bCons['max'], $bCons['max'] );
			}
		}

		if ( $nlConstraints['max'] === null ) {
			// Anything more than two lines will trigger paragraphs, so default to
			// two if nothing is specified. (FIXME: This is a conservative strategy
			// since strictly speaking, this is not always true. This is more a
			// cautious fallback to handle cases where some DOM handler is missing
			// a necessary max constraint.)
			$nlConstraints['max'] = 2;
		}

		if ( ( $nlConstraints['min'] ?? 0 ) > $nlConstraints['max'] ) {
			$nlConstraints['max'] = $nlConstraints['min'];
		}

		return $nlConstraints;
	}

	/**
	 * Create a separator given a (potentially empty) separator text and newline constraints.
	 *
	 * @param Node $node
	 * @param string $sep
	 * @param array $nlConstraints
	 * @return string
	 */
	private function makeSeparator( Node $node, string $sep, array $nlConstraints ): string {
		$origSep = $sep;
		$sepType = $nlConstraints['constraintInfo']['sepType'] ?? null;

		// Split on comment/ws-only lines, consuming subsequent newlines since
		// those lines are ignored by the PHP parser
		// Ignore lines with ws and a single comment in them
		$splitRe = implode( [ "#(?:\n(?:[ \t]*?",
				Utils::COMMENT_REGEXP_FRAGMENT,
				"[ \t]*?)+(?=\n))+|",
				Utils::COMMENT_REGEXP_FRAGMENT,
				"#"
			] );
		$sepNlCount = substr_count( implode( preg_split( $splitRe, $sep ) ), "\n" );
		$minNls = $nlConstraints['min'] ?? 0;

		if ( $this->state->atStartOfOutput && $minNls > 0 ) {
			// Skip first newline as we are in start-of-line context
			$minNls--;
		}

		if ( $minNls > 0 && $sepNlCount < $minNls ) {
			// Append newlines
			$nlBuf = [];
			for ( $i = 0; $i < ( $minNls - $sepNlCount ); $i++ ) {
				$nlBuf[] = "\n";
			}

			/* ------------------------------------------------------------------
			 * The following two heuristics try to do a best-guess on where to
			 * add the newlines relative to nodeA and nodeB that best matches
			 * wikitext output expectations.
			 *
			 * 1. In a parent-child separator scenario, where the first child of
			 *    nodeA is not an element, it could have contributed to the separator.
			 *    In that case, the newlines should be prepended because they
			 *    usually correspond to the parent's constraints,
			 *    and the separator was plucked from the child.
			 *
			 *    Try html2wt on this snippet:
			 *
			 *    a<p><!--cmt-->b</p>
			 *
			 * 2. In a sibling scenario, if nodeB is a literal-HTML element, nodeA is
			 *    forcing the newline and hence the newline should be emitted right
			 *    after it.
			 *
			 *    Try html2wt on this snippet:
			 *
			 *    <p>foo</p>  <p data-parsoid='{"stx":"html"}'>bar</p>
			 * -------------------------------------------------------------------- */
			$constraintInfo = $nlConstraints['constraintInfo'] ?? [];
			$sepType = $constraintInfo['sepType'] ?? null;
			$nodeA = $constraintInfo['nodeA'] ?? null;
			$nodeB = $constraintInfo['nodeB'] ?? null;
			if (
				$sepType === 'parent-child' &&
				!DiffDOMUtils::isContentNode( DiffDOMUtils::firstNonDeletedChild( $nodeA ) ) &&
				!(
					isset( Consts::$HTML['ChildTableTags'][DOMCompat::nodeName( $nodeB )] ) &&
					!WTUtils::isLiteralHTMLNode( $nodeB )
				)
			) {
				$sep = implode( $nlBuf ) . $sep;
			} elseif ( $sepType === 'sibling' && WTUtils::isLiteralHTMLNode( $nodeB ) ) {
				$sep = implode( $nlBuf ) . $sep;
			} else {
				$sep .= implode( $nlBuf );
			}
		} elseif ( isset( $nlConstraints['max'] ) && $sepNlCount > $nlConstraints['max'] && (
			// In selser mode, if the current node is an unmodified rendering-transparent node
			// of a sibling pair, leave the separator alone since the excess newlines aren't
			// going to change the semantics of how this node will be parsed in wt->html direction.
			// This will instead eliminate a dirty diff on the page.
			!$this->state->selserMode ||
			$sepType !== 'sibling' ||
			!$this->state->currNodeUnmodified ||
			!WTUtils::isRenderingTransparentNode( $node )
		) ) {
			// Strip some newlines outside of comments.
			//
			// Capture separators in a single array with a capturing version of
			// the split regexp, so that we can work on the non-separator bits
			// when stripping newlines.
			//
			// Dirty-diff minimizing heuristic: Strip newlines away from an unmodified node.
			// If both nodes are unmodified, this dirties the separator before the current node.
			// If both nodes are modified, this dirties the separator after the previous node.
			$allBits = preg_split( '#(' . PHPUtils::reStrip( $splitRe, '#' ) . ')#',
				$sep, -1, PREG_SPLIT_DELIM_CAPTURE );
			$newBits = [];
			$n = $sepNlCount - $nlConstraints['max'];

			$stripAtEnd = $this->state->prevNodeUnmodified;
			while ( $n > 0 ) {
				$bit = $stripAtEnd ? array_pop( $allBits ) : array_shift( $allBits );
				while ( $bit && preg_match( $splitRe, $bit ) ) {
					// Retain comment-only lines as is
					$newBits[] = $bit;
					$bit = $stripAtEnd ? array_pop( $allBits ) : array_shift( $allBits );
				}
				// @phan-suppress-next-line PhanPluginLoopVariableReuse
				while ( $n > 0 && str_contains( $bit, "\n" ) ) {
					$bit = preg_replace( '/\n([^\n]*)/', '$1', $bit, 1 );
					$n--;
				}
				$newBits[] = $bit;
			}
			if ( $stripAtEnd ) {
				$newBits = array_merge( $allBits, array_reverse( $newBits ) );
			} else {
				PHPUtils::pushArray( $newBits, $allBits );
			}
			$sep = implode( $newBits );
		}

		$this->state->getEnv()->log(
			'debug/wts/sep',
			'make-new   |',
			static function () use ( $nlConstraints, $sepNlCount, $minNls, $sep, $origSep ) {
				$constraints = Utils::clone( $nlConstraints, true, true );
				unset( $constraints['constraintInfo'] );
				return PHPUtils::jsonEncode( $sep ) . ', ' . PHPUtils::jsonEncode( $origSep ) . ', ' .
					$minNls . ', ' . $sepNlCount . ', ' . PHPUtils::jsonEncode( $constraints );
			}
		);

		return $sep;
	}

	/**
	 * Merge two constraints.
	 * @param Env $env
	 * @param array $oldConstraints
	 * @param array $newConstraints
	 * @return array
	 */
	private static function mergeConstraints(
		Env $env, array $oldConstraints, array $newConstraints
	): array {
		$res = [
			'min' => max( $oldConstraints['min'] ?? 0, $newConstraints['min'] ?? 0 ),
			'max' => min( $oldConstraints['max'] ?? 2, $newConstraints['max'] ?? 2 ),
			'constraintInfo' => [],
		];

		if ( $res['min'] > $res['max'] ) {
			$res['max'] = $res['min'];
			$env->log(
				'info/html2wt',
				'Incompatible constraints (merge):',
				$res,
				self::loggableConstraints( $oldConstraints ),
				self::loggableConstraints( $newConstraints )
			);
		}

		return $res;
	}

	public static function debugOut( Node $node ): string {
		$value = '';
		if ( $node instanceof Element ) {
			$value = DOMCompat::getOuterHTML( $node );
		}
		if ( !$value ) {
			$value = $node->nodeValue;
		}
		return mb_substr( PHPUtils::jsonEncode( $value ), 0, 40 );
	}

	/**
	 * Figure out separator constraints and merge them with existing constraints
	 * in state so that they can be emitted when the next content emits source.
	 *
	 * @param Node $nodeA
	 * @param DOMHandler $sepHandlerA
	 * @param Node $nodeB
	 * @param DOMHandler $sepHandlerB
	 */
	public function updateSeparatorConstraints(
		Node $nodeA, DOMHandler $sepHandlerA, Node $nodeB, DOMHandler $sepHandlerB
	): void {
		$state = $this->state;

		if ( $nodeB->parentNode === $nodeA ) {
			// parent-child separator, nodeA parent of nodeB
			'@phan-var Element|DocumentFragment $nodeA'; // @var Element|DocumentFragment $nodeA
			$sepType = 'parent-child';
			$aCons = $sepHandlerA->firstChild( $nodeA, $nodeB, $state );
			$bCons = $nodeB instanceof Element ? $sepHandlerB->before( $nodeB, $nodeA, $state ) : [];
		} elseif ( $nodeA->parentNode === $nodeB ) {
			// parent-child separator, nodeB parent of nodeA
			'@phan-var Element|DocumentFragment $nodeB'; // @var Element|DocumentFragment $nodeA
			$sepType = 'child-parent';
			$aCons = $nodeA instanceof Element ? $sepHandlerA->after( $nodeA, $nodeB, $state ) : [];
			$bCons = $sepHandlerB->lastChild( $nodeB, $nodeA, $state );
		} else {
			// sibling separator
			$sepType = 'sibling';
			$aCons = $nodeA instanceof Element ? $sepHandlerA->after( $nodeA, $nodeB, $state ) : [];
			$bCons = $nodeB instanceof Element ? $sepHandlerB->before( $nodeB, $nodeA, $state ) : [];
		}
		$nlConstraints = $this->getSepNlConstraints( $nodeA, $aCons, $nodeB, $bCons );

		if ( !empty( $state->sep->constraints ) ) {
			// Merge the constraints
			$state->sep->constraints = self::mergeConstraints(
				$this->env,
				$state->sep->constraints,
				$nlConstraints
			);
		} else {
			$state->sep->constraints = $nlConstraints;
		}

		$this->env->log(
			'debug/wts/sep',
			function () use ( $sepType, $nodeA, $nodeB, $state ) {
				return 'constraint' . ' | ' .
					$sepType . ' | ' .
					'<' . DOMCompat::nodeName( $nodeA ) . ',' . DOMCompat::nodeName( $nodeB ) .
					'>' . ' | ' . PHPUtils::jsonEncode( $state->sep->constraints ) . ' | ' .
					self::debugOut( $nodeA ) . ' | ' . self::debugOut( $nodeB );
			}
		);

		$state->sep->constraints['constraintInfo'] = [
			'onSOL' => $state->onSOL,
			// force SOL state when separator is built/emitted
			'forceSOL' => $sepHandlerB->forceSOL(),
			'sepType' => $sepType,
			'nodeA' => $nodeA,
			'nodeB' => $nodeB,
		];
	}

	public function __construct( Env $env, SerializerState $state ) {
		$this->env = $env;
		$this->state = $state;
	}

	private function makeSepIndentPreSafe(
		string $sep, array $nlConstraints
	): string {
		$state = $this->state;
		$constraintInfo = $nlConstraints['constraintInfo'] ?? [];
		$sepType = $constraintInfo['sepType'] ?? null;
		$nodeA = $constraintInfo['nodeA'] ?? null;
		$nodeB = $constraintInfo['nodeB'] ?? null;
		$forceSOL = ( $constraintInfo['forceSOL'] ?? false ) && $sepType !== 'child-parent';
		$origNodeB = $nodeB;

		// Ex: "<div>foo</div>\n <span>bar</span>"
		//
		// We also should test for onSOL state to deal with HTML like
		// <ul> <li>foo</li></ul>
		// and strip the leading space before non-indent-pre-safe tags
		if (
			!$state->inPHPBlock &&
			!$state->inIndentPre &&
			preg_match( self::INDENT_PRE_WS_IN_SEP_REGEXP, $sep ) && (
				str_contains( $sep, "\n" ) || !empty( $constraintInfo['onSOL'] ) || $forceSOL
			)
		) {
			// 'sep' is the separator before 'nodeB' and it has leading spaces on a newline.
			// We have to decide whether that leading space will trigger indent-pres in wikitext.
			// The decision depends on where this separator will be emitted relative
			// to 'nodeA' and 'nodeB'.

			$isIndentPreSafe = false;

			// Example sepType scenarios:
			//
			// 1. sibling
			// <div>foo</div>
			// <span>bar</span>
			// The span will be wrapped in an indent-pre if the leading space
			// is not stripped since span is not a block tag
			//
			// 2. child-parent
			// <span>foo
			// </span>bar
			// The " </span>bar" will be wrapped in an indent-pre if the
			// leading space is not stripped since span is not a block tag
			//
			// 3. parent-child
			// <div>foo
			// <span>bar</span>
			// </div>
			//
			// In all cases, only block-tags prevent indent-pres.
			// (except for a special case for <br> nodes)
			if ( $nodeB && WTSUtils::precedingSpaceSuppressesIndentPre( $nodeB, $origNodeB ) ) {
				$isIndentPreSafe = true;
			} elseif ( $sepType === 'sibling' || $nodeA && DOMUtils::atTheTop( $nodeA ) ) {
				Assert::invariant( !DOMUtils::atTheTop( $nodeA ) || $sepType === 'parent-child', __METHOD__ );

				// 'nodeB' is the first non-separator child of 'nodeA'.
				//
				// Walk past sol-transparent nodes in the right-sibling chain
				// of 'nodeB' till we establish indent-pre safety.
				while ( $nodeB &&
					( DiffUtils::isDiffMarker( $nodeB ) || WTUtils::emitsSolTransparentSingleLineWT( $nodeB ) )
				) {
					$nodeB = $nodeB->nextSibling;
				}

				$isIndentPreSafe = !$nodeB || WTSUtils::precedingSpaceSuppressesIndentPre( $nodeB, $origNodeB );
			}

			// Check whether nodeB is nested inside an element that suppresses
			// indent-pres.
			if ( $nodeB && !$isIndentPreSafe && !DOMUtils::atTheTop( $nodeB ) ) {
				$parentB = $nodeB->parentNode; // could be nodeA
				while ( WTUtils::isZeroWidthWikitextElt( $parentB ) ) {
					$parentB = $parentB->parentNode;
				}

				// The token stream paragraph wrapper (and legacy doBlockLevels)
				// tracks this separately with $inBlockquote
				$isIndentPreSafe = DOMUtils::hasNameOrHasAncestorOfName(
					$parentB, 'blockquote'
				);

				// First scope wins
				while ( !$isIndentPreSafe && !DOMUtils::atTheTop( $parentB ) ) {
					if (
						TokenUtils::tagOpensBlockScope( DOMCompat::nodeName( $parentB ) ) &&
						// Only html p-tag is indent pre suppressing
						( DOMCompat::nodeName( $parentB ) !== 'p' || WTUtils::isLiteralHTMLNode( $parentB ) )
					) {
						$isIndentPreSafe = true;
						break;
					} elseif ( TokenUtils::tagClosesBlockScope( DOMCompat::nodeName( $parentB ) ) ) {
						break;
					}
					$parentB = $parentB->parentNode;
				}
			}

			$stripLeadingSpace = ( !empty( $constraintInfo['onSOL'] ) || $forceSOL ) &&
				$nodeB && !WTUtils::isLiteralHTMLNode( $nodeB ) &&
				isset( Consts::$HTMLTagsRequiringSOLContext[DOMCompat::nodeName( $nodeB )] );
			if ( !$isIndentPreSafe || $stripLeadingSpace ) {
				// Wrap non-nl ws from last line, but preserve comments.
				// This avoids triggering indent-pres.
				$sep = preg_replace_callback(
					self::INDENT_PRE_WS_IN_SEP_REGEXP,
					static function ( $matches ) use ( $stripLeadingSpace, $state ) {
						if ( !$stripLeadingSpace ) {
							// Since we nowiki-ed, we are no longer in sol state
							$state->onSOL = false;
							$state->hasIndentPreNowikis = true;
							$space = '<nowiki>' . $matches[2] . '</nowiki>';
						}
						return ( $matches[1] ?? '' ) . ( $space ?? '' ) . ( $matches[3] ?? '' );
					},
					$sep
				);
			}
		}

		$state->getEnv()->log(
			'debug/wts/sep',
			'ipre-safe  |',
			static function () use ( $sep, $nlConstraints ) {
				$constraints = Utils::clone( $nlConstraints, true, true );
				unset( $constraints['constraintInfo'] );
				return PHPUtils::jsonEncode( $sep ) . ', ' . PHPUtils::jsonEncode( $constraints );
			}
		);

		return $sep;
	}

	/**
	 * Serializing auto inserted content should invalidate the original separator
	 * @param Element $node
	 * @return DomSourceRange|null
	 */
	private static function handleAutoInserted( Element $node ): ?DomSourceRange {
		$dp = DOMDataUtils::getDataParsoid( $node );
		if ( !isset( $dp->dsr ) ) {
			return null;
		}

		$dsr = clone $dp->dsr;
		if ( !empty( $dp->autoInsertedStart ) ) {
			$dsr->openWidth = null;
		}
		if ( !empty( $dp->autoInsertedEnd ) ) {
			$dsr->closeWidth = null;
		}
		return $dsr;
	}

	/**
	 * $node is embedded inside a parent node that has its leading/trailing whitespace trimmed
	 * in the wt->html direction. In this method, we attempt to recover leading trimmed whitespace
	 * using DSR information on $node.
	 *
	 * In some cases, $node might have an additional "data-mw-selser-wrapper" span
	 * that is added by SelSer - look past those wrappers.
	 *
	 * The recovery is attempted in two different ways:
	 * 1. If we have additional DSR fields about leading/trailing WS
	 *    (represented by $state->haveTrimmedWsDSR), that info is used.
	 * 2. If not, we simply inspect source at $dsr->innerStart and if it
	 *    happens to be whitespace, we use that.
	 *
	 * @param Node $node
	 * @return ?string
	 */
	private function fetchLeadingTrimmedSpace( Node $node ): ?string {
		$origNode = $node;
		$parentNode = $node->parentNode;

		// Skip past the artificial span wrapper
		if ( $parentNode instanceof Element && $parentNode->hasAttribute( 'data-mw-selser-wrapper' ) ) {
			$node = $parentNode;
			$parentNode = $parentNode->parentNode;
		}

		// Leading trimmed whitespace only makes sense for first child.
		// Ignore comments (which are part of separators) + deletion markers.
		if ( DiffDOMUtils::previousNonSepSibling( $node ) ) {
			return null;
		}

		'@phan-var Element|DocumentFragment $parentNode'; // @var Element|DocumentFragment $parentNode
		if ( isset( Consts::$WikitextTagsWithTrimmableWS[DOMCompat::nodeName( $parentNode )] ) &&
			( $origNode instanceof Element || !preg_match( '/^[ \t]/', $origNode->nodeValue ) )
		) {
			// Don't reintroduce whitespace that's already been captured as a DisplaySpace
			if ( DOMUtils::hasTypeOf( $origNode, 'mw:DisplaySpace' ) ) {
				return null;
			}

			// FIXME: Is this complexity worth some minor dirty diff on this test?
			// ParserTest: "3. List embedded in a formatting tag in a misnested way"
			// I've not added an equivalent check in the trailing whitespace case.
			if ( $origNode instanceof Element &&
				isset( DOMDataUtils::getDataParsoid( $origNode )->autoInsertedStart ) &&
				strspn( $origNode->firstChild->textContent ?? '', " \t" ) >= 1
			) {
				return null;
			}

			$state = $this->state;
			$dsr = DOMDataUtils::getDataParsoid( $parentNode )->dsr ?? null;
			if ( Utils::isValidDSR( $dsr, true ) ) {
				if ( $state->haveTrimmedWsDSR && (
					$dsr->leadingWS > 0 || ( $dsr->leadingWS === 0 && $dsr->trailingWS > 0 )
				) ) {
					$sep = $state->getOrigSrc( $dsr->innerStart(), $dsr->innerStart() + $dsr->leadingWS ) ?? '';
					return strspn( $sep, " \t" ) === strlen( $sep ) ? $sep : null;
				} else {
					$offset = $dsr->innerStart();
					if ( $offset < $dsr->innerEnd() ) {
						$sep = $state->getOrigSrc( $offset, $offset + 1 ) ?? '';
						return preg_match( '/[ \t]/', $sep ) ? $sep : null;
					}
				}
			}
		}

		return null;
	}

	/**
	 * $node is embedded inside a parent node that has its leading/trailing whitespace trimmed
	 * in the wt->html direction. In this method, we attempt to recover trailing trimmed whitespace
	 * using DSR information on $node.
	 *
	 * In some cases, $node might have an additional "data-mw-selser-wrapper" span
	 * that is added by SelSer - look past those wrappers.
	 *
	 * The recovery is attempted in two different ways:
	 * 1. If we have additional DSR fields about leading/trailing WS
	 *    (represented by $state->haveTrimmedWsDSR), that info is used.
	 * 2. If not, we simply inspect source at $dsr->innerEnd and if it
	 *    happens to be whitespace, we use that.
	 *
	 * @param Node $node
	 * @return ?string
	 */
	private function fetchTrailingTrimmedSpace( Node $node ): ?string {
		$origNode = $node;
		$parentNode = $node->parentNode;

		// Skip past the artificial span wrapper
		if ( $parentNode instanceof Element && $parentNode->hasAttribute( 'data-mw-selser-wrapper' ) ) {
			$node = $parentNode;
			$parentNode = $parentNode->parentNode;
		}

		// Trailing trimmed whitespace only makes sense for last child.
		// Ignore comments (which are part of separators) + deletion markers.
		if ( DiffDOMUtils::nextNonSepSibling( $node ) ) {
			return null;
		}

		$sep = null;
		'@phan-var Element|DocumentFragment $parentNode'; // @var Element|DocumentFragment $parentNode
		if ( isset( Consts::$WikitextTagsWithTrimmableWS[DOMCompat::nodeName( $parentNode )] ) &&
			( $origNode instanceof Element || !preg_match( '/[ \t]$/', $origNode->nodeValue ) )
		) {
			// Don't reintroduce whitespace that's already been captured as a DisplaySpace
			if ( DOMUtils::hasTypeOf( $origNode, 'mw:DisplaySpace' ) ) {
				return null;
			}

			$state = $this->state;
			$dsr = DOMDataUtils::getDataParsoid( $parentNode )->dsr ?? null;
			if ( Utils::isValidDSR( $dsr, true ) ) {
				if ( $state->haveTrimmedWsDSR && (
					$dsr->trailingWS > 0 || ( $dsr->trailingWS === 0 && $dsr->leadingWS > 0 )
				) ) {
					$sep = $state->getOrigSrc( $dsr->innerEnd() - $dsr->trailingWS, $dsr->innerEnd() ) ?? '';
					if ( !preg_match( '/^[ \t]*$/', $sep ) ) {
						$sep = null;
					}
				} else {
					$offset = $dsr->innerEnd() - 1;
					// The > instead of >= is to deal with an edge case
					// = = where that single space is captured by the
					// getLeadingSpace case above
					if ( $offset > $dsr->innerStart() ) {
						$sep = $state->getOrigSrc( $offset, $offset + 1 ) ?? '';
						if ( !preg_match( '/[ \t]/', $sep ) ) {
							$sep = null;
						}
					}
				}
			}
		}

		return $sep;
	}

	/**
	 * Emit a separator based on the collected (and merged) constraints
	 * and existing separator text. Called when new output is triggered.
	 * @param Node $node
	 * @param bool $leading
	 *   if true, trimmed leading whitespace is emitted
	 *   if false, trimmed railing whitespace is emitted
	 * @return string|null
	 */
	public function recoverTrimmedWhitespace( Node $node, bool $leading ): ?string {
		// Deal with scenarios where leading / trailing whitespace were trimmed.
		// We now need to figure out if we need to add any leading / trailing WS back.
		if ( $this->state->useWhitespaceHeuristics && $this->state->selserMode ) {
			if ( $leading ) {
				return $this->fetchLeadingTrimmedSpace( $node );
			} else {
				$lastChild = DiffDOMUtils::lastNonDeletedChild( $node );
				return $lastChild ? $this->fetchTrailingTrimmedSpace( $lastChild ) : null;
			}
		}

		return null;
	}

	/**
	 * Emit a separator based on the collected (and merged) constraints
	 * and existing separator text. Called when new output is triggered.
	 * @param Node $node
	 * @return string|null
	 */
	public function buildSep( Node $node ): ?string {
		$state = $this->state;
		$sepType = $state->sep->constraints['constraintInfo']['sepType'] ?? null;
		$sep = null;
		$origNode = $node;
		$prevNode = $state->sep->lastSourceNode;
		$dsrA = null;
		$dsrB = null;

		/* ----------------------------------------------------------------------
		 * Assuming we have access to the original source, we can use DSR offsets
		 * to extract separators from source only if:
		 * - we are in selser mode AND
		 * - this node is not part of a newly inserted subtree (marked 'modified')
		 *   for which DSR isn't available
		 * - neither node is adjacent to a deleted block node
		 *   (see the long comment in SerializerState::emitChunk in the middle)
		 *
		 * In other scenarios, DSR values on "adjacent" nodes in the edited DOM
		 * may not reflect deleted content between them.
		 * ---------------------------------------------------------------------- */
		$origSepNeeded = $node !== $prevNode && $state->selserMode;
		$origSepNeededAndUsable =
			$origSepNeeded && !$state->inInsertedContent &&
			!WTSUtils::nextToDeletedBlockNodeInWT( $prevNode, true ) &&
			!WTSUtils::nextToDeletedBlockNodeInWT( $node, false ) &&
			WTSUtils::origSrcValidInEditedContext( $state, $prevNode ) &&
			WTSUtils::origSrcValidInEditedContext( $state, $node );

		if ( $origSepNeededAndUsable ) {
			if ( $prevNode instanceof Element ) {
				$dsrA = self::handleAutoInserted( $prevNode );
			} elseif ( !( $prevNode instanceof DocumentFragment ) ) {
				// Check if $prevNode is the last child of a zero-width element,
				// and use that for dsr purposes instead. Typical case: text in p.
				if (
					!$prevNode->nextSibling &&
					$prevNode->parentNode !== $node &&
					$prevNode->parentNode instanceof Element &&
					( DOMDataUtils::getDataParsoid( $prevNode->parentNode )->dsr->closeWidth ?? null ) === 0
				) {
					$dsrA = self::handleAutoInserted( $prevNode->parentNode );
				} elseif (
					// Can we extrapolate DSR from $prevNode->previousSibling?
					// Yes, if $prevNode->parentNode didn't have its children edited.
					$prevNode->previousSibling instanceof Element &&
					!DiffUtils::directChildrenChanged( $prevNode->parentNode )
				) {
					$endDsr = DOMDataUtils::getDataParsoid( $prevNode->previousSibling )->dsr->end ?? null;
					$correction = null;
					if ( is_int( $endDsr ) ) {
						if ( $prevNode instanceof Comment ) {
							'@phan-var Comment $prevNode'; // @var Comment $prevNode
							$correction = WTUtils::decodedCommentLength( $prevNode );
						} else {
							$correction = strlen( $prevNode->nodeValue );
						}
						$dsrA = new DomSourceRange(
							$endDsr,
							$endDsr + $correction + WTUtils::indentPreDSRCorrection( $prevNode ),
							0,
							0
						);
					}
				}
			}

			if ( !$dsrA ) {
				// nothing to do -- no reason to compute dsrB if dsrA is null
			} elseif ( $node instanceof Element ) {
				// $node is parent of $prevNode
				if ( $prevNode->parentNode === $node ) {
					'@phan-var Element|DocumentFragment $node'; // @var Element|DocumentFragment $node
					// FIXME: Maybe we shouldn't set dsr in the dsr pass if both aren't valid?
					//
					// When we are in the lastChild sep scenario and the parent doesn't have
					// useable dsr, if possible, walk up the ancestor nodes till we find
					// a dsr-bearing node
					//
					// This fix is needed to handle trailing newlines in this wikitext:
					// [[File:foo.jpg|thumb|300px|foo\n{{1x|A}}\n{{1x|B}}\n{{1x|C}}\n\n]]
					while (
						!$node->nextSibling &&
						!DOMUtils::atTheTop( $node ) &&
						(
							empty( DOMDataUtils::getDataParsoid( $node )->dsr ) ||
							DOMDataUtils::getDataParsoid( $node )->dsr->start === null ||
							DOMDataUtils::getDataParsoid( $node )->dsr->end === null
						)
					) {
						$node = $node->parentNode;
					}
				}

				// The top node could be a document fragment
				$dsrB = $node instanceof Element ? self::handleAutoInserted( $node ) : null;
			} elseif ( !( $node instanceof DocumentFragment ) ) {
				// $node is text/comment. Can we extrapolate DSR from $node->parentNode?
				// Yes, if this is the child of a zero-width element and
				// is only preceded by separator elements.
				//
				// 1. text in p.
				// 2. ws-only child of a node with auto-inserted start tag
				//    Ex: "<span> <s>x</span> </s>" --> <span> <s>x</s*></span><s*> </s>
				// 3. ws-only children of a node with auto-inserted start tag
				//    Ex: "{|\n|-\n <!--foo--> \n|}"
				$nodeParent = $node->parentNode;
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'@phan-var Element|DocumentFragment $nodeParent'; // @var Element|DocumentFragment $nodeParent

				if (
					$nodeParent !== $prevNode &&
					$nodeParent instanceof Element &&
					( DOMDataUtils::getDataParsoid( $nodeParent )->dsr->openWidth ?? null ) === 0
				) {
					$sepLen = self::precedingSeparatorTextLen( $node );
					if ( $sepLen !== null ) {
						$dsrB = DOMDataUtils::getDataParsoid( $nodeParent )->dsr;
						if ( is_int( $dsrB->start ) && $sepLen > 0 ) {
							$dsrB = clone $dsrB;
							$dsrB->start += $sepLen;
						}
					}
				}
			}

			// FIXME: Maybe we shouldn't set dsr in the dsr pass if both aren't valid?
			if ( Utils::isValidDSR( $dsrA ) && Utils::isValidDSR( $dsrB ) ) {
				// Figure out containment relationship
				if ( $dsrA->start <= $dsrB->start ) {
					if ( $dsrB->end <= $dsrA->end ) {
						if ( $dsrA->start === $dsrB->start && $dsrA->end === $dsrB->end ) {
							// Both have the same dsr range, so there can't be any
							// separators between them
							$sep = '';
						} elseif ( isset( $dsrA->openWidth ) ) {
							// B in A, from parent to child
							$sep = $state->getOrigSrc( $dsrA->innerStart(), $dsrB->start );
						}
					} elseif ( $dsrA->end <= $dsrB->start ) {
						// B following A (siblingish)
						$sep = $state->getOrigSrc( $dsrA->end, $dsrB->start );
					} elseif ( isset( $dsrB->closeWidth ) ) {
						// A in B, from child to parent
						$sep = $state->getOrigSrc( $dsrA->end, $dsrB->innerEnd() );
					}
				} elseif ( $dsrA->end <= $dsrB->end ) {
					if ( isset( $dsrB->closeWidth ) ) {
						// A in B, from child to parent
						$sep = $state->getOrigSrc( $dsrA->end, $dsrB->innerEnd() );
					}
				} else {
					$this->env->log( 'info/html2wt', 'dsr backwards: should not happen!' );
				}

				// Reset if $sep is invalid
				if ( $sep && !WTSUtils::isValidSep( $sep ) ) {
					$sep = null;
				}
			}
		} elseif ( $origSepNeeded && !DiffUtils::hasDiffMarkers( $prevNode ) ) {
			// Given the following conditions:
			// - $prevNode has no diff markers. (checked above)
			// - $prevNode's next non-sep sibling ($next) was inserted.
			// - $next is an ancestor of $node.
			// - all of those ancestor nodes from $node->$next have zero-width
			//   wikitext (otherwise, the separator isn't usable)
			// Try to extract a separator from original source that existed
			// between $prevNode and its original next sibling or its parent
			// (if $prevNode was the last non-sep child).
			//
			// This minimizes dirty-diffs to that separator text from
			// the insertion of $next after $prevNode.
			$next = DiffDOMUtils::nextNonSepSibling( $prevNode );
			$origSepUsable = $next && DiffUtils::hasInsertedDiffMark( $next );

			// Check that $next is an ancestor of $node and all nodes
			// on that path have zero-width wikitext
			if ( $origSepUsable && $node !== $next ) {
				$n = $node->parentNode;
				while ( $n && $next !== $n ) {
					if ( !WTUtils::isZeroWidthWikitextElt( $n ) ) {
						$origSepUsable = false;
						break;
					}
					$n = $n->parentNode;
				}
				$origSepUsable = $origSepUsable && $n !== null;
			}

			// Extract separator from original source if possible
			if ( $origSepUsable ) {
				$origNext = DiffDOMUtils::nextNonSepSibling( $next );
				if ( !$origNext ) { // $prevNode was last non-sep child of its parent
					// We could work harder for text/comments and extrapolate, but skipping that here
					// FIXME: If we had a generic DSR extrapolation utility, that would be useful
					$o1 = $prevNode instanceof Element ?
						DOMDataUtils::getDataParsoid( $prevNode )->dsr->end ?? null : null;
					if ( $o1 !== null ) {
						$dsr2 = DOMDataUtils::getDataParsoid( $prevNode->parentNode )->dsr ?? null;
						$o2 = $dsr2 ? $dsr2->innerEnd() : null;
						$sep = $o2 !== null ? $state->getOrigSrc( $o1, $o2 ) : null;
					}
				} elseif ( !DiffUtils::hasDiffMarkers( $origNext ) ) {
					// We could work harder for text/comments and extrapolate, but skipping that here
					// FIXME: If we had a generic DSR extrapolation utility, that would be useful
					$o1 = $prevNode instanceof Element ?
						DOMDataUtils::getDataParsoid( $prevNode )->dsr->end ?? null : null;
					if ( $o1 !== null ) {
						$o2 = $origNext instanceof Element ?
							DOMDataUtils::getDataParsoid( $origNext )->dsr->start ?? null : null;
						$sep = $o2 !== null ? $state->getOrigSrc( $o1, $o2 ) : null;
					}
				}

				if ( $sep !== null ) {
					// Since this is an inserted node, we might have to augment this
					// with newline constraints and so, we just set this recovered sep
					// to the buffered sep in state->sep->src
					$state->sep->src = $sep;
					$sep = null;
				}
			}
		}

		// If all efforts failed, use special-purpose heuristics to recover
		// trimmed leading / trailing whitespace from lists, headings, table-cells
		if ( $sep === null ) {
			if ( $sepType === 'parent-child' ) {
				$sep = $this->recoverTrimmedWhitespace( $node, true );
				if ( $sep !== null ) {
					$state->sep->src = $sep . $state->sep->src;
				}
			} elseif ( $sepType === 'child-parent' ) {
				$sep = $this->recoverTrimmedWhitespace( $node, false );
				if ( $sep !== null ) {
					$state->sep->src .= $sep;
				}
			}
		}

		$this->env->log(
			'debug/wts/sep',
			static function () use ( $prevNode, $origNode, $sep, $state ) {
				return 'maybe-sep  | ' .
					'prev:' . ( $prevNode ? DOMCompat::nodeName( $prevNode ) : '--none--' ) .
					', node:' . DOMCompat::nodeName( $origNode ) .
					', sep: ' . PHPUtils::jsonEncode( $sep ) .
					', state.sep.src: ' . PHPUtils::jsonEncode( $state->sep->src ?? null );
			}
		);

		// If the separator is being emitted before a node that emits sol-transparent WT,
		// go through makeSeparator to verify indent-pre constraints are met.
		$sepConstraints = $state->sep->constraints ?? [ 'max' => 0 ];
		if ( $sep === null || ( $state->sep->src && $state->sep->src !== $sep ) ) {
			if ( !empty( $state->sep->constraints ) || !empty( $state->sep->src ) ) {
				// TODO: set modified flag if start or end node (but not both) are
				// modified / new so that the selser can use the separator
				$sep = $this->makeSeparator( $node, $state->sep->src ?? '', $sepConstraints );
			} else {
				$sep = null;
			}
		}

		if ( $sep !== null ) {
			$sep = self::makeSepIndentPreSafe( $sep, $sepConstraints );
		}
		return $sep;
	}
}
