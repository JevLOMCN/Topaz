<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Tokens\CommentTk;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\NlTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

/**
 * PRE-handling relies on the following 6-state FSM.
 *
 * States
 * ------
 * ```
 * SOL           -- start-of-line
 *                  (white-space, comments, meta-tags are all SOL transparent)
 *                  The FSM always starts in this state.
 * PRE           -- we might need a pre-block
 *                  (if we enter the PRE_COLLECT state)
 * PRE_COLLECT   -- we will need to generate a pre-block and are collecting
 *                  content for it.
 * SOL_AFTER_PRE -- we might need to extend the pre-block to multiple lines.
 *                  (depending on whether we see a white-space tok or not)
 * MULTILINE_PRE -- We will wrap one or more previous lines with <pre>
 *                  This line could be part of that pre if we enter PRE_COLLECT state
 * IGNORE        -- nothing to do for the rest of the line.
 * ```
 *
 * Action helpers
 * --------------
 *
 * genPre             : return merge("<pre>$TOKS</pre>" while skipping sol-tr toks, sol-tr toks)
 * processCurrLine    : $TOKS += $PRE_TOKS; $PRE_TOKS = [];
 * purgeBuffers       : convert meta token to ' '; processCurrLine; RET = $TOKS; $TOKS = []; return RET
 * discardCurrLinePre : return merge(genPre, purgeBuffers)
 *
 * Transitions
 * -----------
 *
 * ```
 * + --------------+-----------------+---------------+-------------------------+
 * | Start state   |     Token       | End state     |  Action                 |
 * + --------------+-----------------+---------------+-------------------------+
 * | SOL           | --- nl      --> | SOL           | purgeBuffers            |
 * | SOL           | --- eof     --> | ---           | purgeBuffers            |
 * | SOL           | --- sol-tr  --> | SOL           | TOKS << tok             |
 * | SOL           | --- ws      --> | PRE           | PRE_TOKS = [ wsTok(#) ] |
 * | SOL           | --- other   --> | IGNORE        | purgeBuffers            |
 * + --------------+-----------------+---------------+-------------------------+
 * | PRE           | --- nl      --> | SOL           | purgeBuffers            |
 * | PRE           | --- eof     --> | ---           | purgeBuffers            |
 * | PRE           | --- sol-tr  --> | PRE           | PRE_TOKS << tok         |
 * | PRE           | --- blk tag --> | IGNORE        | purgeBuffers            |
 * | PRE           | --- other   --> | PRE_COLLECT   | PRE_TOKS << tok         |
 * + --------------+-----------------+---------------+-------------------------+
 * | PRE_COLLECT   | --- nl      --> | SOL_AFTER_PRE | processCurrLine         |
 * | PRE_COLLECT   | --- eof     --> | ---           | processCurrLine; genPre |
 * | PRE_COLLECT   | --- blk tag --> | IGNORE        | discardCurrLinePre      |
 * | PRE_COLLECT   | --- other   --> | PRE_COLLECT   | PRE_TOKS << tok         |
 * + --------------+-----------------+---------------+-------------------------+
 * | SOL_AFTER_PRE | --- nl      --> | SOL           | discardCurrLinePre      |
 * | SOL_AFTER_PRE | --- eof     --> | ---           | discardCurrLinePre      |
 * | SOL_AFTER_PRE | --- sol-tr  --> | SOL_AFTER_PRE | PRE_TOKS << tok         |
 * | SOL_AFTER_PRE | --- ws      --> | MULTILINE_PRE | PRE_TOKS << wsTok(#)    |
 * | SOL_AFTER_PRE | --- other   --> | IGNORE        | discardCurrLinePre      |
 * + --------------+-----------------+---------------+-------------------------+
 * | MULTILINE_PRE | --- nl      --> | SOL_AFTER_PRE | processCurrLine         |
 * | MULTILINE_PRE | --- eof     --> | ---           | discardCurrLinePre      |
 * | MULTILINE_PRE | --- sol-tr  --> | SOL_AFTER_PRE | PRE_TOKS << tok         |
 * | MULTILINE_PRE | --- blk tag --> | IGNORE        | discardCurrLinePre      |
 * | MULTILINE_PRE | --- other   --> | PRE_COLLECT   | PRE_TOKS << tok         |
 * + --------------+-----------------+---------------+-------------------------+
 * | IGNORE        | --- eof     --> | ---           | purgeBuffers            |
 * | IGNORE        | --- nl      --> | SOL           | purgeBuffers            |
 * + --------------+-----------------+---------------+-------------------------+
 *
 * # In these states, we assume that the whitespace char is split off from the
 *   the rest of the string.
 * ```
 */
