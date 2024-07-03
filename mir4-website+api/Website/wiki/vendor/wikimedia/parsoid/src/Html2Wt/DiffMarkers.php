<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

class DiffMarkers {
	/**
	 * @var string
	 */
	public const DELETED = 'deleted';

	/**
	 * @var string
	 */
	public const INSERTED = 'inserted';

	/**
	 * @var string
	 */
	public const MOVED = 'moved';

	/**
	 * @var string
	 */
	public const CHILDREN_CHANGED = 'children-changed';

	/**
	 * @var string
	 */
	public const SUBTREE_CHANGED = 'subtree-changed';

	/**
	 * @var string
	 */
	public const MODIFIED_WRAPPER = 'modified-wrapper';
}
