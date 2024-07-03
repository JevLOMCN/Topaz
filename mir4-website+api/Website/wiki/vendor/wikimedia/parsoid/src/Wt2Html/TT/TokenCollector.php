<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

/**
 * Small utility class that encapsulates the common 'collect all tokens
 * starting from a token of type x until token of type y or (optionally) the
 * end-of-input'.
 */
abstract class TokenCollector extends TokenHandler {
	/** @var Token[][] */
	protected array $scopeStack = [];

	/**
	 * @param TokenTransformManager $manager manager enviroment
	 * @param array $options various configuration options
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
		$this->onAnyEnabled = false;
	}

	/**
	 * Token type to register for ('tag', 'text' etc)
	 * @return string
	 */
	abstract protected function type(): string;

	/**
	 * (optional, only for token type 'tag'): tag name.
	 * @return string
	 */
	abstract protected function name(): string;

	/**
	 * Match the 'end' tokens as closing tag as well (accept unclosed sections).
	 * @return bool
	 */
	abstract protected function toEnd(): bool;

	/**
	 * Whether to transform unmatched end tags. If this returns true,
	 * unbalanced end tags will be passed to transform(). If it returns false,
	 * they will be left in the token stream unmodified.
	 *
	 * @return bool
	 */
	abstract protected function ackEnd(): bool;

	/**
	 * When an end delimiter is found, this function is called with the
	 * collected token array including the start and end delimiters. The
	 * subclass should transform it and return the result.
	 *
	 * @param array $array
	 * @return TokenHandlerResult
	 */
	abstract protected function transformation( array $array ): TokenHandlerResult;

	/**
	 * Handle the delimiter token.
	 * XXX: Adjust to sync phase callback when that is modified!
	 * @param Token $token
	 * @return TokenHandlerResult|null
	 */
	private function onDelimiterToken( Token $token ): ?TokenHandlerResult {
		$haveOpenTag = count( $this->scopeStack ) > 0;
		if ( $token instanceof TagTk ) {
			if ( count( $this->scopeStack ) === 0 ) {
				$this->onAnyEnabled = true;
				// Set up transforms
				$this->env->log( 'debug', 'starting collection on ', $token );
			}

			// Push a new scope
			$newScope = [];
			$this->scopeStack[] = &$newScope;
			$newScope[] = $token;

			return new TokenHandlerResult( [] );
		} elseif ( $token instanceof SelfclosingTagTk ) {
			// We need to handle <ref /> for example, so call the handler.
			return $this->transformation( [ $token, $token ] );
		} elseif ( $haveOpenTag ) {
			// EOFTk or EndTagTk
			$this->env->log( 'debug', 'finishing collection on ', $token );

			// Pop top scope and push token onto it
			$activeTokens = array_pop( $this->scopeStack );
			$activeTokens[] = $token;

			if ( $token instanceof EndTagTk ) {
				// Transformation receives all collected tokens instead of a single token.
				$res = $this->transformation( $activeTokens );

				if ( count( $this->scopeStack ) === 0 ) {
					$this->onAnyEnabled = false;
					return $res;
				} else {
					// Merge tokens onto parent scope and return [].
					// Only when we hit the bottom of the stack,
					// we will return the collapsed token stream.
					$topScope = array_pop( $this->scopeStack );
					array_push( $this->scopeStack, array_merge( $topScope, $res->tokens ) );
					return new TokenHandlerResult( [] );
				}
			} else {
				// EOF -- collapse stack!
				$allToks = [];
				for ( $i = 0, $n = count( $this->scopeStack );  $i < $n;  $i++ ) {
					PHPUtils::pushArray( $allToks, $this->scopeStack[$i] );
				}
				PHPUtils::pushArray( $allToks, $activeTokens );

				$res = $this->toEnd() ? $this->transformation( $allToks ) : new TokenHandlerResult( $allToks );
				if ( $res->tokens !== null
					&& count( $res->tokens )
					&& !( PHPUtils::lastItem( $res->tokens ) instanceof EOFTk )
				) {
					$this->env->log( 'error', $this::name(), 'handler dropped the EOFTk!' );

					// preserve the EOFTk
					$res->tokens[] = $token;
				}

				$this->scopeStack = [];
				$this->onAnyEnabled = false;
				return $res;
			}
		} else {
			// EndTagTk should be the only one that can reach here.
			Assert::invariant( $token instanceof EndTagTk, 'Expected an end tag.' );
			if ( $this->ackEnd() ) {
				return $this->transformation( [ $token ] );
			} else {
				// An unbalanced end tag. Ignore it.
				return null;
			}
		}
	}

	/**
	 * Handle 'any' token in between delimiter tokens. Activated when
	 * encountering the delimiter token, and collects all tokens until the end
	 * token is reached.
	 * @param Token|string $token
	 * @return TokenHandlerResult
	 */
	private function onAnyToken( $token ): TokenHandlerResult {
		// Simply collect anything ordinary in between
		$this->scopeStack[count( $this->scopeStack ) - 1][] = $token;
		return new TokenHandlerResult( [] );
	}

	/**
	 * This helper function will build a meta token in the right way for these tags.
	 * @param TokenTransformManager $manager
	 * @param string $tokenName
	 * @param bool $isEnd
	 * @param SourceRange $tsr
	 * @param ?string $src
	 * @return SelfclosingTagTk
	 */
	public static function buildMetaToken(
		TokenTransformManager $manager, string $tokenName, bool $isEnd,
		SourceRange $tsr, ?string $src
	): SelfclosingTagTk {
		if ( $isEnd ) {
			$tokenName .= '/End';
		}

		$srcText = $manager->getFrame()->getSrcText();
		$newSrc = $tsr->substr( $srcText );
		$dp = new DataParsoid;
		$dp->tsr = $tsr;
		$dp->src = $newSrc;

		return new SelfclosingTagTk( 'meta',
			[ new KV( 'typeof', $tokenName ) ],
			$dp
		);
	}

	protected static function buildStrippedMetaToken(
		TokenTransformManager $manager, string $tokenName, Token $startDelim,
		?Token $endDelim
	): SelfclosingTagTk {
		$da = $startDelim->dataParsoid;
		$tsr0 = $da ? $da->tsr : null;
		$t0 = $tsr0 ? $tsr0->start : null;
		$t1 = null;

		if ( $endDelim !== null ) {
			$da = $endDelim->dataParsoid ?? null;
			$tsr1 = $da ? $da->tsr : null;
			$t1 = $tsr1 ? $tsr1->end : null;
		} else {
			$t1 = strlen( $manager->getFrame()->getSrcText() );
		}

		return self::buildMetaToken( $manager, $tokenName, false, new SourceRange( $t0, $t1 ), '' );
	}

	/**
	 * @inheritDoc
	 */
	public function onTag( Token $token ): ?TokenHandlerResult {
		return $token->getName() === $this->name() ? $this->onDelimiterToken( $token ) : null;
	}

	/**
	 * @inheritDoc
	 */
	public function onEnd( EOFTk $token ): ?TokenHandlerResult {
		return $this->onAnyEnabled ? $this->onDelimiterToken( $token ) : null;
	}

	/**
	 * @inheritDoc
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		return $this->onAnyToken( $token );
	}
}
