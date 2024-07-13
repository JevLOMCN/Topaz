<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\NlTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Wt2Html\PegTokenizer;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

/**
 * This class is an attempt to fixup the token stream to reparse strings
 * as tokens that failed to parse in the tokenizer because of SOL or
 * other constraints OR because tags were being constructed in pieces
 * or whatever.
 *
 * This is a pure hack to improve compatibility with the core parser
 * given that we dont have a preprocessor.  This will be a grab-bag of
 * heuristics and tricks to handle different scenarios.
 */
class TokenStreamPatcher extends TokenHandler {
	private PegTokenizer $tokenizer;

	/** @var int|null */
	private $srcOffset;

	private bool $sol;

	private array $tokenBuf;
	private int $wikiTableNesting;
	/** True only for top-level & attribute value pipelines */
	private bool $inIndependentParse;

	/** @var Token|null */
	private $lastConvertedTableCellToken;

	/** @var SelfclosingTagTk|null */
	private $tplStartToken = null;

	/** @var NlTk|null */
	private $discardableNlTk = null;

	public function __construct( TokenTransformManager $manager, array $options ) {
		$newOptions = [ 'tsp' => true ] + $options;
		parent::__construct( $manager, $newOptions );
		$this->tokenizer = new PegTokenizer( $this->env );
		$this->reset();
	}

	/**
	 * Resets any internal state for this token handler.
	 *
	 * @param array $parseOpts
	 */
	public function resetState( array $parseOpts ): void {
		parent::resetState( $parseOpts );
		$this->inIndependentParse = $this->atTopLevel || isset( $this->options['attrExpansion'] );
	}

	private function reset() {
		$this->srcOffset = 0;
		$this->sol = true;
		$this->tokenBuf = [];
		$this->wikiTableNesting = 0;
		// This marker tries to track the most recent table-cell token (td/th)
		// that was converted to string. For those, we want to get rid
		// of their corresponding mw:TSRMarker meta tag.
		//
		// This marker is set when we convert a td/th token to string
		//
		// This marker is cleared in one of the following scenarios:
		// 1. When we clear a mw:TSRMarker corresponding to the token set earlier
		// 2. When we change table nesting
		// 3. When we hit a tr/td/th/caption token that wasn't converted to string
		$this->lastConvertedTableCellToken = null;
	}

	/**
	 * @inheritDoc
	 */
	public function onNewline( NlTk $token ): ?TokenHandlerResult {
		$self = $this;
		$this->env->log( 'trace/tsp', $this->pipelineId,
			static function () use ( $self, $token ) {
				return "(indep=" . ( $self->inIndependentParse ? "yes" : "no " ) .
					";sol=" . ( $self->sol ? "yes" : "no " ) .
					PHPUtils::jsonEncode( $token );
			}
		);
		$this->srcOffset = $token->dataParsoid->tsr->end ?? null;
		if ( $this->sol && $this->tplStartToken ) {
			// When using core preprocessor, start-of-line start is forced by
			// inserting a newline in certain cases (the "T2529 hack"). In the
			// legacy parser, the T2529 hack is never applied if the template was
			// already at the start of the line (the `!$piece['lineStart']`
			// check in Parser::braceSubstitution where T2529 is handled), but
			// that context (`$this->sol`) isn't passed through when Parsoid
			// invokes the core preprocessor. Thus, when $this->sol is true,
			// prepare to (if the following tokens warrant it) remove an unnecessary
			// T2529 newline added by the legacy preprocessor.
			$this->discardableNlTk = $token;
		}
		$this->tokenBuf[] = $token;
		$this->sol = true;
		return new TokenHandlerResult( [] );
	}

	/**
	 * @inheritDoc
	 */
	public function onEnd( EOFTk $token ): ?TokenHandlerResult {
		$res = $this->onAny( $token );
		$this->reset();
		return $res;
	}

	/**
	 * Clear start of line info
	 */
	private function clearSOL() {
		// clear tsr and sol flag
		$this->srcOffset = null;
		$this->sol = false;
	}

