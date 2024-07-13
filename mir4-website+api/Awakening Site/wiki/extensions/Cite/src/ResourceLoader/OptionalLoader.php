<?php

namespace Cite\ResourceLoader;

use ExtensionRegistry;
use MediaWiki\ResourceLoader\FileModule;
use MediaWiki\ResourceLoader\Module;

/**
 * @license MIT
 */
class OptionalLoader {
	public static function addOptionalDependencies( array $info ): Module {
		// Copied from DiscussionTools
		$extensionRegistry = ExtensionRegistry::getInstance();
		foreach ( $info['optionalDependencies'] as $ext => $deps ) {
			if ( $extensionRegistry->isLoaded( $ext ) ) {
				$info['dependencies'] = array_merge( $info['dependencies'], (array)$deps );
			}
		}
		$class = $info['class'] ?? FileModule::class;
		return new $class( $info );
	}
}
