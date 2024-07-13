<?php

namespace Wikimedia\RemexHtml\DOM;

use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\TreeBuilder\Element;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;
use Wikimedia\RemexHtml\TreeBuilder\TreeHandler;

/**
 * A TreeHandler which constructs a DOMDocument.
 *
 * Note that this class permits third-party `DOMImplementation`s
 * (documents other than `\DOMDocument`, nodes other than `\DOMNode`,
 * etc) and so no enforced PHP type hints are used which name these
 * classes directly.  For the sake of static type checking, the
 * types *in comments* are given as if the standard PHP `\DOM*`
 * classes are being used but at runtime everything is duck-typed.
 */
class DOMBuilder implements TreeHandler {

	/** @var string|null The name of the input document type */
	public $doctypeName;

	/** @var string|null The public ID */
	public $public;

	/** @var string|null The system ID */
	public $system;

	/**
	 * @var int The quirks mode. May be either TreeBuilder::NO_QUIRKS,
	 *   TreeBuilder::LIMITED_QUIRKS or TreeBuilder::QUIRKS to indicate
	 *   no-quirks mode, limited-quirks mode or quirks mode respectively.
	 */
	public $quirks;

	/** @var \DOMDocument */
	private $doc;

	/** @var callable|null */
	private $errorCallback;

	/** @var bool */
	private $suppressHtmlNamespace;

	/** @var bool */
	private $suppressIdAttribute;

	/** @var \DOMImplementation */
	private $domImplementation;

	/** @var class-string */
	private $domExceptionClass;

	/** @var bool */
	private $isFragment;

	/** @var bool */
	private $coerced = false;

	/**
	 * @param array $options An associative array of options:
	 *   - errorCallback : A function which is called on parse errors
	 *   - suppressHtmlNamespace : omit the namespace when creating HTML
	 *     elements. False by default.
	 *   - suppressIdAttribute : don't call the nonstandard
	 *     DOMElement::setIdAttribute() method while constructing elements.
	 *     False by default (this method is needed for efficient
	 *     DOMDocument::getElementById() calls).  Set to true if you are
	 *     using a W3C spec-compliant DOMImplementation and wish to avoid
	 *     nonstandard calls.
	 *   - domImplementation: The DOMImplementation object to use.  If this
	 *     parameter is missing or null, a new DOMImplementation object will
	 *     be constructed using the `domImplementationClass` option value.
	 *     You can use a third-party DOM implementation by passing in an
	 *     appropriately duck-typed object here.
	 *   - domImplementationClass: The string name of the DOMImplementation
	 *     class to use.  Defaults to `\DOMImplementation::class` but
	 *     you can use a third-party DOM implementation by passing
	 *     an alternative class name here.
	 *   - domExceptionClass: The string name of the DOMException
	 *     class to use.  Defaults to `\DOMException::class` but
	 *     you can use a third-party DOM implementation by passing
	 *     an alternative class name here.
	 */
	public function __construct( $options = [] ) {
		$options += [
			'suppressHtmlNamespace' => false,
			'suppressIdAttribute' => false,
			'errorCallback' => null,
			'domImplementation' => null,
			'domImplementationClass' => \DOMImplementation::class,
			'domExceptionClass' => \DOMException::class,
		];
		$this->errorCallback = $options['errorCallback'];
		$this->suppressHtmlNamespace = $options['suppressHtmlNamespace'];
		$this->suppressIdAttribute = $options['suppressIdAttribute'];
		$this->domImplementation = $options['domImplementation'] ??
			new $options['domImplementationClass'];
		$this->domExceptionClass = $options['domExceptionClass'];
	}

	private function rethrowIfNotDomException( \Throwable $t ) {
		if ( is_a( $t, $this->domExceptionClass, false ) ) {
			return;
		}
		throw $t;
	}

	/**
	 * Get the constructed document or document fragment. In the fragment case,
	 * a DOMElement is returned, and the caller is expected to extract its
	 * inner contents, ignoring the wrapping element. This convention is
	 * convenient because the wrapping element gives libxml somewhere to put
	 * its namespace declarations. If we copied the children into a
	 * DOMDocumentFragment, libxml would invent new prefixes for the orphaned
	 * namespaces.
	 *
	 * @return \DOMNode
	 */
	public function getFragment() {
		if ( $this->isFragment ) {
			return $this->doc->documentElement;
		} else {
			return $this->doc;
		}
	}

	/**
	 * Returns true if the document was coerced due to libxml limitations. We
	 * follow HTML 5.1 § 8.2.7 "Coercing an HTML DOM into an infoset".
	 *
	 * @return bool
	 */
	public function isCoerced() {
		return $this->coerced;
	}

	public function startDocument( $fragmentNamespace, $fragmentName ) {
		$this->isFragment = $fragmentNamespace !== null;
		$this->doc = $this->createDocument();
	}

