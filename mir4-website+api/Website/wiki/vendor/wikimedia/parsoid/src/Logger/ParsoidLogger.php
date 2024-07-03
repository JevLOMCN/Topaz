<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Wikimedia\Parsoid\Utils\PHPUtils;

class ParsoidLogger {
	/* @var Logger */
	private $backendLogger;

	/* @var string|null Null means nothing is enabled */
	private $enabledRE = null;

	/** PORT-FIXME: Not yet implemented. Monolog supports sampling as well! */
	/* @var string */
	private $samplingRE;

	private const PRETTY_LOGTYPE_MAP = [
		'debug' => '[DEBUG]',
		'trace/peg' => '[peg]',
		'trace/pre' => '[PRE]',
		'debug/pre' => '[PRE-DBG]',
		'trace/p-wrap' => '[P]',
		'trace/html' => '[HTML]',
		'debug/html' => '[HTML-DBG]',
		'trace/sanitizer' => '[SANITY]',
		'trace/tsp' => '[TSP]',
		'trace/dsr' => '[DSR]',
		'trace/list' => '[LIST]',
		'trace/quote' => '[QUOTE]',
		'trace/wts' => '[WTS]',
		'debug/wts' => '---[WTS-DBG]---',
		'debug/wts/sep' => '[SEP]',
		'trace/selser' => '[SELSER]',
		'trace/domdiff' => '[DOM-DIFF]',
		'trace/wt-escape' => '[wt-esc]',
		'trace/ttm:2' => '[2-TTM]',
		'trace/ttm:3' => '[3-TTM]',
	];

	/**
	 * TRACE / DEBUG: Make trace / debug regexp with appropriate postfixes,
	 * depending on the command-line options passed in.
	 *
	 * @param array $flags
	 * @param string $logType
	 * @return string
	 */
	private function buildLoggingRE( array $flags, string $logType ): string {
		return $logType . '/(?:' . implode( '|', array_keys( $flags ) ) . ')(?:/|$)';
	}

	/**
	 * @param LoggerInterface $backendLogger
	 * @param array $options
	 * - logLevels  string[]
	 * - traceFlags <string,bool>[]
	 * - debugFlags <string,bool>[]
	 * - dumpFlags  <string,bool>[]
	 */
	public function __construct( LoggerInterface $backendLogger, array $options ) {
		$this->backendLogger = $backendLogger;

		$rePatterns = $options['logLevels'];
		if ( $options['traceFlags'] ) {
			$rePatterns[] = $this->buildLoggingRE( $options['traceFlags'], 'trace' );
		}
		if ( $options['debugFlags'] ) {
			$rePatterns[] = $this->buildLoggingRE( $options['debugFlags'], 'debug' );
		}
		if ( $options['dumpFlags'] ) {
			// For tracing, Parsoid simply calls $env->log( "trace/SOMETHING", ... );
			// The filtering based on whether a trace is enabled is handled by this class.
			// This is done via the regexp pattern being constructed above.
			// However, for dumping, at some point before / during the port from JS to PHP,
			// all filtering is being done at the site of constructing dumps. This might
			// have been because of the expensive nature of dumps, but closures could have
			// been used. In any case, given that usage, we don't need to do any filtering
			// here. The only caller for dumps is Env.php::writeDump right now which is
			// called after filtering for enabled flags, and which calls us with a "dump"
			// prefix. So, all we need to do here is enable the 'dump' prefix without
			// processing CLI flags. In the future, if those dump logging call sites go
			// back to usage like $env->log( "dump/dom:post-dsr", ... ), etc. we can
			// switch this back to constructing a regexp.
			$rePatterns[] = 'dump'; // $this->buildLoggingRE( $options['dumpFlags'], 'dump' );
		}

		if ( count( $rePatterns ) > 0 ) {
			$this->enabledRE = '#^(?:' . implode( '|', $rePatterns ) . ')#';
		}
	}

	/**
	 * PORT-FIXME: This can become a MonologFormatter possibly.
	 *
	 * We can create channel-specific loggers and this formatter
	 * can be added to the trace-channel logger.
	 *
	 * @param string $logType
	 * @param array $args
	 * @return string
	 */
	private function formatTrace( string $logType, array $args ): string {
		$typeColumnWidth = 15;
		$firstArg = $args[0];

		// Assume first numeric arg is always the pipeline id
		if ( is_numeric( $firstArg ) ) {
			$msg = $firstArg . '-';
			array_shift( $args );
		} else {
			$msg = '';
		}

		// indent by number of slashes
		$numMatches = substr_count( $logType, '/' );
		$indent = str_repeat( '  ', $numMatches > 1 ? $numMatches - 1 : 0 );
		$msg .= $indent;

		$prettyLogType = self::PRETTY_LOGTYPE_MAP[$logType] ?? null;
		if ( $prettyLogType ) {
			$msg .= $prettyLogType;
		} else {
			// XXX: could shorten or strip trace/ logType prefix in a pure trace logger
			$msg .= $logType;

			// More space for these log types
			$typeColumnWidth = 30;
		}

		// Fixed-width type column so that the messages align
		$msg = substr( $msg, 0, $typeColumnWidth );
		$msg .= str_repeat( ' ', $typeColumnWidth - strlen( $msg ) );
		$msg .= '|' . $indent . $this->logMessage( null, $args );

		return $msg;
	}

	/**
	 * @param ?string $logType
	 * @param array $args
	 * @return string
	 */
	private function logMessage( ?string $logType, array $args ): string {
		$numArgs = count( $args );
		$output = $logType ? "[$logType]" : '';
		foreach ( $args as $arg ) {
			// don't use is_callable, it would return true for any string that happens to be a function name
			if ( $arg instanceof \Closure ) {
				$output .= ' ' . $arg();
			} elseif ( is_string( $arg ) ) {
				if ( strlen( $arg ) ) {
					$output .= ' ' . $arg;
				}
			} else {
				$output .= PHPUtils::jsonEncode( $arg );
			}
		}

		return $output;
	}

	/**
	 * @param string $prefix
	 * @param mixed ...$args
	 */
	public function log( string $prefix, ...$args ): void {
		// FIXME: This requires enabled loglevels to percolate all the way here!!
		// Quick check for un-enabled logging.
		if ( !$this->enabledRE || !preg_match( $this->enabledRE, $prefix ) ) {
			return;
		}

		// PORT-FIXME: Are there any instances where we will have this?
		if ( $this->backendLogger instanceof \Psr\Log\NullLogger ) {
			// No need to build the string if it's going to be thrown away anyway.
			return;
		}

		$logLevel = strstr( $prefix, '/', true ) ?: $prefix;

		// Handle trace type first
		if ( $logLevel === 'trace' || $logLevel === 'debug' ) {
			$this->backendLogger->log( LogLevel::DEBUG, $this->formatTrace( $prefix, $args ) );
		} else {
			if ( $logLevel === 'dump' ) {
				$logLevel = LogLevel::DEBUG;
			} elseif ( $logLevel === 'fatal' ) {
				$logLevel = LogLevel::CRITICAL;
			} elseif ( $logLevel === 'warn' ) {
				$logLevel = LogLevel::WARNING;
			}
			$this->backendLogger->log( $logLevel, $this->logMessage( $prefix, $args ) );
		}
	}
}
