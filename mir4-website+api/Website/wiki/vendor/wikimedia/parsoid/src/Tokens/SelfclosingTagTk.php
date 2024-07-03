<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Tokens;

use Wikimedia\Parsoid\NodeData\DataParsoid;

/**
 * Token for a self-closing tag (HTML or otherwise)
 */
class SelfclosingTagTk extends Token {
	/** @var string Name of the end tag */
	private $name;

	/**
	 * @param string $name
	 * @param KV[] $attribs
	 * @param ?DataParsoid $dataParsoid
	 */
	public function __construct(
		string $name, array $attribs = [], ?DataParsoid $dataParsoid = null
	) {
		$this->name = $name;
		$this->attribs = $attribs;
		$this->dataParsoid = $dataParsoid ?? new DataParsoid;
	}

	public function getName(): string {
		return $this->name;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize(): array {
		return [
			'type' => $this->getType(),
			'name' => $this->name,
			'attribs' => $this->attribs,
			'dataParsoid' => $this->dataParsoid
		];
	}
}
