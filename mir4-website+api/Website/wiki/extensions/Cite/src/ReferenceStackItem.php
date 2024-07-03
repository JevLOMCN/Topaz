<?php

namespace Cite;

/**
 * Internal data container for a single <ref> tag and all information about it.
 *
 * @license GPL-2.0-or-later
 */
class ReferenceStackItem {
	/**
	 * @var int How often a reference footnote mark appears.  Can be 0 in the case
	 * of not-yet-used or unused list-defined references, or sub-ref parents.
	 */
	public int $count = 0;
	/**
	 * @var ?string Direction of the text. Should either be "ltr", "rtl" or null
	 * if unspecified.
	 */
	public ?string $dir = null;
	/**
	 * @var ?string Marks a sub-reference. Points to the parent reference by name.
	 */
	public ?string $extends = null;
	/**
	 * @var ?int Count how many subreferences point to a parent.  Corresponds to
	 *   the last {@see extendsIndex} but this field belongs to the parent.
	 */
	public ?int $extendsCount = null;
	/**
	 * @var ?int Sequence number for sub-references with the same extends
	 * attribute value, starting from 1. {@see number} and this extends index are
	 * combined to render a footnote marker like "[1.1]".
	 */
	public ?int $extendsIndex = null;
	/**
	 * @var ?string Marks a "follow" ref which continues the ref text from a
	 * previous page, e.g. in the Page:… namespace on Wikisource.
	 */
	public ?string $follow = null;
	/**
	 * @var string Name of the group (or empty for the default group) which this ref
	 * belongs to.
	 */
	public string $group;
	/**
	 * @var int Sequence number for all references, no matter which group, starting
	 * from 1. Used to generate IDs and anchors.
	 */
	public int $key;
	/**
	 * @var ?string The original name attribute of a reference, or null for anonymous
	 * references.
	 */
	public ?string $name = null;
	/**
	 * @var ?int Sequence number per {@see group}, starting from 1. To be used in the
	 * footnote marker, eg "[1]".
	 */
	public ?int $number = null;
	/**
	 * @var bool Temporarily marks an incomplete parent ref referenced via
	 * {@see extends} before it exists.
	 */
	public bool $placeholder = false;
	/**
	 * @var ?string The content inside the <ref>…</ref> tag. Null for a
	 * self-closing <ref /> without content. Also null for <ref></ref> without any
	 * non-whitespace content.
	 */
	public ?string $text = null;
	/**
	 * @var array Error messages attached to this reference.
	 */
	public array $warnings = [];
}
