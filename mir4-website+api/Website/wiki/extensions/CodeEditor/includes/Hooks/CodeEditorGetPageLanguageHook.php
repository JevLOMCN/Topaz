<?php

namespace MediaWiki\Extension\CodeEditor\Hooks;

use MediaWiki\Title\Title;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "CodeEditorGetPageLanguage" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface CodeEditorGetPageLanguageHook {
	/**
	 * Allows to set a code language for extensions content models
	 *
	 * @param Title $title The title the language is for
	 * @param string|null &$lang The language to use
	 * @param string $model The content model of the title
	 * @param string $format The content format of the title
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onCodeEditorGetPageLanguage( Title $title, ?string &$lang, string $model, string $format );
}
