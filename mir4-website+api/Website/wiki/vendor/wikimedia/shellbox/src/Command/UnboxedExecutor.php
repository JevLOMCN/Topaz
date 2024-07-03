<?php

namespace Shellbox\Command;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shellbox\FileUtils;
use Shellbox\Shellbox;
use Shellbox\ShellboxError;
use Shellbox\TempDirManager;

/**
 * A concrete class for executing UnboxedCommand objects
 */
class UnboxedExecutor {
	/** @var LoggerInterface */
	protected $logger;
	/** @var Wrapper[] */
	private $wrappers = [];

	/** @var string|null */
	private $stdoutPath;

	/** @var string|null */
	private $stderrPath;

	/** @var string|null */
	private $tempDirBase;

	/** @var TempDirManager|null */
	private $tempDirManager;

	/**
	 * @param string|null $tempDirBase The parent directory of the temporary
	 *   directory to use if the command is run on Windows. For example /tmp.
	 *   If this is null, sys_get_temp_dir() will be used. The temporary
	 *   directory may also be overridden later using setTempDirManager().
	 */
	public function __construct( $tempDirBase = null ) {
		$this->logger = new NullLogger;
		$this->tempDirBase = $tempDirBase;
	}

	/**
	 * Create a Command linked to this executor.
	 *
	 * @return UnboxedCommand
	 */
	public function createCommand() {
		return new UnboxedCommand( $this );
	}

	/**
	 * Get a TempDirManager with optional lazy initialisation
	 *
	 * @return TempDirManager
	 */
	protected function getTempDirManager() {
		if ( !$this->tempDirManager ) {
			$tempDirBase = $this->tempDirBase;
			if ( $tempDirBase === null ) {
				$tempDirBase = sys_get_temp_dir();
			}

			$this->tempDirManager = new TempDirManager(
				$tempDirBase . '/shellbox-' . Shellbox::getUniqueString()
			);
		}
		return $this->tempDirManager;
	}

	/**
	 * Explicitly set a TempDirManager, overriding lazy initialisation config
	 *
	 * @param TempDirManager $manager
	 */
	public function setTempDirManager( TempDirManager $manager ) {
		$this->tempDirManager = $manager;
	}

	/**
	 * Set the logger.
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
		foreach ( $this->wrappers as $wrapper ) {
			$wrapper->setLogger( $logger );
		}
	}

	/**
	 * Add a Wrapper, which modifies the Command, typically providing more
	 * security.
	 *
	 * @param Wrapper $wrapper
	 */
	public function addWrapper( Wrapper $wrapper ) {
		$wrapper->setLogger( $this->logger );
		$this->wrappers[] = $wrapper;
	}

	/**
	 * Add wrappers based on a configuration array
	 *
	 * @param array $config Associative array of configuration parameters:
	 *   - useSystemd: If true, systemd-run will be used
	 *   - useBashWrapper: If true, limit.sh will be used
	 *   - useFirejail: If true, firejail will be used
	 *   - firejailPath: The path to the firejail binary
	 *   - firejailProfile: The path to the firejail profile
	 *   - cgroup: A writable cgroup path which can be used for manual memory
	 *     limiting
	 */
	public function addWrappersFromConfiguration( $config ) {
		$isWindows = PHP_OS_FAMILY === 'Windows';
		$useSystemd = !empty( $config['useSystemd'] );
		if ( isset( $config['useBashWrapper'] ) ) {
			$useBashWrapper = $config['useBashWrapper'];
		} elseif ( $isWindows || $useSystemd ) {
			$useBashWrapper = false;
		} else {
			$useBashWrapper = is_executable( '/bin/bash' );
		}

		if ( $useBashWrapper ) {
			$this->addWrapper( new BashWrapper( $config['cgroup'] ?? '' ) );
		}
		if ( !empty( $config['useFirejail'] ) ) {
			$this->addWrapper( new FirejailWrapper(
				$config['firejailPath'] ?? '/usr/bin/firejail',
				$config['firejailProfile'] ?? __DIR__ . '/firejail.profile'
			) );
		}
		if ( $useSystemd ) {
			$this->addWrapper( new SystemdWrapper );
		}
		if ( $isWindows ) {
			$this->addWrapper( new WindowsWrapper );
		}
	}

	/**
	 * Get pipe descriptors for proc_open()
	 *
	 * @param Command $command
	 * @return array
	 */
	private function getPipeDescriptors( Command $command ) {
		$desc = [
			0 => $command->getPassStdin() ? [ 'file', 'php://stdin', 'r' ] : [ 'pipe', 'r' ]
		];
		if ( PHP_OS_FAMILY === 'Windows' ) {
			// PHP's view of Windows anonymous pipes is too broken to be usable
			$this->stdoutPath = $this->getTempDirManager()->preparePath( 'sb-stdout' );
			$this->stderrPath = $this->getTempDirManager()->preparePath( 'sb-stderr' );
			$desc[1] = [ 'file', $this->stdoutPath, 'wb' ];
			$desc[2] = [ 'file', $this->stderrPath, 'wb' ];
		} else {
			$desc[1] = $desc[2] = [ 'pipe', 'w' ];
		}
		if ( $command->getUseLogPipe() ) {
			$desc[3] = [ 'pipe', 'w' ];
		}
		return $desc;
	}

