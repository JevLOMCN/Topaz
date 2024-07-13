<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use ExtensionRegistry;
use MediaWiki\Tests\ExtensionJsonTestBase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversNothing
 */
class AbuseFilterExtensionJsonTest extends ExtensionJsonTestBase {

	/** @inheritDoc */
	protected string $extensionJsonPath = __DIR__ . '/../../../extension.json';

	public function provideHookHandlerNames(): iterable {
		foreach ( $this->getExtensionJson()['HookHandlers'] ?? [] as $hookHandlerName => $specification ) {
			if ( $hookHandlerName === 'UserMerge' && !ExtensionRegistry::getInstance()->isLoaded( 'UserMerge' ) ) {
				continue;
			}
			yield [ $hookHandlerName ];
		}
	}
}
