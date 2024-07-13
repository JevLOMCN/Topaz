<?php

namespace Shellbox\Command;

class UnboxedResult {
	/** @var int|null */
	private $exitCode;

	/** @var string|null */
	private $stdout;

	/** @var string|null */
	private $stderr;

	/**
	 * Set the exit code
	 *
	 * @param int $exitCode
	 * @return $this
	 */
	public function exitCode( $exitCode ) {
		$this->exitCode = $exitCode;
		return $this;
	}

	/**
	 * Set the stdout contents
	 *
	 * @param string $stdout
	 * @return $this
	 */
	public function stdout( $stdout ) {
		$this->stdout = $stdout;
		return $this;
	}

	/**
	 * Set the stderr contents
	 *
	 * @param string $stderr
	 * @return $this
	 */
	public function stderr( $stderr ) {
		$this->stderr = $stderr;
		return $this;
	}

	/**
	 * Combine another result with this one. The semantics are like the shell's
	 * "&&" operator, although in practice we use this to upgrade an
	 * UnboxedResult to a BoxedResult, so $this is typically empty.
	 *
	 * @param UnboxedResult $other
	 */
	public function merge( UnboxedResult $other ) {
		if ( !$this->exitCode ) {
			$this->exitCode = $other->exitCode;
		}
		$this->stdout .= $other->getStdout();
		$this->stderr .= $other->getStderr();
	}

	/**
	 * Returns exit code of the process
	 *
	 * @return int|null
	 */
	public function getExitCode() {
		return $this->exitCode;
	}

	/**
	 * Returns stdout of the process
	 *
	 * @return string|null
	 */
	public function getStdout() {
		return $this->stdout;
	}

	/**
	 * Returns stderr of the process or null if the Command was configured to add stderr to stdout
	 * with includeStderr( true )
	 *
	 * @return string|null
	 */
	public function getStderr() {
		return $this->stderr;
	}
}
