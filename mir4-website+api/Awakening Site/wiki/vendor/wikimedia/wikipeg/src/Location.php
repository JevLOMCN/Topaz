<?php

namespace Wikimedia\WikiPEG;

class Location implements \JsonSerializable {
	/** @var int 0-based byte offset into the input string */
	public $offset;
	/** @var int 1-based line number */
	public $line;
	/** @var int 1-based column number */
	public $column;

	/**
	 * @param int $offset
	 * @param int $line
	 * @param int $column
	 */
	public function __construct( $offset, $line, $column ) {
		$this->offset = $offset;
		$this->line = $line;
		$this->column = $column;
	}

	/** @return string */
	public function __toString() {
		return "{$this->line}:{$this->column}";
	}

	/**
	 * Emit a JSON serialization similar to JS, for testing
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize(): array {
		return [
			'offset' => $this->offset,
			'line' => $this->line,
			'column' => $this->column,
		];
	}
}
