<?php

namespace Shellbox\Command;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Shellbox\FileUtils;
use Shellbox\Multipart\MultipartReader;

/**
 * Encapsulation of an output file that is copied to a stream
 */
class OutputFileToStream extends OutputFile {
	/** @var StreamInterface */
	private $stream;

	public function __construct( StreamInterface $stream ) {
		$this->stream = $stream;
	}

	public function copyFromFile( $sourcePath ) {
		Utils::copyToStream( FileUtils::openInputFileStream( $sourcePath ), $this->stream );
		$this->received = true;
	}

	public function getContents() {
		$this->stream->rewind();
		return $this->stream->getContents();
	}

	public function readFromMultipart( MultipartReader $multipartReader ) {
		$multipartReader->copyPartToStream( $this->stream );
		$this->received = true;
	}
}
