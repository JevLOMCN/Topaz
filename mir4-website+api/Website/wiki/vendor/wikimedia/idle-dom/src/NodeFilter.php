<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * NodeFilter
 *
 * @see https://dom.spec.whatwg.org/#callbackdef-nodefilter
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface NodeFilter {
	/** @var int */
	public const FILTER_ACCEPT = 1;

	/** @var int */
	public const FILTER_REJECT = 2;

	/** @var int */
	public const FILTER_SKIP = 3;

	/** @var int */
	public const SHOW_ALL = -1;

	/** @var int */
	public const SHOW_ELEMENT = 1;

	/** @var int */
	public const SHOW_ATTRIBUTE = 2;

	/** @var int */
	public const SHOW_TEXT = 4;

	/** @var int */
	public const SHOW_CDATA_SECTION = 8;

	/** @var int */
	public const SHOW_ENTITY_REFERENCE = 16;

	/** @var int */
	public const SHOW_ENTITY = 32;

	/** @var int */
	public const SHOW_PROCESSING_INSTRUCTION = 64;

	/** @var int */
	public const SHOW_COMMENT = 128;

	/** @var int */
	public const SHOW_DOCUMENT = 256;

	/** @var int */
	public const SHOW_DOCUMENT_TYPE = 512;

	/** @var int */
	public const SHOW_DOCUMENT_FRAGMENT = 1024;

	/** @var int */
	public const SHOW_NOTATION = 2048;

	/**
	 * @param Node $node
	 * @return int
	 */
	public function acceptNode( /* Node */ $node ): int;

}
