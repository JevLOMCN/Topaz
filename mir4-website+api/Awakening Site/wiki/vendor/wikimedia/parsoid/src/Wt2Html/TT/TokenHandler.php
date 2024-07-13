<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\NlTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

abstract class TokenHandler {
	/** @var Env */
	protected $env;
	/** @var TokenTransformManager */
	protected $manager;
	/** @var int|null */
	protected $pipelineId;
	/** @var array */
	protected $options;
	/** This is set if the token handler is disabled for the entire pipeline. */
	protected bool $disabled = false;
	/**
	 * This is set/reset by the token handlers at various points in the token stream based on what
	 * is encountered. This only enables/disables the onAny handler.
	 */
	protected bool $onAnyEnabled = true;
	/** @var bool */
	protected $atTopLevel = false;

	/**
	 * @param TokenTransformManager $manager The manager for this stage of the parse.
	 * @param array $options Any options for the expander.
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		$this->manager = $manager;
		$this->env = $manager->getEnv();
		$this->options = $options;
	}

	public function setPipelineId( int $id ): void {
		$this->pipelineId = $id;
	}

	/**
	 * Resets any internal state for this token handler.
	 *
	 * @param array $options
	 */
	public function resetState( array $options ): void {
		$this->atTopLevel = $options['toplevel'] ?? false;
	}

	/**
	 * Is this transformer disabled?
	 * @return bool
	 */
	public function isDisabled(): bool {
		return $this->disabled;
	}

	/**
	 * This handler is called for EOF tokens only
	 * @param EOFTk $token EOF token to be processed
	 * @return TokenHandlerResult|null A TokenHandlerResult, or null to efficiently
	 *   indicate that the input token is unchanged.
	 */
	public function onEnd( EOFTk $token ): ?TokenHandlerResult {
		return null;
	}

	/**
	 * This handler is called for newline tokens only
	 * @param NlTk $token Newline token to be processed
	 * @return TokenHandlerResult|null A TokenHandlerResult, or null to efficiently
	 *   indicate that the input token is unchanged.
	 */
	public function onNewline( NlTk $token ): ?TokenHandlerResult {
		return null;
	}

	/**
	 * This handler is called for tokens that are not EOFTk or NLTk tokens.
	 * The handler may choose to process only specific kinds of tokens.
	 * For example, a list handler may only process 'listitem' TagTk tokens.
	 *
	 * @param Token $token Token to be processed
	 * @return TokenHandlerResult|null A TokenHandlerResult, or null to efficiently
	 *   indicate that the input token is unchanged.
	 */
	public function onTag( Token $token ): ?TokenHandlerResult {
		return null;
	}

	/**
	 * This handler is called for *all* tokens in the token stream except if
	 * (a) The more specific handlers above modified the token
	 * (b) the more specific handlers (onTag, onEnd, onNewline) have set
	 *     the skip flag in their return values.
	 * (c) this handlers 'active' flag is set to false (can be set by any
	 *     of the handlers).
	 *
	 * @param Token|string $token Token to be processed
	 * @return TokenHandlerResult|null A TokenHandlerResult, or null to efficiently
	 *   indicate that the input token is unchanged.
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		return null;
	}

	/**
	 * @param mixed $token
	 * @param TokenHandlerResult $res
	 * @return bool
	 */
	private function isModified( $token, $res ): bool {
		return $res->tokens !== null
			&& $res->tokens !== [ $token ];
	}

	/**
	 * Push an input array of tokens through the transformer
	 * and return the transformed tokens
	 * @param array $tokens
	 * @return array
	 */
	public function process( $tokens ): array {
		$accum = [];
		foreach ( $tokens as $token ) {
			$res = null;
			$resTokens = null; // Not needed but helpful for code comprehension
			if ( $token instanceof NlTk ) {
				$res = $this->onNewline( $token );
			} elseif ( $token instanceof EOFTk ) {
				$res = $this->onEnd( $token );
			} elseif ( !is_string( $token ) ) {
				$res = $this->onTag( $token );
			} else {
				$res = null;
			}

			$modified = $res && $this->isModified( $token, $res );
			if ( $modified ) {
				$resTokens = $res->tokens;
			} elseif ( $this->onAnyEnabled && ( !$res || !$res->skipOnAny ) ) {
				$res = $this->onAny( $token );
				$modified = $res && $this->isModified( $token, $res );
				if ( $modified ) {
					$resTokens = $res->tokens;
				}
			}

			if ( !$modified ) {
				$accum[] = $token;
			} elseif ( $resTokens ) {
				// Avoid array_merge() -- see https://w.wiki/3zvE
				PHPUtils::pushArray( $accum, $resTokens );
			}
		}

		return $accum;
	}
}
