<?php

namespace Wikimedia\RemexHtml\Serializer;

use Wikimedia\RemexHtml\PropGuard;
use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;
use Wikimedia\RemexHtml\TreeBuilder\Element;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

/**
 * A TreeHandler which builds a serialized representation of a document, by
 * encoding elements when the end tags are seen. This is faster than building
 * a DOM and then serializing it, even if you use DOMDocument::saveHTML().
 */
class Serializer implements AbstractSerializer {
	use PropGuard;

	/**
	 * A node corresponding to the Document
	 * @var SerializerNode
	 */
	private $root;

	/**
	 * The error callback
	 *
	 * @var callable|null
	 */
	private $errorCallback;

	/**
	 * The Formatter implementation
	 *
	 * @var Formatter
	 */
	private $formatter;

	/**
	 * All active SerializerNode objects in an array, so that they can be
	 * referred to by integer indexes. This is a way to emulate weak references,
	 * to avoid circular references, allowing nodes to be freed.
	 *
	 * @var SerializerNode[]
	 * @internal
	 */
	protected $nodes = [];

	/**
	 * True if we are parsing a fragment. The children of the <html> element
	 * will be serialized, instead of the whole document.
	 *
	 * @var bool
	 */
	private $isFragment;

	/**
	 * The result string
	 *
	 * @var string
	 */
	private $result = '';

	/**
	 * Constructor
	 *
	 * @param Formatter $formatter
	 * @param callable|null $errorCallback A function which is called with the
	 *   details of each parse error
	 */
	public function __construct( Formatter $formatter, $errorCallback = null ) {
		$this->formatter = $formatter;
		$this->errorCallback = $errorCallback;
	}

	/**
	 * Get the final string. This can only be called after endDocument() is received.
	 * @return string
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * Get the root SerializerNode.
	 *
	 * @return SerializerNode
	 */
	public function getRootNode() {
		return $this->root;
	}

	/**
	 * Get the parent SerializerNode of a given SerializerNode
	 *
	 * @param SerializerNode $node
	 * @return SerializerNode
	 */
	public function getParentNode( SerializerNode $node ) {
		return $this->nodes[$node->parentId];
	}

	/**
	 * Get the last child of a given SerializerNode
	 *
	 * @param SerializerNode $node
	 * @return SerializerNode|string|null
	 */
	public function getLastChild( SerializerNode $node ) {
		$children = $node->children;
		$lastChildIndex = count( $children ) - 1;
		$lastChild = $lastChildIndex >= 0 ? $children[$lastChildIndex] : null;
		return $lastChild;
	}

	public function startDocument( $fragmentNamespace, $fragmentName ) {
		$this->root = new SerializerNode( 0, 0, '', '', new PlainAttributes, false );
		$this->nodes = [ $this->root ];
		$this->isFragment = $fragmentNamespace !== null;
		$this->result = $this->formatter->startDocument( $fragmentNamespace, $fragmentName );
	}

	public function endDocument( $pos ) {
		if ( $this->isFragment ) {
			$root = $this->root->children[0];
		} else {
			$root = $this->root;
		}
		foreach ( $root->children as $childIndex => $child ) {
			if ( is_string( $child ) ) {
				$this->result .= $child;
			} else {
				$this->result .= $this->serializeNode( $root, $child, false );
			}
		}
		// @phan-suppress-next-line PhanTypeMismatchPropertyProbablyReal
		$this->root = null;
		$this->nodes = [];
	}

	protected function interpretPlacement( $preposition, $refElement ) {
		if ( $preposition === TreeBuilder::ROOT ) {
			return [ $this->root, null ];
		}
		if ( $refElement instanceof Element ) {
			$refNode = $refElement->userData;
		} elseif ( $refElement instanceof SerializerNode ) {
			$refNode = $refElement;
		} else {
			throw new SerializerError( "Invalid type of ref element" );
		}
		if ( $preposition === TreeBuilder::BEFORE ) {
			return [ $this->nodes[$refNode->parentId], $refNode ];
		} else {
			return [ $refNode, $refNode ];
		}
	}

	public function characters( $preposition, $refElement, $text, $start, $length,
		$sourceStart, $sourceLength
	) {
		[ $parent, $refNode ] = $this->interpretPlacement( $preposition, $refElement );
		$encoded = (string)$this->formatter->characters( $parent, $text, $start, $length );

		$children =& $parent->children;
		$lastChildIndex = count( $children ) - 1;
		$lastChild = $lastChildIndex >= 0 ? $children[$lastChildIndex] : null;

		if ( $preposition === TreeBuilder::BEFORE ) {
			// Insert before element
			if ( $lastChild !== $refNode ) {
				$refIndex = array_search( $refNode, $children, true );
				throw new SerializerError( "invalid insert position $refIndex/$lastChildIndex" );
			}
			$children[$lastChildIndex] = $encoded;
			$children[$lastChildIndex + 1] = $refNode;
		} else {
			// Append to the list of children
			if ( is_string( $lastChild ) ) {
				$children[$lastChildIndex] .= $encoded;
			} else {
				$children[] = $encoded;
			}
		}
	}

