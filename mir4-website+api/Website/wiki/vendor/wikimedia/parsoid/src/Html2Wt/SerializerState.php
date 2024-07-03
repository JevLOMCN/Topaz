<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use Composer\Semver\Semver;
use stdClass;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\SelserData;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\ConstrainedText;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;

/**
 * State object for the wikitext serializers.
 */
class SerializerState {

	/**
	 * Regexp for checking if what we have consumed wikimarkup that has special meaning at the
	 * beginning of the line, and is indeed at the beginning of the line (modulo comments and
	 * other ignored elements).
	 *
	 * @return string
	 */
	private function solWikitextRegexp(): string {
		static $solWikitextRegexp = null;
		if ( $solWikitextRegexp === null ) {
			$sol = PHPUtils::reStrip(
				$this->env->getSiteConfig()->solTransparentWikitextNoWsRegexp( true ),
				'@'
			);
			$solWikitextRegexp = '@' .
				'^(' . $sol . ')' .
				'([\ \*#:;{\|!=].*)$' .
				'@D';
		}
		return $solWikitextRegexp;
	}

	/**
	 * Regexp for checking whether we are at the start of the line (modulo comments and
	 * other ignored elements).
	 *
	 * @return string
	 */
	private function solRegexp(): string {
		static $solRegexp = null;
		if ( $solRegexp === null ) {
			$sol = PHPUtils::reStrip(
				$this->env->getSiteConfig()->solTransparentWikitextNoWsRegexp( true ),
				'@'
			);
			$solRegexp = '@(^|\n)' . $sol . '$@D';
		}
		return $solRegexp;
	}

	/**
	 * Separator information:
	 * - constraints (array<array|int>|null): min/max number of newlines
	 * - src (string|null): collected separator text from DOM text/comment nodes
	 * - lastSourceNode (?Node): Seems to be bookkeeping to make sure we don't reuse
	 *     original separators when `emitChunk` is called
	 *     consecutively on the same node.  However, it also
	 *     differs from `state.prevNode` in that it only gets
	 *     updated when a node calls `emitChunk` so that nodes
	 *     serializing `justChildren` don't mix up `buildSep`.
	 * FIXME: could use a dedicated class
	 * @var stdClass
	 */
	public $sep;

	/**
	 * Is the serializer at the start of a new wikitext line?
	 * @var bool
	 */
	public $onSOL = true;

	/**
	 * True when wts kicks off, false after the first char has been output
	 * SSS FIXME: Can this be done away with in some way?
	 * @var bool
	 */
	public $atStartOfOutput = true;

	/**
	 * Is the serializer currently handling link content (children of `<a>`)?
	 * @var bool
	 */
	public $inLink = false;

	/**
	 * Is the serializer currently handling caption content?
	 * @var bool
	 */
	public $inCaption = false;

	/**
	 * Is the serializer currently handling an indent-pre tag?
	 * @var bool
	 */
	public $inIndentPre = false;

	/**
	 * Is the serializer currently handling a html-pre tag?
	 * @var bool
	 */
	public $inHTMLPre = false;

	/**
	 * Is the serializer currently handling a tag that the PHP parser
	 * treats as a block tag?
	 * @var bool
	 */
	public $inPHPBlock = false;

	/**
	 * Is the serializer being invoked recursively to serialize a
	 * template-generated attribute (via `WSP.getAttributeValue`'s
	 * template handling).  If so, we should suppress some
	 * serialization escapes, like autolink protection, since
	 * these are not valid for attribute values.
	 * @var bool
	 */
	public $inAttribute = false;

	/**
	 * Is the serializer currently processing a subtree that has been
	 * marked inserted compared to original content (ex: via VE / CX)?
	 *
	 * @var bool
	 */
	public $inInsertedContent;

	/**
	 * Did we introduce nowikis for indent-pre protection?
	 * If yes, we might run a post-pass to strip useless ones.
	 * @var bool
	 */
	public $hasIndentPreNowikis = false;

	/**
	 * Did we introduce nowikis to preserve quote semantics?
	 * If yes, we might run a post-pass to strip useless ones.
	 * @var bool
	 */
	public $hasQuoteNowikis = false;

