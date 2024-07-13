<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\NlTk;

/**
 * Private helper class for ListHandler.
 * @private
 */
class ListFrame {
	/**
	 * Flag indicating a list-less line that terminates a list block
	 * @var bool
	 */
	public $atEOL = true;
	/**
	 * NlTk that triggered atEOL
	 * @var ?NlTk
	 */
	public $nlTk = null;
	/** @var array */
	public $solTokens = [];
	/**
	 * Bullet stack, previous element's listStyle
	 * @var array
	 */
	public $bstack = [];
	/**
	 * Stack of end tags
	 * @var array<EndTagTk>
	 */
	public $endtags = [];
	/**
	 * Partial DOM building heuristic:
	 * Number of open block tags encountered within list context.
	 * @var int
	 */
	public $numOpenBlockTags = 0;
	/**
	 * Number of open tags encountered within list context.
	 * @var int
	 */
	public $numOpenTags = 0;
}