	/**
	 * Insert an element
	 *
	 * @param int $preposition
	 * @param Element|SerializerNode|null $refElement
	 * @param Element $element
	 * @param bool $void
	 * @param int $sourceStart
	 * @param int $sourceLength
	 */
	public function insertElement( $preposition, $refElement, Element $element, $void,
		$sourceStart, $sourceLength
	) {
		[ $parent, $refNode ] = $this->interpretPlacement( $preposition, $refElement );
		$children =& $parent->children;
		$lastChildIndex = count( $children ) - 1;
		$lastChild = $lastChildIndex >= 0 ? $children[$lastChildIndex] : null;

		if ( $element->userData ) {
			// This element has already been inserted, this is a reparenting operation
			$self = $element->userData;
			'@phan-var SerializerNode $self'; /** @var SerializerNode $self */
			$oldParent = $this->nodes[$self->parentId];
			$oldChildren =& $oldParent->children;
			$oldChildIndex = array_search( $self, $oldChildren, true );
			if ( $oldChildIndex === false ) {
				throw new SerializerError( "cannot find node to reparent: " .
					$element->getDebugTag() );
			}
			// Remove from the old parent, update parent pointer
			$oldChildren[$oldChildIndex] = '';
			$self->parentId = $parent->id;
		} else {
			// Inserting an element which has not been seen before
			$id = $element->uid;
			$self = new SerializerNode( $id, $parent->id, $element->namespace,
				$element->name, $element->attrs, $void );
			$this->nodes[$id] = $element->userData = $self;
		}

		if ( $preposition === TreeBuilder::BEFORE ) {
			// Insert before element
			if ( $lastChild !== $refNode ) {
				$refIndex = array_search( $refNode, $children, true );
				throw new SerializerError( "invalid insert position $refIndex/$lastChildIndex" );
			}
			$children[$lastChildIndex] = $self;
			$children[$lastChildIndex + 1] = $refNode;
		} else {
			// Append to the list of children
			$children[] = $self;
		}
	}

	public function endTag( Element $element, $sourceStart, $sourceLength ) {
		if ( $element->htmlName === 'head' || $element->isVirtual ) {
			// <head> elements are immortal
			return;
		}
		$self = $element->userData;
		$parent = $this->nodes[$self->parentId];
		$children =& $parent->children;
		for ( $index = count( $children ) - 1; $index >= 0; $index-- ) {
			if ( $children[$index] === $self ) {
				$children[$index] = $this->serializeNode( $parent, $self, true );
				return;
			}
		}
		// Ignore requests to end non-existent elements (this happens sometimes)
	}

	/**
	 * Serialize a specific node
	 *
	 * @param SerializerNode $parent The parent of $node
	 * @param SerializerNode $node The node to serialize
	 * @param bool $destroy If true, the node and its descendants will be removed from $this->nodes
	 * @return string
	 */
	private function serializeNode( SerializerNode $parent, SerializerNode $node, $destroy ) {
		if ( $node->void ) {
			$contents = null;
		} else {
			$contents = '';
			foreach ( $node->children as $childIndex => $child ) {
				if ( is_string( $child ) ) {
					$contents .= $child;
				} else {
					$contents .= $this->serializeNode( $node, $child, $destroy );
				}
			}
		}
		if ( $destroy ) {
			unset( $this->nodes[$node->id] );
		}
		return $this->formatter->element( $parent, $node, $contents );
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->result .= $this->formatter->doctype( $name, $public, $system );
	}

	public function comment( $preposition, $refElement, $text, $sourceStart, $sourceLength ) {
		[ $parent, $refNode ] = $this->interpretPlacement( $preposition, $refElement );
		$encoded = $this->formatter->comment( $parent, $text );
		$children =& $parent->children;
		$lastChildIndex = count( $children ) - 1;
		$lastChild = $lastChildIndex >= 0 ? $children[$lastChildIndex] : null;

		if ( $preposition === TreeBuilder::BEFORE ) {
			// Insert before element
			if ( $lastChild !== $refNode ) {
				throw new SerializerError( "invalid insert position" );
			}
			$children[$lastChildIndex] = $encoded;
			$children[$lastChildIndex + 1] = $refNode;
		} else {
			// Append to the list of children
			if ( is_string( $lastChild ) ) {
				$children[$lastChildIndex] .= $encoded;
			} else {
				$children[] = $encoded;
			}
		}
	}

	public function error( $text, $pos ) {
		if ( $this->errorCallback ) {
			call_user_func( $this->errorCallback, $text, $pos );
		}
	}

	public function mergeAttributes( Element $element, Attributes $attrs, $sourceStart ) {
		$element->attrs->merge( $attrs );
		if ( $element->userData instanceof SerializerNode ) {
			$element->userData->attrs = $element->attrs;
		}
	}

	public function removeNode( Element $element, $sourceStart ) {
		$self = $element->userData;
		$parent = $this->nodes[$self->parentId];
		$children =& $parent->children;
		for ( $index = count( $children ) - 1; $index >= 0; $index-- ) {
			if ( $children[$index] === $self ) {
				$children[$index] = '';
				return;
			}
		}
		throw new SerializerError( "cannot find element to remove" );
	}

	public function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
		$self = $element->userData;
		$children = $self->children;
		$self->children = [];
		$this->insertElement( TreeBuilder::UNDER, $element, $newParent, false, $sourceStart, 0 );
		$newParentNode = $newParent->userData;
		$newParentId = $newParentNode->id;
		foreach ( $children as $child ) {
			if ( is_object( $child ) ) {
				$child->parentId = $newParentId;
			}
		}
		$newParentNode->children = $children;
	}

	/**
	 * Get a text representation of the current state of the serializer, for
	 * debugging.
	 *
	 * @return string
	 */
	public function dump() {
		$s = $this->serializeNode( $this->root, $this->root, false );
		return substr( $s, 2, -3 ) . "\n";
	}
}