	/**
	 * @param string|null $doctypeName
	 * @param string|null $public
	 * @param string|null $system
	 * @return \DOMDocument
	 * @suppress PhanTypeMismatchArgumentInternalReal
	 *   Null args to DOMImplementation::createDocument
	 */
	protected function createDocument(
		string $doctypeName = null,
		string $public = null,
		string $system = null
	) {
		$impl = $this->domImplementation;
		if ( $doctypeName === '' ) {
			$this->coerced = true;
			$doc = $impl->createDocument( null, '' );
		} elseif ( $doctypeName === null ) {
			$doc = $impl->createDocument( null, '' );
		} else {
			$doctype = $impl->createDocumentType( $doctypeName, $public, $system );
			$doc = $impl->createDocument( null, '', $doctype );
		}
		$doc->encoding = 'UTF-8';
		return $doc;
	}

	public function endDocument( $pos ) {
	}

	protected function insertNode( $preposition, $refElement, $node ) {
		if ( $preposition === TreeBuilder::ROOT ) {
			$parent = $this->doc;
			$refNode = null;
		} elseif ( $preposition === TreeBuilder::BEFORE ) {
			$parent = $refElement->userData->parentNode;
			$refNode = $refElement->userData;
		} else {
			$parent = $refElement->userData;
			$refNode = null;
		}
		// @phan-suppress-next-line PhanTypeMismatchArgumentInternal
		$parent->insertBefore( $node, $refNode );
	}

	/**
	 * Replace unsupported characters with a code of the form U123456.
	 *
	 * @param string $name
	 * @return string
	 */
	private function coerceName( $name ) {
		$coercedName = DOMUtils::coerceName( $name );
		if ( $name !== $coercedName ) {
			$this->coerced = true;
		}
		return $coercedName;
	}

	protected function createNode( Element $element ) {
		$noNS = $this->suppressHtmlNamespace && $element->namespace === HTMLData::NS_HTML;
		try {
			if ( $noNS ) {
				$node = $this->doc->createElement( $element->name );
			} else {
				$node = $this->doc->createElementNS(
					$element->namespace,
					$element->name );
			}
		} catch ( \Throwable $e ) {
			$this->rethrowIfNotDomException( $e );
			'@phan-var \DOMException $e'; /** @var \DOMException $e */
			// Attempt to escape the name so that it is more acceptable
			if ( $noNS ) {
				$node = $this->doc->createElement(
					$this->coerceName( $element->name )
				);
			} else {
				$node = $this->doc->createElementNS(
					$element->namespace,
					$this->coerceName( $element->name ) );
			}
		}

		foreach ( $element->attrs->getObjects() as $attr ) {
			if ( $attr->namespaceURI === null
				&& strpos( $attr->localName, ':' ) !== false
			) {
				// Create a DOMText explicitly instead of setting $attrNode->value,
				// to work around the DOMAttr entity expansion bug (T324408)
				$textNode = new \DOMText( $attr->value );
				try {
					// FIXME: this apparently works to create a prefixed localName
					// in the null namespace, but this is probably taking advantage
					// of a bug in PHP's DOM library, and screws up in various
					// interesting ways. For example, attributes created in this
					// way can't be discovered via hasAttribute() or hasAttributeNS().
					$attrNode = $this->doc->createAttribute( $attr->localName );
					$attrNode->appendChild( $textNode );
					$node->setAttributeNodeNS( $attrNode );
				} catch ( \Throwable $e ) {
					$this->rethrowIfNotDomException( $e );
					'@phan-var \DOMException $e'; /** @var \DOMException $e */
					$attrNode = $this->doc->createAttribute(
						$this->coerceName( $attr->localName ) );
					$attrNode->appendChild( $textNode );
					$node->setAttributeNodeNS( $attrNode );
				}
			} else {
				try {
					$node->setAttributeNS(
						$attr->namespaceURI,
						$attr->qualifiedName,
						$attr->value );
				} catch ( \Throwable $e ) {
					$this->rethrowIfNotDomException( $e );
					'@phan-var \DOMException $e'; /** @var \DOMException $e */
					$node->setAttributeNS(
						$attr->namespaceURI,
						$this->coerceName( $attr->qualifiedName ),
						$attr->value );
				}
			}
		}
		if ( ( !$this->suppressIdAttribute ) && $node->hasAttribute( 'id' ) ) {
			// This is a call to a non-standard DOM method required by PHP in
			// order to implement DOMDocument::getElementById() efficiently.
			$node->setIdAttribute( 'id', true );
		}
		$element->userData = $node;
		return $node;
	}

