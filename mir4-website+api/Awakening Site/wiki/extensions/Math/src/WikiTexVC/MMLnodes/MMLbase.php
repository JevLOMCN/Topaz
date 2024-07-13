<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Html\Html;

class MMLbase {
	private string $name;
	private array $attributes;

	public function __construct( string $name, string $texclass = '', array $attributes = [] ) {
		$this->name = $name;
		$this->attributes = $attributes;
		if ( $texclass !== '' ) {
			$this->attributes[ TAG::CLASSTAG ] = $texclass;
		}
	}

	public function name(): string {
		return $this->name;
	}

	/**
	 * Encapsulating the input structure with start and end element
	 *
	 * @param string $input The raw HTML contents of the element: *not* escaped!
	 * @return string <tag> input </tag>
	 */
	public function encapsulateRaw( string $input ): string {
		return HTML::rawElement( $this->name, $this->attributes, $input );
	}

	/**
	 * Encapsulating the input with start and end element
	 *
	 * @param string $input
	 * @return string <tag> input </tag>
	 */
	public function encapsulate( string $input = '' ): string {
		return HTML::element( $this->name, $this->attributes, $input );
	}

	/**
	 * Getting the start element
	 * @return string
	 */
	public function getStart(): string {
		return HTML::openElement( $this->name, $this->attributes );
	}

	/**
	 * Gets an empty element with the specified name.
	 * Example: "<mrow/>"
	 * @return string
	 */
	public function getEmpty(): string {
		return substr( $this->getStart(), 0, -1 )
			. '/>';
	}

	/**
	 * Getting the end element
	 * @return string
	 */
	public function getEnd(): string {
		return HTML::closeElement( $this->name );
	}
}
