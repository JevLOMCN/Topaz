<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

class Fun2sq extends Fun2 {

	public function inCurlies() {
		return $this->render();
	}

	public function render() {
		return '{' . $this->fname . '[' . $this->arg1->render() . ']' . $this->arg2->inCurlies() . '}';
	}
}
