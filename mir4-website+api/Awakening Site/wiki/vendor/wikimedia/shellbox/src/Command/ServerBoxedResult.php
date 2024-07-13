<?php

namespace Shellbox\Command;

/**
 * A BoxedResult subclass used by ServerBoxedExecutor, providing simplified
 * output file handling.
 */
class ServerBoxedResult extends BoxedResult {
	/** @var string[] */
	private $fileNames = [];

	public function getFileNames() {
		return $this->fileNames;
	}

	/**
	 * @param string[] $fileNames
	 */
	public function setFileNames( $fileNames ) {
		$this->fileNames = $fileNames;
	}
}
