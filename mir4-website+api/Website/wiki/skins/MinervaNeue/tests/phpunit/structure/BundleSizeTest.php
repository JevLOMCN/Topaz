<?php

namespace MediaWiki\Skins\MinervaNeue\Tests\Structure;

class BundleSizeTest extends \MediaWiki\Tests\Structure\BundleSizeTest {

	/** @inheritDoc */
	public function getBundleSizeConfig(): string {
		return dirname( __DIR__, 3 ) . '/bundlesize.config.json';
	}

	/** @inheritDoc */
	public function getSkinName(): string {
		return 'minerva';
	}
}
