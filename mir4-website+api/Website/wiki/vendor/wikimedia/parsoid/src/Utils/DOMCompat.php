<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\DOM\CharacterData;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DOMCompat\TokenList;
use Wikimedia\Parsoid\Wt2Html\XMLSerializer;
use Wikimedia\RemexHtml\DOM\DOMBuilder;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;
use Wikimedia\Zest\Zest;

/**
 * Helper class that provides missing DOM level 3 methods for the PHP DOM classes.
 * For a DOM method $node->foo( $bar) the equivalent helper is DOMCompat::foo( $node, $bar ).
 * For a DOM property $node->foo there is a DOMCompat::getFoo( $node ) and
 * DOMCompat::setFoo( $node, $value ).
 *
 * Only implements the methods that are actually used by Parsoid.
 *
 * Because this class may be used by code outside Parsoid it tries to
 * be relatively tolerant of object types: you can call it either with
 * PHP's DOM* types or with a "proper" DOM implementation, and it will
 * attempt to Do The Right Thing regardless.  As a result there are
 * generally not parameter type hints for DOM object types, and the
 * return types will be broad enough to accomodate the value a "real"
 * DOM implementation would return, as well as the values our
 * thunk will return. (For instance, we can't create a "real" NodeList
 * in our compatibility thunk.)
 */
class DOMCompat {

	/**
	 * Tab, LF, FF, CR, space
	 * @see https://infra.spec.whatwg.org/#ascii-whitespace
	 */
	private const ASCII_WHITESPACE = "\t\r\f\n ";

	/**
	 * Create a new empty document.
	 * This is abstracted because the process is a little different depending
	 * on whether we're using Dodo or DOMDocument, and phan gets a little
	 * confused by this.
	 * @param bool $isHtml
	 * @return Document
	 */
	public static function newDocument( bool $isHtml ) {
		// @phan-suppress-next-line PhanParamTooMany,PhanTypeInstantiateInterface
		return new Document( "1.0", "UTF-8" );
	}

	/**
	 * Return the lower-case version of the node name (HTML says this should
	 * be capitalized).
	 * @param Node $node
	 * @return string
	 */
	public static function nodeName( Node $node ): string {
		return strtolower( $node->nodeName );
	}

	/**
	 * Get document body.
	 * Unlike the spec we return it as a native PHP DOM object.
	 * @param Document $document
	 * @return Element|null
	 * @see https://html.spec.whatwg.org/multipage/dom.html#dom-document-body
	 */
	public static function getBody( $document ) {
		// WARNING: this will not be updated if (for some reason) the
		// document body changes.
		if ( $document->body !== null ) {
			return $document->body;
		}
		foreach ( $document->documentElement->childNodes as $element ) {
			/** @var Element $element */
			$nodeName = self::nodeName( $element );
			if ( $nodeName === 'body' || $nodeName === 'frameset' ) {
				// Caching!
				$document->body = $element;
				// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
				return $element;
			}
		}
		return null;
	}

	/**
	 * Get document head.
	 * Unlike the spec we return it as a native PHP DOM object.
	 * @param Document $document
	 * @return Element|null
	 * @see https://html.spec.whatwg.org/multipage/dom.html#dom-document-head
	 */
	public static function getHead( $document ) {
		// Use an undeclared dynamic property as a cache.
		// WARNING: this will not be updated if (for some reason) the
		// document head changes.
		if ( isset( $document->head ) ) {
			return $document->head;
		}
		foreach ( $document->documentElement->childNodes as $element ) {
			/** @var Element $element */
			if ( self::nodeName( $element ) === 'head' ) {
				$document->head = $element; // Caching!
				// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
				return $element;
			}
		}
		return null;
	}

	/**
	 * Get document title.
	 * @param Document $document
	 * @return string
	 * @see https://html.spec.whatwg.org/multipage/dom.html#document.title
	 */
	public static function getTitle( $document ): string {
		$titleElement = self::querySelector( $document, 'title' );
		return $titleElement ? self::stripAndCollapseASCIIWhitespace( $titleElement->textContent ) : '';
	}

	/**
	 * Set document title.
	 * @param Document $document
	 * @param string $title
	 * @see https://html.spec.whatwg.org/multipage/dom.html#document.title
	 */
	public static function setTitle( $document, string $title ): void {
		$titleElement = self::querySelector( $document, 'title' );
		if ( !$titleElement ) {
			$headElement = self::getHead( $document );
			if ( $headElement ) {
				$titleElement = DOMUtils::appendToHead( $document, 'title' );
			}
		}
		if ( $titleElement ) {
			$titleElement->textContent = $title;
		}
	}