	/**
	 * Get the environment to be passed through to the subprocess. In CLI mode
	 * this uses getenv() because that is backwards compatible and relatively
	 * sensible. In the other SAPIs, there's no way to get the real environment
	 * short of shell_exec('env'), but it's usually near-empty anyway. We add
	 * PATH for convenience.
	 *
	 * In the FastCGI SAPI, $_ENV and getenv() return CGI-like variables sent
	 * from the web server. So the PATH here is typically inherited from Apache
	 * not PHP-FPM.
	 *
	 * @return array
	 */
	private function getParentEnvironment() {
		if ( PHP_SAPI === 'cli' ) {
			return getenv();
		} elseif ( isset( $_ENV['PATH'] ) ) {
			return [ 'PATH' => $_ENV['PATH'] ];
		} else {
			return [];
		}
	}

	/**
	 * If the environment contains an unsafe character set, filter out the
	 * variable.
	 *
	 * @param array &$env
	 */
	private function fixLocaleEnvironment( array &$env ) {
		if ( PHP_OS_FAMILY === 'Windows' ) {
			// Probably OK?
			return;
		}
		foreach ( [ 'LC_CTYPE', 'LC_ALL', 'LANG' ] as $name ) {
			if ( isset( $env[$name] )
				&& preg_match( '/\.(gb\w*)/i', $env[$name] )
			) {
				$this->logger->warning( "Filtering out unsafe environment variable " .
					"$name={$env[$name]}" );
				unset( $env[$name] );
			}
		}
	}