	/**
	 * Did we introduce `<nowiki />`s?
	 * If yes, we do a postpass to remove unnecessary trailing ones.
	 * @var bool
	 */
	public $hasSelfClosingNowikis = false;

	/**
	 * Did we introduce nowikis around `=.*=` text?
	 * If yes, we do a postpass to remove unnecessary escapes.
	 * @var bool
	 */
	public $hasHeadingEscapes = false;

	/**
	 * Records the nesting level of wikitext tables
	 * @var int
	 */
	public $wikiTableNesting = 0;

	/**
	 * Stack of wikitext escaping handlers -- these handlers are responsible
	 * for smart escaping when the surrounding wikitext context is known.
	 * @var callable[] See {@link serializeChildren()}
	 */
	public $wteHandlerStack = [];

	/**
	 * This array is used by the wikitext escaping algorithm -- represents
	 * a "single line" of output wikitext as represented by a block node in
	 * the DOM.
	 * - firstNode (?Node): first DOM node processed on this line
	 * - text (string): output so far from all nodes on the current line
	 * - chunks (ConstrainedText[]): list of chunks comprising the current line
	 * @var stdClass
	 * XXX: replace with output buffering per line
	 * FIXME: could use a dedicated class
	 */
	public $currLine;

	/**
	 * Stack used to enforce single-line context
	 * @var SingleLineContext
	 */
	public $singleLineContext;

	/**
	 * Text to be emitted at the start of file, for redirects
	 * @var string|null
	 */
	public $redirectText = null;

	/** @var WikitextSerializer */
	public $serializer;

	/** @var ParsoidExtensionAPI */
	public $extApi;

	/** @var string The serialized output */
	public $out = '';

	/**
	 * Whether to use heuristics to determine if a list item, heading, table cell, etc.
	 * should have whitespace inserted after the "*#=|!" wikitext chars? This is normally
	 * true by default, but not so if HTML content version is older than 1.7.0.
	 * In practice, we are now at version 2.1, but Flow stores HTML, so till Flow migrates
	 * all its content over to a later version, we need a boolean flag.
	 * @var bool
	 */
	public $useWhitespaceHeuristics;

	/**
	 * Are we in selective serialization mode?
	 * @see SelectiveSerializer
	 * @var bool
	 */
	public $selserMode;

	/** @var SelserData */
	private $selserData;

	/**
	 * If in selser mode, while processing a node, do we know if
	 * its previous node has not been modified in an edit?
	 * @var bool
	 */
	public $prevNodeUnmodified;

	/**
	 * If in selser mode, while processing a node, do we know if
	 * it has not been modified in an edit?
	 * @var bool
	 */
	public $currNodeUnmodified;

	/**
	 * Should we run the wikitext escaping code on the wikitext chunk
	 * that will be emitted?
	 * @var bool
	 */
	public $needsEscaping = false;

	/**
	 * Used as fast patch for special protected characters in WikitextEscapeHandlers and
	 * comes from LanguageVariantHandler
	 * @var string|null
	 */
	public $protect;

	/** @var Separators */
	public $separators;

	/** @var Env */
	private $env;

	/** @var Element */
	public $currNode;

	/** @var Element */
	private $prevNode;

	/** @var array */
	public $openAnnotations;

	/**
	 * Log prefix to use in trace output
	 * @var string
	 */
	private $logPrefix = 'OUT:';

	public $haveTrimmedWsDSR = false;

	/**
	 * @param WikitextSerializer $serializer
	 * @param array $options List of options for serialization:
	 *   - onSOL: (bool)
	 *   - inPHPBlock: (bool)
	 *   - inAttribute: (bool)
	 *   - protect: (string)
	 *   - selserData: (SelserData)
	 */
	public function __construct( WikitextSerializer $serializer, array $options = [] ) {
		$this->env = $serializer->env;
		$this->serializer = $serializer;
		$this->extApi = new ParsoidExtensionAPI( $this->env, [ 'html2wt' => [ 'state' => $this ] ] );
		$this->onSOL = $options['onSOL'] ?? $this->onSOL;
		$this->inPHPBlock = $options['inPHPBlock'] ?? $this->inPHPBlock;
		$this->inAttribute = $options['inAttribute'] ?? $this->inAttribute;
		$this->protect = $options['protect'] ?? null;
		$this->selserData = $options['selserData'] ?? null;
		$this->resetCurrLine( null );
		$this->singleLineContext = new SingleLineContext();
		$this->resetSep();
		$this->haveTrimmedWsDSR = Semver::satisfies( $this->env->getInputContentVersion(), '>=2.1.1' );
		$this->separators = new Separators( $this->env, $this );
	}

