<?php

namespace Shellbox;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Shellbox\Action\CallAction;
use Shellbox\Action\ShellAction;
use Throwable;

/**
 * The Shellbox server main class
 *
 * To use this, create a PHP entry point file with:
 *
 *   require __DIR__ . '/vendor/autoload.php';
 *   Shellbox\Server::main();
 *
 */
class Server {
	/** @var array */
	private $config;
	/** @var bool[] */
	private $forgottenConfig = [];
	/** @var Logger */
	private $logger;
	/** @var ClientLogHandler|null */
	private $clientLogHandler;

	private const DEFAULT_CONFIG = [
		'allowedActions' => [ 'call', 'shell' ],
		'allowedRoutes' => null,
		'routeSpecs' => [],
		'useSystemd' => null,
		'useBashWrapper' => null,
		'useFirejail' => null,
		'firejailPath' => null,
		'firejailProfile' => null,
		'logFile' => false,
		'jsonLogFile' => false,
		'logToStderr' => false,
		'jsonLogToStderr' => false,
		'syslogIdent' => 'shellbox',
		'logToSyslog' => false,
		'logToClient' => true,
		'logFormat' => LineFormatter::SIMPLE_FORMAT
	];

	/**
	 * The main entry point. Call this from the webserver.
	 *
	 * @param string|null $configPath The location of the JSON config file
	 */
	public static function main( $configPath = null ) {
		( new self )->execute( $configPath );
	}

	/**
	 * Non-static entry point
	 *
	 * @param string|null $configPath
	 */
	protected function execute( $configPath ) {
		set_error_handler( [ $this, 'handleError' ] );
		try {
			$this->guardedExecute( $configPath );
		} catch ( Throwable $e ) {
			$this->handleException( $e );
		} finally {
			set_error_handler( null );
		}
	}

	/**
	 * Entry point that may throw exceptions
	 *
	 * @param string|null $configPath
	 */
	private function guardedExecute( $configPath ) {
		$this->setupConfig( $configPath );
		$this->setupLogger();

		$url = $_SERVER['REQUEST_URI'];
		$base = ( new Uri( $this->getConfig( 'url' ) ) )->getPath();
		if ( $base[-1] !== '/' ) {
			$base .= '/';
		}
		if ( substr_compare( $url, $base, 0, strlen( $base ) ) !== 0 ) {
			throw new ShellboxError( "Request URL does not match configured base path", 404 );
		}
		$baseLength = strlen( $base );
		$pathInfo = substr( $url, $baseLength );
		$components = explode( '/', $pathInfo );
		$action = array_shift( $components );

		if ( $action === '' ) {
			throw new ShellboxError( "No action was specified" );
		}
		if ( $action === 'healthz' ) {
			$this->showHealth();
			return;
		} elseif ( $action === 'spec' ) {
			$this->showSpec();
			return;
		}

		if ( $this->validateAction( $action ) ) {
			switch ( $action ) {
				case 'call':
					$handler = new CallAction( $this );
					break;

				case 'shell':
					$handler = new ShellAction( $this );
					break;

				default:
					throw new ShellboxError( "Unknown action: $action" );
			}
		} else {
			throw new ShellboxError( "Invalid action: $action" );
		}

		$handler->setLogger( $this->logger );
		$handler->baseExecute( $components );
	}

	/**
	 * Read the configuration file into $this->config
	 *
	 * @param string|null $configPath
	 */
	private function setupConfig( $configPath ) {
		if ( $configPath === null ) {
			$configPath = $_ENV['SHELLBOX_CONFIG_PATH'] ?? '';
			if ( $configPath === '' ) {
				$configPath = __DIR__ . '/../shellbox-config.json';
			}
		}
		$json = file_get_contents( $configPath );
		if ( $json === false ) {
			throw new ShellboxError( 'This entry point is disabled: ' .
				"the configuration file $configPath is not present" );
		}
		$config = json_decode( $json, true );
		if ( $config === null ) {
			throw new ShellboxError( 'Error parsing JSON config file' );
		}

		$key = $_ENV['SHELLBOX_SECRET_KEY'] ?? $_SERVER['SHELLBOX_SECRET_KEY'] ?? '';
		if ( $key !== '' ) {
			if ( isset( $config['secretKey'] )
				&& $key !== $config['secretKey']
			) {
				throw new ShellboxError( 'The SHELLBOX_SECRET_KEY server ' .
					'variable conflicts with the secretKey configuration' );
			}
			// Attempt to hide the key from code running later in the same request.
			// I think this could be made to be secure in plain CGI and
			// apache2handler, but it doesn't work in FastCGI or FPM modes.
			if ( function_exists( 'apache_setenv' ) ) {
				apache_setenv( 'SHELLBOX_SECRET_KEY', '' );
			}
			$_SERVER['SHELLBOX_SECRET_KEY'] = '';
			$_ENV['SHELLBOX_SECRET_KEY'] = '';
			putenv( 'SHELLBOX_SECRET_KEY=' );
			$config['secretKey'] = $key;
		}

		$this->config = $config + self::DEFAULT_CONFIG;
	}

