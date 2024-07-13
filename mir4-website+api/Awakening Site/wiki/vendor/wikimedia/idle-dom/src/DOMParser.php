<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * DOMParser
 *
 * @see https://dom.spec.whatwg.org/#interface-domparser
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface DOMParser {

	/**
	 * @param string $string
	 * @param string $type
	 * @return Document
	 */
	public function parseFromString( string $string, /* DOMParserSupportedType */ string $type );

}