class PreHandler extends TokenHandler {
	// FSM states
	private const STATE_SOL = 1;
	private const STATE_PRE = 2;
	private const STATE_PRE_COLLECT = 3;
	private const STATE_SOL_AFTER_PRE = 4;
	private const STATE_MULTILINE_PRE = 5;
	private const STATE_IGNORE = 6;

	/** @var int */
	private $state;
	/** @var int */
	private $preTSR;
	/** @var array<Token> */
	private $tokens;
	/** @var array<Token|string> */
	private $currLinePreToks;
	/** @var int index of the whitespace token in $currLinePreToks */
	private $wsTkIndex;

	/**
	 * debug string output of FSM states
	 * @return array
	 */
	private static function stateStr(): array {
		return [
			1 => 'sol          ',
			2 => 'pre          ',
			3 => 'pre_collect  ',
			4 => 'sol_after_pre',
			5 => 'multiline_pre',
			6 => 'ignore       '
		];
	}

	/**
	 * Create a token to represent the indent-pre whitespace character.
	 *
	 * Notes about choice of token representation
	 * -------------------------------------------
	 * This token will not make it to the final output and is only present to ensure
	 * DSR computation can account for this whitespace character. This meta tag will
	 * be removed in CleanUp::stripMarkerMetas().
	 *
	 * Given that this token is purely an internal bookkeeping placeholder,
	 * it really does not matter how we represent it as long as
	 * (a) it doesn't impede code comprehension
	 * (b) it is more or less consistent with how other instances of this token behave
	 * (c) it doesn't introduce a lot of special-case handling and checks to deal with it.
	 *
	 * Based on that consideration, we settle for a meta tag because meta tags are transparent
	 * to most token and DOM handlers.
	 *
	 * Notes about DSR computation
	 * ---------------------------
	 * Once we are done with all DOM processing, we expect indent-pre <pre> tags to have
	 * DSR that looks like [ _, _, 1, 0 ], i.e. it has an opening tag width of 1 char and
	 * closing tag width of 0 char. But, since we are now explicitly representing the ws char
	 * as a meta-tag, we <pre> tag will not get a 1-char width during DSR computation since
	 * this meta-tag will consume that width. Accordingly, once we strip this meta-tag in the
	 * cleanup pass, we will reassign its width to the opening tag width of the <pre> tag.
	 *
	 * @return Token
	 */
	public static function newIndentPreWS(): Token {
		return new SelfclosingTagTk( 'meta', [ new KV( 'typeof', 'mw:IndentPreWS' ) ] );
	}

	/**
	 * Does this token or node represent an indent-pre whitespace character?
	 * @param Token|Node|string $tokenOrNode
	 * @return bool
	 */
	public static function isIndentPreWS( $tokenOrNode ): bool {
		if ( $tokenOrNode instanceof Token ) {
			return TokenUtils::hasTypeOf( $tokenOrNode, 'mw:IndentPreWS' );
		} elseif ( $tokenOrNode instanceof Node ) {
			return DOMUtils::hasTypeOf( $tokenOrNode, 'mw:IndentPreWS' );
		} else {
			return false;
		}
	}

