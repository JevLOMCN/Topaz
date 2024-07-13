<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use MediaWiki\Extension\DiscussionTools\LanguageData;

class MockLanguageData extends LanguageData {

	private array $data;

	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function getLocalData(): array {
		return $this->data;
	}
}
