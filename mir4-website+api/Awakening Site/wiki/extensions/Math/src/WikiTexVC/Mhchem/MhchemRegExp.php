<?php
/**
 * Copyright (c) 2023 Johannes Stegmüller
 *
 * This file is a port of mhchemParser originally authored by Martin Hensel in javascript/typescript.
 * The original license for this software can be found in the accompanying LICENSE.mhchemParser-ts.txt file.
 */

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

/**
 * Wrapper class to declare a hardcoded string to a regular expression.
 * @author Johannes Stegmüller
 * @license GPL-2.0-or-later
 */
class MhchemRegExp {

	/** @var string regular expression pattern as a string */
	private string $regexp;

	/**
	 * Utility class to distinguish Regular expression strings defined in
	 * the codebase of mhchemParser from regular strings.
	 * @param string $pattern regular expression pattern, usually of the format "/regexp/"
	 */
	public function __construct( string $pattern ) {
		$this->regexp = $pattern;
	}

	public function getRegExp(): string {
		return $this->regexp;
	}
}
