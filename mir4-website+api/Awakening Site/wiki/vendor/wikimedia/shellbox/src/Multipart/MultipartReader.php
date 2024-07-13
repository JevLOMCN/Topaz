<?php

namespace Shellbox\Multipart;

use Psr\Http\Message\StreamInterface;
use Shellbox\Shellbox;

/**
 * A streaming parser for multipart content
 *
 * Typical usage:
 *    $reader = new MultipartReader( $stream, $boundary );
 *    $reader->readPrologue();
 *    while ( $headers = $reader->readPartHeaders() ) {
 *        $contents = $reader->readPartAsString();
 *    }
 *    $reader->readEpilogue();
 */
class MultipartReader {
	/** @var StreamInterface */
	private $stream;
	/** @var string */
	private $boundary;
	/** @var string */
	private $startBoundary;
	/** @var string */
	private $encapsulationBoundary;
	/** @var string */
	private $closingBoundary;
	/** @var string */
	private $buffer;
	/** @var \HashContext|null */
	private $hashContext;
	/** @var string|null */
	private $hash;
	/**
	 * @var bool True when the body is complete and it is time to read the
	 *   headers of the next part
	 */
	private $atPartEnd = false;
	/** @var bool True when we are reading the epilogue */
	private $inEpilogue = false;
	/** @var bool True when we are at the start of the stream */
	private $atStreamStart = true;
	/** @var bool True when EOF has been reached */
	private $atStreamEnd = false;

	/**
	 * @var int The number of bytes available in the buffer below which the
	 *   buffer is refilled.
	 */
	private const MIN_BUFFER_SIZE = 16384;

	/** @var int The maximum number of bytes withdrawn from the buffer each time */
	public const CHUNK_SIZE = 8192;

	/**
	 * @param StreamInterface $stream
	 * @param string $boundary
	 * @param string|false $hmacKey
	 */
	public function __construct( StreamInterface $stream, string $boundary, $hmacKey = false ) {
		$this->stream = $stream;
		$this->boundary = $boundary;
		$this->startBoundary = "--$boundary\r\n";
		$this->encapsulationBoundary = "\r\n--$boundary\r\n";
		$this->closingBoundary = "\r\n--$boundary--\r\n";
		$this->buffer = '';
		if ( $hmacKey !== false ) {
			$this->hashContext = hash_init( 'sha256',  HASH_HMAC, $hmacKey );
		}
	}

	/**
	 * This must be called first on a new multipart stream, to discard any text
	 * before the first boundary.
	 *
	 * @return string
	 */
	public function readPreamble() {
		return $this->readPartAsString();
	}

	/**
	 * Call this after the last part to read the epilogue, which runs from the
	 * closing boundary to the end of the stream.
	 */
	public function readEpilogue() {
		do {
			$this->buffer = '';
			$this->refillBuffer();
			// @phan-suppress-next-line PhanSuspiciousValueComparison
		} while ( $this->buffer !== '' );
		$this->atStreamEnd = true;
	}

	/**
	 * Read the part headers.
	 *
	 * The Content-Disposition header is decoded, and its value is an
	 * associative array representing the header's parameters. Other headers
	 * are returned as strings.
	 *
	 * The header names are converted to lower case.
	 *
	 * Following the HTTP convention, we don't support line continuation.
	 * The writer we use (MultipartStream) doesn't use line continuation, so
	 * this lack of compliance should be harmless.
	 *
	 * After reading the headers, the caller should call one of readPartAsString(),
	 * copyPartToStream() or readPartAsJson().
	 *
	 * @return array|bool Decoded headers, or false if the closing boundary was reached.
	 * @throws MultipartError
	 */
	public function readPartHeaders() {
		if ( $this->inEpilogue ) {
			return false;
		}
		$headers = [];
		while ( true ) {
			$line = $this->readLine();
			if ( $line === '' ) {
				break;
			}
			$lineParts = explode( ':', $line, 2 );
			if ( count( $lineParts ) !== 2 ) {
				$this->error( 'invalid part header' );
			}
			if ( preg_match( '/^\s+/', $lineParts[0] ) ) {
				$this->error( 'line continuation is not supported' );
			}
			$name = strtolower( $lineParts[0] );
			$value = trim( $lineParts[1] );
			if ( $name === 'content-disposition' ) {
				$structuredValue = [];
				$headerParts = explode( ';', $value, 2 );
				$structuredValue['type'] = trim( $headerParts[0] );
				if ( count( $headerParts ) > 1 ) {
					$structuredValue += MultipartUtils::decodeParameters( $headerParts[1] );
				}
				$headers[$name] = $structuredValue;
			} else {
				$headers[$name] = $value;
			}
		}
		return $headers;
	}

	/**
	 * Read a part to a string buffer.
	 *
	 * @return string
	 */
	public function readPartAsString() {
		$result = '';
		while ( !$this->atPartEnd ) {
			$chunk = $this->readChunk();
			$result .= $chunk;
		}
		$this->nextPart();
		return $result;
	}

	/**
	 * Copy a part to the specified stream, which is open for writing.
	 *
	 * @param StreamInterface $outputStream
	 */
	public function copyPartToStream( StreamInterface $outputStream ) {
		while ( !$this->atPartEnd ) {
			$chunk = $this->readChunk();
			if ( strlen( $chunk ) ) {
				$outputStream->write( $chunk );
			}
		}
		$this->nextPart();
	}

