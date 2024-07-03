<?php

namespace Shellbox\Command;

use Shellbox\Multipart\MultipartReader;
use Shellbox\ShellboxError;

/**
 * The base class for encapsulated output files
 *
 * @internal
 */
abstract class OutputFile extends OutputEntity {
	/** @var bool */
	protected $received = false;

	public function getInstance( $boxedName ) {
		return $this;
	}

	/**
	 * Copy from the specified source path to the registered destination
	 * location, which may be either a string or a path outside the working
	 * directory.
	 *
	 * @param string $sourcePath
	 */
	abstract public function copyFromFile( $sourcePath );

	/**
	 * Get the contents of the output file from its final destination. This
	 * should be called after copyFromFile() or readFromMultipart(). It will
	 * throw if the file is not readable.
	 *
	 * @return string
	 * @throws ShellboxError
	 */
	abstract public function getContents();

	/**
	 * Copy from the MultipartReader to the registered destination location.
	 * The MultipartReader must be at the appropriate place in the input stream.
	 * Used by the client.
	 *
	 * @param MultipartReader $multipartReader
	 */
	abstract public function readFromMultipart( MultipartReader $multipartReader );

	/**
	 * Return true if the file was received from the command or server.
	 *
	 * @return bool
	 */
	public function wasReceived() {
		return $this->received;
	}

	public function getClientData() {
		return [];
	}

	/**
	 * This is used to create a placeholder object for use on the server side.
	 * It doesn't need to actually be functional since the server is responsible
	 * for reading output files.
	 *
	 * @param array $data
	 * @return OutputFilePlaceholder
	 */
	public static function newFromClientData( $data ) {
		return new OutputFilePlaceholder;
	}
}
