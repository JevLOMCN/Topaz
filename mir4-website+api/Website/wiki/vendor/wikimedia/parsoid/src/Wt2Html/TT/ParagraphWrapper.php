<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\UnreachableException;
use Wikimedia\Parsoid\Tokens\CommentTk;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\NlTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

/**
 * Insert paragraph tags where needed -- smartly and carefully
 * -- there is much fun to be had mimicking "wikitext visual newlines"
 * behavior as implemented by the PHP parser.
 */
class ParagraphWrapper extends TokenHandler {

	private bool $inPre = false;
	private bool $hasOpenPTag = false;
	private bool $inBlockElem = false;
	private bool $inBlockquote = false;

	/**
	 * The state machine in the PreHandler is line based and only suppresses
	 * indent-pres when encountering blocks on a line.  However, the legacy
	 * parser's `doBlockLevels` has a concept of being "$inBlockElem", which
	 * is mimicked here.  Rather than replicate that awareness in both passes,
	 * we piggyback on it here to undo indent-pres when they're found to be
	 * undesirable.
	 */
	private bool $undoIndentPre = false;
	private array $tokenBuffer = [];
	private array $nlWsTokens = [];
	private int $newLineCount = 0;

	/** @var array */
	private $currLineTokens = [];
	/** @var bool */
	private $currLineHasWrappableTokens = false;
	/** @var bool */
	private $currLineBlockTagSeen = false;
	/** @var bool */
	private $currLineBlockTagOpen = false;

	/**
	 * Constructor for paragraph wrapper.
	 * @param TokenTransformManager $manager manager enviroment
	 * @param array $options various configuration options
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
		// Disable p-wrapper
		$this->disabled = !empty( $this->options['inlineContext'] );
		$this->reset();
	}

	/**
	 * @inheritDoc
	 */
	public function onNewline( NlTk $token ): ?TokenHandlerResult {
		return $this->inPre ? null : $this->onNewlineOrEOF( $token );
	}

	/**
	 * @inheritDoc
	 */
	public function onEnd( EOFTk $token ): ?TokenHandlerResult {
		return $this->onNewlineOrEOF( $token );
	}

	/**
	 * Reset the token buffer and related info
	 * This is the ordering of buffered tokens and how they should get emitted:
	 *
	 * token-buffer         (from previous lines if newLineCount > 0)
	 * newline-ws-tokens    (buffered nl+sol-transparent tokens since last non-nl-token)
	 * current-line-tokens  (all tokens after newline-ws-tokens)
	 *
	 * newline-token-count is > 0 only when we encounter multiple "empty lines".
	 *
	 * Periodically, when it is clear where an open/close p-tag is required, the buffers
	 * are collapsed and emitted. Wherever tokens are buffered/emitted, verify that this
	 *  order is preserved.
	 */
	private function reset(): void {
		$this->resetBuffers();
		$this->resetCurrLine();
		$this->hasOpenPTag = false;
		$this->inPre = false;
		$this->undoIndentPre = false;
		// NOTE: This flag is the local equivalent of what we're mimicking with
		// the 'inlineContext' pipeline option.
		$this->inBlockElem = false;
		$this->inBlockquote = false;
	}

	/**
	 * Reset the token buffer and new line info
	 *
	 */
	private function resetBuffers(): void {
		$this->tokenBuffer = [];
		$this->nlWsTokens = [];
		$this->newLineCount = 0;
	}

	/**
	 * Reset the current line info
	 *
	 */
	private function resetCurrLine(): void {
		if ( $this->currLineBlockTagSeen ) {
			$this->inBlockElem = $this->currLineBlockTagOpen;
		}
		$this->currLineTokens = [];
		$this->currLineHasWrappableTokens = false;
		$this->currLineBlockTagSeen = false;
		$this->currLineBlockTagOpen = false;
	}

	/**
	 * Process the current buffer contents and token provided
	 *
	 * @param Token|string $token token
	 * @param bool $flushCurrentLine option to flush current line or preserve it
	 * @return array
	 */
	private function processBuffers( $token, bool $flushCurrentLine ): array {
		$res = $this->processPendingNLs();
		$this->currLineTokens[] = $token;
		if ( $flushCurrentLine ) {
			PHPUtils::pushArray( $res, $this->currLineTokens );
			$this->resetCurrLine();
		}
		$this->env->log( 'trace/p-wrap', $this->pipelineId, '---->  ', static function () use( $res ) {
			return PHPUtils::jsonEncode( $res );
		} );
		return $res;
	}