	/**
	 * Read a part to a string and decode the JSON found within.
	 *
	 * @param array $headers
	 * @return mixed
	 * @throws MultipartError
	 */
	public function readPartAsJson( $headers ) {
		if ( ( $headers['content-type'] ?? '' ) !== 'application/json' ) {
			$this->error( 'part must be application/json' );
		}
		$body = $this->readPartAsString();
		return Shellbox::jsonDecode( $body );
	}

	/**
	 * Get the HMAC of all the content in the stream. This must be called after
	 * the reader has reached the end of the input stream.
	 *
	 * @return string
	 * @throws MultipartError
	 */
	public function getHash() {
		if ( $this->hash === null ) {
			if ( !$this->hashContext ) {
				$this->error( 'cannot provide HMAC hash without key' );
			}
			if ( !$this->atStreamEnd ) {
				// Allowing this would cause an error if another read is attempted
				$this->error( 'cannot finalise HMAC before the end of the stream' );
			}
			$this->hash = hash_final( $this->hashContext );
		}
		return $this->hash;
	}

	/**
	 * Try to top up the buffer so that it has at least MIN_BUFFER_SIZE bytes.
	 */
	private function refillBuffer() {
		if ( strlen( $this->buffer ) < self::MIN_BUFFER_SIZE && !$this->stream->eof() ) {
			$newBuffer = $this->stream->read( self::MIN_BUFFER_SIZE );
			if ( $this->hashContext ) {
				hash_update( $this->hashContext, $newBuffer );
			}
			$this->buffer .= $newBuffer;
		}
	}

	/**
	 * Read some bytes from the input stream, stopping if a boundary is reached.
	 * Care must be taken in case a boundary straddles a read operation. We
	 * refill the buffer with MIN_BUFFER_SIZE bytes, and check for a boundary
	 * anywhere within that buffer, but we only withdraw CHUNK_SIZE bytes from
	 * the buffer. The assumption is that as long as the boundary is shorter than
	 * MIN_BUFFER_SIZE - CHUNK_SIZE, we will never split a boundary across a chunk
	 * boundary.
	 *
	 * If a boundary is found, it is consumed, so that we are ready to read the
	 * headers of the next part. atPartEnd is set to terminate the loop in the
	 * caller.
	 *
	 * @return string
	 */
	private function readChunk() {
		$this->refillBuffer();

		if ( $this->buffer === '' ) {
			throw new MultipartError( 'unexpectedly reached the end of the ' .
				'stream while inside a part' );
		}

		// Identify the first boundary
		$boundaries = [
			'encapsulation' => $this->encapsulationBoundary,
			'closing' => $this->closingBoundary
		];
		// An encapsulation boundary must be at the start of a line, which may
		// be either after a CRLF, or at the start of the stream.
		if ( $this->atStreamStart ) {
			$boundaries['start'] = $this->startBoundary;
			$this->atStreamStart = false;
		}
		$foundBoundaries = [];
		foreach ( $boundaries as $type => $boundary ) {
			$pos = strpos( $this->buffer, $boundary );
			if ( $pos !== false ) {
				$foundBoundaries[$pos] = [
					'type' => $type,
					'pos' => $pos,
					'length' => strlen( $boundary )
				];
			}
		}
		ksort( $foundBoundaries, SORT_NUMERIC );
		$boundary = reset( $foundBoundaries );

		// reset() returns false if the array is empty, but phan incorrectly
		// leaves this out of the union
		'@phan-var array|false $boundary';

		$this->atPartEnd = false;
		if ( $boundary === false && strlen( $this->buffer ) <= self::CHUNK_SIZE ) {
			$chunk = $this->buffer;
			$this->buffer = '';
			return $chunk;
		} elseif ( $boundary === false || $boundary['pos'] > self::CHUNK_SIZE ) {
			$chunk = substr( $this->buffer, 0, self::CHUNK_SIZE );
			$this->buffer = substr( $this->buffer, self::CHUNK_SIZE );
			return $chunk;
		} else {
			$chunk = substr( $this->buffer, 0, $boundary['pos'] );
			// Consume boundary
			$this->buffer = substr( $this->buffer, $boundary['pos'] + $boundary['length'] );
			$this->atPartEnd = true;
			if ( $boundary['type'] === 'closing' ) {
				$this->inEpilogue = true;
			}
			return $chunk;
		}
	}

	/**
	 * Read a line. This is used to read headers. Per RFC 1341, lines are
	 * always terminated with CRLF, not LF.
	 *
	 * @return bool|string
	 * @throws MultipartError
	 */
	private function readLine() {
		$this->refillBuffer();
		$breakPos = strpos( $this->buffer, "\r\n" );
		if ( $breakPos === false ) {
			$this->error( "unexpectedly found the end of the input while " .
				"looking for the end of a line" );
		}

		$line = substr( $this->buffer, 0, $breakPos );
		$this->buffer = substr( $this->buffer, $breakPos + 2 );
		return $line;
	}

	/**
	 * Reset the atPartEnd flag so that we can read the next part.
	 */
	private function nextPart() {
		if ( !$this->inEpilogue ) {
			$this->atPartEnd = false;
		}
	}

	/**
	 * Throw an exception.
	 *
	 * @param string $message
	 * @throws MultipartError
	 * @return never
	 */
	private function error( $message ) {
		throw new MultipartError( "Error reading multipart content: $message" );
	}
}
