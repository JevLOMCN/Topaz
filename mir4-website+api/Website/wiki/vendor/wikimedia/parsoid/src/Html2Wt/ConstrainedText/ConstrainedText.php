<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\ConstrainedText;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Utils;

/**
 * A chunk of wikitext output.  This base class contains the
 * wikitext and a pointer to the DOM node which is responsible for
 * generating it.  Subclasses can add additional properties to record
 * context or wikitext boundary restrictions for proper escaping.
 * The chunk is serialized with the `escape` method, which might
 * alter the wikitext in order to ensure it doesn't run together
 * with its context (usually by adding `<nowiki>` tags).
 *
 * The main entry point is the static function `ConstrainedText::escapeLine()`.
 */
class ConstrainedText {
	/**
	 * This adds necessary escapes to a line of chunks.  We provide
	 * the `ConstrainedText#escape` function with its left and right
	 * context, and it can determine what escapes are needed.
	 *
	 * The `line` parameter is an array of `ConstrainedText` *chunks*
	 * which make up a line (or part of a line, in some cases of nested
	 * processing).
	 *
	 * @param ConstrainedText[] $line
	 * @return string
	 */
	public static function escapeLine( array $line ): string {
		// The left context will be precise (that is, it is the result
		// of `ConstrainedText#escape` and will include any escapes
		// triggered by chunks on the left), but the right context
		// is just the (unescaped) text property from the chunk.
		// As we work left to right we will piece together a fully-escaped
		// string.  Be careful not to shoot yourself in the foot -- if the
		// escaped text is significantly different from the chunk's `text`
		// property, the preceding chunk may not have made the correct
		// decisions about emitting an escape suffix.  We could solve
		// this by looping until the state converges (or until we detect
		// a loop) but for now let's hope that's not necessary.
		$state = new State( $line );
		$safeLeft = '';
		for ( $state->pos = 0;  $state->pos < count( $line );  $state->pos++ ) {
			$chunk = $line[$state->pos];
			// Process the escapes for this chunk, given escaped previous chunk
			$state->rightContext = substr( $state->rightContext, strlen( $chunk->text ) );
			$thisEscape = $chunk->escape( $state );
			$state->leftContext .=
				( $thisEscape->prefix ?? '' ) .
				$thisEscape->text .
				( $thisEscape->suffix ?? '' );
			if ( $thisEscape->greedy ) {
				// protect the left context: this will be matched greedily
				// by this chunk, so there's no chance that a subsequent
				// token will include this in its prefix.
				$safeLeft .= $state->leftContext;
				$state->leftContext = '';
			}
		}
		// right context should be empty here.
		return $safeLeft . $state->leftContext;
	}

	/**
	 * The wikitext string associated with this chunk.
	 * @var string
	 */
	public $text;
	/**
	 * The DOM Node associated with this chunk.
	 * @var Node
	 */
	public $node;
	/**
	 * The prefix string to add if the start of the chunk doesn't match its
	 * constraints.
	 * @var ?string
	 */
	public $prefix;
	/**
	 * The suffix string to add if the end of the chunk doesn't match its
	 * constraints.
	 * @var ?string
	 */
	public $suffix;
	/**
	 * Does this chunk come from selser?
	 * @var bool
	 */
	public $selser;
	/**
	 * Suppress separators?
	 * @var bool
	 */
	public $noSep;

	/**
	 * @param array{text:string,node:Node,prefix?:string,suffix?:string} $args Options.
	 */
	public function __construct( array $args ) {
		$this->text = $args['text'];
		$this->node = $args['node'];
		$this->prefix = $args['prefix'] ?? null;
		$this->suffix = $args['suffix'] ?? null;
		$this->selser = false;
		$this->noSep = false;
	}

	/**
	 * Ensure that the argument `o`, which is perhaps a string, is a instance of
	 * `ConstrainedText`.
	 * @param string|ConstrainedText $o
	 * @param Node $node
	 *   The {@link Node} corresponding to `o`.
	 * @return ConstrainedText
	 */
	public static function cast( $o, Node $node ): ConstrainedText {
		if ( $o instanceof ConstrainedText ) {
			return $o;
		}
		return new ConstrainedText( [ 'text' => $o ?? '', 'node' => $node ] );
	}

	/**
	 * Use the provided `state`, which gives context and access to the entire
	 * list of chunks, to determine the proper escape prefix/suffix.
	 * Returns an object with a `text` property as well as optional
	 * `prefix` and 'suffix' properties giving desired escape strings.
	 * @param State $state Context state
	 * @return Result
	 */
	public function escape( State $state ): Result {
		// default implementation: no escaping, no prefixes or suffixes.
		return new Result( $this->text, $this->prefix, $this->suffix );
	}

