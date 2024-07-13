<?php

namespace MediaWiki\Extension\Scribunto\Hooks;

use MediaWiki\HookContainer\HookContainer;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	ScribuntoExternalLibrariesHook,
	ScribuntoExternalLibraryPathsHook
{
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		return $this->hookContainer->run(
			'ScribuntoExternalLibraries',
			[ $engine, &$extraLibraries ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onScribuntoExternalLibraryPaths( string $engine, array &$extraLibraryPaths ) {
		return $this->hookContainer->run(
			'ScribuntoExternalLibraryPaths',
			[ $engine, &$extraLibraryPaths ]
		);
	}
}