	/**
	 * @note Porting note: this replaces direct access
	 * @return Env
	 */
	public function getEnv(): Env {
		return $this->env;
	}

	/**
	 * Initialize a few boolean flags based on serialization mode.
	 * FIXME: Ideally, this should be private. Requires shuffing around
	 * where SerializerState is constructed so that $selserMode is known
	 * at the time of construction.
	 * @private for use by WikitextSerializer only
	 * @param bool $selserMode Are we running selective serialization?
	 */
	public function initMode( bool $selserMode ): void {
		$this->useWhitespaceHeuristics =
			Semver::satisfies( $this->env->getInputContentVersion(), '>=1.7.0' );
		$this->selserMode = $selserMode;
	}

	/**
	 * Appends the separator source to the separator src buffer.
	 * Don't update $state->onSOL since this string hasn't been emitted yet.
	 * If content handlers change behavior based on whether this newline will
	 * be emitted or not, they should peek into this buffer (ex: see TDHandler
	 * and THHandler code).
	 *
	 * @param string $src
	 */
	public function appendSep( string $src ): void {
		$this->sep->src = ( $this->sep->src ?: '' ) . $src;
	}

	/**
	 * Cycle the state after processing a node.
	 * @param Node $node
	 */
	public function updateSep( Node $node ): void {
		$this->sep->lastSourceNode = $node;
	}

	private function resetSep() {
		$this->sep = (object)[
			'constraints' => null,
			'src' => null,
			'lastSourceNode' => null,
		];
	}

	/**
	 * Reset the current line state.
	 * @param ?Node $node
	 */
	private function resetCurrLine( ?Node $node ): void {
		$this->currLine = (object)[
			'text' => '',
			'chunks' => [],
			'firstNode' => $node
		];
	}

	/**
	 * Process and emit a line of ConstrainedText chunks, adjusting chunk boundaries as necessary.
	 * (Start of line and end of line are always safe for ConstrainedText chunks, so we don't need
	 * to buffer more than the last line.)
	 */
	private function flushLine(): void {
		$this->out .= ConstrainedText::escapeLine( $this->currLine->chunks );
		$this->currLine->chunks = [];
	}

	/**
	 * Extracts a subset of the page source bound by the supplied indices.
	 * @param int $start Start offset, in bytes
	 * @param int $end End offset, in bytes
	 * @return string|null
	 */
	public function getOrigSrc( int $start, int $end ): ?string {
		Assert::invariant( $this->selserMode, 'SerializerState::$selserMode must be set' );
		if (
			$start <= $end &&
			// FIXME: Having a $start greater than the source length is
			// probably a canary for corruption.  Maybe we should be throwing
			// here instead.  See T240053.
			// But, see comment in UnpackDOMFragments where we very very rarely
			// can deliberately set DSR to point outside page source.
			$start <= strlen( $this->selserData->oldText )
		) {
			return substr( $this->selserData->oldText, $start, $end - $start );
		} else {
			return null;
		}
	}

	/**
	 * Like it says on the tin.
	 * @param Node $node
	 */
	public function updateModificationFlags( Node $node ): void {
		$this->prevNodeUnmodified = $this->currNodeUnmodified;
		$this->currNodeUnmodified = false;
		$this->prevNode = $node;
	}

	/**
	 * Separators put us in SOL state.
	 * @param string $sep
	 * @param Node $node
	 */
	private function sepIntroducedSOL( string $sep, Node $node ): void {
		// Don't get tripped by newlines in comments!  Be wary of nowikis added
		// by makeSepIndentPreSafe on the last line.
		$nonCommentSep = preg_replace( Utils::COMMENT_REGEXP, '', $sep );
		if ( substr( $nonCommentSep, -1 ) === "\n" ) {
			$this->onSOL = true;
		}

		if ( str_contains( $nonCommentSep, "\n" ) ) {
			// process escapes in our full line
			$this->flushLine();
			$this->resetCurrLine( $node );
		}
	}

