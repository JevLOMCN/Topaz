<?php

namespace Shellbox\Command;

use Shellbox\Shellbox;
use Shellbox\ShellParser\ShellParser;
use Shellbox\ShellParser\ShellSyntaxError;
use Shellbox\ShellParser\SyntaxInfo;

/**
 * The abstract base class for commands.
 */
abstract class Command {
	/** @var string */
	private $command = '';
	/** @var int|float|null */
	private $cpuTimeLimit;
	/** @var int|float|null */
	private $wallTimeLimit;
	/** @var int|float|null */
	private $memoryLimit;
	/** @var int|float|null */
	private $fileSizeLimit;
	/** @var string[] */
	private $environment = [];
	/** @var string */
	private $stdin = '';
	/** @var bool */
	private $passStdin;
	/** @var bool */
	private $includeStderr;
	/** @var bool */
	private $logStderr = false;
	/** @var bool */
	private $forwardStderr = false;
	/** @var bool */
	private $useLogPipe = false;
	/** @var string|null */
	private $workingDirectory;
	/** @var array */
	private $procOpenOptions = [];
	/** @var bool */
	private $disableNetwork = false;
	/** @var string[] */
	private $disabledSyscalls = [];
	/** @var bool */
	private $firejailDefaultSeccomp = false;
	/** @var bool */
	private $noNewPrivs = false;
	/** @var bool */
	private $privateUserNamespace = false;
	/** @var bool */
	private $privateDev = false;
	/** @var string[] */
	private $allowedPaths = [];
	/** @var string[] */
	private $disallowedPaths = [];
	/** @var bool */
	private $disableSandbox = false;
	/** @var SyntaxInfo|null */
	private $syntaxInfo;

	/**
	 * Adds parameters to the command. All parameters are escaped via Shellbox::escape().
	 * Null values are ignored.
	 *
	 * @param mixed|mixed[] ...$args
	 * @return $this
	 */
	public function params( ...$args ) {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$command = Shellbox::escape( $args );
		if ( $this->command === '' ) {
			$this->command = $command;
		} else {
			$this->command .= ' ' . $command;
		}
		$this->syntaxInfo = null;
		return $this;
	}

	/**
	 * Adds unsafe parameters to the command. These parameters are NOT sanitized in any way.
	 * Null values are ignored.
	 *
	 * @param string|string[]|null ...$args
	 * @return $this
	 */
	public function unsafeParams( ...$args ) {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		foreach ( $args as $arg ) {
			if ( $arg !== null ) {
				if ( $this->command !== '' ) {
					$this->command .= ' ';
				}
				$this->command .= $arg;
			}
		}
		$this->syntaxInfo = null;
		return $this;
	}

	/**
	 * Replace the whole command with the given set of arguments.
	 *
	 * @param string|string[] ...$args
	 * @return $this
	 */
	public function replaceParams( ...$args ) {
		$this->command = '';
		$this->params( ...$args );
		return $this;
	}

	/**
	 * Replace the whole command string with something else. The command is not
	 * escaped or sanitized.
	 *
	 * @param string $command
	 * @return $this
	 */
	public function unsafeCommand( string $command ) {
		$this->command = $command;
		$this->syntaxInfo = null;
		return $this;
	}

	/**
	 * Set the CPU time limit, that is, the amount of time the process spends
	 * in the running state.
	 *
	 * Whether this limit can be respected depends on the executor
	 * configuration.
	 *
	 * @param int|float $limit The limit in seconds
	 * @return $this
	 */
	public function cpuTimeLimit( $limit ) {
		$this->cpuTimeLimit = $limit;
		return $this;
	}

	/**
	 * Set the wall clock time limit, that is, the amount of real time the
	 * process may run for.
	 *
	 * Whether this limit can be respected depends on the executor
	 * configuration.
	 *
	 * @param int|float $limit The limit in seconds
	 * @return $this
	 */
	public function wallTimeLimit( $limit ) {
		$this->wallTimeLimit = $limit;
		return $this;
	}

