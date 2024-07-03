<?php
/**
 * Copyright 2022 Wikimedia Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @file
 * @license Apache-2.0
 * @license MIT
 * @license GPL-2.0-or-later
 * @license LGPL-2.1-or-later
 */

namespace Wikimedia\Minify;

/**
 * Utility class to generate the "mappings" string of a source map.
 *
 * @internal
 */
class MappingsGenerator {
	/** @var string */
	private $source = '';

	/** @var int The current source file offset in bytes */
	private $curSourceOffset = 0;

	/** @var int The current source file index */
	private $curSourceFile = -1;
	/** @var int The current source file line number */
	private $curSourceLine = 0;
	/** @var int The current source file column in UTF-16 code units */
	private $curSourceColumn = 0;
	/** @var int The current output file line number */
	private $curOutLine = 0;
	/** @var int The current output file column in UTF-16 code units */
	private $curOutColumn = 0;

	/** @var int The base of the delta encoding for source file index */
	private $prevSourceFile = 0;
	/** @var int The base of the delta encoding for source file line number */
	private $prevSourceLine = 0;
	/** @var int The base of the delta encoding for source file line column */
	private $prevSourceColumn = 0;
	/** @var int The base of the delta encoding for output file line number */
	private $prevOutLine = 0;
	/** @var int The base of the delta encoding for output file column */
	private $prevOutColumn = 0;

	/** @var bool Whether to omit a leading separator when generating a segment */
	private $isFirstSegment = true;

	/** @var string The accumulated mapping string */
	private $mappings = '';

	/** @var string The base-64 encoding table */
	private const BASE64_TABLE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

	/**
	 * Advance to the next source file.
	 *
	 * @param string $source The contents of the new file
	 */
	public function nextSourceFile( $source ) {
		$this->source = $source;
		$this->curSourceFile++;
		$this->curSourceOffset = 0;
		$this->curSourceLine = 0;
		$this->curSourceColumn = 0;
	}

	/**
	 * Advance the source position by the specified number of bytes.
	 *
	 * @param int $length
	 */
	public function consumeSource( $length ) {
		$newOffset = $this->curSourceOffset + $length;
		$lineCount = substr_count( $this->source, "\n", $this->curSourceOffset, $length );
		if ( $lineCount ) {
			$lineStartPos =
				strrpos(
					substr( $this->source, $this->curSourceOffset, $length ),
					"\n"
				) + $this->curSourceOffset + 1;
			$this->curSourceLine += $lineCount;
			$this->curSourceColumn = Utils::getJsLength(
				substr( $this->source, $lineStartPos, $newOffset - $lineStartPos ) );
		} else {
			$this->curSourceColumn += Utils::getJsLength(
				substr( $this->source, $this->curSourceOffset, $length ) );
		}
		$this->curSourceOffset = $newOffset;
	}

	/**
	 * Notify the source map generator of the generated output of text which
	 * should not generate a mapping segment.
	 *
	 * @param string $out
	 */
	public function outputSpace( $out ) {
		$lineCount = substr_count( $out, "\n" );
		if ( $lineCount ) {
			$lineStartPos = strrpos( $out, "\n" ) + 1;
			$this->curOutLine += $lineCount;
			$this->curOutColumn = Utils::getJsLength(
				substr( $out, $lineStartPos, strlen( $out ) - $lineStartPos ) );
		} else {
			$this->curOutColumn += Utils::getJsLength( $out );
		}
	}

	/**
	 * Notify the source map generator of the generated output of text which
	 * should generate a mapping segment. Append the mapping segment to the
	 * internal buffer.
	 *
	 * @param string $out
	 */
	public function outputToken( $out ) {
		$outLineDelta = $this->curOutLine - $this->prevOutLine;
		if ( $outLineDelta > 0 ) {
			$this->mappings .= str_repeat( ';', $outLineDelta );
			$this->prevOutColumn = 0;
			$this->isFirstSegment = false;
		} elseif ( $this->isFirstSegment ) {
			$this->isFirstSegment = false;
		} else {
			$this->mappings .= ',';
		}

		$this->appendNumber( $this->curOutColumn - $this->prevOutColumn );
		$this->appendNumber( $this->curSourceFile - $this->prevSourceFile );
		$this->appendNumber( $this->curSourceLine - $this->prevSourceLine );
		$this->appendNumber( $this->curSourceColumn - $this->prevSourceColumn );

		$this->prevSourceFile = $this->curSourceFile;
		$this->prevOutLine = $this->curOutLine;
		$this->prevOutColumn = $this->curOutColumn;
		$this->prevSourceLine = $this->curSourceLine;
		$this->prevSourceColumn = $this->curSourceColumn;

		$this->outputSpace( $out );
	}

	/**
	 * Append a VLQ encoded number to the buffer.
	 *
	 * @param int $n
	 */
	private function appendNumber( $n ) {
		$encoded = '';

		// The sign bit goes in the LSB for some reason
		$vlq = $n < 0 ? ( -$n << 1 ) | 1 : $n << 1;

		do {
			$digit = $vlq & 0x1f;
			$vlq >>= 5;
			if ( $vlq > 0 ) {
				$digit |= 0x20;
			}
			$encoded .= self::BASE64_TABLE[$digit];
		} while ( $vlq > 0 );

		$this->mappings .= $encoded;
	}

	/**
	 * Get the generated mappings string.
	 *
	 * @return string
	 */
	public function getMap() {
		return $this->mappings;
	}
}