	/**
	 * Accumulates chunks on the current line.
	 * @param ConstrainedText $chunk
	 * @param string $logPrefix
	 */
	private function pushToCurrLine( ConstrainedText $chunk, string $logPrefix ) {
		// Emitting text that has not been escaped
		$this->currLine->text .= $chunk->text;

		$this->currLine->chunks[] = $chunk;

		$this->serializer->trace( '--->', $logPrefix, static function () use ( $chunk ) {
			return PHPUtils::jsonEncode( $chunk->text );
		} );
	}

	/**
	 * Pushes the separator to the current line and resets the separator state.
	 * @param string $sep
	 * @param Node $node
	 * @param string $debugPrefix
	 */
	private function emitSep( string $sep, Node $node, string $debugPrefix ): void {
		$sep = ConstrainedText::cast( $sep, $node );

		// Replace newlines if we're in a single-line context
		if ( $this->singleLineContext->enforced() ) {
			$sep->text = preg_replace( '/\n/', ' ', $sep->text );
		}

		$this->pushToCurrLine( $sep, $debugPrefix );
		$this->sepIntroducedSOL( $sep->text, $node );

		// Reset separator state
		$this->resetSep();
		$this->updateSep( $node );
	}

	/**
	 * Determines if we can use the original separator for this node or if we
	 * need to build one based on its constraints, and then emits it.
	 *
	 * @param Node $node
	 */
	private function emitSepForNode( Node $node ): void {
		/* When block nodes are deleted, the deletion affects whether unmodified
		 * newline separators between a pair of unmodified P tags can be reused.
		 *
		 * Example:
		 * ```
		 * Original WT  : "<div>x</div>foo\nbar"
		 * Original HTML: "<div>x</div><p>foo</p>\n<p>bar</p>"
		 * Edited HTML  : "<p>foo</p>\n<p>bar</p>"
		 * Annotated DOM: "<mw:DiffMarker is-block><p>foo</p>\n<p>bar</p>"
		 * Expected WT  : "foo\n\nbar"
		 * ```
		 *
		 * Note the additional newline between "foo" and "bar" even though originally,
		 * there was just a single newline.
		 *
		 * So, even though the two P tags and the separator between them is
		 * unmodified, it is insufficient to rely on just that. We have to look at
		 * what has happened on the two wikitext lines onto which the two P tags
		 * will get serialized.
		 *
		 * Now, if you check the code for `nextToDeletedBlockNodeInWT`, that code is
		 * not really looking at ALL the nodes before/after the nodes that could
		 * serialize onto the wikitext lines. It is looking at the immediately
		 * adjacent nodes, i.e. it is not necessary to look if a block-tag was
		 * deleted 2 or 5 siblings away. If we had to actually examine all of those,
		 * nodes, this would get very complex, and it would be much simpler to just
		 * discard the original separators => potentially lots of dirty diffs.
		 *
		 * To understand why it is sufficient (for correctness) to examine just
		 * the immediately adjacent nodes, let us look at an additional example.
		 * ```
		 * Original WT  : "a<div>b</div>c<div>d</div>e\nf"
		 * Original HTML: "<p>a</p><div>b</div><p>c</p><div>d</div><p>e</p>\n<p>f</p>"
		 * ```
		 * Note how `<block>` tags and `<p>` tags interleave in the HTML. This would be
		 * the case always no matter how much inline content showed up between the
		 * block tags in wikitext. If the b-`<div>` was deleted, we don't care
		 * about it, since we still have the d-`<div>` before the P tag that preserves
		 * the correctness of the single `"\n"` separator. If the d-`<div>` was deleted,
		 * we conservatively ignore the original separator and let normal P-P constraints
		 * take care of it. At worst, we might generate a dirty diff in this scenario. */
		$origSepNeeded = ( $node !== $this->sep->lastSourceNode );
		$origSepUsable = $origSepNeeded &&
			(
				// first-content-node of <body> ($this->prevNode)
				(
					DOMUtils::isBody( $this->prevNode ) &&
					$node->parentNode === $this->prevNode
				)
				||
				// unmodified sibling node of $this->prevNode
				(
					$this->prevNode && $this->prevNodeUnmodified &&
					$node->parentNode === $this->prevNode->parentNode &&
					!WTSUtils::nextToDeletedBlockNodeInWT( $this->prevNode, true )
				)
			) &&
			$this->currNodeUnmodified && !WTSUtils::nextToDeletedBlockNodeInWT( $node, false );

		$origSep = null;
		if ( $origSepUsable ) {
			if ( $this->prevNode instanceof Element && $node instanceof Element ) {
				'@phan-var Element $node';/** @var Element $node */
				$origSep = $this->getOrigSrc(
					// <body> won't have DSR in body_only scenarios
					( DOMUtils::isBody( $this->prevNode ) ?
						0 : DOMDataUtils::getDataParsoid( $this->prevNode )->dsr->end ),
					DOMDataUtils::getDataParsoid( $node )->dsr->start
				);
			} elseif ( $this->sep->src && WTSUtils::isValidSep( $this->sep->src ) ) {
				// We don't know where '$this->sep->src' comes from. So, reuse it
				// only if it is a valid separator string.
				$origSep = $this->sep->src;
			}
		}

		if ( $origSep !== null ) {
			$this->emitSep( $origSep, $node, 'ORIG-SEP:' );
		} else {
			$sep = $this->separators->buildSep( $node );
			$this->emitSep( $sep ?: '', $node, 'SEP:' );
		}
	}

