<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Tokens;

use Wikimedia\Assert\Assert;

/**
 * Represents a source offset range for a key-value pair.
 */
class KVSourceRange implements \JsonSerializable {

	/**
	 * Source range for the key.
	 * @var SourceRange
	 */
	public $key;

	/**
	 * Source range for the value.
	 * @var SourceRange
	 */
	public $value;

	/**
	 * Create a new key-value source offset range.
	 * @param int $keyStart The start index of the key
	 *   (unicode code points, inclusive)
	 * @param int $keyEnd The end index of the key
	 *   (unicode code points, exclusive)
	 * @param int $valueStart The start index of the value
	 *   (unicode code points, inclusive)
	 * @param int $valueEnd The end index of the value
	 *   (unicode code points, exclusive)
	 */
	public function __construct( int $keyStart, int $keyEnd, int $valueStart, int $valueEnd ) {
		$this->key = new SourceRange( $keyStart, $keyEnd );
		$this->value = new SourceRange( $valueStart, $valueEnd );
	}

	/**
	 * Return a new key-value source offset range shifted by $amount.
	 * @param int $amount The amount to shift by
	 * @return KVSourceRange
	 */
	public function offset( int $amount ): KVSourceRange {
		return new KVSourceRange(
			$this->key->start + $amount,
			$this->key->end + $amount,
			$this->value->start + $amount,
			$this->value->end + $amount
		);
	}

	/**
	 * Create a new key-value source offset range from an array of
	 * integers (such as created during JSON serialization).
	 * @param int[] $so
	 * @return KVSourceRange
	 */
	public static function fromArray( array $so ): KVSourceRange {
		Assert::invariant(
			count( $so ) === 4,
			'Not enough elements in KVSourceRange array'
		);
		return new KVSourceRange( $so[0], $so[1], $so[2], $so[3] );
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize(): array {
		return [
			$this->key->start,
			$this->key->end,
			$this->value->start,
			$this->value->end,
		];
	}
}