	/**
	 * @param Command $command
	 * @return UnboxedResult
	 * @throws ShellboxError
	 */
	public function execute( Command $command ) {
		$command = clone $command;
		$this->buildFinalCommand( $command );
		$cmd = $command->getCommandString();

		$this->logger->info( "Executing: $cmd" );

		// Don't try to execute commands that exceed Linux's MAX_ARG_STRLEN.
		// Other platforms may be more accomodating, but we don't want to be
		// accomodating, because very long commands probably include user
		// input. See T129506.
		if ( strlen( $cmd ) > Shellbox::getMaxCmdLength() ) {
			throw new ShellboxError( 'Total length of $cmd must not exceed MAX_ARG_STRLEN' );
		}

		$desc = $this->getPipeDescriptors( $command );

		$cmd = $command->getCommandString();
		$options = $command->getProcOpenOptions();

		$combinedEnvironment = $command->getEnvironment() + $this->getParentEnvironment();
		$this->fixLocaleEnvironment( $combinedEnvironment );
		$env = [];
		foreach ( $combinedEnvironment as $name => $value ) {
			$env[] = "$name=$value";
		}
		$pipes = null;
		$proc = proc_open( $cmd, $desc, $pipes,
			$command->getWorkingDirectory(), $env, $options );

		if ( !$proc ) {
			$this->logger->error( "proc_open() failed: {command}", [ 'command' => $cmd ] );
			throw new ShellboxError( 'proc_open() failed' );
		}

		$buffers = [
			0 => $command->getStdin(), // input
			1 => '', // stdout
			2 => '', // stderr
			3 => '', // log
		];
		$emptyArray = [];
		$status = false;
		$logMsg = false;

		/* According to the documentation, it is possible for stream_select()
		 * to fail due to EINTR. I haven't managed to induce this in testing
		 * despite sending various signals. If it did happen, the error
		 * message would take the form:
		 *
		 * stream_select(): unable to select [4]: Interrupted system call (max_fd=5)
		 *
		 * where [4] is the value of the macro EINTR and "Interrupted system
		 * call" is string which according to the Linux manual is "possibly"
		 * localised according to LC_MESSAGES.
		 */
		$eintr = defined( 'SOCKET_EINTR' ) ? SOCKET_EINTR : 4;
		$eintrMessage = "stream_select(): unable to select [$eintr]";

		/* The select(2) system call only guarantees a "sufficiently small write"
		 * can be made without blocking. And on Linux the read might block too
		 * in certain cases, although I don't know if any of them can occur here.
		 * Regardless, set all the pipes to non-blocking to avoid T184171.
		 */
		foreach ( $pipes as $pipe ) {
			stream_set_blocking( $pipe, false );
		}

		$running = true;
		$timeout = null;
		$numReadyPipes = 0;

		while ( $pipes && ( $running === true || $numReadyPipes !== 0 ) ) {
			if ( $running ) {
				$status = proc_get_status( $proc );
				// If the process has terminated, switch to nonblocking selects
				// for getting any data still waiting to be read.
				if ( !$status['running'] ) {
					$running = false;
					$timeout = 0;
				}
			}

			error_clear_last();

			$readPipes = array_filter( $pipes, static function ( $fd ) use ( $desc ) {
				return $desc[$fd][0] === 'pipe' && $desc[$fd][1] === 'r';
			}, ARRAY_FILTER_USE_KEY );
			$writePipes = array_filter( $pipes, static function ( $fd ) use ( $desc ) {
				return $desc[$fd][0] === 'pipe' && $desc[$fd][1] === 'w';
			}, ARRAY_FILTER_USE_KEY );
			// stream_select parameter names are from the POV of us being able to do the operation;
			// proc_open descriptor types are from the POV of the process doing it.
			// So $writePipes is passed as the $read parameter and $readPipes as $write.
			// phpcs:ignore Generic.PHP.NoSilencedErrors
			$numReadyPipes = @stream_select( $writePipes, $readPipes, $emptyArray, $timeout );
			if ( $numReadyPipes === false ) {
				$error = error_get_last();
				if ( !$error ) {
					$logMsg = 'unknown error';
					break;
				} elseif ( strncmp( $error['message'], $eintrMessage, strlen( $eintrMessage ) ) == 0 ) {
					continue;
				} else {
					$logMsg = $error['message'];
					break;
				}
			}
			foreach ( $writePipes + $readPipes as $fd => $pipe ) {
				// True if a pipe is unblocked for us to write into, false if for reading from
				$isWrite = array_key_exists( $fd, $readPipes );

				if ( $isWrite ) {
					// Don't bother writing if the buffer is empty
					if ( $buffers[$fd] === '' ) {
						fclose( $pipes[$fd] );
						unset( $pipes[$fd] );
						continue;
					}
					$res = fwrite( $pipe, $buffers[$fd], 65536 );
				} else {
					$res = fread( $pipe, 65536 );
				}

				if ( $res === false ) {
					$logMsg = 'Error ' . ( $isWrite ? 'writing to' : 'reading from' ) . ' pipe';
					break 2;
				}

				if ( $res === '' || $res === 0 ) {
					// End of file?
					if ( feof( $pipe ) ) {
						fclose( $pipes[$fd] );
						unset( $pipes[$fd] );
					}
				} elseif ( $isWrite ) {
					$buffers[$fd] = (string)substr( $buffers[$fd], $res );
					if ( $buffers[$fd] === '' ) {
						fclose( $pipes[$fd] );
						unset( $pipes[$fd] );
					}
				} else {
					$buffers[$fd] .= $res;
					if ( $fd === 3 && strpos( $res, "\n" ) !== false ) {
						// For the log FD, every line is a separate log entry.
						$lines = explode( "\n", $buffers[3] );
						$buffers[3] = array_pop( $lines );
						foreach ( $lines as $line ) {
							$this->logger->info( $line );
						}
					}
				}
			}
		}

		foreach ( $pipes as $pipe ) {
			fclose( $pipe );
		}

		// Use the status previously collected if possible, since proc_get_status()
		// just calls waitpid() which will not return anything useful the second time.
		if ( $running ) {
			$status = proc_get_status( $proc );
		}

		if ( $logMsg !== false ) {
			// Read/select error
			$retval = -1;
			proc_close( $proc );
		} elseif ( $status['signaled'] ) {
			$logMsg = "Exited with signal {$status['termsig']}";
			// Use the shell convention of setting the exit status to 128 + the signal number
			$retval = 128 + $status['termsig'];
			proc_close( $proc );
		} else {
			if ( $status['running'] ) {
				$retval = proc_close( $proc );
			} else {
				$retval = $status['exitcode'];
				proc_close( $proc );
			}
			if ( $retval == 127 ) {
				$logMsg = "Possibly missing executable file";
			} elseif ( $retval >= 129 && $retval <= 192 ) {
				// Per the shell convention
				$logMsg = "Probably exited with signal " . ( $retval - 128 );
			}
		}

		if ( $logMsg !== false ) {
			$this->logger->warning( "$logMsg: {command}", [ 'command' => $cmd ] );
		}

		if ( $this->stdoutPath !== null ) {
			$stdout = FileUtils::getContents( $this->stdoutPath );
		} else {
			$stdout = $buffers[1];
		}
		if ( $this->stderrPath !== null ) {
			$stderr = FileUtils::getContents( $this->stderrPath );
		} else {
			$stderr = $buffers[2];
		}
		if ( $stderr !== '' && $command->getForwardStderr() ) {
			fwrite( STDERR, $stderr );
		}
		if ( $stderr !== '' && $command->getLogStderr() ) {
			$this->logger->error( "Error running {command}: {error}", [
				'command' => $cmd,
				'error' => $stderr,
				'exitcode' => $retval,
				'exception' => new ShellboxError( 'Shell error' ),
			] );
		}

		return ( new UnboxedResult )
			->exitCode( $retval )
			->stdout( $stdout )
			->stderr( $stderr );
	}

	/**
	 * Modify the command by running wrappers on it.
	 *
	 * @param Command $command
	 */
	protected function buildFinalCommand( Command $command ) {
		$wrappers = $this->wrappers;
		usort( $wrappers, static function ( Wrapper $a, Wrapper $b ) {
			return $a->getPriority() <=> $b->getPriority();
		} );
		foreach ( $wrappers as $wrapper ) {
			$wrapper->wrap( $command );
		}

		if ( $command->getIncludeStderr() ) {
			$command->unsafeCommand( $command->getCommandString() . ' 2>&1' );
		}
	}
}
