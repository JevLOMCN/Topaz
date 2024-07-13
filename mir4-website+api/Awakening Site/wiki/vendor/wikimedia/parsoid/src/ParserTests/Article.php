<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

/**
 * Represents a parser test
 */
class Article extends Item {
	/** @var string */
	public $title;

	/** @var string */
	public $text;

	/**
	 * @param array $articleProps key-value mapping of properties
	 * @param ?string $comment Optional comment describing the article
	 */
	public function __construct( array $articleProps, ?string $comment = null ) {
		parent::__construct( $articleProps, $comment );
		$this->title = $articleProps['title'];
		$this->text = $articleProps['text'];
	}
}
