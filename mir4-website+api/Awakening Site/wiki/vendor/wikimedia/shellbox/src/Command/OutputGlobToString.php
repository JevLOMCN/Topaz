<?php

namespace Shellbox\Command;

/**
 * An output glob for files that are handled as strings
 *
 * @internal
 */
class OutputGlobToString extends OutputGlob {
	public function getInstance( $boxedName ) {
		$instance = new OutputFileToString;
		$this->files[$boxedName] = $instance;
		return $instance;
	}
}
