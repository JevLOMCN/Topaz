<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Tokens;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Utils\PHPUtils;

/**
 * Represents a source offset range.
 */
class SourceRange implements \JsonSerializable {

	/**
	 * Offset of the first character (range start is inclusive).
	 * @var ?int
	 */
	public $start;

	/**
	 * Offset just past the last character (range end is exclusive).
	 * @var ?int
	 */
	public $end;

	/**
	 * Create a new source offset range.
	 * @param ?int $start The starting index (UTF-8 byte count, inclusive)
	 * @param ?int $end The ending index (UTF-8 byte count, exclusive)
	 */
	public function __construct( ?int $start, ?int $end ) {
		$this->start = $start;
		$this->end = $end;
	}

	/**
	 * Return a KVSourceRange where this SourceRange is the key,
	 * and the value has zero length.
	 * @return KVSourceRange
	 */
	public function expandTsrK(): KVSourceRange {
		return new KVSourceRange(
			$this->start, $this->end, $this->end, $this->end
		);
	}

	/**
	 * Return a KVSourceRange where this SourceRange is the value,
	 * and the key has zero length.
	 * @return KVSourceRange
	 */
	public function expandTsrV(): KVSourceRange {
		return new KVSourceRange(
			$this->start, $this->start, $this->start, $this->end
		);
	}

	/**
	 * Return a KVSourceRange by using this SourceRange for the key
	 * and the given SourceRange parameter for the value.
	 * @param SourceRange $value
	 * @return KVSourceRange
	 */
	public function join( SourceRange $value ): KVSourceRange {
		return new KVSourceRange(
			$this->start, $this->end, $value->start, $value->end
		);
	}

	/**
	 * Return the substring of the given string corresponding to this
	 * range.
	 * @param string $str The source text string
	 * @return string
	 */
	public function substr( string $str ): string {
		$start = $this->start;
		$length = $this->length();
		Assert::invariant( ( $start ?? -1 ) >= 0, "Bad SourceRange start" );
		// @phan-suppress-next-line PhanCoalescingNeverNull
		Assert::invariant( ( $length ?? -1 ) >= 0, "Bad SourceRange length" );
		return PHPUtils::safeSubstr( $str, $start, $length );
	}

	/**
	 * Return a new source range shifted by $amount.
	 * @param int $amount The amount to shift by
	 * @return SourceRange
	 */
	public function offset( int $amount ) {
		/* PHP 7.3 won't allow a return type declaration on this static
		* function, since DomSourceRange::offset's return differs */
		return new SourceRange( $this->start + $amount, $this->end + $amount );
	}

	/**
	 * Return the length of this source range.
	 * @return int
	 */
	public function length(): int {
		return $this->end - $this->start;
	}

	/**
	 * Create a new source offset range from an array of
	 * integers (such as created during JSON serialization).
	 * @param int[] $sr
	 * @return SourceRange
	 */
	public static function fromArray( array $sr ) {
		/* PHP 7.3 won't allow a return type declaration on this static
		* function, since DomSourceRange::fromArray's return differs */
		Assert::invariant(
			count( $sr ) === 2,
			'Wrong # of elements in SourceRange array'
		);
		return new SourceRange( $sr[0], $sr[1] );
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize(): array {
		return [ $this->start, $this->end ];
	}
}
