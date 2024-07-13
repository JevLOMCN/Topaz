<?php

namespace MediaWiki\Extension\DiscussionTools;

use DOMException;
use Throwable;
use Wikimedia\Parsoid\DOM\Node;

/**
 * Partial implementation of W3 DOM4 TreeWalker interface.
 *
 * See also:
 * - https://dom.spec.whatwg.org/#interface-treewalker
 *
 * Ported from https://github.com/TRowbotham/PHPDOM (MIT)
 */
class TreeWalker {

	public Node $root;
	public int $whatToShow;
	public Node $currentNode;
	/** @var callable|null */
	public $filter;
	private bool $isActive = false;

	/**
	 * See https://dom.spec.whatwg.org/#interface-treewalker
	 */
	public function __construct(
		Node $root,
		int $whatToShow = NodeFilter::SHOW_ALL,
		?callable $filter = null
	) {
		$this->currentNode = $root;
		$this->filter = $filter;
		$this->root = $root;
		$this->whatToShow = $whatToShow;
	}

	/**
	 * See https://dom.spec.whatwg.org/#dom-treewalker-nextnode
	 *
	 * @return Node|null The current node
	 */
	public function nextNode(): ?Node {
		$node = $this->currentNode;
		$result = NodeFilter::FILTER_ACCEPT;

		while ( true ) {
			while ( $result !== NodeFilter::FILTER_REJECT && $node->firstChild !== null ) {
				$node = $node->firstChild;
				$result = $this->filterNode( $node );
				if ( $result === NodeFilter::FILTER_ACCEPT ) {
					$this->currentNode = $node;
					return $node;
				}
			}

			$sibling = null;
			$temp = $node;
			while ( $temp !== null ) {
				if ( $temp === $this->root ) {
					return null;
				}

				$sibling = $temp->nextSibling;

				if ( $sibling !== null ) {
					$node = $sibling;

					break;
				}

				$temp = $temp->parentNode;
			}

			'@phan-var Node $node';
			$result = $this->filterNode( $node );

			if ( $result === NodeFilter::FILTER_ACCEPT ) {
				$this->currentNode = $node;

				return $node;
			}

		}
	}

	/**
	 * Filters a node.
	 *
	 * @internal
	 *
	 * @see https://dom.spec.whatwg.org/#concept-node-filter
	 *
	 * @param Node $node The node to check.
	 * @return int Returns one of NodeFilter's FILTER_* constants.
	 *     - NodeFilter::FILTER_ACCEPT
	 *     - NodeFilter::FILTER_REJECT
	 *     - NodeFilter::FILTER_SKIP
	 */
	private function filterNode( Node $node ): int {
		if ( $this->isActive ) {
			throw new DOMException( 'InvalidStateError' );
		}

		// Let n be node’s nodeType attribute value minus 1.
		$n = $node->nodeType - 1;

		// If the nth bit (where 0 is the least significant bit) of whatToShow
		// is not set, return FILTER_SKIP.
		if ( !( ( 1 << $n ) & $this->whatToShow ) ) {
			return NodeFilter::FILTER_SKIP;
		}

		// If filter is null, return FILTER_ACCEPT.
		if ( !$this->filter ) {
			return NodeFilter::FILTER_ACCEPT;
		}

		$this->isActive = true;

		try {
			// Let $result be the return value of call a user object's operation
			// with traverser's filter, "acceptNode", and Node. If this throws
			// an exception, then unset traverser's active flag and rethrow the
			// exception.
			$result = $this->filter instanceof NodeFilter
				? $this->filter->acceptNode( $node )
				: ( $this->filter )( $node );
		} catch ( Throwable $e ) {
			$this->isActive = false;

			throw $e;
		}

		$this->isActive = false;

		return $result;
	}
}
