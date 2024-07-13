<?php

namespace Wikimedia\WikiPEG;

class LocationRange implements \JsonSerializable {
	/** @var Location */
	public $start;

	/** @var Location */
	public $end;

	/**
	 * @param int $startOffset
	 * @param int $startLine
	 * @param int $startColumn
	 * @param int $endOffset
	 * @param int $endLine
	 * @param int $endColumn
	 */
	public function __construct( $startOffset, $startLine, $startColumn, $endOffset, $endLine, $endColumn ) {
		$this->start = new Location( $startOffset, $startLine, $startColumn );
		$this->end = new Location( $endOffset, $endLine, $endColumn );
	}

	/** @return string */
	public function __toString() {
		return "{$this->start}-{$this->end}";
	}

	/**
	 * Emit a JSON serialization similar to JS, for testing
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize(): array {
		return [
			'start' => $this->start,
			'end' => $this->end,
		];
	}
}