	/**
	 * Recovers and emits any trimmed whitespace for $node
	 * @param Node $node
	 * @param bool $leading
	 *   if true, trimmed leading whitespace is emitted
	 *   if false, trimmed railing whitespace is emitted
	 * @return string|null
	 */
	public function recoverTrimmedWhitespace( Node $node, bool $leading ): ?string {
		$sep = $this->separators->recoverTrimmedWhitespace( $node, $leading );
		$this->serializer->trace( '--->', "TRIMMED-SEP:", static function () use ( $sep ) {
			return PHPUtils::jsonEncode( $sep );
		} );
		return $sep;
	}

	/**
	 * Pushes the chunk to the current line.
	 * @param ConstrainedText|string $res
	 * @param Node $node
	 */
	public function emitChunk( $res, Node $node ): void {
		$res = ConstrainedText::cast( $res, $node );

		// Replace newlines if we're in a single-line context
		if ( $this->singleLineContext->enforced() ) {
			$res->text = str_replace( "\n", ' ', $res->text );
		}

		// Emit separator first
		if ( $res->noSep ) {
			/* skip separators for internal tokens from SelSer */
			if ( $this->onSOL ) {
				// process escapes in our full line
				$this->flushLine();
				$this->resetCurrLine( $node );
			}
		} else {
			$this->emitSepForNode( $node );
		}

		$needsEscaping = $this->needsEscaping;
		if ( $needsEscaping && $this->currNode instanceof Text ) {
			$needsEscaping = !$this->inHTMLPre && ( $this->onSOL || !$this->currNodeUnmodified );
		}

		// Escape 'res' if necessary
		if ( $needsEscaping ) {
			$res = new ConstrainedText( [
				'text' => $this->serializer->escapeWikitext( $this, $res->text, [
					'node' => $node,
					'isLastChild' => DiffDOMUtils::nextNonDeletedSibling( $node ) === null,
				] ),
				'prefix' => $res->prefix,
				'suffix' => $res->suffix,
				'node' => $res->node,
			] );
			$this->needsEscaping = false;
		} else {
			// If 'res' is coming from selser and the current node is a paragraph tag,
			// check if 'res' might need some leading chars nowiki-escaped before being output.
			// Because of block-tag p-wrapping behavior, sol-sensitive characters that used to
			// be in non-sol positions, but yet wrapped in p-tags, could end up in sol-position
			// if those block tags get deleted during edits.
			//
			// Ex: a<div>foo</div>*b
			// -- wt2html --> <p>a</p><div>foo<div><p>*b</p>
			// --   EDIT  --> <p>a</p><p>*b</p>
			// -- html2wt --> a\n\n<nowiki>*</nowiki>b
			//
			// In this scenario, the <p>a</p>, <p>*b</p>, and <p>#c</p>
			// will be marked unmodified and will be processed below.
			if ( $this->selserMode
				&& $this->onSOL
				&& $this->currNodeUnmodified
				// 'node' came from original Parsoid HTML unmodified. So, if its content
				// needs nowiki-escaping, we know that the reason it didn't parse into
				// lists/headings/whatever is because it didn't occur at the start of the
				// line => it had a block-tag in the original wikitext. So if the previous
				// node was also unmodified (and since it also came from original Parsoid
				// HTML), we can safely infer that it couldn't have been an inline node or
				// a P-tag (if it were, the p-wrapping code would have swallowed that content
				// into 'node'). So, it would have to be some sort of block tag => this.onSOL
				// couldn't have been true (because we could have serialized 'node' on the
				// same line as the block tag) => we can save some effort by eliminating
				// scenarios where 'this.prevNodeUnmodified' is true.
				 && !$this->prevNodeUnmodified
				&& DOMCompat::nodeName( $node ) === 'p' && !WTUtils::isLiteralHTMLNode( $node )
			) {
				$pChild = DiffDOMUtils::firstNonSepChild( $node );
				// If a text node, we have to make sure that the text doesn't
				// get reparsed as non-text in the wt2html pipeline.
				if ( $pChild instanceof Text ) {
					$match = $res->matches( $this->solWikitextRegexp(), $this->env );
					if ( $match && isset( $match[2] ) ) {
						if ( preg_match( '/^([\*#:;]|{\||.*=$)/D', $match[2] )
							// ! and | chars are harmless outside tables
							|| ( strspn( $match[2], '|!' ) && $this->wikiTableNesting > 0 )
							// indent-pres are suppressed inside <blockquote>
							|| ( preg_match( '/^ \S/', $match[2] )
								&& !DOMUtils::hasNameOrHasAncestorOfName( $node, 'blockquote' ) )
						) {
							$res = ConstrainedText::cast( ( $match[1] ?: '' )
								. '<nowiki>' . substr( $match[2], 0, 1 ) . '</nowiki>'
								. substr( $match[2], 1 ), $node );
						}
					}
				}
			}
		}

		// Output res
		$this->pushToCurrLine( $res, $this->logPrefix );

		// Update sol flag. Test for newlines followed by optional includeonly or comments
		if ( !$res->matches( $this->solRegexp(), $this->env ) ) {
			$this->onSOL = false;
		}

		// We've emit something so we're no longer at SOO.
		$this->atStartOfOutput = false;
	}

