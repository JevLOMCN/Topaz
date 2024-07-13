<?php

namespace Wikimedia\WikiPEG;

class SyntaxError extends \Exception implements \JsonSerializable {
	public $expected;
	public $found;
	public $location;

	/**
	 * @param string $message
	 * @param Expectation[] $expected
	 * @param string|null $found
	 * @param LocationRange $location
	 */
	public function __construct( string $message, array $expected, $found, LocationRange $location ) {
		parent::__construct( $message );
		$this->expected = $expected;
		$this->found    = $found;
		$this->location = $location;
	}

	/**
	 * JSON serialization similar to the JavaScript SyntaxError, for testing
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize(): array {
		return [
			'name' => 'SyntaxError',
			'message' => $this->message,
			'expected' => $this->expected,
			'found' => $this->found,
			'location' => $this->location
		];
	}
}
