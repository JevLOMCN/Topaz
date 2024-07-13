<?php

namespace Wikimedia\Zest;

use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMNode;

/**
 * Zest.php (https://github.com/wikimedia/zest.php)
 * Copyright (c) 2019, C. Scott Ananian. (MIT licensed)
 * PHP port based on:
 *
 * Zest (https://github.com/chjj/zest)
 * A css selector engine.
 * Copyright (c) 2011-2012, Christopher Jeffrey. (MIT Licensed)
 * Domino version based on Zest v0.1.3 with bugfixes applied.
 */

class Zest {

	/** @var ZestInst */
	private static $singleton = null;

	private static function singleton() {
		if ( !self::$singleton ) {
			self::$singleton = new ZestInst();
		}
		return self::$singleton;
	}

	/**
	 * Find elements matching a CSS selector underneath $context.
	 * @param string $sel The CSS selector string
	 * @param DOMDocument|DOMDocumentFragment|DOMElement $context
	 *   The scoping root for the search
	 * @param array $opts Additional match-context options (optional)
	 * @return array Elements matching the CSS selector
	 */
	public static function find( string $sel, $context, array $opts = [] ): array {
		return self::singleton()->find( $sel, $context, $opts );
	}

	/**
	 * Determine whether an element matches the given selector.
	 * @param DOMNode $el The element to be tested
	 * @param string $sel The CSS selector string
	 * @param array $opts Additional match-context options (optional)
	 * @return bool True iff the element matches the selector
	 */
	public static function matches( $el, string $sel, array $opts = [] ): bool {
		return self::singleton()->matches( $el, $sel, $opts );
	}

	/**
	 * Get descendants by ID.
	 * The PHP DOM doesn't provide this method for DOMElement, and the
	 * implementation in DOMDocument is broken.
	 *
	 * @param DOMDocument|DOMDocumentFragment|DOMElement $context
	 *   The scoping root for the search
	 * @param string $id
	 * @param array $opts Additional match-context options (optional)
	 * @return array<DOMElement> A list of the elements with the given ID. When there are more
	 *   than one, this method might return all of them or only the first one.
	 */
	public static function getElementsById( $context, string $id, array $opts = [] ): array {
		return self::singleton()->getElementsById( $context, $id, $opts );
	}

	/**
	 * Get descendants by tag name.
	 * The PHP DOM doesn't provide this method for DOMElement, and the
	 * implementation in DOMDocument has performance issues.
	 *
	 * @param DOMDocument|DOMDocumentFragment|DOMElement $context
	 * @param string $tagName
	 * @param array $opts Additional match-context options (optional)
	 * @return array<DOMElement>
	 */
	public static function getElementsByTagName( $context, string $tagName, array $opts = [] ) {
		return self::singleton()->getElementsByTagName( $context, $tagName, $opts );
	}

}