	/**
	 * Process and flush existing buffer contents
	 *
	 * @param Token|string $token token
	 * @return array
	 */
	private function flushBuffers( $token ): array {
		Assert::invariant( $this->newLineCount === 0, "PWrap: Trying to flush buffers with pending newlines" );

		$this->currLineTokens[] = $token;
		// Juggle the array reference count to allow us to append to it without
		// copying the array
		$resToks = $this->tokenBuffer;
		$nlWsTokens = $this->nlWsTokens;
		$this->resetBuffers();
		PHPUtils::pushArray( $resToks, $nlWsTokens );
		$this->env->log( 'trace/p-wrap', $this->pipelineId, '---->  ',
			static function () use( $resToks ) {
				return PHPUtils::jsonEncode( $resToks );
			} );
		return $resToks;
	}

	/**
	 * Append tokens from the newline/whitespace buffer to the output array
	 * until a newline is encountered. Increment the offset reference. Return
	 * the newline token.
	 *
	 * @param array &$out array to append to
	 * @param int &$offset The offset reference to update
	 * @return Token
	 */
	public function processOneNlTk( array &$out, &$offset ) {
		$n = count( $this->nlWsTokens );
		while ( $offset < $n ) {
			$t = $this->nlWsTokens[$offset++];
			if ( $t instanceof NlTk ) {
				return $t;
			} else {
				$out[] = $t;
			}
		}
		throw new UnreachableException( 'nlWsTokens was expected to contain an NlTk.' );
	}

	/**
	 * Search for the opening paragraph tag
	 *
	 * @param array &$out array to process and update
	 */
	private function openPTag( array &$out ): void {
		if ( !$this->hasOpenPTag ) {
			$tplStartIndex = -1;
			// Be careful not to expand template ranges unnecessarily.
			// Look for open markers before starting a p-tag.
			$countOut = count( $out );
			for ( $i = 0; $i < $countOut; $i++ ) {
				$t = $out[$i];
				if ( !is_string( $t ) && $t->getName() === 'meta' ) {
					if ( TokenUtils::hasTypeOf( $t, 'mw:Transclusion' ) ) {
						// We hit a start tag and everything before it is sol-transparent.
						$tplStartIndex = $i;
						continue;
					} elseif ( TokenUtils::matchTypeOf( $t, '#^mw:Transclusion/#' ) ) {
						// End tag. All tokens before this are sol-transparent.
						// Let us leave them all out of the p-wrapping.
						$tplStartIndex = -1;
						continue;
					} elseif ( TokenUtils::isAnnotationStartToken( $t ) ) {
						break;
					}
				}
				// Not a transclusion meta; Check for nl/sol-transparent tokens
				// and leave them out of the p-wrapping.
				if ( !TokenUtils::isSolTransparent( $this->env, $t ) && !( $t instanceof NlTk ) ) {
					break;
				}
			}
			if ( $tplStartIndex > -1 ) {
				$i = $tplStartIndex;
			}
			array_splice( $out, $i, 0, [ new TagTk( 'p' ) ] );
			$this->hasOpenPTag = true;
		}
	}

	/**
	 * Search for the closing paragraph tag
	 *
	 * @param array &$out array to process and update
	 */
	private function closeOpenPTag( array &$out ): void {
		if ( $this->hasOpenPTag ) {
			$tplEndIndex = -1;
			// Be careful not to expand template ranges unnecessarily.
			// Look for open markers before closing.
			for ( $i = count( $out ) - 1; $i > -1; $i-- ) {
				$t = $out[$i];
				if ( !is_string( $t ) && $t->getName() === 'meta' ) {
					if ( TokenUtils::hasTypeOf( $t, 'mw:Transclusion' ) ) {
						// We hit a start tag and everything after it is sol-transparent.
						// Don't include the sol-transparent tags OR the start tag.
						$tplEndIndex = -1;
						continue;
					} elseif ( TokenUtils::matchTypeOf( $t, '#^mw:Transclusion/#' ) ) {
						// End tag. The rest of the tags past this are sol-transparent.
						// Let us leave them all out of the p-wrapping.
						$tplEndIndex = $i;
						continue;
					} elseif ( TokenUtils::isAnnotationEndToken( $t ) ) {
						break;
					}
				}
				// Not a transclusion meta; Check for nl/sol-transparent tokens
				// and leave them out of the p-wrapping.
				if ( !TokenUtils::isSolTransparent( $this->env, $t ) && !( $t instanceof NlTk ) ) {
					break;
				}
			}
			if ( $tplEndIndex > -1 ) {
				$i = $tplEndIndex;
			}
			array_splice( $out, $i + 1, 0, [ new EndTagTk( 'p' ) ] );
			$this->hasOpenPTag = false;
		}
	}

