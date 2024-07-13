<?php

namespace Shellbox\Action;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shellbox\FileUtils;
use Shellbox\Multipart\MultipartReader;
use Shellbox\Multipart\MultipartUtils;
use Shellbox\Server;
use Shellbox\Shellbox;
use Shellbox\ShellboxError;
use Shellbox\TempDirManager;

/**
 * Base class for actions that share a specific input/output protocol.
 *
 * @todo Protocol documentation
 */
abstract class MultipartAction {
	/** @var TempDirManager */
	protected $tempDirManager;
	/** @var array */
	private $structuredData;
	/** @var string[] */
	private $binaryData;
	/** @var Server */
	private $server;
	/** @var string[] */
	private $headers;
	/** @var LoggerInterface */
	protected $logger;
	/** @var string[] */
	private $inputFiles = [];

	private const COPY_BUFFER_SIZE = 65536;

	/**
	 * @param Server $server
	 */
	public function __construct( Server $server ) {
		$this->server = $server;
		$this->logger = new NullLogger;
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * The entry point for execution of the action
	 *
	 * @param string[] $pathParts
	 */
	public function baseExecute( $pathParts ) {
		try {
			$this->tempDirManager = Shellbox::createTempDirManager(
				$this->getConfig( 'tempDir' ) );
			$this->tempDirManager->setLogger( $this->logger );

			$this->processInput();
			$this->execute( $pathParts );
		} finally {
			if ( $this->tempDirManager ) {
				$this->tempDirManager->teardown();
			}
		}
	}

	/**
	 * Override this to implement the action.
	 *
	 * @param string[] $pathParts
	 */
	abstract protected function execute( $pathParts );

	/**
	 * Override this to provide the action name as used in the URL.
	 *
	 * @return string
	 */
	abstract protected function getActionName();

	/**
	 * Get a parameter from the request, or throw if it isn't present
	 *
	 * @param string $name
	 * @return mixed
	 * @throws ShellboxError
	 */
	protected function getRequiredParam( $name ) {
		$nonexistent = new \stdClass;
		$result = $this->getParam( $name, $nonexistent );
		if ( $result === $nonexistent ) {
			$this->error( "The $name parameter is required" );
		}
		return $result;
	}

	/**
	 * Get a parameter from the request. Return the specified default if it
	 * isn't present.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getParam( $name, $default = null ) {
		return $this->structuredData[$name] ?? $this->binaryData[$name] ?? $default;
	}

	/**
	 * Get a configuration option, or throw if it doesn't exist.
	 *
	 * @param string $name
	 * @return mixed
	 * @throws ShellboxError
	 */
	protected function getConfig( $name ) {
		return $this->server->getConfig( $name );
	}

	/**
	 * Erase a configuration option
	 *
	 * @param string $name
	 */
	protected function forgetConfig( $name ) {
		$this->server->forgetConfig( $name );
	}

	/**
	 * Get a request header of a given name, or null if there was no such header.
	 *
	 * @param string $name
	 * @return string|null
	 */
	protected function getHeader( $name ) {
		if ( $this->headers === null ) {
			$this->headers = array_change_key_case( getallheaders(), CASE_LOWER );
		}
		return $this->headers[strtolower( $name )] ?? null;
	}

	/**
	 * Throw an error exception
	 *
	 * @param string $message
	 * @param int $code
	 * @throws ShellboxError
	 * @return never
	 */
	protected function error( $message, $code = 500 ) {
		throw new ShellboxError( $message, $code );
	}

	/**
	 * Process the request. Read the POST data, do some generic validation,
	 * create input files and populate the parameter arrays.
	 */
	private function processInput() {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			$this->error( 'The POST method must be used', 405 );
		}
		$auth = $this->getHeader( 'Authorization' );
		if ( $auth === null ) {
			$this->error( 'An Authorization header is required' );
		}
		// Phan doesn't understand functions that always throw, so thinks that
		// $auth could still be null. Maybe fixable -- see comment in
		// BlockExitStatusChecker::computeStatusOfCall().
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullableInternal
		if ( !preg_match( '/^sha256 ([0-9a-z]+)$/', $auth, $m ) ) {
			$this->error( 'Invalid Authorization header' );
		}
		$authHash = $m[1];

		$ctype = $this->getHeader( 'Content-Type' );
		if ( !preg_match( '/^multipart\/mixed/i', $ctype, $m ) ) {
			$this->error( 'The Content-Type must be multipart/mixed', 415 );
		}
		$boundary = MultipartUtils::extractBoundary( $ctype );
		if ( $boundary === false || $boundary === '' ) {
			$this->error( 'boundary is a required parameter of Content-Type' );
		}

		$inputFile = FileUtils::openInputFile( 'php://input' );
		$multipartReader = new MultipartReader(
			new Stream( $inputFile ),
			$boundary,
			$this->getConfig( 'secretKey' ) );
		$preamble = $multipartReader->readPreamble();

		if ( trim( $preamble ) !== '' ) {
			$this->error( 'The multipart preamble must be empty, otherwise a ' .
				'fraudulent boundary with a replayed body could be used to send ' .
				'unauthorized requests' );
		}

		// phpcs:ignore MediaWiki.ControlStructures.AssignmentInControlStructures
		while ( ( $headers = $multipartReader->readPartHeaders() ) !== false ) {
			if ( !isset( $headers['content-disposition'] ) ) {
				$this->error( 'Part has no Content-Disposition', 400 );
			}
			$disposition = $headers['content-disposition'];
			if ( $disposition['type'] === 'json-data' ) {
				$this->structuredData = $multipartReader->readPartAsJson( $headers );
			} elseif ( $disposition['type'] === 'form-data' ) {
				if ( !isset( $disposition['name'] ) ) {
					$this->error( "multipart form-data requires name" );
				}
				$name = $disposition['name'];
				$this->binaryData[$name] = $multipartReader->readPartAsString();
			} elseif ( $disposition['type'] === 'attachment' ) {
				$this->processFile( $multipartReader, $headers );
			} else {
				$this->error( "Unknown content disposition type" );
			}
		}
		$multipartReader->readEpilogue();

		if ( $this->getRequiredParam( 'action' ) !== $this->getActionName() ) {
			$this->error( "The URL action must match the HMAC-verified parameter" );
		}

		if ( !hash_equals( $multipartReader->getHash(), $authHash ) ) {
			$this->error( "HMAC signature verification failed" );
		}
	}

