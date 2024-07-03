<?php

namespace Wikimedia\Purtle;

/**
 * RdfWriter implementation for generating Turtle output.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TurtleRdfWriter extends N3RdfWriterBase {

	/**
	 * @var bool
	 */
	private $trustIRIs = true;

	/**
	 * @return bool
	 */
	public function getTrustIRIs() {
		return $this->trustIRIs;
	}

	/**
	 * @param bool $trustIRIs
	 */
	public function setTrustIRIs( $trustIRIs ) {
		$this->trustIRIs = $trustIRIs;
	}

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
		$this->transitionTable[self::STATE_OBJECT] = [
			self::STATE_DOCUMENT => " .\n",
			self::STATE_SUBJECT => " .\n\n",
			self::STATE_PREDICATE => " ;\n\t",
			self::STATE_OBJECT => ",\n\t\t",
		];
		$this->transitionTable[self::STATE_DOCUMENT][self::STATE_SUBJECT] = "\n";
		$this->transitionTable[self::STATE_SUBJECT][self::STATE_PREDICATE] = ' ';
		$this->transitionTable[self::STATE_PREDICATE][self::STATE_OBJECT] = ' ';
		$this->transitionTable[self::STATE_START][self::STATE_DOCUMENT] = function () {
			$this->beginDocument();
		};
	}

	/**
	 * Write prefixes
	 */
	private function beginDocument() {
		foreach ( $this->getPrefixes() as $prefix => $uri ) {
			$this->write( "@prefix $prefix: <" . $this->quoter->escapeIRI( $uri ) . "> .\n" );
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function writeSubject( $base, $local = null ) {
		if ( $local !== null ) {
			$this->write( "$base:$local" );
		} else {
			$this->writeIRI( $base, $this->trustIRIs );
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function writePredicate( $base, $local = null ) {
		if ( $base === 'a' ) {
			$this->write( 'a' );
			return;
		}
		if ( $local !== null ) {
			$this->write( "$base:$local" );
		} else {
			$this->writeIRI( $base, $this->trustIRIs );
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function writeResource( $base, $local = null ) {
		if ( $local !== null ) {
			$this->write( "$base:$local" );
		} else {
			$this->writeIRI( $base );
		}
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
		return 'text/turtle; charset=UTF-8';
	}

}