	/**
	 * @param TokenTransformManager $manager manager enviroment
	 * @param array $options various configuration options
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
		if ( !empty( $this->options['inlineContext'] ) ) {
			$this->disabled = true;
		} else {
			$this->disabled = false;
			$this->resetState( [] );
		}
	}

	public function resetState( array $opts ): void {
		$this->reset();
	}

	/**
	 * Resets the FSM state with optional any handler enabled
	 */
	private function reset(): void {
		$this->state = self::STATE_SOL;
		// Initialize to zero to deal with indent-pre
		// on the very first line where there is no
		// preceding newline to initialize this.
		$this->preTSR = 0;
		$this->tokens = [];
		$this->currLinePreToks = [];
		$this->wsTkIndex = -1;
		$this->onAnyEnabled = true;
	}

	/**
	 * Switches the FSM to STATE_IGNORE
	 */
	private function moveToIgnoreState(): void {
		$this->onAnyEnabled = false;
		$this->state = self::STATE_IGNORE;
	}

	/**
	 * Wrap buffered tokens with <pre>..</pre>
	 *
	 * @return array
	 */
	private function genPre(): array {
		$ret = [];

		// pre only if we have tokens to enclose
		$n = $i = count( $this->tokens );
		if ( $n > 0 ) {
			$env = $this->env;

			// Don't wrap sol-transparent toks.
			// Find index for last token to wrap.
			$i--;
			while ( $i > 0 ) {
				$t = $this->tokens[$i];
				if ( !( $t instanceof NlTk ) && !TokenUtils::isSolTransparent( $env, $t ) ) {
					break;
				}
				if ( $t instanceof Token && TokenUtils::matchTypeOf( $t, '#^mw:Transclusion/End#' ) ) {
					break;
				}
				$i--;
			}

			// Add pre wrapper around the selected tokens
			$da = null;
			if ( $this->preTSR !== -1 ) {
				$da = new DataParsoid;
				$da->tsr = new SourceRange( $this->preTSR, $this->preTSR );
			}
			$ret = [ new TagTk( 'pre', [], $da ) ];
			for ( $j = 0; $j < $i + 1; $j++ ) {
				$ret[] = $this->tokens[$j];
			}
			$ret[] = new EndTagTk( 'pre' );
			for ( $j = $i + 1; $j < $n; $j++ ) {
				$t = $this->tokens[$j];
				if ( self::isIndentPreWS( $t ) ) {
					$t = ' ';
				}
				$ret[] = $t;
			}
			$this->tokens = [];
		}
		return $ret;
	}

	/**
	 * @param Token|string|null $token
	 * @param bool $metaToWS
	 * - if true, convert the IndentPreWS meta token to ' '.
	 * - if false, leave the meta token as is (it will later be stripped
	 *   by CleanUp::stripMarkerMetas() and the DSR updated)
	 */
	private function processCurrLine( $token = null, bool $metaToWS = false ): void {
		if ( count( $this->currLinePreToks ) > 0 ) {
			if ( $metaToWS && $this->wsTkIndex !== -1 ) {
				$this->currLinePreToks[$this->wsTkIndex] = ' '; // replace meta token with ' '
			}
			PHPUtils::pushArray( $this->tokens, $this->currLinePreToks );
			$this->currLinePreToks = [];
			$this->wsTkIndex = -1;
		}
		if ( $token !== null ) {
			$this->tokens[] = $token;
		}
	}

	/**
	 * Get results and cleanup state
	 *
	 * @param Token|string $token
	 * @return array
	 */
	private function purgeBuffers( $token ): array {
		$this->processCurrLine( $token, true );
		$ret = $this->tokens;
		$this->tokens = [];

		return $ret;
	}

	/**
	 * Discard pre on this line. Generate pre formatting for previous lines, if any.
	 *
	 * @param Token|string $token
	 * @return array
	 */
	private function discardCurrLinePre( $token ): array {
		$ret = $this->genPre();
		PHPUtils::pushArray( $ret, $this->purgeBuffers( $token ) );
		return $ret;
	}