	/**
	 * Return the parent element, or null if the parent is not an element.
	 * @param Node $node
	 * @return Element|null
	 * @see https://dom.spec.whatwg.org/#dom-node-parentelement
	 */
	public static function getParentElement( $node ) {
		$parent = $node->parentNode;
		if ( $parent && $parent->nodeType === XML_ELEMENT_NODE ) {
			/** @var Element $parent */
			// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
			return $parent;
		}
		return null;
	}

	/**
	 * Return the descendant with the specified ID.
	 * Workaround for https://bugs.php.net/bug.php?id=77686 and other issues related to
	 * inconsistent indexing behavior.
	 * XXX: 77686 is fixed in php 8.1.21
	 * @param Document|DocumentFragment $node
	 * @param string $id
	 * @return Element|null
	 * @see https://dom.spec.whatwg.org/#dom-nonelementparentnode-getelementbyid
	 */
	public static function getElementById( $node, string $id ) {
		Assert::parameterType(
			self::or(
				Document::class, DocumentFragment::class,
				// For compatibility with code which might call this from
				// outside Parsoid.
				\DOMDocument::class, \DOMDocumentFragment::class
			),
			$node, '$node' );
		// @phan-suppress-next-line PhanTypeMismatchArgument Zest is declared to take DOMDocument\DOMElement
		$elements = Zest::getElementsById( $node, $id );
		// @phan-suppress-next-line PhanTypeMismatchReturn
		return $elements[0] ?? null;
	}

	/**
	 * Workaround bug in PHP's Document::getElementById() which doesn't
	 * actually index the 'id' attribute unless you use the non-standard
	 * `Element::setIdAttribute` method after the attribute is set;
	 * see https://www.php.net/manual/en/domdocument.getelementbyid.php
	 * for more details.
	 *
	 * @param Element $element
	 * @param string $id The desired value for the `id` attribute on $element.
	 * @see https://phabricator.wikimedia.org/T232390
	 */
	public static function setIdAttribute( $element, string $id ): void {
		$element->setAttribute( 'id', $id );
		$element->setIdAttribute( 'id', true );// phab:T232390
	}

	/**
	 * Return all descendants with the specified tag name.
	 * Workaround for PHP's getElementsByTagName being inexplicably slow in some situations
	 * and the lack of Element::getElementsByTagName().
	 * @param Document|Element $node
	 * @param string $tagName
	 * @return (iterable<Element>&\Countable)|array<Element> Either an array or an HTMLCollection object
	 * @see https://dom.spec.whatwg.org/#dom-document-getelementsbytagname
	 * @see https://dom.spec.whatwg.org/#dom-element-getelementsbytagname
	 * @note Note that unlike the spec this method is not guaranteed to return a NodeList
	 *   (which cannot be freely constructed in PHP), just a traversable containing Elements.
	 */
	public static function getElementsByTagName( $node, string $tagName ): iterable {
		Assert::parameterType(
			self::or(
				Document::class, Element::class,
				// For compatibility with code which might call this from
				// outside Parsoid.
				\DOMDocument::class, \DOMElement::class
			),
			$node, '$node' );
		// @phan-suppress-next-line PhanTypeMismatchArgument Zest is declared to take DOMDocument\DOMElement
		$result = Zest::getElementsByTagName( $node, $tagName );
		'@phan-var array<Element> $result'; // @var array<Element> $result
		return $result;
	}

	/**
	 * Return the last child of the node that is an Element, or null otherwise.
	 * @param Document|DocumentFragment|Element $node
	 * @return Element|null
	 * @see https://dom.spec.whatwg.org/#dom-parentnode-lastelementchild
	 */
	public static function getLastElementChild( $node ) {
		Assert::parameterType(
			self::or(
				Document::class, DocumentFragment::class, Element::class,
				// For compatibility with code which might call this from
				// outside Parsoid.
				\DOMDocument::class, \DOMDocumentFragment::class, \DOMElement::class
			),
			$node, '$node' );
		$lastChild = $node->lastChild;
		while ( $lastChild && $lastChild->nodeType !== XML_ELEMENT_NODE ) {
			$lastChild = $lastChild->previousSibling;
		}
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $lastChild;
	}

