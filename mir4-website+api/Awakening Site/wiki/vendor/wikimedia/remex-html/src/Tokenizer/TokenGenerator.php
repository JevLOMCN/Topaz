<?php

namespace Wikimedia\RemexHtml\Tokenizer;

/**
 * This class provides a convenient iterative view of the token stream,
 * implemented as a Generator. It is intended to be used as follows:
 *
 *   foreach ( TokenGenerator::generate( $html, [] ) as $token ) {
 *       ...
 *   }
 *
 * Performance is slightly slower than a plain TokenHandler, probably due to
 * the need to convert event parameters to associative arrays.
 */
class TokenGenerator {
	/** @var TokenGeneratorHandler */
	protected $handler;

	/** @var Tokenizer */
	protected $tokenizer;

	/**
	 * @param string $text
	 * @param array $options Options passed through to Tokenizer
	 */
	protected function __construct( $text, $options ) {
		$this->handler = new TokenGeneratorHandler;
		$this->tokenizer = new Tokenizer( $this->handler, $text, $options );
	}

	/**
	 * Get a Generator which iterates over all tokens in the supplied HTML
	 *
	 * @param string $text The HTML
	 * @param array $options The Tokenizer options, see Tokenizer::__construct()
	 * @return \Generator
	 */
	public static function generate( $text, $options ) {
		$tg = new self( $text, $options );
		$tg->tokenizer->beginStepping();
		while ( $tg->tokenizer->step() ) {
			foreach ( $tg->handler->tokens as $token ) {
				yield $token;
			}
			$tg->handler->tokens = [];
		}
		foreach ( $tg->handler->tokens as $token ) {
			yield $token;
		}
	}
}
