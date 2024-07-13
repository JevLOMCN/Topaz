<?php

namespace Wikimedia\Purtle;

use InvalidArgumentException;

/**
 * XML/RDF implementation of RdfWriter
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class XmlRdfWriter extends RdfWriterBase {

	/**
	 * @param string $role
	 * @param BNodeLabeler|null $labeler
	 */
	public function __construct( $role = parent::DOCUMENT_ROLE, BNodeLabeler $labeler = null ) {
		parent::__construct( $role, $labeler );

		$this->transitionTable[self::STATE_START][self::STATE_DOCUMENT] = function () {
			$this->beginDocument();
		};
		$this->transitionTable[self::STATE_DOCUMENT][self::STATE_FINISH] = function () {
			$this->finishDocument();
		};
		$this->transitionTable[self::STATE_OBJECT][self::STATE_DOCUMENT] = function () {
			$this->finishSubject();
		};
		$this->transitionTable[self::STATE_OBJECT][self::STATE_SUBJECT] = function () {
			$this->finishSubject();
		};
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	private function escape( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES );
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
	protected function expandPredicate( &$base, &$local ) {
		$this->expandShorthand( $base, $local );
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
	 * @param string $ns
	 * @param string $name
	 * @param string[] $attributes
	 * @param string|null $content
	 */
	private function tag( $ns, $name, $attributes = [], $content = null ) {
		$sep = $ns === '' ? '' : ':';
		$this->write( '<' . $ns . $sep . $name );

		foreach ( $attributes as $attr => $value ) {
			if ( is_int( $attr ) ) {
				// positional array entries are passed verbatim, may be callbacks.
				$this->write( $value );
				continue;
			}

			$this->write( " $attr=\"" . $this->escape( $value ) . '"' );
		}

		if ( $content === null ) {
			$this->write( '>' );
		} elseif ( $content === '' ) {
			$this->write( '/>' );
		} else {
			$this->write( '>' . $content );
			$this->close( $ns, $name );
		}
	}

	/**
	 * @param string $ns
	 * @param string $name
	 */
	private function close( $ns, $name ) {
		$sep = $ns === '' ? '' : ':';
		$this->write( '</' . $ns . $sep . $name . '>' );
	}

	/**
	 * Generates an attribute list, containing the attribute given by $name, or rdf:nodeID
	 * if $target is a blank node id (starting with "_:"). If $target is a qname, an attempt
	 * is made to resolve it into a full IRI based on the namespaces registered by calling
	 * prefix().
	 *
	 * @param string $name the attribute name (without the 'rdf:' prefix)
	 * @param string|null $base
	 * @param string|null $local
	 *
	 * @throws InvalidArgumentException
	 * @return string[]
	 */
	private function getTargetAttributes( $name, $base, $local ) {
		if ( $base === null && $local === null ) {
			return [];
		}

		// handle blank
		if ( $base === '_' ) {
			$name = 'nodeID';
			$value = $local;
		} elseif ( $local !== null ) {
			throw new InvalidArgumentException( "Expected IRI, got QName: $base:$local" );
		} else {
			$value = $base;
		}

		return [
			"rdf:$name" => $value
		];
	}

	/**
	 * Emit a document header.
	 */
	private function beginDocument() {
		$this->write( "<?xml version=\"1.0\"?>\n" );

		// define a callback for generating namespace attributes
		$namespaceAttrCallback = function () {
			$attr = '';

			$namespaces = $this->getPrefixes();
			foreach ( $namespaces as $ns => $uri ) {
				$escapedUri = htmlspecialchars( $uri, ENT_QUOTES );
				$nss = $ns === '' ? '' : ":$ns";
				$attr .= " xmlns$nss=\"$escapedUri\"";
			}

			return $attr;
		};

		$this->tag( 'rdf', 'RDF', [ $namespaceAttrCallback ] );
		$this->write( "\n" );
	}

	/**
	 * @param string $base
	 * @param string|null $local
	 */
	protected function writeSubject( $base, $local = null ) {
		$attr = $this->getTargetAttributes( 'about', $base, $local );

		$this->write( "\t" );
		$this->tag( 'rdf', 'Description', $attr );
		$this->write( "\n" );
	}

	/**
	 * Emit the root element
	 */
	private function finishSubject() {
		$this->write( "\t" );
		$this->close( 'rdf', 'Description' );
		$this->write( "\n" );
	}

	/**
	 * Write document footer
	 */
	private function finishDocument() {
		// close document element
		$this->close( 'rdf', 'RDF' );
		$this->write( "\n" );
	}

	/**
	 * @param string $base
	 * @param string|null $local
	 */
	protected function writePredicate( $base, $local = null ) {
		// noop
	}

	/**
	 * @param string $base
	 * @param string|null $local
	 */
	protected function writeResource( $base, $local = null ) {
		$attr = $this->getTargetAttributes( 'resource', $base, $local );

		$this->write( "\t\t" );
		$this->tag( $this->currentPredicate[0], $this->currentPredicate[1], $attr, '' );
		$this->write( "\n" );
	}

	/**
	 * @param string $text
	 * @param string|null $language
	 */
	protected function writeText( $text, $language = null ) {
		$attr = $this->isValidLanguageCode( $language )
			? [ 'xml:lang' => $language ]
			: [];

		$this->write( "\t\t" );
		$this->tag(
			$this->currentPredicate[0],
			$this->currentPredicate[1],
			$attr,
			$this->escape( $text )
		);
		$this->write( "\n" );
	}

	/**
	 * @param string $literal
	 * @param string|null $typeBase
	 * @param string|null $typeLocal
	 */
	public function writeValue( $literal, $typeBase, $typeLocal = null ) {
		$attr = $this->getTargetAttributes( 'datatype', $typeBase, $typeLocal );

		$this->write( "\t\t" );
		$this->tag(
			$this->currentPredicate[0],
			$this->currentPredicate[1],
			$attr,
			$this->escape( $literal )
		);
		$this->write( "\n" );
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfWriterBase
	 */
	protected function newSubWriter( $role, BNodeLabeler $labeler ) {
		$writer = new self( $role, $labeler );

		return $writer;
	}

	/**
	 * @return string a MIME type
	 */
	public function getMimeType() {
		return 'application/rdf+xml; charset=UTF-8';
	}

}