	/**
	 * Initialize a pre TSR
	 *
	 * @param NlTk $nltk
	 * @return int
	 */
	private function initPreTSR( NlTk $nltk ): int {
		$da = $nltk->dataParsoid;
		// tsr->end can never be zero, so safe to use tsr->end to check for null/undefined
		return $da->tsr->end ?? -1;
	}

	/**
	 * @inheritDoc
	 */
	public function onNewline( NlTk $token ): ?TokenHandlerResult {
		$env = $this->env;

		$env->log( 'trace/pre', $this->pipelineId, 'NL    |',
			$this->state, ':',
			self::stateStr()[$this->state], '|',
			static function () use ( $token ) {
				return PHPUtils::jsonEncode( $token );
			}
		);

		// Whenever we move into SOL-state, init preTSR to
		// the newline's tsr->end.  This will later be used
		// to assign 'tsr' values to the <pre> token.

		switch ( $this->state ) {
			case self::STATE_SOL:
			case self::STATE_PRE:
				$ret = $this->purgeBuffers( $token );
				$this->preTSR = self::initPreTSR( $token );
				$this->state = self::STATE_SOL;
				break;

			case self::STATE_MULTILINE_PRE:
			case self::STATE_PRE_COLLECT:
				$this->processCurrLine( $token );
				$ret = [];
				$this->state = self::STATE_SOL_AFTER_PRE;
				break;

			case self::STATE_SOL_AFTER_PRE:
				$ret = $this->discardCurrLinePre( $token );
				$this->state = self::STATE_SOL;
				$this->preTSR = self::initPreTSR( $token );
				break;

			case self::STATE_IGNORE:
				$ret = null; // Signals unmodified token
				$this->reset();
				$this->preTSR = self::initPreTSR( $token );
				break;

			default:
				// probably unreachable but makes phan happy
				$ret = [];
		}

		$env->log( 'debug/pre', $this->pipelineId, 'saved :', $this->tokens );
		$env->log( 'debug/pre', $this->pipelineId, '---->  ',
			static function () use ( $ret ) {
				return PHPUtils::jsonEncode( $ret );
			}
		);

		return new TokenHandlerResult( $ret, true );
	}

	/**
	 * @inheritDoc
	 */
	public function onEnd( EOFTk $token ): ?TokenHandlerResult {
		$this->env->log( 'trace/pre', $this->pipelineId, 'eof   |',
			$this->state, ':',
			self::stateStr()[$this->state], '|',
			static function () use ( $token ) {
				return PHPUtils::jsonEncode( $token );
			}
		);

		switch ( $this->state ) {
			case self::STATE_SOL:
			case self::STATE_PRE:
				$ret = $this->purgeBuffers( $token );
				break;

			case self::STATE_SOL_AFTER_PRE:
			case self::STATE_MULTILINE_PRE:
				$ret = $this->discardCurrLinePre( $token );
				break;

			case self::STATE_PRE_COLLECT:
				$this->processCurrLine();
				$ret = $this->genPre();
				$ret[] = $token;
				break;

			case self::STATE_IGNORE:
				$ret = null;
				break;

			default:
				// Probably unreachable but makes phan happy
				$ret = [];
		}

		$this->env->log( 'debug/pre', $this->pipelineId, 'saved :', $this->tokens );
		$this->env->log( 'debug/pre', $this->pipelineId, '---->  ',
			static function () use ( $ret ){
				return PHPUtils::jsonEncode( $ret );
			}
		);

		return new TokenHandlerResult( $ret, true );
	}

	/**
	 * Get updated pre TSR value
	 *
	 * @param int $tsr
	 * @param Token|string $token
	 * @return int
	 */
	private function getUpdatedPreTSR( int $tsr, $token ): int {
		if ( $token instanceof CommentTk ) {
			$tsr = isset( $token->dataParsoid->tsr ) ? $token->dataParsoid->tsr->end :
				( ( $tsr === -1 ) ? -1 : WTUtils::decodedCommentLength( $token ) + $tsr );
		} elseif ( $token instanceof SelfclosingTagTk ) {
			// meta-tag (cannot compute)
			$tsr = -1;
		} elseif ( $tsr !== -1 ) {
			// string
			$tsr += strlen( $token );
		}
		return $tsr;
	}

