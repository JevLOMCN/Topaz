<?php

namespace Wikimedia\Purtle;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class NTriplesRdfWriter extends N3RdfWriterBase {

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
		parent::__construct( $role, $labeler, $quoter );

		// NOTE: The RDF 1.1 spec of N-Triples allows full UTF-8, so escaping would not be required.
		// However, as of 2015, many consumers of N-Triples still expect non-ASCII characters
		// to be escaped.
		// NOTE: if this is changed, getMimeType must be changed accordingly.
		$this->quoter->setEscapeUnicode( true );

		$this->transitionTable[self::STATE_OBJECT] = [
				self::STATE_DOCUMENT => " .\n",
				self::STATE_SUBJECT => " .\n",
				self::STATE_PREDICATE => " .\n",
				self::STATE_OBJECT => " .\n",
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function expandSubject( &$base, &$local ) {
		$this->expandQName( $base, $local );
	}

	/**
	 * @inheritDoc
	 */
	protected function writeSubject( $base, $local = null ) {
		// noop
	}

	/**
	 * @inheritDoc
	 */
	protected function expandPredicate( &$base, &$local ) {
		$this->expandShorthand( $base, $local ); // e.g. ( 'a', null ) => ( 'rdf', 'type' )
		$this->expandQName( $base, $local ); // e.g. ( 'acme', 'foo' ) => ( 'http://acme.test/foo', null )
	}

	/**
	 * @inheritDoc
	 */
	protected function writePredicate( $base, $local = null ) {
		// noop
	}

	private function writeSubjectAndObject() {
		$this->writeRef( $this->currentSubject[0], $this->currentSubject[1] );
		$this->write( ' ' );
		$this->writeRef( $this->currentPredicate[0], $this->currentPredicate[1] );
	}

	/**
	 * @inheritDoc
	 */
	protected function expandResource( &$base, &$local ) {
		$this->expandQName( $base, $local );
	}

	/**
	 * @inheritDoc
	 */
	protected function expandType( &$base, &$local ) {
		$this->expandQName( $base, $local );
	}

	/**
	 * @inheritDoc
	 */
	protected function writeResource( $base, $local = null ) {
		$this->writeSubjectAndObject();
		$this->write( ' ' );
		$this->writeRef( $base, $local );
	}

	/**
	 * @inheritDoc
	 */
	protected function writeText( $text, $language = null ) {
		$this->writeSubjectAndObject();
		$this->write( ' ' );

		parent::writeText( $text, $language );
	}

	/**
	 * @param string $value
	 * @param string|null $typeBase
	 * @param string|null $typeLocal
	 */
	protected function writeValue( $value, $typeBase, $typeLocal = null ) {
		$this->writeSubjectAndObject();
		$this->write( ' ' );

		parent::writeValue( $value, $typeBase, $typeLocal );
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfWriterBase
	 */
	protected function newSubWriter( $role, BNodeLabeler $labeler ) {
		$writer = new self( $role, $labeler, $this->quoter );

		return $writer;
	}

	/**
	 * @return string a MIME type
	 */
	public function getMimeType() {
		// NOTE: Add charset=UTF-8 if and when the constructor configures $this->quoter
		// to write utf-8.
		return 'application/n-triples';
	}

}
