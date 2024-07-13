<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

class Item {
	/** @var string The type of this item. */
	public $type;
	/** @var string The filename containing this item. */
	public $filename;
	/** @var int The line number of the start of this item. */
	public $lineNumStart;
	/** @var int The line number of the end of this item. */
	public $lineNumEnd;
	/** @var ?string An optional comment describing this item. */
	public $comment;

	/**
	 * @param array $props Common item properties, including type.
	 * @param ?string $comment Optional comment describing the item
	 */
	public function __construct( array $props, ?string $comment = null ) {
		$this->type = $props['type'];
		$this->filename = $props['filename'];
		$this->lineNumStart = $props['lineNumStart'];
		$this->lineNumEnd = $props['lineNumEnd'];
		$this->comment = $comment ?: null;
	}

	/**
	 * Return a friendly error message related to this item.
	 * @param string $desc The error description.
	 * @param ?string $text Optional additional context.
	 * @return string The error message string, including the line number and
	 *   filename of this item.
	 */
	public function errorMsg( string $desc, ?string $text = null ): string {
		$start = $this->lineNumStart;
		$end = $this->lineNumEnd;
		$lineDesc = $end > $start ? "lines $start-$end" : "line $start";
		$fileDesc = $this->filename; // trim path in future?
		$extraText = $text ? ": $text" : "";
		return "$desc on $lineDesc of $fileDesc$extraText";
	}

	/**
	 * Throw an error related to this item.
	 * @param string $desc The error description.
	 * @param ?string $text Optional additional context.
	 * @throws \Error
	 * @return never
	 */
	public function error( string $desc, ?string $text = null ) {
		throw new \Error( $this->errorMsg( $desc, $text ) );
	}
}
