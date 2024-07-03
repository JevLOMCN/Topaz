<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\ConstrainedText;

class Result {
	/** @var string */
	public $text;
	/** @var ?string */
	public $prefix;
	/** @var ?string */
	public $suffix;
	/** @var bool */
	public $greedy;

	/**
	 * Construct a new constrained text result object.
	 *
	 * @param string $text
	 * @param ?string $prefix
	 * @param ?string $suffix
	 */
	public function __construct(
		string $text, ?string $prefix = null, ?string $suffix = null
	) {
		$this->text = $text;
		$this->prefix = $prefix;
		$this->suffix = $suffix;
		$this->greedy = false;
	}
}
