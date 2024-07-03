<?php

namespace Shellbox\Command;

/**
 * An output glob for files that are written to a local directory
 *
 * @internal
 */
class OutputGlobToFile extends OutputGlob {
	/** @var string */
	private $destDir;

	/**
	 * @param string $prefix
	 * @param string $extension
	 * @param string $destDir
	 */
	public function __construct( $prefix, $extension, $destDir ) {
		parent::__construct( $prefix, $extension );
		$this->destDir = $destDir;
	}

	public function getInstance( $boxedName ) {
		$instance = new OutputFileToFile( $this->destDir . '/' . basename( $boxedName ) );
		$this->files[$boxedName] = $instance;
		return $instance;
	}
}