	/**
	 * Serialize the children of a DOM node, sharing the global serializer state.
	 * Typically called by a DOM-based handler to continue handling its children.
	 * @param Element|DocumentFragment $node
	 * @param ?callable $wtEscaper ( $state, $text, $opts )
	 *   PORT-FIXME document better; should this be done via WikitextEscapeHandlers somehow?
	 * @param ?Node $firstChild
	 */
	public function serializeChildren(
		Node $node, ?callable $wtEscaper = null, ?Node $firstChild = null
	): void {
		// SSS FIXME: Unsure if this is the right thing always
		if ( $wtEscaper ) {
			$this->wteHandlerStack[] = $wtEscaper;
		}

		$child = $firstChild ?: $node->firstChild;
		while ( $child !== null ) {
			// We always get the next child to process
			$child = $this->serializer->serializeNode( $child );
		}

		if ( $wtEscaper ) {
			array_pop( $this->wteHandlerStack );
		}

		// If we serialized children explicitly,
		// we were obviously processing a modified node.
		$this->currNodeUnmodified = false;
	}

	/**
	 * Abstracts some steps taken in `serializeChildrenToString` and `serializeDOM`
	 *
	 * @param Element|DocumentFragment $node
	 * @param ?callable $wtEscaper See {@link serializeChildren()}
	 * @internal For use by WikitextSerializer only
	 */
	public function kickOffSerialize(
		Node $node, ?callable $wtEscaper = null
	): void {
		$this->updateSep( $node );
		$this->currNodeUnmodified = false;
		$this->updateModificationFlags( $node );
		$this->resetCurrLine( $node->firstChild );
		$this->serializeChildren( $node, $wtEscaper );
		// Emit child-parent seps.
		$this->emitSepForNode( $node );
		// We've reached EOF, flush the remaining buffered text.
		$this->flushLine();
	}