	/**
	 * Handle newline tokens
	 *
	 * @param Token $token token
	 * @return TokenHandlerResult
	 */
	private function onNewlineOrEOF( Token $token ): TokenHandlerResult {
		$this->env->log( 'trace/p-wrap', $this->pipelineId, 'NL    |',
			static function () use( $token ) {
				return PHPUtils::jsonEncode( $token );
			} );
		if ( $this->currLineBlockTagSeen ) {
			$this->closeOpenPTag( $this->currLineTokens );
		} elseif ( !$this->inBlockElem && !$this->hasOpenPTag && $this->currLineHasWrappableTokens ) {
			$this->openPTag( $this->currLineTokens );
		}

		// Assertion to catch bugs in p-wrapping; both cannot be true.
		if ( $this->newLineCount > 0 && count( $this->currLineTokens ) > 0 ) {
			$this->env->log( 'error/p-wrap', 'Failed assertion in onNewlineOrEOF: newline-count:',
			$this->newLineCount, '; current line tokens: ', PHPUtils::jsonEncode( $this->currLineTokens ) );
		}

		PHPUtils::pushArray( $this->tokenBuffer, $this->currLineTokens );

		if ( $token instanceof EOFTk ) {
			$this->nlWsTokens[] = $token;
			$this->closeOpenPTag( $this->tokenBuffer );
			$res = $this->processPendingNLs();
			$this->reset();
			$this->env->log( 'trace/p-wrap', $this->pipelineId, '---->  ', static function () use( $res ) {
				return PHPUtils::jsonEncode( $res );
			} );
			return new TokenHandlerResult( $res, true );
		} else {
			$this->resetCurrLine();
			$this->newLineCount++;
			$this->nlWsTokens[] = $token;
			return new TokenHandlerResult( [] );
		}
	}

	/**
	 * Process pending newlines
	 *
	 * @return array
	 */
	private function processPendingNLs(): array {
		$resToks = $this->tokenBuffer;
		$newLineCount = $this->newLineCount;
		$nlTk = null;
		$nlOffset = 0;

		$this->env->log( 'trace/p-wrap', $this->pipelineId, '        NL-count: ',
			$newLineCount );

		if ( $newLineCount >= 2 && !$this->inBlockElem ) {
			$this->closeOpenPTag( $resToks );

			// First is emitted as a literal newline
			$resToks[] = $this->processOneNlTk( $resToks, $nlOffset );
			$newLineCount -= 1;

			$remainder = $newLineCount % 2;

			while ( $newLineCount > 0 ) {
				$nlTk = $this->processOneNlTk( $resToks, $nlOffset );
				if ( $newLineCount % 2 === $remainder ) {
					if ( $this->hasOpenPTag ) {
						$resToks[] = new EndTagTk( 'p' );
						$this->hasOpenPTag = false;
					}
					if ( $newLineCount > 1 ) {
						$resToks[] = new TagTk( 'p' );
						$this->hasOpenPTag = true;
					}
				} else {
					$resToks[] = new SelfclosingTagTk( 'br' );
				}
				$resToks[] = $nlTk;
				$newLineCount -= 1;
			}
		}

		if ( $this->currLineBlockTagSeen ) {
			$this->closeOpenPTag( $resToks );
			if ( $newLineCount === 1 ) {
				$resToks[] = $this->processOneNlTk( $resToks, $nlOffset );
			}
		}

		// Gather remaining ws and nl tokens
		for ( $i = $nlOffset; $i < count( $this->nlWsTokens ); $i++ ) {
			$resToks[] = $this->nlWsTokens[$i];
		}

		// reset buffers
		$this->resetBuffers();

		return $resToks;
	}