	/**
	 * @inheritDoc
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		$env = $this->env;

		$env->log( 'trace/pre', $this->pipelineId, 'any   |',
			$this->state, ':',
			self::stateStr()[$this->state], '|',
			static function () use ( $token ) {
				return PHPUtils::jsonEncode( $token );
			}
		);

		if ( $this->state === self::STATE_IGNORE ) {
			$env->log( 'error', static function () use ( $token ) {
				return '!ERROR! IGNORE! Cannot get here: ' . PHPUtils::jsonEncode( $token );
			} );
			return null;
		}

		$ret = [];
		switch ( $this->state ) {
			case self::STATE_SOL:
				if ( is_string( $token ) && ( $token[0] ?? '' ) === ' ' ) {
					$ret = $this->tokens;
					$this->tokens = [];
					$this->wsTkIndex = 0;
					$this->currLinePreToks = [ self::newIndentPreWS() ];
					$this->state = self::STATE_PRE;
					if ( strlen( $token ) > 1 ) {
						// Treat everything after the first space as a new token
						// (`substr` not `mb_substr` since we know space is ASCII)
						// This is inlined handling of 'case self::PRE'
						// scenario for a string.
						$token = substr( $token, 1 );
						$this->currLinePreToks[] = $token;
						if ( !TokenUtils::isSolTransparent( $this->env, $token ) ) {
							$this->state = self::STATE_PRE_COLLECT;
						}
					}
				} elseif ( TokenUtils::isSolTransparent( $env, $token ) ) {
					// continue watching ...
					// update pre-tsr since we haven't transitioned to PRE yet
					$this->preTSR = $this->getUpdatedPreTSR( $this->preTSR, $token );
					$this->tokens[] = $token;
				} else {
					$ret = $this->purgeBuffers( $token );
					$this->moveToIgnoreState();
				}
				break;

			case self::STATE_PRE:
			case self::STATE_PRE_COLLECT:
			case self::STATE_MULTILINE_PRE:
				if ( !is_string( $token ) && TokenUtils::isWikitextBlockTag( $token->getName() ) ) {
					$ret = $this->state === self::STATE_PRE ?
						$this->purgeBuffers( $token ) : $this->discardCurrLinePre( $token );
					$this->moveToIgnoreState();
				} else {
					$this->currLinePreToks[] = $token;
					if ( !TokenUtils::isSolTransparent( $this->env, $token ) ) {
						$this->state = self::STATE_PRE_COLLECT;
					}
				}
				break;

			case self::STATE_SOL_AFTER_PRE:
				if ( is_string( $token ) && ( $token[0] ?? '' ) === ' ' ) {
					$this->wsTkIndex = count( $this->currLinePreToks );
					$this->currLinePreToks[] = self::newIndentPreWS();
					$this->state = self::STATE_MULTILINE_PRE;
					if ( strlen( $token ) > 1 ) {
						// Treat everything after the first space as a new token
						// (`substr` not `mb_substr` since we know space is ASCII)
						// This is inlined handling of 'case self::MULTILINE_PRE'
						// scenario for a string.
						$token = substr( $token, 1 );
						$this->currLinePreToks[] = $token;
						if ( !TokenUtils::isSolTransparent( $this->env, $token ) ) {
							$this->state = self::STATE_PRE_COLLECT;
						}
					}
				} elseif ( TokenUtils::isSolTransparent( $env, $token ) ) { // continue watching
					$this->currLinePreToks[] = $token;
				} else {
					$ret = $this->discardCurrLinePre( $token );
					$this->moveToIgnoreState();
				}
				break;
		}

		$env->log( 'debug/pre', $this->pipelineId, 'saved :', $this->tokens );
		$env->log( 'debug/pre', $this->pipelineId, '---->  ',
			static function () use ( $ret ) {
				return PHPUtils::jsonEncode( $ret );
			}
		);

		return new TokenHandlerResult( $ret );
	}
}