	/**
	 * Serialize children to a string
	 *
	 * FIXME(arlorla): Shouldn't affect the separator state, but accidents have
	 * have been known to happen. T109793 suggests using its own wts / state.
	 *
	 * @param Element|DocumentFragment $node
	 * @param ?callable $wtEscaper See {@link serializeChildren()}
	 * @param string $inState
	 * @return string
	 */
	private function serializeChildrenToString(
		Node $node, ?callable $wtEscaper, string $inState
	): string {
		$states = [ 'inLink', 'inCaption', 'inIndentPre', 'inHTMLPre', 'inPHPBlock', 'inAttribute' ];
		Assert::parameter( in_array( $inState, $states, true ), '$inState', 'Must be one of: '
			. implode( ', ', $states ) );
		// FIXME: Make sure that the separators emitted here conform to the
		// syntactic constraints of syntactic context.
		$oldSep = $this->sep;
		$oldSOL = $this->onSOL;
		$oldOut = $this->out;
		$oldStart = $this->atStartOfOutput;
		$oldCurrLine = $this->currLine;
		$oldLogPrefix = $this->logPrefix;
		// Modification flags
		$oldPrevNodeUnmodified = $this->prevNodeUnmodified;
		$oldCurrNodeUnmodified = $this->currNodeUnmodified;
		$oldPrevNode = $this->prevNode;

		$this->out = '';
		$this->logPrefix = 'OUT(C):';
		$this->resetSep();
		$this->onSOL = false;
		$this->atStartOfOutput = false;
		$this->$inState = true;

		$this->singleLineContext->disable();
		$this->kickOffSerialize( $node, $wtEscaper );
		$this->singleLineContext->pop();

		// restore the state
		$bits = $this->out;
		$this->out = $oldOut;
		$this->$inState = false;
		$this->sep = $oldSep;
		$this->onSOL = $oldSOL;
		$this->atStartOfOutput = $oldStart;
		$this->currLine = $oldCurrLine;
		$this->logPrefix = $oldLogPrefix;
		// Modification flags
		$this->prevNodeUnmodified = $oldPrevNodeUnmodified;
		$this->currNodeUnmodified = $oldCurrNodeUnmodified;
		$this->prevNode = $oldPrevNode;
		return $bits;
	}

	/**
	 * Serialize children of a link to a string
	 * @param Element|DocumentFragment $node
	 * @param ?callable $wtEscaper See {@link serializeChildren()}
	 * @return string
	 */
	public function serializeLinkChildrenToString(
		Node $node, ?callable $wtEscaper = null
	): string {
		return $this->serializeChildrenToString( $node, $wtEscaper, 'inLink' );
	}

	/**
	 * Serialize children of a caption to a string
	 * @param Element|DocumentFragment $node
	 * @param ?callable $wtEscaper See {@link serializeChildren()}
	 * @return string
	 */
	public function serializeCaptionChildrenToString(
		Node $node, ?callable $wtEscaper = null
	): string {
		return $this->serializeChildrenToString( $node, $wtEscaper, 'inCaption' );
	}

	/**
	 * Serialize children of an indent-pre to a string
	 * @param Element|DocumentFragment $node
	 * @param ?callable $wtEscaper See {@link serializeChildren()}
	 * @return string
	 */
	public function serializeIndentPreChildrenToString(
		Node $node, ?callable $wtEscaper = null
	): string {
		return $this->serializeChildrenToString( $node, $wtEscaper, 'inIndentPre' );
	}

	/**
	 * Take notes of the open annotation ranges and whether they have been extended.
	 * @param string $ann
	 * @param bool $extended
	 */
	public function openAnnotationRange( string $ann, bool $extended ) {
		$this->openAnnotations[$ann] = $extended;
	}

	/**
	 * Removes the corresponding annotation range from the list of open ranges.
	 * @param string $ann
	 */
	public function closeAnnotationRange( string $ann ) {
		unset( $this->openAnnotations[$ann] );
	}

}
