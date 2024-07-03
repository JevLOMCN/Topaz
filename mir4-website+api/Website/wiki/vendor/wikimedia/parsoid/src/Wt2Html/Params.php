<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Utils\TokenUtils;

/**
 * A parameter object wrapper, essentially an array of key/value pairs with a few extra methods.
 */
class Params {
	/** @var KV[] */
	public $args;

	/** @var ?array */
	public $argDict;

	/** @var ?array */
	public $namedArgsDict;

	/**
	 * @param KV[] $args
	 */
	public function __construct( array $args ) {
		$this->args = $args;
		$this->argDict = null;
		$this->namedArgsDict = null;
	}

	/**
	 * FIXME: Used in Parsoid native parser functions implementation
	 * and may not be needed in the final version.
	 *
	 * Converts the args that are an array of ($k, $v) KV objects to
	 * an assocative array mapping $k to $v.
	 *
	 * @return array
	 */
	public function dict(): array {
		if ( $this->argDict === null ) {
			$res = [];
			foreach ( $this->args as $kv ) {
				$key = trim( TokenUtils::tokensToString( $kv->k ) );
				$res[$key] = $kv->v;
			}
			$this->argDict = $res;
		}
		return $this->argDict;
	}

	/**
	 * Converts the args that are an array of ($k, $v) KV objects to
	 * an assocative array mapping $k to $v while handling named and
	 * locational orgs and trimming whitespace around the keys.
	 *
	 * @return array
	 */
	public function named(): array {
		if ( $this->namedArgsDict === null ) {
			$n = 1;
			$out = [];
			$namedArgs = [];

			foreach ( $this->args as $kv ) {
				// FIXME: Also check for whitespace-only named args!
				$k = $kv->k;
				$v = $kv->v;
				if ( is_string( $k ) ) {
					$k = trim( $k );
				}
				if ( !is_array( $k ) &&
					// Check for blank named parameters
					$kv->srcOffsets->key->end === $kv->srcOffsets->value->start
				) {
					$out[(string)$n] = $v;
					$n++;
				} elseif ( is_string( $k ) ) {
					$namedArgs[$k] = true;
					$out[$k] = $v;
				} else {
					$k = trim( TokenUtils::tokensToString( $k ) );
					$namedArgs[$k] = true;
					$out[$k] = $v;
				}
			}
			$this->namedArgsDict = [ 'namedArgs' => $namedArgs, 'dict' => $out ];
		}

		return $this->namedArgsDict;
	}

	/**
	 * FIXME: Used in Parsoid native parser functions implementation
	 * and may not be needed in the final version.
	 *
	 * @param int $start
	 * @param int $end
	 * @return array
	 */
	public function getSlice( int $start, int $end ): array {
		$out = [];
		$args = array_slice( $this->args, $start, $end /*CHECK THIS*/ );
		foreach ( $args as $kv ) {
			$out[] = new KV( $kv->k, TokenUtils::tokensToString( $kv->v ), $kv->srcOffsets );
		}
		return $out;
	}
}
