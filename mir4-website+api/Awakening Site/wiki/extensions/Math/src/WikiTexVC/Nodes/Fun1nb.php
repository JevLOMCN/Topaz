<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

class Fun1nb extends Fun1 {

	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	public function render() {
		return $this->fname . ' ' . $this->arg->inCurlies() . ' ';
	}

	public function renderMML( $arguments = [], $state = [] ): string {
		return $this->parseToMML( $this->fname, $arguments, $state );
	}
}