	public function characters( $preposition, $refElement, $text, $start, $length,
		$sourceStart, $sourceLength
	) {
		// Parse $preposition and $refElement as in self::insertNode()
		if ( $preposition === TreeBuilder::ROOT ) {
			$parent = $this->doc;
			$refNode = null;
		} elseif ( $preposition === TreeBuilder::BEFORE ) {
			$parent = $refElement->userData->parentNode;
			$refNode = $refElement->userData;
		} else {
			$parent = $refElement->userData;
			$refNode = null;
		}
		// https://html.spec.whatwg.org/#insert-a-character
		// If the adjusted insertion location is in a Document node, then
		// return.
		if ( $parent === $this->doc ) {
			return;
		}
		$data = substr( $text, $start, $length );
		// If there is a Text node immediately before the adjusted insertion
		// location, then append data to that Text node's data.
		if ( $refNode === null ) {
			$prev = $parent->lastChild;
		} else {
			/** @var \DOMNode $refNode */
			$prev = $refNode->previousSibling;
		}
		if ( $prev !== null && $prev->nodeType === XML_TEXT_NODE ) {
			'@phan-var \DOMCharacterData $prev'; /** @var \DOMCharacterData $prev */
			$prev->appendData( $data );
		} else {
			$node = $this->doc->createTextNode( $data );
			$this->insertNode( $preposition, $refElement, $node );
		}
	}

	public function insertElement( $preposition, $refElement, Element $element, $void,
		$sourceStart, $sourceLength
	) {
		if ( $element->userData ) {
			$node = $element->userData;
		} else {
			$node = $this->createNode( $element );
		}
		$this->insertNode( $preposition, $refElement, $node );
	}

	public function endTag( Element $element, $sourceStart, $sourceLength ) {
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		if ( !$this->doc->firstChild ) {
			$this->doc = $this->createDocument( $name, $public, $system );
		}
		$this->doctypeName = $name;
		$this->public = $public;
		$this->system = $system;
		$this->quirks = $quirks;
	}

	public function comment( $preposition, $refElement, $text, $sourceStart, $sourceLength ) {
		$node = $this->doc->createComment( $text );
		$this->insertNode( $preposition, $refElement, $node );
	}

	public function error( $text, $pos ) {
		if ( $this->errorCallback ) {
			call_user_func( $this->errorCallback, $text, $pos );
		}
	}

	public function mergeAttributes( Element $element, Attributes $attrs, $sourceStart ) {
		$node = $element->userData;
		'@phan-var \DOMElement $node'; /** @var \DOMElement $node */
		foreach ( $attrs->getObjects() as $name => $attr ) {
			if ( $attr->namespaceURI === null
				&& strpos( $attr->localName, ':' ) !== false
			) {
				try {
					// As noted in createNode(), we can't use hasAttribute() here.
					// However, we can use the return value of setAttributeNodeNS()
					// instead.
					$attrNode = $this->doc->createAttribute( $attr->localName );
					$attrNode->value = $attr->value;
					$replaced = $node->setAttributeNodeNS( $attrNode );
				} catch ( \Throwable $e ) {
					$this->rethrowIfNotDomException( $e );
					'@phan-var \DOMException $e'; /** @var \DOMException $e */
					$attrNode = $this->doc->createAttribute(
						$this->coerceName( $attr->localName ) );
					$attrNode->value = $attr->value;
					$replaced = $node->setAttributeNodeNS( $attrNode );
				}
				if ( $replaced ) {
					// Put it back how it was
					$node->setAttributeNodeNS( $replaced );
				}
			} elseif ( $attr->namespaceURI === null ) {
				try {
					if ( !$node->hasAttribute( $attr->localName ) ) {
						$node->setAttribute( $attr->localName, $attr->value );
					}
				} catch ( \Throwable $e ) {
					$this->rethrowIfNotDomException( $e );
					'@phan-var \DOMException $e'; /** @var \DOMException $e */
					$name = $this->coerceName( $attr->localName );
					if ( !$node->hasAttribute( $name ) ) {
						$node->setAttribute( $name, $attr->value );
					}
				}
			} else {
				try {
					if ( !$node->hasAttributeNS( $attr->namespaceURI, $attr->localName ) ) {
						$node->setAttributeNS( $attr->namespaceURI,
							$attr->localName, $attr->value );
					}
				} catch ( \Throwable $e ) {
					$this->rethrowIfNotDomException( $e );
					'@phan-var \DOMException $e'; /** @var \DOMException $e */
					$name = $this->coerceName( $attr->localName );
					if ( !$node->hasAttributeNS( $attr->namespaceURI, $name ) ) {
						$node->setAttributeNS( $attr->namespaceURI, $name, $attr->value );
					}
				}
			}
		}
	}

	public function removeNode( Element $element, $sourceStart ) {
		$node = $element->userData;
		$node->parentNode->removeChild( $node );
	}

	public function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
		$this->insertElement( TreeBuilder::UNDER, $element, $newParent, false, $sourceStart, 0 );
		$node = $element->userData;
		/** @var \DOMElement $newParentNode */
		$newParentNode = $newParent->userData;
		'@phan-var \DOMElement $newParentNode';
		while ( $node->firstChild !== $newParentNode ) {
			$firstChild = $node->firstChild;
			'@phan-var \DOMNode $firstChild';
			$newParentNode->appendChild( $firstChild );
		}
	}
}
