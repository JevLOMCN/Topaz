<?php

namespace MediaWiki\Extension\CodeEditor\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\Title;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	CodeEditorGetPageLanguageHook
{
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onCodeEditorGetPageLanguage( Title $title, ?string &$lang, string $model, string $format ) {
		return $this->hookContainer->run(
			'CodeEditorGetPageLanguage',
			[ $title, &$lang, $model, $format ]
		);
	}
}