	/**
	 * Set the memory limit in bytes.
	 *
	 * Whether this limit can be respected depends on the executor
	 * configuration.
	 *
	 * @param int|float $limit The limit in bytes
	 * @return $this
	 */
	public function memoryLimit( $limit ) {
		$this->memoryLimit = $limit;
		return $this;
	}

	/**
	 * Set the maximum file size that the command may create
	 *
	 * Whether this limit can be respected depends on the executor
	 * configuration.
	 *
	 * @param int|float $limit The limit in bytes
	 * @return $this
	 */
	public function fileSizeLimit( $limit ) {
		$this->fileSizeLimit = $limit;
		return $this;
	}

	/**
	 * Sets environment variables which should be added to the executed command
	 * environment. In CLI mode, the environment of the parent process will
	 * also be inherited.
	 *
	 * @param string[] $environment array of variable name => value
	 * @return $this
	 */
	public function environment( array $environment ) {
		$this->environment = $environment;
		return $this;
	}

	/**
	 * Sends the provided input to the command. Defaults to an empty string.
	 * If you want to pass stdin through to the command instead, use
	 * passStdin().
	 *
	 * @param string $stdin
	 * @return $this
	 */
	public function stdin( string $stdin ) {
		$this->stdin = $stdin;
		return $this;
	}

	/**
	 * Controls whether stdin is passed through to the command, so that the
	 * user can interact with the command when it is run in CLI mode. If this
	 * is enabled:
	 *   - The wall clock timeout will be disabled to avoid stopping the
	 *     process with SIGTTIN/SIGTTOU (T206957).
	 *   - The string specified with input() will be ignored.
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function passStdin( bool $yesno = true ) {
		$this->passStdin = $yesno;
		return $this;
	}

	/**
	 * Controls whether stderr should be included in stdout, including errors
	 * from wrappers. Default: don't include.
	 *
	 * @param bool $includeStderr
	 * @return $this
	 */
	public function includeStderr( bool $includeStderr = true ) {
		$this->includeStderr = $includeStderr;
		return $this;
	}

	/**
	 * If this is set to true, text written to stderr by the command will be
	 * passed through to PHP's stderr. To avoid SIGTTIN/SIGTTOU, and to support
	 * Result::getStderr(), the file descriptor is not passed through, we just
	 * copy the data to stderr as we receive it.
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function forwardStderr( bool $yesno = true ) {
		$this->forwardStderr = $yesno;
		return $this;
	}

	/**
	 * When enabled, text sent to stderr will be logged with a level of 'error'.
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function logStderr( bool $yesno = true ) {
		$this->logStderr = $yesno;
		return $this;
	}

	/**
	 * Open FD 3 as a pipe and pass the write side to the command. Lines
	 * written to this pipe will be logged. This is used by some wrappers to
	 * provide log messages.
	 *
	 * @internal For Wrapper subclasses only
	 * @param bool $yesno
	 * @return $this
	 */
	public function useLogPipe( bool $yesno = true ) {
		$this->useLogPipe = $yesno;
		return $this;
	}

	/**
	 * Set the working directory under which the command will be run.
	 *
	 * @param string $path
	 * @return $this
	 */
	public function workingDirectory( string $path ) {
		$this->workingDirectory = $path;
		return $this;
	}

	/**
	 * Set special options to proc_open().
	 *
	 * @internal For Wrapper subclasses only
	 * @param array $options
	 * @return $this
	 */
	public function procOpenOptions( array $options ) {
		$this->procOpenOptions = $options;
		return $this;
	}

	/**
	 * Disable networking, if possible.
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function disableNetwork( bool $yesno = true ) {
		$this->disableNetwork = $yesno;
		return $this;
	}

	/**
	 * Specify the set of disabled syscalls. If the sandbox configuration
	 * permits, a seccomp filter will be set up to disallow them.
	 *
	 * @param string[] $syscalls
	 * @return $this
	 */
	public function disabledSyscalls( array $syscalls ) {
		$this->disabledSyscalls = $syscalls;
		return $this;
	}