	/**
	 * @param Document|DocumentFragment|Element $node
	 * @param string $selector
	 * @return Element|null
	 * @see https://dom.spec.whatwg.org/#dom-parentnode-queryselector
	 */
	public static function querySelector( $node, string $selector ) {
		foreach ( self::querySelectorAll( $node, $selector ) as $el ) {
			return $el;
		}
		return null;
	}

	/**
	 * @param Document|DocumentFragment|Element $node
	 * @param string $selector
	 * @return (iterable<Element>&\Countable)|array<Element> Either a NodeList or an array
	 * @see https://dom.spec.whatwg.org/#dom-parentnode-queryselectorall
	 * @note Note that unlike the spec this method is not guaranteed to return a NodeList
	 *   (which cannot be freely constructed in PHP), just a traversable containing Elements.
	 */
	public static function querySelectorAll( $node, string $selector ): iterable {
		Assert::parameterType(
			self::or(
				Document::class, DocumentFragment::class, Element::class,
				// For compatibility with code which might call this from
				// outside Parsoid.
				\DOMDocument::class, \DOMDocumentFragment::class, \DOMElement::class
			),
			$node, '$node' );
		// @phan-suppress-next-line PhanTypeMismatchArgument DOMNode
		return Zest::find( $selector, $node );
	}

	/**
	 * Return the last preceding sibling of the node that is an element, or null otherwise.
	 * @param Node $node
	 * @return Element|null
	 * @see https://dom.spec.whatwg.org/#dom-nondocumenttypechildnode-previouselementsibling
	 */
	public static function getPreviousElementSibling( $node ) {
		Assert::parameterType(
			self::or(
				Element::class, CharacterData::class,
				// For compatibility with code which might call this from
				// outside Parsoid.
				\DOMElement::class, \DOMCharacterData::class
			),
			$node, '$node' );
		$previousSibling = $node->previousSibling;
		while ( $previousSibling && $previousSibling->nodeType !== XML_ELEMENT_NODE ) {
			$previousSibling = $previousSibling->previousSibling;
		}
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $previousSibling;
	}

	/**
	 * Return the first following sibling of the node that is an element, or null otherwise.
	 * @param Node $node
	 * @return Element|null
	 * @see https://dom.spec.whatwg.org/#dom-nondocumenttypechildnode-nextelementsibling
	 */
	public static function getNextElementSibling( $node ) {
		Assert::parameterType(
			self::or(
				Element::class, CharacterData::class,
				// For compatibility with code which might call this from
				// outside Parsoid.
				\DOMElement::class, \DOMCharacterData::class
			),
			$node, '$node' );
		$nextSibling = $node->nextSibling;
		while ( $nextSibling && $nextSibling->nodeType !== XML_ELEMENT_NODE ) {
			$nextSibling = $nextSibling->nextSibling;
		}
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $nextSibling;
	}

	/**
	 * Removes the node from the document.
	 * @param Element|CharacterData $node
	 * @see https://dom.spec.whatwg.org/#dom-childnode-remove
	 */
	public static function remove( $node ): void {
		Assert::parameterType(
			self::or(
				Element::class, CharacterData::class,
				// For compatibility with code which might call this from
				// outside Parsoid.
				\DOMElement::class, \DOMCharacterData::class
			),
			$node, '$node' );
		if ( $node->parentNode ) {
			$node->parentNode->removeChild( $node );
		}
	}

	/**
	 * Get innerHTML.
	 * @see DOMUtils::getFragmentInnerHTML() for the fragment version
	 * @param Element $element
	 * @return string
	 * @see https://w3c.github.io/DOM-Parsing/#dom-innerhtml-innerhtml
	 */
	public static function getInnerHTML( $element ): string {
		return XMLSerializer::serialize( $element, [ 'innerXML' => true ] )['html'];
	}

