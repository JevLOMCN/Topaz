<?php

namespace Cite;

use MediaWiki\Parser\Sanitizer;

/**
 * Compiles unique identifiers and formats them as anchors for use in `href="#…"` and `id="…"`
 * attributes.
 *
 * @license GPL-2.0-or-later
 */
class AnchorFormatter {

	/**
	 * Return an id for use in wikitext output based on a key and
	 * optionally the number of it, used in <references>, not <ref>
	 * (since otherwise it would link to itself)
	 *
	 * @param string|int $key
	 * @param string|null $num The number of the key
	 *
	 * @return string
	 */
	private function refKey( $key, ?string $num ): string {
		if ( $num !== null ) {
			$key = $key . '_' . $num;
		}
		return $this->normalizeKey( "cite_ref-$key" );
	}

	/**
	 * @param string|int $key
	 * @param string|null $num
	 * @return string Escaped to be used as part of a [[#…]] link
	 */
	public function backLink( $key, ?string $num = null ): string {
		$key = $this->refKey( $key, $num );
		// This does both URL encoding (e.g. %A0, which only makes sense in href="…") and HTML
		// entity encoding (e.g. &#xA0;). The browser will decode in reverse order.
		return Sanitizer::safeEncodeAttribute( Sanitizer::escapeIdForLink( $key ) );
	}

	/**
	 * @param string|int $key
	 * @param string|null $num
	 * @return string Already escaped to be used directly in an id="…" attribute
	 */
	public function backLinkTarget( $key, ?string $num ): string {
		$key = $this->refKey( $key, $num );
		return Sanitizer::safeEncodeAttribute( $key );
	}

	/**
	 * Return an id for use in wikitext output based on a key and
	 * optionally the number of it, used in <ref>, not <references>
	 * (since otherwise it would link to itself)
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	private function getReferencesKey( string $key ): string {
		return $this->normalizeKey( "cite_note-$key" );
	}

	/**
	 * @param string $key
	 * @return string Escaped to be used as part of a [[#…]] link
	 */
	public function jumpLink( string $key ): string {
		$key = $this->getReferencesKey( $key );
		// This does both URL encoding (e.g. %A0, which only makes sense in href="…") and HTML
		// entity encoding (e.g. &#xA0;). The browser will decode in reverse order.
		return Sanitizer::safeEncodeAttribute( Sanitizer::escapeIdForLink( $key ) );
	}

	/**
	 * @param string $key
	 * @return string Already escaped to be used directly in an id="…" attribute
	 */
	public function jumpLinkTarget( string $key ): string {
		$key = $this->getReferencesKey( $key );
		return Sanitizer::safeEncodeAttribute( $key );
	}

	private function normalizeKey( string $key ): string {
		// MediaWiki normalizes spaces and underscores in [[#…]] links, but not in id="…"
		// attributes. To make them behave the same we normalize in advance.
		return preg_replace( '/[_\s]+/u', '_', $key );
	}

}
