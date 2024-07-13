<?php

namespace Wikimedia\Zest;

use DOMNode;

class ZestFunc {
	/** @var callable(DOMNode,array):bool */
	public $func;
	/** @var ?string */
	public $sel = null;
	/** @var ?callable(DOMNode,array):bool */
	public $simple = null;
	/** @var ?callable(DOMNode,array):(?DOMNode) */
	public $combinator = null;
	/** @var ?ZestFunc */
	public $test = null;
	/** @var ?string */
	public $lname = null;
	/** @var ?string */
	public $qname = null;

	/** @param callable(DOMNode,array):bool $func */
	function __construct( callable $func ) {
		$this->func = $func;
	}
}
