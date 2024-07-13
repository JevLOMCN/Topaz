<?php

namespace OOUI;

/**
 * Wraps a HTML snippet for use with Tag::appendContent() and Tag::prependContent().
 */
class HtmlSnippet {

	/* Properties */

	/**
	 * HTML snippet this instance represents.
	 *
	 * @var string
	 */
	protected $content;

	/* Methods */

	/**
	 * @param string $content HTML snippet
	 * @param-taint $content exec_html
	 */
	public function __construct( $content ) {
		if ( !is_string( $content ) ) {
			throw new Exception( 'Content passed to HtmlSnippet must be a string' );
		}
		$this->content = $content;
	}

	/**
	 * Render into HTML.
	 *
	 * @return string Unchanged HTML snippet
	 */
	public function __toString() {
		return $this->content;
	}
}
