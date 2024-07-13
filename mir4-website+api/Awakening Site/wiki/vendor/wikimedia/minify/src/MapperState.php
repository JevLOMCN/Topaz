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
 * The base class for stateful minifying with source map fetching
 */
abstract class MapperState extends MinifierState {
	/** @var MappingsGenerator|null */
	protected $mappingsGenerator;

	public function __construct() {
		$this->mappingsGenerator = new MappingsGenerator;
	}

	/**
	 * Minify a source file and collect the output and mappings data.
	 *
	 * @param string $url The name of the input file. Possibly a URL relative
	 *   to the source root.
	 * @param string $source The input source text.
	 * @param bool $bundle Whether to add the source text to sourcesContent
	 * @return $this
	 */
	public function addSourceFile( string $url, string $source, bool $bundle = false ) {
		$this->sources[] = $url;
		if ( $bundle ) {
			$this->sourcesContent[] = $source;
		} else {
			$this->sourcesContent[] = null;
		}
		$this->mappingsGenerator->nextSourceFile( $source );
		return parent::addSourceFile( $url, $source, $bundle );
	}

	/**
	 * Add a string to the output without any minification or source mapping.
	 *
	 * @param string $output
	 * @return $this
	 */
	public function addOutput( string $output ) {
		$this->mappingsGenerator->outputSpace( $output );
		return parent::addOutput( $output );
	}

	/**
	 * Get the source map data to be JSON encoded.
	 *
	 * @return array
	 */
	public function getSourceMapData() {
		$data = [ 'version' => 3 ];
		if ( $this->outputFile !== null ) {
			$data['file'] = $this->outputFile;
		}
		if ( $this->sourceRoot !== null ) {
			$data['sourceRoot'] = $this->sourceRoot;
		}
		$data['sources'] = $this->sources;

		$needSourcesContent = false;
		foreach ( $this->sourcesContent as $content ) {
			if ( $content !== null ) {
				$needSourcesContent = true;
			}
		}
		if ( $needSourcesContent ) {
			$data['sourcesContent'] = $this->sourcesContent;
		}
		$data['names'] = [];
		$data['mappings'] = $this->mappingsGenerator->getMap();
		return $data;
	}

	/**
	 * Get the JSON-encoded source map. Take care to avoid leaking private data
	 * due to an XSSI attack.
	 *
	 * @return string
	 */
	public function getRawSourceMap() {
		$data = $this->getSourceMapData();
		$out = "{\n";
		$first = true;
		foreach ( $data as $key => $value ) {
			if ( $first ) {
				$first = false;
			} else {
				$out .= ",\n";
			}
			$out .= json_encode( $key ) . ': ' .
				json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		$out .= "\n}\n";
		return $out;
	}

	/**
	 * Get the JSON-encoded source map including XSSI protection prefix.
	 *
	 * @return string
	 */
	public function getSourceMap() {
		return ")]}\n" . $this->getRawSourceMap();
	}
}
