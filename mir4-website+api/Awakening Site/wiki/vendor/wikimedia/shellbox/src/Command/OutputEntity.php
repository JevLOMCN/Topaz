<?php

namespace Shellbox\Command;

/**
 * An umbrella for OutputFile and OutputGlob which exists for the convenience
 * of LocalBoxedExecutor::collectOutputFiles().
 *
 * @internal
 */
abstract class OutputEntity {
	/**
	 * Get an OutputFile corresponding to an instance of the OutputEntity.
	 * For OutputFile objects this returns $this. For OutputGlob objects it
	 * returns an OutputFile object for a file that matches the glob.
	 *
	 * @param string $boxedName
	 * @return OutputFile
	 */
	abstract public function getInstance( $boxedName );
}
