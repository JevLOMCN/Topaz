<?php

namespace Shellbox;

use Psr\Log\LoggerInterface;
use Shellbox\Command\LocalBoxedExecutor;
use Shellbox\Command\UnboxedExecutor;

/**
 * Static factories and miscellaneous utility functions
 */
class Shellbox {
	/**
	 * Create a LocalBoxedExecutor from a configuration array. This can be used
	 * to run commands locally, without the client/server split.
	 *
	 * @param array $config Associative array of configuration parameters:
	 *   - tempDir: The parent directory in which a temporary directory may be created
	 *   - useSystemd: If true, systemd-run will be used
	 *   - useBashWrapper: If true, limit.sh will be used
	 *   - useFirejail: If true, firejail will be used
	 *   - firejailPath: The path to the firejail binary
	 *   - firejailProfile: The path to the firejail profile
	 *   - cgroup: A writable cgroup path which can be used for manual memory
	 *     limiting
	 * @param LoggerInterface|null $logger
	 * @return LocalBoxedExecutor
	 */
	public static function createBoxedExecutor( $config = [], LoggerInterface $logger = null ) {
		$tempDirManager = self::createTempDirManager( $config['tempDir'] ?? null );
		$unboxedExecutor = new UnboxedExecutor;
		$unboxedExecutor->setTempDirManager( $tempDirManager );
		$unboxedExecutor->addWrappersFromConfiguration( $config );
		$executor = new LocalBoxedExecutor( $unboxedExecutor, $tempDirManager );
		if ( $logger ) {
			$executor->setLogger( $logger );
			$unboxedExecutor->setLogger( $logger );
			$tempDirManager->setLogger( $logger );
		}
		return $executor;
	}

	/**
	 * Create an UnboxedExecutor from a configuration array. This can be used
	 * to run commands locally, without temporary directory setup or the
	 * client/server split.
	 *
	 * A temporary directory is only needed if the command runs on Windows.
	 *
	 * @param array $config Associative array of configuration parameters:
	 *   - tempDir: The parent directory in which a temporary directory may be created
	 *   - useSystemd: If true, systemd-run will be used
	 *   - useBashWrapper: If true, limit.sh will be used
	 *   - useFirejail: If true, firejail will be used
	 *   - firejailPath: The path to the firejail binary
	 *   - firejailProfile: The path to the firejail profile
	 *   - cgroup: A writable cgroup path which can be used for manual memory
	 *     limiting
	 * @param LoggerInterface|null $logger
	 * @return UnboxedExecutor
	 */
	public static function createUnboxedExecutor( $config = [], LoggerInterface $logger = null ) {
		$executor = new UnboxedExecutor( $config['tempDir'] ?? null );
		$executor->addWrappersFromConfiguration( $config );
		if ( $logger ) {
			$executor->setLogger( $logger );
		}
		return $executor;
	}

	/**
	 * Create a TempDirManager from a shared base path (e.g. /tmp)
	 *
	 * @param string|null $tempDirBase
	 * @return TempDirManager
	 */
	public static function createTempDirManager( $tempDirBase = null ) {
		if ( $tempDirBase === null ) {
			$tempDirBase = sys_get_temp_dir();
		}
		return new TempDirManager(
			$tempDirBase . '/shellbox-' . self::getUniqueString()
		);
	}

	/**
	 * Escape arguments for the shell
	 *
	 * @param mixed|mixed[] ...$args strings to escape and glue together, or a single
	 *     array of strings parameter. Null values are ignored.
	 * @return string
	 */
	public static function escape( ...$args ): string {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			// If only one argument has been passed, and that argument is an array,
			// treat it as a list of arguments
			$args = $args[0];
		}