	/**
	 * Extract a single file from the MultipartReader
	 *
	 * @param MultipartReader $multipartReader
	 * @param array $headers The part headers
	 */
	private function processFile( $multipartReader, $headers ) {
		if ( !isset( $headers['content-disposition']['filename'] ) ) {
			$this->error( 'Part has no filename' );
		}
		$fileName = Shellbox::normalizePath( $headers['content-disposition']['filename'] );
		$path = $this->tempDirManager->preparePath( $fileName );
		$file = FileUtils::openOutputFile( $path );
		$multipartReader->copyPartToStream( Utils::streamFor( $file ) );
		$this->inputFiles[] = $fileName;
	}

	/**
	 * Write a standard result
	 *
	 * @param array $structuredData JSON serializable data
	 * @param string[] $binaryData An array of strings to be sent as multipart
	 *   parts
	 * @param string[] $files The names of the output files relative to the
	 *   working directory
	 */
	protected function writeResult( $structuredData, $binaryData = [], $files = [] ) {
		$boundary = Shellbox::getUniqueString();
		header( "Content-Type: multipart/mixed; boundary=\"$boundary\"" );
		$parts = [];

		$structuredData['log'] = $this->server->flushLogBuffer();
		$parts[] = [
			'name' => 'json-data',
			'headers' => [
				'Content-Type' => 'application/json',
				'Content-Disposition' => 'json-data',
			],
			'contents' => Shellbox::jsonEncode( $structuredData )
		];
		foreach ( $binaryData as $name => $value ) {
			$parts[] = [
				'name' => $name,
				'contents' => $value
			];
		}
		foreach ( $files as $name ) {
			// phpcs:ignore Generic.PHP.NoSilencedErrors
			$stream = @fopen( $this->tempDirManager->getPath( $name ), 'r' );
			if ( $stream ) {
				$parts[] = [
					'name' => $name,
					'headers' => [
						'Content-Type' => 'application/octet-stream',
						'Content-Disposition' => "attachment; name=\"$name\""
					],
					'contents' => Utils::streamFor( $stream )
				];
			}
		}
		$multipartStream = new MultipartStream( $parts, $boundary );
		while ( !$multipartStream->eof() ) {
			echo $multipartStream->read( self::COPY_BUFFER_SIZE );
		}
	}

	/**
	 * Get the names of the received files, relative to the temporary directory
	 *
	 * @return string[]
	 */
	protected function getReceivedFileNames() {
		return $this->inputFiles;
	}
}
