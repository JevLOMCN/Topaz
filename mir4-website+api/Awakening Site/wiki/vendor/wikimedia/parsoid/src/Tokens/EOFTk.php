<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Tokens;

/**
 * Represents EOF
 */
class EOFTk extends Token {
	/**
	 * @suppress PhanEmptyPublicMethod
	 */
	public function __construct() {
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize(): array {
		return [
			'type' => $this->getType()
		];
	}
}
