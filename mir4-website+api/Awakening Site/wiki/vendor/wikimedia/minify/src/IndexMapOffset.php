<?php
/**
 * Copyright 2023 Wikimedia Foundation
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
 * A class representing a line/column offset into a combined generated file,
 * for index map generation.
 *
 * Or it can represent the past-the-end offset of a single specified file,
 * that is, the number of lines in the file and the number of columns in
 * the last line of the file.
 */
class IndexMapOffset {
	/** @var int */
	public $line;
	/** @var int */
	public $column;

	/**
	 * @param int $line The zero-based line number
	 * @param int $column The zero-based column number
	 */
	public function __construct( int $line, int $column ) {
		$this->line = $line;
		$this->column = $column;
	}

	/**
	 * Count the number of lines and columns in the specified string, and
	 * create an IndexMapOffset representing the corresponding size.
	 *
	 * @param string $text
	 * @return self
	 */
	public static function newFromText( string $text ) {
		$lines = substr_count( $text, "\n" );
		$lastBreakPos = strrpos( $text, "\n" );
		if ( $lastBreakPos === false ) {
			$columns = Utils::getJsLength( $text );
		} else {
			$columns = Utils::getJsLength( substr( $text, $lastBreakPos + 1 ) );
		}
		return new self( $lines, $columns );
	}

	/**
	 * Restore an IndexMapOffset which was serialized with toArray().
	 *
	 * @param array $data
	 * @return self
	 */
	public static function newFromArray( array $data ) {
		return new self( $data[0], $data[1] );
	}

	/**
	 * Convert the object to plain data.
	 *
	 * @return array
	 */
	public function toArray(): array {
		return [ $this->line, $this->column ];
	}

	/**
	 * Advance the offset, assuming a file of the specified size was added
	 * to the combined file.
	 *
	 * @param IndexMapOffset $nextSize
	 * @return void
	 */
	public function add( self $nextSize ) {
		if ( $nextSize->line > 0 ) {
			$this->line += $nextSize->line;
			$this->column = $nextSize->column;
		} else {
			$this->column += $nextSize->column;
		}
	}
}
