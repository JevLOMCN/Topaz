<?php

namespace PageImages\Tests;

use ApiPageSet;
use PageImages\ApiQueryPageImages;

class ApiQueryPageImagesProxyMock extends ApiQueryPageImages {

	private ApiPageSet $pageSet;

	/** @inheritDoc */
	public function __construct( ApiPageSet $pageSet ) {
		$this->pageSet = $pageSet;
	}

	/** @inheritDoc */
	public function getPageSet() {
		return $this->pageSet;
	}

	/** @inheritDoc */
	public function getTitles() {
		return parent::getTitles();
	}
}
