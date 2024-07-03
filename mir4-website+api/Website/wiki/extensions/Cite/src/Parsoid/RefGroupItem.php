<?php
declare( strict_types = 1 );

namespace Cite\Parsoid;

use Wikimedia\Parsoid\DOM\Element;

/**
 * Individual item in a {@see RefGroup}.
 *
 * @license GPL-2.0-or-later
 */
class RefGroupItem {

	/**
	 * Pointer to the contents of the ref, accessible with the
	 * {@see ParsoidExtensionAPI::getContentDOM}, to be used when serializing the references group.
	 * It gets set when extracting the ref from a node and not $missingContent.  Note that that
	 * might not be the first one for named refs.  Also, for named refs, it's used to detect
	 * multiple conflicting definitions.
	 */
	public ?string $contentId = null;

	/** Just used for comparison when we have multiples */
	public ?string $cachedHtml = null;

	public string $dir = '';
	public string $group = '';
	public int $groupIndex = 1;
	public int $index = 0;
	public string $key;
	public string $id;
	/** @var array<int,string> */
	public array $linkbacks = [];
	public string $name = '';
	public string $target;

	/** @var Element[] */
	public array $nodes = [];

	/** @var string[] */
	public array $embeddedNodes = [];

}
