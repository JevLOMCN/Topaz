<?php

namespace Wikimedia\WikiPEG;

class Expectation implements \JsonSerializable {
	/** @var string */
	public $type;
	/** @var string|null */
	public $value;
	/** @var string */
	public $description;

	/**
	 * @param array{type:string,value?:?string,description:string} $info
	 *   The expectation array, which comes from the generated code, with keys:
	 *   - type: The failed node type
	 *   - value: The actual string which failed to match, may be absent for some node types
	 *   - description: A readable description of the value
	 */
	public function __construct( $info ) {
		$this->type = $info['type'];
		$this->value = $info['value'] ?? null;
		$this->description = $info['description'];
	}

	/**
	 * Compare two Expectation objects, and return a value less than, equal to,
	 * or greater than zero, depending on whether $a is less than, equal to, or
	 * greater than $b respectively.
	 *
	 * This is used to sort expectations before combining them into SyntaxError
	 * descriptions.
	 *
	 * @param Expectation $a
	 * @param Expectation $b
	 * @return int
	 */
	public static function compare( Expectation $a, Expectation $b ) {
		return $a->type <=> $b->type
					  ?: $a->value <=> $b->value
					  ?: $a->description <=> $b->description;
	}

	/**
	 * Emit a JSON serialization similar to JS, for testing
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize(): array {
		return [
			'type' => $this->type,
			'value' => $this->value,
			'description' => $this->description
		];
	}
}