	/**
	 * Get a configuration variable
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getConfig( $name ) {
		if ( isset( $this->forgottenConfig[$name] ) ) {
			throw new ShellboxError( "Access to the configuration variable \"$name\" " .
				"is no longer possible" );
		}
		if ( !array_key_exists( $name, $this->config ) ) {
			throw new ShellboxError( "The configuration variable \"$name\" is required, " .
				"but it is not present in the config file." );
		}
		return $this->config[$name];
	}

	/**
	 * Forget a configuration variable. This is used to try to hide the HMAC
	 * key from code which is run by the call action.
	 *
	 * @param string $name
	 */
	public function forgetConfig( $name ) {
		if ( isset( $this->config[$name] ) && is_string( $this->config[$name] ) ) {
			$conf =& $this->config[$name];
			$length = strlen( $conf );
			for ( $i = 0; $i < $length; $i++ ) {
				$conf[$i] = ' ';
			}
			unset( $conf );
		}
		unset( $this->config[$name] );
		$this->forgottenConfig[$name] = true;
	}

	/**
	 * Set up logging based on current configuration.
	 */
	private function setupLogger() {
		$this->logger = new Logger( 'shellbox' );
		$this->logger->pushProcessor( new PsrLogMessageProcessor );
		$formatter = new LineFormatter( $this->getConfig( 'logFormat' ) );
		$jsonFormatter = new JsonFormatter( JsonFormatter::BATCH_MODE_NEWLINES );

		if ( strlen( $this->getConfig( 'logFile' ) ) ) {
			$handler = new StreamHandler( $this->getConfig( 'logFile' ) );
			$handler->setFormatter( $formatter );
			$this->logger->pushHandler( $handler );
		}
		if ( strlen( $this->getConfig( 'jsonLogFile' ) ) ) {
			$handler = new StreamHandler( $this->getConfig( 'jsonLogFile' ) );
			$handler->setFormatter( $jsonFormatter );
			$this->logger->pushHandler( $handler );
		}
		if ( $this->getConfig( 'logToStderr' ) ) {
			$handler = new StreamHandler( 'php://stderr' );
			$handler->setFormatter( $formatter );
			$this->logger->pushHandler( $handler );
		}
		if ( $this->getConfig( 'jsonLogToStderr' ) ) {
			$handler = new StreamHandler( 'php://stderr' );
			$handler->setFormatter( $jsonFormatter );
			$this->logger->pushHandler( $handler );
		}
		if ( $this->getConfig( 'logToSyslog' ) ) {
			$this->logger->pushHandler(
				new SyslogHandler( $this->getConfig( 'syslogIdent' ) ) );
		}
		if ( $this->getConfig( 'logToClient' ) ) {
			$this->clientLogHandler = new ClientLogHandler;
			$this->logger->pushHandler( $this->clientLogHandler );
		}
	}

	/**
	 * Check whether the action is in the list of allowed actions.
	 *
	 * @param string $action
	 * @return bool
	 */
	private function validateAction( $action ) {
		$allowed = $this->getConfig( 'allowedActions' );
		return in_array( $action, $allowed, true );
	}

	/**
	 * Handle an exception.
	 *
	 * @param Throwable $exception
	 */
	private function handleException( $exception ) {
		if ( $this->logger ) {
			$this->logger->error(
				"Exception of class " . get_class( $exception ) . ': ' .
				$exception->getMessage(),
				[
					'trace' => $exception->getTraceAsString()
				]
			);
		}

		if ( headers_sent() ) {
			return;
		}

		if ( $exception->getCode() >= 300 && $exception->getCode() < 600 ) {
			$code = $exception->getCode();
		} else {
			$code = 500;
		}
		$code = intval( $code );
		$response = new Response( $code );
		$reason = $response->getReasonPhrase();
		header( "HTTP/1.1 $code $reason" );
		header( 'Content-Type: application/json' );

		echo Shellbox::jsonEncode( [
			'__' => 'Shellbox server error',
			'class' => get_class( $exception ),
			'message' => $exception->getMessage(),
			'log' => $this->flushLogBuffer(),
		] );
	}

	/**
	 * Handle an error
	 * @param int $level
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return never
	 */
	public function handleError( $level, $message, $file, $line ) {
		throw new ShellboxError( "PHP error in $file line $line: $message" );
	}

	/**
	 * healthz action
	 */
	private function showHealth() {
		header( 'Content-Type: application/json' );
		echo Shellbox::jsonEncode( [
			'__' => 'Shellbox running',
			'pid' => getmypid()
		] );
	}

	/**
	 * spec action
	 */
	private function showSpec() {
		header( 'Content-Type: application/json' );
		echo file_get_contents( __DIR__ . '/spec.json' );
	}

	/**
	 * Get the buffered log entries to return to the client, and clear the
	 * buffer. If logToClient is false, this returns an empty array.
	 *
	 * @return array
	 */
	public function flushLogBuffer() {
		return $this->clientLogHandler ? $this->clientLogHandler->flush() : [];
	}
}
