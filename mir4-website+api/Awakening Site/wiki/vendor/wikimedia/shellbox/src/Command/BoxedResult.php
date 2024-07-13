<?php

namespace Shellbox\Command;

/**
 * Class representing the result of running a BoxedCommand
 */
class BoxedResult extends UnboxedResult {
	/** @var OutputFile[] */
	private $files = [];

	/**
	 * Add an output file to the result
	 *
	 * @internal For use by BoxedExecutor
	 * @param string $name The file name relative to the working directory
	 * @param OutputFile $outputFile
	 */
	public function addOutputFile( $name, OutputFile $outputFile ) {
		$this->files[$name] = $outputFile;
	}

	/**
	 * Get the contents of an output file as a string
	 *
	 * @param string $name The file name relative to the working directory
	 * @return string|null The contents, or null if the command did not create
	 *   a file of that name, or if the output file was not registered in the
	 *   BoxedCommand.
	 */
	public function getFileContents( $name ) {
		if ( !isset( $this->files[$name] ) ) {
			return null;
		} else {
			return $this->files[$name]->getContents();
		}
	}

	/**
	 * Query whether an output file was received.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function wasReceived( $name ) {
		return isset( $this->files[$name] );
	}

	/**
	 * Get the names of all files which were registered and were created by the
	 * command.
	 *
	 * @return string[]
	 */
	public function getFileNames() {
		return array_keys( $this->files );
	}
}
