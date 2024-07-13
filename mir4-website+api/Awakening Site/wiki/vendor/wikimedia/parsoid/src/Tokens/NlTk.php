<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Tokens;

use Wikimedia\Parsoid\NodeData\DataParsoid;

/**
 * Newline token.
 */
class NlTk extends Token {
	/**
	 * @param ?SourceRange $tsr
	 *    TSR ("tag source range") represents the (start, end) wikitext
	 *    byte offsets for a token (in this case, the newline) in the
	 *    UTF8-encoded source string
	 * @param ?DataParsoid $dataParsoid
	 */
	public function __construct(
		?SourceRange $tsr, ?DataParsoid $dataParsoid = null
	) {
		if ( $dataParsoid ) {
			$this->dataParsoid = $dataParsoid;
		} elseif ( $tsr ) {
			$this->dataParsoid = new DataParsoid;
			$this->dataParsoid->tsr = $tsr;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize(): array {
		return [
			'type' => $this->getType(),
			'dataParsoid' => $this->dataParsoid
		];
	}
}