	/**
	 * @inheritDoc
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		$this->env->log( 'trace/p-wrap', $this->pipelineId, 'ANY   |',
			static function () use( $token ) {
				return PHPUtils::jsonEncode( $token );
			} );
		$res = null;
		if ( $token instanceof TagTk && $token->getName() === 'pre'
			 && !TokenUtils::isHTMLTag( $token )
		) {
			if ( $this->inBlockElem || $this->inBlockquote ) {
				$this->undoIndentPre = true;
				if ( $this->newLineCount === 0 ) {
					return new TokenHandlerResult( $this->flushBuffers( '' ) );
				} else {
					return new TokenHandlerResult( [] );
				}
			} else {
				$this->inPre = true;
				// This will put us `inBlockElem`, so we need the extra `!inPre`
				// condition below.  Presumably, we couldn't have entered
				// `inBlockElem` while being `inPre`.  Alternatively, we could say
				// that indent-pre is "never suppressing" and set the `blockTagOpen`
				// flag to false. The point of all this is that we want to close
				// any open p-tags.
				$this->currLineBlockTagSeen = true;
				$this->currLineBlockTagOpen = true;
				// skip ensures this doesn't hit the AnyHandler
				return new TokenHandlerResult( $this->processBuffers( $token, true ) );
			}
		} elseif ( $token instanceof EndTagTk && $token->getName() === 'pre' &&
			!TokenUtils::isHTMLTag( $token )
		) {
			if ( ( $this->inBlockElem && !$this->inPre ) || $this->inBlockquote ) {
				$this->undoIndentPre = false;
				// No pre-tokens inside block tags -- swallow it.
				return new TokenHandlerResult( [] );
			} else {
				$this->inPre = false;
				$this->currLineBlockTagSeen = true;
				$this->currLineBlockTagOpen = false;
				$this->env->log( 'trace/p-wrap', $this->pipelineId, '---->  ',
					static function () use( $token ) {
						return PHPUtils::jsonEncode( $token );
					} );
				$res = [ $token ];
				return new TokenHandlerResult( $res );
			}
		} elseif ( $token instanceof EOFTk || $this->inPre ) {
			$this->env->log( 'trace/p-wrap', $this->pipelineId, '---->  ',
				static function () use( $token ) {
					return PHPUtils::jsonEncode( $token );
				}
			 );
			$res = [ $token ];
			return new TokenHandlerResult( $res );
		} elseif ( $token instanceof CommentTk
			|| is_string( $token ) && preg_match( '/^[\t ]*$/D', $token )
			|| TokenUtils::isEmptyLineMetaToken( $token )
		) {
			if ( $this->newLineCount === 0 ) {
				// Since we have no pending newlines to trip us up,
				// no need to buffer -- just flush everything
				return new TokenHandlerResult( $this->flushBuffers( $token ) );
			} else {
				// We are in buffering mode waiting till we are ready to
				// process pending newlines.
				$this->nlWsTokens[] = $token;
				return new TokenHandlerResult( [] );
			}
		} elseif ( !is_string( $token ) &&
			// T186965: <style> behaves similarly to sol transparent tokens in
			// that it doesn't open/close paragraphs, but also doesn't induce
			// a new paragraph by itself.
			( TokenUtils::isSolTransparent( $this->env, $token ) || $token->getName() === 'style' )
		) {
			if ( $this->undoIndentPre && PreHandler::isIndentPreWS( $token ) ) {
				$this->nlWsTokens[] = ' ';
				return new TokenHandlerResult( [] );
			} elseif ( $this->newLineCount === 0 ) {
				// Since we have no pending newlines to trip us up,
				// no need to buffer -- just flush everything
				return new TokenHandlerResult( $this->flushBuffers( $token ) );
			} elseif ( $this->newLineCount === 1 ) {
				// Swallow newline, whitespace, comments, and the current line
				PHPUtils::pushArray( $this->tokenBuffer, $this->nlWsTokens );
				PHPUtils::pushArray( $this->tokenBuffer, $this->currLineTokens );
				$this->newLineCount = 0;
				$this->nlWsTokens = [];
				$this->resetCurrLine();

				// But, don't process the new token yet.
				$this->currLineTokens[] = $token;
				return new TokenHandlerResult( [] );
			} else {
				return new TokenHandlerResult( $this->processBuffers( $token, false ) );
			}
		} else {
			if ( !is_string( $token ) ) {
				$name = $token->getName();
				if ( isset( Consts::$wikitextBlockElems[$name] ) ) {
					$this->currLineBlockTagSeen = true;
					$this->currLineBlockTagOpen = true;
					if (
						( isset( Consts::$blockElems[$name] ) && $token instanceof EndTagTk ) ||
						( isset( Consts::$antiBlockElems[$name] ) && !$token instanceof EndTagTk ) ||
						isset( Consts::$neverBlockElems[$name] )
					) {
						$this->currLineBlockTagOpen = false;
					}
				}
				if ( $name === 'blockquote' ) {
					$this->inBlockquote = !( $token instanceof EndTagTk );
				}
			}
			$this->currLineHasWrappableTokens = true;
			return new TokenHandlerResult( $this->processBuffers( $token, false ) );
		}
	}
}
