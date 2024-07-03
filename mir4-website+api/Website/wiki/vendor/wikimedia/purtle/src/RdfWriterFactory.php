<?php

namespace Wikimedia\Purtle;

use InvalidArgumentException;

/**
 * @since 0.5
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RdfWriterFactory {

	/**
	 * Returns a list of canonical format names.
	 * These names for internal use with getMimeTypes() and getFileExtension(),
	 * they are not themselves MIME types or file extensions.
	 *
	 * @return string[]
	 */
	public function getSupportedFormats() {
		return [ 'n3', 'turtle', 'ntriples', 'rdfxml', 'jsonld' ];
	}

	/**
	 * Returns a list of mime types that correspond to the format.
	 *
	 * @param string $format a format name, as returned by getSupportedFormats() or getFormatName().
	 *
	 * @throws InvalidArgumentException if $format is not a cononical format name
	 * @return string[]
	 */
	public function getMimeTypes( $format ) {
		// NOTE: Maintaining mime types and file extensions in the RdfWriter implementations
		// is tempting, but means we have to load all these classes to find the right
		// one for a requested name. Better avoid that overhead when serving lots of
		// HTTP requests.

		switch ( strtolower( $format ) ) {
			case 'n3':
				return [ 'text/n3', 'text/rdf+n3' ];

			case 'turtle':
				return [ 'text/turtle', 'application/x-turtle' ];

			case 'ntriples':
				return [ 'application/n-triples', 'text/n-triples', 'text/plain' ];

			case 'rdfxml':
				return [ 'application/rdf+xml', 'application/xml', 'text/xml' ];

			case 'jsonld':
				return [ 'application/ld+json', 'application/json' ];

			default:
				throw new InvalidArgumentException( 'Bad format: ' . $format );
		}
	}

	/**
	 * Returns a file extension that correspond to the format.
	 *
	 * @param string $format a format name, as returned by getSupportedFormats() or getFormatName().
	 *
	 * @throws InvalidArgumentException if $format is not a cononical format name
	 * @return string
	 */
	public function getFileExtension( $format ) {
		switch ( strtolower( $format ) ) {
			case 'n3':
				return 'n3';

			case 'turtle':
				return 'ttl';

			case 'ntriples':
				return 'nt';

			case 'rdfxml':
				return 'rdf';

			case 'jsonld':
				return 'jsonld';

			default:
				throw new InvalidArgumentException( 'Bad format: ' . $format );
		}
	}

	/**
	 * Returns an RdfWriter for the given format name.
	 *
	 * @param string $format a format name, as returned by getSupportedFormats() or getFormatName().
	 * @param BNodeLabeler|null $labeler Optional labeler
	 *
	 * @throws InvalidArgumentException if $format is not a canonical format name
	 * @return RdfWriter the format object, or null if not found.
	 */
	public function getWriter( $format, BNodeLabeler $labeler = null ) {
		switch ( strtolower( $format ) ) {
			case 'n3':
				// falls through to turtle

			case 'turtle':
				return new TurtleRdfWriter( RdfWriterBase::DOCUMENT_ROLE, $labeler );

			case 'ntriples':
				return new NTriplesRdfWriter( RdfWriterBase::DOCUMENT_ROLE, $labeler );

			case 'rdfxml':
				return new XmlRdfWriter( RdfWriterBase::DOCUMENT_ROLE, $labeler );

			case 'jsonld':
				return new JsonLdRdfWriter( RdfWriterBase::DOCUMENT_ROLE, $labeler );

			default:
				throw new InvalidArgumentException( 'Bad format: ' . $format );
		}
	}

	/**
	 * Returns the canonical format name for $format. $format may be a file extension,
	 * a mime type, or a common or canonical name of the format.
	 *
	 * If no format is found for $format, this method returns false.
	 *
	 * @param string $format the name (file extension, mime type) of the desired format.
	 *
	 * @return string|false the canonical format name
	 */
	public function getFormatName( $format ) {
		switch ( strtolower( $format ) ) {
			case 'n3':
			case 'text/n3':
			case 'text/rdf+n3':
				return 'n3';

			case 'ttl':
			case 'turtle':
			case 'text/turtle':
			case 'application/x-turtle':
				return 'turtle';

			case 'nt':
			case 'ntriples':
			case 'n-triples':
			case 'text/plain':
			case 'text/n-triples':
			case 'application/ntriples':
			case 'application/n-triples':
				return 'ntriples';

			case 'xml':
			case 'rdf':
			case 'rdfxml':
			case 'application/rdf+xml':
			case 'application/xml':
			case 'text/xml':
				return 'rdfxml';

			case 'json':
			case 'jsonld':
			case 'application/ld+json':
			case 'application/json':
				return 'jsonld';

			default:
				return false;
		}
	}

}
