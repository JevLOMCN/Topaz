<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;

class WrapSectionsTplInfo {
	public Element $first;
	// FIXME: This maybe-null feels broken.
	// This is because language variant markup is considered
	// encapsulated content (by WTUtils helpers) right now but
	// they may not have any about ids.
	public ?string $about;
	public Node $last;
	/** @var Node[] */
	public array $rtContentNodes = [];
	public ?Section $firstSection;
	public ?Section $lastSection;
}