		$first = true;
		$retVal = '';
		foreach ( $args as $arg ) {
			if ( $arg === null ) {
				continue;
			}
			$arg = (string)$arg;
			if ( !$first ) {
				$retVal .= ' ';
			} else {
				$first = false;
			}

			if ( PHP_OS_FAMILY === 'Windows' ) {
				// Escaping for an MSVC-style command line parser and CMD.EXE
				// Refs:
				// * phpcs:ignore Generic.Files.LineLength.TooLong
				// * https://web.archive.org/web/20020708081031/http://mailman.lyra.org/pipermail/scite-interest/2002-March/000436.html
				// * https://technet.microsoft.com/en-us/library/cc723564.aspx
				// * T15518
				// * CR r63214
				// Double the backslashes before any double quotes. Escape the double quotes.
				$tokens = preg_split( '/(\\\\*")/', $arg, -1, PREG_SPLIT_DELIM_CAPTURE );
				$arg = '';
				$iteration = 0;
				foreach ( $tokens as $token ) {
					if ( $iteration % 2 == 1 ) {
						// Delimiter, a double quote preceded by zero or more slashes
						$arg .= str_replace( '\\', '\\\\', substr( $token, 0, -1 ) ) . '\\"';
					} elseif ( $iteration % 4 == 2 ) {
						// ^ in $token will be outside quotes, need to be escaped
						$arg .= str_replace( '^', '^^', $token );
					} else { // $iteration % 4 == 0
						// ^ in $token will appear inside double quotes, so leave as is
						$arg .= $token;
					}
					$iteration++;
				}
				// Double the backslashes before the end of the string, because
				// we will soon add a quote
				$m = [];
				if ( preg_match( '/^(.*?)(\\\\+)$/', $arg, $m ) ) {
					$arg = $m[1] . str_replace( '\\', '\\\\', $m[2] );
				}

				// Add surrounding quotes
				$retVal .= '"' . $arg . '"';
			} else {
				// In PHP 8.0+, the locale is "C" unless setlocale() has been
				// called, regardless of the environment. So escapeshellarg()
				// will strip non-ASCII bytes to avoid misinterpretation by a
				// shell that uses GBK or a similar character set. So we roll
				// our own, but UnboxedExecutor will filter the environment to
				// avoid misinterpretation.
				$retVal .= "'" . str_replace( "'", "'\\''", $arg ) . "'";
			}
		}
		return $retVal;
	}

	/**
	 * Get the platform's maximum command length in bytes, minus a safety margin.
	 *
	 * @return int
	 */
	public static function getMaxCmdLength() {
		if ( PHP_OS_FAMILY === 'Windows' ) {
			// phpcs:ignore Generic.Files.LineLength.TooLong
			// Ref: https://docs.microsoft.com/en-us/windows/win32/api/processthreadsapi/nf-processthreadsapi-createprocessa
			$max = 32767;
		} else {
			// Ref: MAX_ARG_STRLEN in linux/binfmts.h
			$max = 131072;
		}
		// In case there is a hidden extra wrapper
		$max -= 200;
		return $max;
	}

	/**
	 * Get a random string from a CSPRNG.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function getUniqueString() {
		return bin2hex( random_bytes( 8 ) );
	}

	/**
	 * JSON encode with our preferred options
	 * @param mixed $value
	 * @return string
	 */
	public static function jsonEncode( $value ) {
		$json = json_encode( $value,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES |
			JSON_UNESCAPED_UNICODE );
		if ( $json === false ) {
			throw new ShellboxError( "The supplied value cannot be converted " .
				"to JSON. Try transferring it as a file." );
		}
		$json .= "\n";
		return $json;
	}

	/**
	 * Throwing wrapper for JSON decode with our preferred options.
	 *
	 * @param string $json
	 * @return mixed
	 */
	public static function jsonDecode( $json ) {
		// phpcs:ignore Generic.PHP.NoSilencedErrors
		$value = @json_decode( $json, true, 512 );
		if ( $value === null ) {
			throw new ShellboxError( 'Received invalid JSON: ' .
				json_last_error_msg() );
		}
		return $value;
	}

	/**
	 * Validate a relative path for path traversal safety and cross-platform
	 * file name compliance. Under Windows, the path may contain backslashes,
	 * which will be replaced with slashes.
	 *
	 * @param string $path
	 * @return string
	 * @throws ShellboxError
	 */
	public static function normalizePath( $path ) {
		$windowsReservedNames = [
			'CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4',
			'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3',
			'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'
		];

		if ( DIRECTORY_SEPARATOR === '\\' ) {
			$path = str_replace( '\\', '/', $path );
		}
		if ( preg_match( '/[\\x00-\x1f\x7f-\xff<>:"|?*$!\[\];&(){}\\\'\0]/', $path ) ) {
			throw new ShellboxError( "Relative path contains invalid characters" );
		}
		foreach ( explode( '/', $path ) as $component ) {
			if ( $component === ''
				|| $component === '.'
				|| $component === '..'
				|| substr( $component, -1 ) === ':'
			) {
				throw new ShellboxError( "Invalid relative file path \"$path\"" );
			}
			$firstPart = substr( $component, 0, strcspn( $component, '.' ) );
			if ( in_array( strtoupper( $firstPart ), $windowsReservedNames ) ) {
				throw new ShellboxError( "Relative path contains Windows reserved name" );
			}
		}
		return $path;
	}

}
