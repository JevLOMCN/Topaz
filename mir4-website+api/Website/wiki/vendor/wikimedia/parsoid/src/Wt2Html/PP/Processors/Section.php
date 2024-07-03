<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Core\SectionMetadata;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;

class Section {
	/** @var int */
	private $level;

	/**
	 * Useful during debugging, unrelated to data-mw-section-id
	 * @var int
	 */
	private $debugId;

	/** @var Element */
	public $container;

	/** @var SectionMetadata */
	public $metadata;

	public function __construct( int $level, int $debugId, Document $ownerDoc ) {
		$this->level = $level;
		$this->debugId = $debugId;
		$this->container = $ownerDoc->createElement( 'section' );
		// Use named arguments here in PHP 8.0+
		$this->metadata = new SectionMetadata(
			-1, /* tocLevel */
			$level /* hLevel */
		);
	}

	public function setId( int $id ): void {
		$this->container->setAttribute( 'data-mw-section-id', (string)$id );
		// $this->container->setAttribute( 'data-debug-id', (string)$this->debugId );
	}

	public function setAboutId( string $aboutId ): void {
		$this->container->setAttribute( 'about', $aboutId );
	}

	public function addNode( Node $node ): void {
		$this->container->appendChild( $node );
	}

	public function addSection( Section $section ): void {
		// error_log( "Appending to " . $this->debugId . '\n' );
		$this->container->appendChild( $section->container );
	}

	/**
	 * Does this section have a nesting level of $level?
	 * @param int $level
	 * @return bool
	 */
	public function hasNestedLevel( int $level ): bool {
		return $level > $this->level;
	}
}
