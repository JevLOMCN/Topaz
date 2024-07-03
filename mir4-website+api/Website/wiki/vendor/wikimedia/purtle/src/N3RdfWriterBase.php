<?php

namespace Wikimedia\Purtle;

/**
 * Base class for RdfWriter implementations that output an N3 dialect.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
abstract class N3RdfWriterBase extends RdfWriterBase {

	/**
	 * @var N3Quoter
	 */
	protected $quoter;

	/**
	 * @param string $role
	 * @param BNodeLabeler|null $labeler
	 * @param N3Quoter|null $quoter
	 */
	public function __construct(
		$role = parent::DOCUMENT_ROLE,
		BNodeLabeler $labeler = null,
		N3Quoter $quoter = null
	) {
		parent::__construct( $role, $labeler );

		$this->quoter = $quoter ?: new N3Quoter();
	}

	/**
	 * @param string $base
	 * @param string|null $local
	 */
	protected function writeRef( $base, $local = null ) {
		if ( $local === null ) {
			if ( $base === 'a' ) {
				$this->write( 'a' );
			} else {
				$this->writeIRI( $base );
			}
		} else {
			$this->write( "$base:$local" );
		}
	}

	/**
	 * @param string $iri
	 * @param bool $trustIRI
	 */
	protected function writeIRI( $iri, $trustIRI = false ) {
		if ( !$trustIRI ) {
			$iri = $this->quoter->escapeIRI( $iri );
		}
		$this->write( "<$iri>" );
	}

	/**
	 * @inheritDoc
	 */
	protected function writeText( $text, $language = null ) {
		$value = $this->quoter->escapeLiteral( $text );
		$this->write( '"' . $value . '"' );

		if ( $this->isValidLanguageCode( $language ) ) {
			$this->write( '@' . $language );
		}
	}

	/**
	 * @param string $value
	 * @param string|null $typeBase
	 * @param string|null $typeLocal
	 */
	protected function writeValue( $value, $typeBase, $typeLocal = null ) {
		$value = $this->quoter->escapeLiteral( $value );
		$this->write( '"' . $value . '"' );

		if ( $typeBase !== null ) {
			$this->write( '^^' );
			$this->writeRef( $typeBase, $typeLocal );
		}
	}

}
