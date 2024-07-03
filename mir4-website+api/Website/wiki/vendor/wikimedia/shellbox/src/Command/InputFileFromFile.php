<?php

namespace Shellbox\Command;

use Shellbox\FileUtils;

/**
 * Encapsulation of an input file that is copied from another file
 *
 * @internal
 */
class InputFileFromFile extends InputFile {
	/** @var string */
	private $path;

	/**
	 * @param string $path
	 */
	public function __construct( $path ) {
		$this->path = $path;
	}

	public function copyTo( $destPath ) {
		FileUtils::copy( $this->path, $destPath );
	}

	public function getStreamOrString() {
		return FileUtils::openInputFileStream( $this->path );
	}
}
