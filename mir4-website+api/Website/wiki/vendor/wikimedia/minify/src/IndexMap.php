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
 * A class representing an index map, as defined by the source map
 * specification. This allows several mapped sources to be combined into a
 * single file.
 */
class IndexMap {
	/** @var string|null */
	private $file;
	/** @var IndexMapOffset */
	private $offset;
	/** @var array */
	private $sections;

	/**
	 * Create an empty index map
	 */
	public function __construct() {
		$this->offset = new IndexMapOffset( 0, 0 );
		$this->sections = [];
	}

	/**
	 * Set the name of the output file, to be given as the "file" key.
	 *
	 * @param string $file
	 * @return $this
	 */
	public function outputFile( string $file ) {
		$this->file = $file;
		return $this;
	}

	/**
	 * Add a section with a source map which was encoded in the "raw" JSON format.
	 *
	 * @param string $mapJson The JSON-encoded source map.
	 * @param IndexMapOffset $generatedSize The size of the generated output
	 *   corresponding to $mapJson. This is used to advance the current offset
	 *   and will be used to calculate the offset of the next section, if there
	 *   is one.
	 * @return $this
	 */
	public function addEncodedMap( string $mapJson, IndexMapOffset $generatedSize ) {
		$this->sections[] =
			'{"offset":' .
			 json_encode( [
				'line' => $this->offset->line,
				'column' => $this->offset->column,
			] ) .
			',"map":' .
			$mapJson .
			'}';
		$this->offset->add( $generatedSize );
		return $this;
	}

	/**
	 * Get the index map, encoded as JSON
	 *
	 * @return string
	 */
	public function getMap(): string {
		$map = "{\n" .
			"\"version\": 3,\n";
		if ( $this->file !== null ) {
			$map .= '"file": ' . json_encode( $this->file ) . ",\n";
		}
		$map .= "\"sections\": [\n";
		if ( $this->sections ) {
			$map .= implode( ",\n", $this->sections ) . "\n";
		}
		$map .= "]\n}";
		return $map;
	}
}