	/**
	 * Simple equality.  This enforces type equality
	 * (ie subclasses are not equal).
	 * @param ConstrainedText $ct
	 * @return bool
	 */
	public function equals( ConstrainedText $ct ): bool {
		return $this === $ct || (
			get_class( $this ) === self::class &&
			get_class( $ct ) === self::class &&
			$this->text === $ct->text
		);
	}

	/**
	 * Useful shortcut: execute a regular expression on the raw wikitext.
	 * @param string $re
	 * @param Env $env
	 * @return array|null
	 *  An array containing the matched results or null if there were no matches.
	 */
	public function matches( string $re, Env $env ): ?array {
		$r = preg_match( $re, $this->text, $m );
		if ( $r === false ) {
			if ( version_compare( PHP_VERSION, '8.0.0', '>' ) ) {
				$error_msg = preg_last_error_msg();
			} else {
				$error_msg = "preg_last_error: " . preg_last_error();
			}
			$env->log( 'error', $error_msg, $re, $this->text );
			throw new \Error( 'Bad regular expression' );
		}
		return $r === 0 ? null : $m;
	}

	/**
	 * SelSer support: when we come across an unmodified node in during
	 * selective serialization, we know we can use the original wikitext
	 * for that node unmodified.  *But* there may be boundary conditions
	 * on the left and right sides of the selser'ed text which are going
	 * to require escaping.
	 *
	 * So rather than turning the node into a plain old `ConstrainedText`
	 * chunk, allow subclasses of `ConstrainedText` to register as potential
	 * handlers of selser nodes.  A selser'ed magic link, for example,
	 * will then turn into a `MagicLinkText` and thus be able to enforce
	 * the proper boundary constraints.
	 *
	 * @param string $text
	 * @param Element $node
	 * @param DataParsoid $dataParsoid
	 * @param Env $env
	 * @param array $opts
	 * @return ConstrainedText[]
	 */
	public static function fromSelSer(
		string $text, Element $node, DataParsoid $dataParsoid,
		Env $env, array $opts = []
	): array {
		// Main dispatch point: iterate through registered subclasses, asking
		// each if they can handle this node (by invoking `fromSelSerImpl`).

		// We define parent types before subtypes, so search the list backwards
		// to be sure we check subtypes before parent types.
		$types = self::$types;
		for ( $i = count( $types ) - 1;  $i >= 0;  $i-- ) {
			$ct = call_user_func(
				[ $types[$i], 'fromSelSerImpl' ],
				$text, $node, $dataParsoid, $env, $opts
			);
			if ( !$ct ) {
				continue;
			}
			if ( !is_array( $ct ) ) {
				$ct = [ $ct ];
			}
			// tag these chunks as coming from selser
			foreach ( $ct as $t ) {
				$t->selser = true;
			}
			return $ct;
		}
		// ConstrainedText::fromSelSerImpl should handle everything which reaches it
		// so nothing should make it here.
		throw new \Error( 'Should never happen.' );
	}

