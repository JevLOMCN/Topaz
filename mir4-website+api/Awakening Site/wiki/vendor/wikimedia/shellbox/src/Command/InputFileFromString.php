<?php

namespace Shellbox\Command;

use Shellbox\FileUtils;

/**
 * Encapsulation of an input file that comes from a string
 * @internal
 */
class InputFileFromString extends InputFile {
	/** @var string */
	private $contents;

	public function __construct( string $contents ) {
		$this->contents = $contents;
	}

	public function copyTo( $destPath ) {
		FileUtils::putContents( $destPath, $this->contents );
	}

	public function getStreamOrString() {
		return $this->contents;
	}
}