	/**
	 * Enable/disable the default Firejail seccomp filter. This only works if
	 * Firejail is enabled. Firejail will also enable no_new_privs when this is
	 * enabled.
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function firejailDefaultSeccomp( bool $yesno = true ) {
		$this->firejailDefaultSeccomp = $yesno;
		return $this;
	}

	/**
	 * Enable the no_new_privs attribute to prevent privilege escalation via
	 * setuid executables and similar.
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function noNewPrivs( bool $yesno = true ) {
		$this->noNewPrivs = $yesno;
		return $this;
	}

	/**
	 * Use a private user namespace.
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function privateUserNamespace( bool $yesno = true ) {
		$this->privateUserNamespace = $yesno;
		return $this;
	}

	/**
	 * Create a private /dev mount
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function privateDev( bool $yesno = true ) {
		$this->privateDev = $yesno;
		return $this;
	}

	/**
	 * If called, the files/directories that are allowed will certainly be
	 * available to the shell command.
	 *
	 * Whether this can be respected depends on the configuration of the
	 * executor.
	 *
	 * @param string ...$paths
	 *
	 * @return $this
	 */
	public function allowPath( ...$paths ) {
		$this->allowedPaths = array_merge( $this->allowedPaths, $paths );
		return $this;
	}

	/**
	 * Replace the list of allowed paths.
	 *
	 * @param string[] $paths
	 * @return $this
	 */
	public function allowedPaths( array $paths ) {
		$this->allowedPaths = $paths;
		return $this;
	}

	/**
	 * Disallow the specified paths so that the command cannot access them.
	 *
	 * Whether this can be respected depends on the configuration of the
	 * executor.
	 *
	 * @param string ...$paths
	 * @return $this
	 */
	public function disallowPath( ...$paths ) {
		$this->disallowedPaths = array_merge( $this->disallowedPaths, $paths );
		return $this;
	}

	/**
	 * Replace the list of disallowed paths
	 *
	 * @param string[] $paths
	 * @return $this
	 */
	public function disallowedPaths( array $paths ) {
		$this->disallowedPaths = $paths;
		return $this;
	}

	/**
	 * Disable firejail and similar sandboxes
	 *
	 * @param bool $yesno
	 * @return $this
	 */
	public function disableSandbox( bool $yesno = true ) {
		$this->disableSandbox = $yesno;
		return $this;
	}

	/**
	 * Get command parameters for JSON serialization by the client.
	 *
	 * @internal
	 * @return array
	 */
	public function getClientData() {
		return [
			'command' => $this->command,
			'cpuLimit' => $this->cpuTimeLimit,
			'wallTimeLimit' => $this->wallTimeLimit,
			'memoryLimit' => $this->memoryLimit,
			'fileSizeLimit' => $this->fileSizeLimit,
			'environment' => $this->environment,
			'includeStderr' => $this->includeStderr,
			'logStderr' => $this->logStderr
		];
	}

	/**
	 * Set command parameters using a data array created by getClientData()
	 *
	 * @internal
	 * @param array $data
	 */
	public function setClientData( $data ) {
		foreach ( $data as $name => $value ) {
			switch ( $name ) {
				case 'command':
					$this->command = $value;
					$this->syntaxInfo = null;
					break;

				case 'cpuLimit':
					$this->cpuTimeLimit = $value;
					break;

				case 'wallTimeLimit':
					$this->wallTimeLimit = $value;
					break;

				case 'memoryLimit':
					$this->memoryLimit = $value;
					break;

				case 'fileSizeLimit':
					$this->fileSizeLimit = $value;
					break;

				case 'environment':
					$this->environment = $value;
					break;

				case 'includeStderr':
					$this->includeStderr = $value;
					break;

				case 'logStderr':
					$this->logStderr = $value;
					break;
			}
		}
	}

	/**
	 * Get the current command string
	 *
	 * @return string
	 */
	public function getCommandString() {
		return $this->command;
	}

