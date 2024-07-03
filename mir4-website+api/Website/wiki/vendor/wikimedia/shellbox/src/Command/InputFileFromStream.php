<?php

namespace Shellbox\Command;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Shellbox\FileUtils;

class InputFileFromStream extends InputFile {
	/** @var StreamInterface */
	private $stream;

	public function __construct( StreamInterface $stream ) {
		$this->stream = $stream;
	}

	public function copyTo( $destPath ) {
		Utils::copyToStream( $this->stream, FileUtils::openOutputFileStream( $destPath ) );
	}

	public function getStreamOrString() {
		return $this->stream;
	}
}