	/**
	 * Base case: the given node type does not correspond to a special
	 * `ConstrainedText` subclass.  We still have to be careful: the leftmost
	 * (rightmost) children of `node` may still be exposed to our left (right)
	 * context.  If so (ie, their DSR bounds coincide) split the selser text
	 * and emit multiple `ConstrainedText` chunks to preserve the proper
	 * boundary conditions.
	 *
	 * @param string $text
	 * @param Element $node
	 * @param DataParsoid $dataParsoid
	 * @param Env $env
	 * @param array $opts
	 * @return ConstrainedText|ConstrainedText[]
	 */
	protected static function fromSelSerImpl(
		string $text, Element $node, DataParsoid $dataParsoid,
		Env $env, array $opts
	) {
		// look at leftmost and rightmost children, it may be that we need
		// to turn these into ConstrainedText chunks in order to preserve
		// the proper escape conditions on the prefix/suffix text.
		$firstChild = DiffDOMUtils::firstNonDeletedChild( $node );
		$lastChild = DiffDOMUtils::lastNonDeletedChild( $node );
		$firstChildDp = $firstChild instanceof Element ?
			DOMDataUtils::getDataParsoid( $firstChild ) : null;
		$lastChildDp = $lastChild instanceof Element ?
			DOMDataUtils::getDataParsoid( $lastChild ) : null;
		$prefixChunks = [];
		$suffixChunks = [];
		$len = null;
		$ignorePrefix = $opts['ignorePrefix'] ?? false;
		$ignoreSuffix = $opts['ignoreSuffix'] ?? false;
		// check to see if first child's DSR start is the same as this node's
		// DSR start.  If so, the first child is exposed to the (modified)
		// left-hand context, and so recursively convert it to the proper
		// list of specialized chunks.
		if (
			!$ignorePrefix &&
			$firstChildDp && Utils::isValidDSR( $firstChildDp->dsr ?? null ) &&
			$dataParsoid->dsr->start === $firstChildDp->dsr->start
		) {
			DOMUtils::assertElt( $firstChild ); // implied by $firstChildDp
			$len = $firstChildDp->dsr->length();
			if ( $len < 0 ) { // T254412: Bad DSR
				$env->log( "error/html2wt/dsr",
					"Bad DSR: " . PHPUtils::jsonEncode( $firstChildDp->dsr ),
					"Node: " . DOMCompat::getOuterHTML( $firstChild ) );
			} else {
				if ( $len > strlen( $text ) ) { // T254412: Bad DSR
					$env->log( "error/html2wt/dsr",
						"Bad DSR: " . PHPUtils::jsonEncode( $firstChildDp->dsr ),
						"Node: " . DOMCompat::getOuterHTML( $firstChild ) );
					$len = strlen( $text );
				}
				$prefixChunks = self::fromSelSer(
					substr( $text, 0, $len ), $firstChild, $firstChildDp, $env,
					// this child node's right context will be protected:
					[ 'ignoreSuffix' => true ]
				);
				$text = substr( $text, $len );
			}
		}
		// check to see if last child's DSR end is the same as this node's
		// DSR end.  If so, the last child is exposed to the (modified)
		// right-hand context, and so recursively convert it to the proper
		// list of specialized chunks.
		if (
			!$ignoreSuffix && $lastChild !== $firstChild &&
			$lastChildDp && Utils::isValidDSR( $lastChildDp->dsr ?? null ) &&
			$dataParsoid->dsr->end === $lastChildDp->dsr->end
		) {
			DOMUtils::assertElt( $lastChild ); // implied by $lastChildDp
			$len = $lastChildDp->dsr->length();
			if ( $len < 0 ) { // T254412: Bad DSR
				$env->log( "error/html2wt/dsr",
					"Bad DSR: " . PHPUtils::jsonEncode( $lastChildDp->dsr ),
					"Node: " . DOMCompat::getOuterHTML( $lastChild ) );
			} else {
				if ( $len > strlen( $text ) ) { // T254412: Bad DSR
					$env->log( "error/html2wt/dsr",
						"Bad DSR: " . PHPUtils::jsonEncode( $lastChildDp->dsr ),
						"Node: " . DOMCompat::getOuterHTML( $lastChild ) );
					$len = strlen( $text );
				}
				$suffixChunks = self::fromSelSer(
					substr( $text, -$len ), $lastChild, $lastChildDp, $env,
					// this child node's left context will be protected:
					[ 'ignorePrefix' => true ]
				);
				$text = substr( $text, 0, -$len );
			}
		}
		// glue together prefixChunks, whatever's left of `text`, and suffixChunks
		$chunks = [ self::cast( $text, $node ) ];
		$chunks = array_merge( $prefixChunks, $chunks, $suffixChunks );
		// top-level chunks only:
		if ( !( $ignorePrefix || $ignoreSuffix ) ) {
			// ensure that the first chunk belongs to `node` in order to
			// emit separators correctly before `node`
			if ( $chunks[0]->node !== $node ) {
				array_unshift( $chunks, self::cast( '', $node ) );
			}
			// set 'noSep' flag on all but the first chunk, so we don't get
			// extra separators from `SSP.emitChunk`
			foreach ( $chunks as $i => $t ) {
				if ( $i > 0 ) {
					$t->noSep = true;
				}
			}
		}
		return $chunks;
	}

	/**
	 * List of types we attempt `fromSelSer` with.  This should include all the
	 * concrete subclasses of `ConstrainedText` (`RegExpConstrainedText` is
	 * missing since it is an abstract class).  We also include the
	 * `ConstrainedText` class as the first element (even though it is
	 * an abstract base class) as a little bit of a hack: it simplifies
	 * `ConstrainedText.fromSelSer` by factoring some of its work into
	 * `ConstrainedText.fromSelSerImpl`.
	 * @var class-string[]
	 */
	private static $types = [
		// Base class is first, as a special case
		self::class,
		// All concrete subclasses of ConstrainedText
		WikiLinkText::class, ExtLinkText::class, AutoURLLinkText::class,
		MagicLinkText::class, LanguageVariantText::class
	];
}