	/**
	 * Set innerHTML.
	 * @see https://w3c.github.io/DOM-Parsing/#dom-innerhtml-innerhtml
	 * @see DOMUtils::setFragmentInnerHTML() for the fragment version
	 * @param Element $element
	 * @param string $html
	 */
	public static function setInnerHTML( $element, string $html ): void {
		$domBuilder = new class( [
			'suppressHtmlNamespace' => true,
		] ) extends DOMBuilder
		{
			/** @inheritDoc */
			protected function createDocument(
				string $doctypeName = null,
				string $public = null,
				string $system = null
			) {
				// @phan-suppress-next-line PhanTypeMismatchReturn
				return DOMCompat::newDocument( $doctypeName === 'html' );
			}
		};
		$treeBuilder = new TreeBuilder( $domBuilder );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $html, [ 'ignoreErrors' => true ] );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => self::nodeName( $element ),
		] );

		// Empty the element
		self::replaceChildren( $element );

		$frag = $domBuilder->getFragment();
		'@phan-var Node $frag'; // @var Node $frag
		DOMUtils::migrateChildrenBetweenDocs(
			$frag, $element
		);
	}

	/**
	 * Get outerHTML.
	 * @param Element $element
	 * @return string
	 * @see https://w3c.github.io/DOM-Parsing/#dom-element-outerhtml
	 */
	public static function getOuterHTML( $element ): string {
		return XMLSerializer::serialize( $element, [ 'addDoctype' => false ] )['html'];
	}

	/**
	 * Return the value of an element attribute.
	 *
	 * Unlike PHP's version, this is spec-compliant and returns `null` if
	 * the attribute is not present, allowing the caller to distinguish
	 * between "the attribute exists but has the empty string as its value"
	 * and "the attribute does not exist".
	 *
	 * @param Element $element
	 * @param string $attributeName
	 * @return ?string The attribute value, or `null` if the attribute does
	 *   not exist on the element.
	 * @see https://dom.spec.whatwg.org/#dom-element-getattribute
	 */
	public static function getAttribute( $element, string $attributeName ): ?string {
		if ( !$element->hasAttribute( $attributeName ) ) {
			return null;
		}
		return $element->getAttribute( $attributeName );
	}

	/**
	 * Return the class list of this element.
	 * @param Element $node
	 * @return TokenList
	 * @see https://dom.spec.whatwg.org/#dom-element-classlist
	 */
	public static function getClassList( $node ): TokenList {
		return new TokenList( $node );
	}

	/**
	 * @param string $text
	 * @return string
	 * @see https://infra.spec.whatwg.org/#strip-and-collapse-ascii-whitespace
	 */
	private static function stripAndCollapseASCIIWhitespace( string $text ): string {
		$ws = self::ASCII_WHITESPACE;
		return preg_replace( "/[$ws]+/", ' ', trim( $text, $ws ) );
	}

	/**
	 * @param Element|DocumentFragment $e
	 */
	private static function stripEmptyTextNodes( $e ): void {
		$c = $e->firstChild;
		while ( $c ) {
			$next = $c->nextSibling;
			if ( $c instanceof Text ) {
				if ( $c->nodeValue === '' ) {
					$e->removeChild( $c );
				}
			} elseif ( $c instanceof Element ) {
				self::stripEmptyTextNodes( $c );
			}
			$c = $next;
		}
	}

	/**
	 * @param Element|DocumentFragment $elt root of the DOM tree that
	 *   needs to be normalized
	 */
	public static function normalize( $elt ): void {
		$elt->normalize();

		// Now traverse the tree rooted at $elt and remove any stray empty text nodes
		// Unlike what https://www.w3.org/TR/DOM-Level-2-Core/core.html#ID-normalize says,
		// the PHP DOM's normalization leaves behind up to 1 empty text node.
		// See https://bugs.php.net/bug.php?id=78221
		self::stripEmptyTextNodes( $elt );
	}

	/**
	 * ParentNode.replaceChildren()
	 * https://developer.mozilla.org/en-US/docs/Web/API/ParentNode/replaceChildren
	 *
	 * @param Document|DocumentFragment|Element $parentNode
	 * @param string|Node ...$nodes
	 */
	public static function replaceChildren(
		$parentNode, ...$nodes
	): void {
		Assert::parameterType(
			self::or(
				Document::class, DocumentFragment::class, Element::class,
				// For compatibility with code which might call this from
				// outside Parsoid.
				\DOMDocument::class, \DOMDocumentFragment::class, \DOMElement::class
			),
			$parentNode, '$parentNode'
		);
		while ( $parentNode->firstChild ) {
			$parentNode->removeChild( $parentNode->firstChild );
		}
		foreach ( $nodes as $node ) {
			if ( is_string( $node ) ) {
				$node = $parentNode->ownerDocument->createTextNode( $node );
			}
			$parentNode->insertBefore( $node, null );
		}
	}

	/**
	 * Join class names together in a form suitable for Assert::parameterType.
	 * @param class-string ...$args
	 * @return string
	 */
	private static function or( ...$args ) {
		return implode( '|', $args );
	}
}