	/**
	 * Fully reprocess the output tokens from the tokenizer through
	 * all the other handlers in stage 2.
	 *
	 * @param int $srcOffset
	 * @param array $toks
	 * @param bool $popEOF
	 * @return array
	 */
	private function reprocessTokens( int $srcOffset, array $toks, bool $popEOF = false ): array {
		// Update tsr
		TokenUtils::shiftTokenTSR( $toks, $srcOffset );
		$pipe = $this->env->getPipelineFactory()->getPipeline( "tokens/x-mediawiki" );
		$pipe->init( [
			'frame' => $this->manager->getFrame(),
			'toplevel' => $this->atTopLevel,
			// The tokens should be reprocessed in the context of the original frame's source
			'srcText' => $this->manager->getFrame()->getSrcText()
		] );
		$toks = (array)$pipe->parse( $toks, [] );
		if ( $popEOF ) {
			array_pop( $toks ); // pop EOFTk
		}
		return $toks;
	}

	private function convertTokenToString( Token $token ): array {
		$da = $token->dataParsoid;
		$tsr = $da->tsr ?? null;

		if ( $tsr && $tsr->end > $tsr->start ) {
			// > will only hold if these are valid numbers
			$str = $tsr->substr( $this->manager->getFrame()->getSrcText() );
			// sol === false ensures that the pipe will not be parsed as a <td>/listItem again
			$toks = $this->tokenizer->tokenizeSync( $str, [ 'sol' => false ] );
			return $this->reprocessTokens( $tsr->start, $toks, true );
		} elseif ( !empty( $da->autoInsertedStart ) && !empty( $da->autoInsertedEnd ) ) {
			return [ '' ];
		} else {
			// SSS FIXME: What about "!!" and "||"??
			switch ( $token->getName() ) {
				case 'td':
					return [ '|' ];
				case 'th':
					return [ '!' ];
				case 'tr':
					return [ '|-' ];
				case 'caption':
					return [ $token instanceof TagTk ? '|+' : '' ];
				case 'table':
					return [ $token instanceof EndTagTk ? '|}' : $token ];
				case 'listItem':
					return [ implode( '', $token->getAttributeV( 'bullets' ) ) ];
			}

			// No conversion if we get here
			return [ $token ];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		try {
			return $this->onAnyInternal( $token );
		} finally {
			// Ensure we always clean up discardableNlTk and tplStartToken even
			// in the presence of exceptions.
			$this->discardableNlTk = null;
			if ( $this->tplStartToken !== $token ) {
				$this->tplStartToken = null;
			}
		}
	}

	/**
	 * The legacy parser's "T2529 hack" attempts to ensure templates are
	 * always evaluated in start-of-line context by prepending a newline
	 * if necessary.  However, it is inconsistent: in particular it
	 * only treats }| : ; # * as SOL-sensitive tokens, neglecting ==
	 * (headings) and ! | |} (in table context).
	 *
	 * If we're using the core preprocessor for template expansion:
	 *  - The core preprocessor as invoked by Parsoid will always insert the
	 *    newline in the "T2529 cases" (even though it's not necessary; Parsoid
	 *    is already in SOL mode) *HOWEVER*
	 *  - As described in ::onNewline() above, the newline insertion is
	 *    /supposed/ to be suppressed if the template was *already*
	 *    at the start of the line.  So we need to strip the unnecessarily
	 *    added NlTk to avoid "extra" whitespace in Parsoid's expansion.
	 *     Ex: "{{my-tpl}}" in sol-context which will get expanded to "\n*foo"
	 *     but the "\n" wasn't necessary
	 *
	 * If we're in native preprocessor mode:
	 *  - If we are in SOL state, we don't need to add a newline.
	 *  - If we are not in SOL state, we need to insert a newline in 'T2529' cases.
	 *    Ex: "{{my-tpl}}" in sol-context which expands to "*foo" but in
	 *    non-sol context expands to "\n*foo"
	 *
	 * @param string $tokenName
	 */
	private function handleT2529Hack( string $tokenName ): void {
		// Core's
		if ( $tokenName === 'table' || $tokenName === 'listItem' ) {
			// We're in a context when the core preprocessor would apply
			// the "T2529 hack" to ensure start-of-line context.
			if ( $this->discardableNlTk ) {
				// We're using core preprocessor and were already at
				// the start of the line, so the core preprocessor wouldn't
				// actually have inserted a newline here.  Swallow up ours.
				array_pop( $this->tokenBuf );
			} elseif ( !$this->sol &&
				$this->tplStartToken &&
				$this->env->nativeTemplateExpansionEnabled()
			) {
				// Native preprocessor; add a newline in "T2529 cases"
				// for correct whitespace. (Remember that this only happens
				// if we weren't already at the start of the line.)
				// Add a newline & force SOL
				$this->tokenBuf[] = new NlTk( null );
				$this->sol = true;
			}
		}
	}

	/**
	 * @param mixed $token
	 * @return ?TokenHandlerResult
	 */
	public function onAnyInternal( $token ): ?TokenHandlerResult {
		$self = $this;
		$this->env->log( 'trace/tsp', $this->pipelineId,
			static function () use ( $self, $token ) {
				return "(indep=" . ( $self->inIndependentParse ? "yes" : "no " ) .
					";sol=" . ( $self->sol ? "yes" : "no " ) .
					PHPUtils::jsonEncode( $token );
			}
		);

		$tokens = [ $token ];
		$tc = TokenUtils::getTokenType( $token );
		switch ( $tc ) {
			case 'string':
				// While we are buffering newlines to suppress them
				// in case we see a category, buffer all intervening
				// white-space as well.
				if ( count( $this->tokenBuf ) > 0 && preg_match( '/^\s*$/D', $token ) ) {
					$this->tokenBuf[] = $token;
					return new TokenHandlerResult( [] );
				}

				// This is only applicable where we use Parsoid's (broken) native preprocessor.
				// This supports scenarios like "{{1x|*bar}}". When "{{{1}}}" is tokenized
				// "*bar" isn't available and so won't become a list.
				// FIXME: {{1x|1===foo==}} will still be broken. So, this fix below is somewhat
				// independent of T2529 for our broken preprocessor but we are restricting the
				// fix to T2529.
				$T2529hack = false;
				if ( $this->env->nativeTemplateExpansionEnabled() &&
					$this->tplStartToken &&
					preg_match( '/^(?:{\\||[:;#*])/', $token )
				) {
					// Add a newline & force SOL
					$T2529hack = true;
					// Remove newline insertion in the core preprocessor
					// only occurs if we weren't already at the start of
					// the line (see discussion in ::onNewline() above).
					if ( !$this->sol ) {
						$this->tokenBuf[] = new NlTk( null );
						$this->sol = true;
					}
				}

				if ( $this->sol ) {
					// Attempt to match "{|" after a newline and convert
					// it to a table token.
					if ( $this->inIndependentParse && str_starts_with( $token, '{|' ) ) {
						// Reparse string with the 'table_start_tag' rule
						// and fully reprocess them.
						$retoks = $this->tokenizer->tokenizeAs( $token, 'table_start_tag', /* sol */true );
						if ( $retoks === false ) {
							// XXX: The string begins with table start syntax,
							// we really shouldn't be here. Anything else on the
							// line would get swallowed up as attributes.
							$this->env->log( 'error', 'Failed to tokenize table start tag.' );
							$this->clearSOL();
						} else {
							$tokens = $this->reprocessTokens( $this->srcOffset, $retoks );
							$this->wikiTableNesting++;
							$this->lastConvertedTableCellToken = null;
						}
					} elseif ( $this->inIndependentParse && $T2529hack ) { // {| has been handled above
						$retoks = $this->tokenizer->tokenizeAs( $token, 'list_item', /* sol */true );
						if ( $retoks === false ) {
							$this->env->log( 'error', 'Failed to tokenize list item.' );
							$this->clearSOL();
						} else {
							$tokens = $this->reprocessTokens( $this->srcOffset, $retoks );
						}
					} elseif ( preg_match( '/^\s*$/D', $token ) ) {
						// White-space doesn't change SOL state
						// Update srcOffset
						$this->srcOffset += strlen( $token );
					} else {
						$this->clearSOL();
					}
				} else {
					$this->clearSOL();
				}
				break;

			case 'CommentTk':
				// Comments don't change SOL state
				// Update srcOffset
				$this->srcOffset = $token->dataParsoid->tsr->end ?? null;
				break;

			case 'SelfclosingTagTk':
				if ( $token->getName() === 'meta' && ( $token->dataParsoid->stx ?? '' ) !== 'html' ) {
					if ( TokenUtils::hasTypeOf( $token, 'mw:Transclusion' ) ) {
						$this->tplStartToken = $token;
					}
					$this->srcOffset = $token->dataParsoid->tsr->end ?? null;
					if ( count( $this->tokenBuf ) > 0 &&
						TokenUtils::hasTypeOf( $token, 'mw:Transclusion' )
					) {
						// If we have buffered newlines, we might very well encounter
						// a category link, so continue buffering.
						$this->tokenBuf[] = $token;
						return new TokenHandlerResult( [] );
					}
				} elseif ( $token->getName() === 'link' &&
					$token->getAttributeV( 'rel' ) === 'mw:PageProp/Category'
				) {
					// Replace buffered newline & whitespace tokens with mw:EmptyLine
					// meta-tokens. This tunnels them through the rest of the transformations
					// without affecting them. During HTML building, they are expanded
					// back to newlines / whitespace.
					$n = count( $this->tokenBuf );
					if ( $n > 0 ) {
						$i = 0;
						while ( $i < $n &&
							!( $this->tokenBuf[$i] instanceof SelfclosingTagTk )
						) {
							$i++;
						}

						$dp = new DataParsoid;
						$dp->tokens = array_slice( $this->tokenBuf, 0, $i );
						$toks = [
							new SelfclosingTagTk( 'meta',
								[ new KV( 'typeof', 'mw:EmptyLine' ) ],
								$dp
							)
						];
						if ( $i < $n ) {
							$toks[] = $this->tokenBuf[$i];
							if ( $i + 1 < $n ) {
								$dp = new DataParsoid;
								$dp->tokens = array_slice( $this->tokenBuf, $i + 1 );
								$toks[] = new SelfclosingTagTk( 'meta',
									[ new KV( 'typeof', 'mw:EmptyLine' ) ],
									$dp
								);
							}
						}
						$tokens = array_merge( $toks, $tokens );
						$this->tokenBuf = [];
					}
					$this->clearSOL();
				} else {
					$this->clearSOL();
				}
				break;

			case 'TagTk':
				if ( $this->inIndependentParse && !TokenUtils::isHTMLTag( $token ) ) {
					$tokenName = $token->getName();
					$this->handleT2529Hack( $tokenName );
					if ( $tokenName === 'listItem' && isset( $this->options['attrExpansion'] ) ) {
						// Convert list items back to bullet wikitext in attribute context
						$tokens = $this->convertTokenToString( $token );
					} elseif ( $tokenName === 'table' ) {
						$this->lastConvertedTableCellToken = null;
						$this->wikiTableNesting++;
					} elseif ( in_array( $tokenName, [ 'td', 'th', 'tr', 'caption' ], true ) ) {
						if ( $this->wikiTableNesting === 0 ) {
							if ( $token->getName() === 'td' || $token->getName() === 'th' ) {
								$this->lastConvertedTableCellToken = $token;
							}
							$tokens = $this->convertTokenToString( $token );
						} else {
							$this->lastConvertedTableCellToken = null;
						}
					}
				}
				$this->clearSOL();
				break;

			case 'EndTagTk':
				if ( $this->inIndependentParse && !TokenUtils::isHTMLTag( $token ) ) {
					if ( $this->wikiTableNesting > 0 ) {
						if ( $token->getName() === 'table' ) {
							$this->lastConvertedTableCellToken = null;
							$this->wikiTableNesting--;
						}
					} elseif ( $token->getName() === 'table' || $token->getName() === 'caption' ) {
						// Convert this to "|}"
						$tokens = $this->convertTokenToString( $token );
					}
				}
				$this->clearSOL();
				break;

			default:
				break;
		}

		// Emit buffered newlines (and a transclusion meta-token, if any)
		if ( count( $this->tokenBuf ) > 0 ) {
			$tokens = array_merge( $this->tokenBuf, $tokens );
			$this->tokenBuf = [];
		}
		return new TokenHandlerResult( $tokens );
	}
}
