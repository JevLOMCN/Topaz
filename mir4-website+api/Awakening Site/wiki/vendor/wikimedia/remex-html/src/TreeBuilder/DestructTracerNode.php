<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

class DestructTracerNode {
	private $callback;
	private $tag;

	public function __construct( $callback, $tag ) {
		$this->callback = $callback;
		$this->tag = $tag;
	}

	public function __destruct() {
		call_user_func( $this->callback, "[Destruct] {$this->tag}" );
	}
}