	/**
	 * Get the CPU limit
	 *
	 * @return int|float|null
	 */
	public function getCpuTimeLimit() {
		return $this->cpuTimeLimit;
	}

	/**
	 * Get the wall clock time limit
	 *
	 * @return int|float|null
	 */
	public function getWallTimeLimit() {
		return $this->wallTimeLimit;
	}

	/**
	 * Get the memory limit
	 *
	 * @return int|float|null
	 */
	public function getMemoryLimit() {
		return $this->memoryLimit;
	}

	/**
	 * Get the file size limit
	 *
	 * @return int|float|null
	 */
	public function getFileSizeLimit() {
		return $this->fileSizeLimit;
	}

	/**
	 * Get the environment
	 *
	 * @return string[]
	 */
	public function getEnvironment() {
		return $this->environment;
	}

	/**
	 * Get the text to be passed to stdin
	 *
	 * @return string
	 */
	public function getStdin() {
		return $this->stdin;
	}

	/**
	 * Get whether to pass through stdin
	 *
	 * @return bool
	 */
	public function getPassStdin() {
		return $this->passStdin;
	}

	/**
	 * Get whether to duplicate stderr to stdout
	 *
	 * @return bool
	 */
	public function getIncludeStderr() {
		return $this->includeStderr;
	}

	/**
	 * Get whether to log text seen on stderr
	 *
	 * @return bool
	 */
	public function getLogStderr() {
		return $this->logStderr;
	}

	/**
	 * Get whether to forward the command's stderr to the parent's stderr
	 *
	 * @return bool
	 */
	public function getForwardStderr() {
		return $this->forwardStderr;
	}

	/**
	 * Get whether to enable the log pipe
	 *
	 * @return bool
	 */
	public function getUseLogPipe() {
		return $this->useLogPipe;
	}

	/**
	 * @return string|null
	 */
	public function getWorkingDirectory() {
		return $this->workingDirectory;
	}

	/**
	 * Get the additional proc_open() options
	 *
	 * @return array
	 */
	public function getProcOpenOptions() {
		return $this->procOpenOptions;
	}

	/**
	 * Get whether to disable external networking
	 *
	 * @return bool
	 */
	public function getDisableNetwork() {
		return $this->disableNetwork;
	}

	/**
	 * Get the list of disabled syscalls
	 *
	 * @return string[]
	 */
	public function getDisabledSyscalls() {
		return $this->disabledSyscalls;
	}

	/**
	 * Get whether to use firejail's default seccomp filter
	 *
	 * @return bool
	 */
	public function getFirejailDefaultSeccomp() {
		return $this->firejailDefaultSeccomp;
	}

	/**
	 * Get whether to enable the no_new_privs process attribute
	 *
	 * @return bool
	 */
	public function getNoNewPrivs() {
		return $this->noNewPrivs;
	}

	/**
	 * Get whether to use a private user namespace
	 *
	 * @return bool
	 */
	public function getPrivateUserNamespace() {
		return $this->privateUserNamespace;
	}

	/**
	 * Get whether to mount a private /dev filesystem
	 *
	 * @return bool
	 */
	public function getPrivateDev() {
		return $this->privateDev;
	}

	/**
	 * Get the allowed paths
	 *
	 * @return string[]
	 */
	public function getAllowedPaths() {
		return $this->allowedPaths;
	}

	/**
	 * Get the disallowed paths
	 *
	 * @return string[]
	 */
	public function getDisallowedPaths() {
		return $this->disallowedPaths;
	}

	/**
	 * Get whether to disable firejail and similar sandboxes
	 *
	 * @return bool
	 */
	public function getDisableSandbox() {
		return $this->disableSandbox;
	}

	/**
	 * Parse the current command string
	 *
	 * @return SyntaxInfo
	 * @throws ShellSyntaxError
	 */
	public function getSyntaxInfo() {
		if ( $this->syntaxInfo === null ) {
			$parser = new ShellParser;
			$tree = $parser->parse( $this->getCommandString() );
			$this->syntaxInfo = $tree->getInfo();
		}
		return $this->syntaxInfo;
	}
}
