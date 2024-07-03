<?php

namespace MediaWiki\Extension\DiscussionTools;

use DOMException;
use Wikimedia\Parsoid\DOM\Node;

/**
 * Partial implementation of W3 DOM4 NodeFilter interface.
 *
 * See also:
 * - https://dom.spec.whatwg.org/#interface-nodefilter
 *
 * Adapted from https://github.com/Krinkle/dom-TreeWalker-polyfill/blob/master/src/TreeWalker-polyfill.js
 */
class NodeFilter {

	// Constants for acceptNode()
	public const FILTER_ACCEPT = 1;
	public const FILTER_REJECT = 2;
	public const FILTER_SKIP = 3;

	// Constants for whatToShow
	public const SHOW_ALL = 0xFFFFFFFF;
	public const SHOW_ELEMENT = 0x1;
	public const SHOW_ATTRIBUTE = 0x2;
	public const SHOW_TEXT = 0x4;
	public const SHOW_CDATA_SECTION = 0x8;
	public const SHOW_ENTITY_REFERENCE = 0x10;
	public const SHOW_ENTITY = 0x20;
	public const SHOW_PROCESSING_INSTRUCTION = 0x40;
	public const SHOW_COMMENT = 0x80;
	public const SHOW_DOCUMENT = 0x100;
	public const SHOW_DOCUMENT_TYPE = 0x200;
	public const SHOW_DOCUMENT_FRAGMENT = 0x400;
	public const SHOW_NOTATION = 0x800;

	/** @var callable */
	public $filter;

	private bool $active = false;

	/**
	 * See https://dom.spec.whatwg.org/#dom-nodefilter-acceptnode
	 *
	 * @param Node $node
	 * @return int Constant NodeFilter::FILTER_ACCEPT,
	 *  NodeFilter::FILTER_REJECT or NodeFilter::FILTER_SKIP.
	 */
	public function acceptNode( $node ) {
		if ( $this->active ) {
			throw new DOMException( 'INVALID_STATE_ERR' );
		}

		$this->active = true;
		$result = call_user_func( $this->filter, $node );
		$this->active = false;

		return $result;
	}
}
