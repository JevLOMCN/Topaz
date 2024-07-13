<?php

namespace MediaWiki\Extension\Scribunto;

use MediaWiki\Config\Config;
use MediaWiki\Extension\CodeEditor\Hooks\CodeEditorGetPageLanguageHook;
use MediaWiki\Title\Title;

/**
 * Hooks from CodeEditor extension,
 * which is optional to use with this extension.
 */
class CodeEditorHooks implements CodeEditorGetPageLanguageHook {

	private bool $useCodeEditor;

	public function __construct(
		Config $config
	) {
		$this->useCodeEditor = $config->get( 'ScribuntoUseCodeEditor' );
	}

	/**
	 * @param Title $title
	 * @param string|null &$languageCode
	 * @param string $model
	 * @param string $format
	 * @return bool
	 */
	public function onCodeEditorGetPageLanguage( Title $title, ?string &$languageCode, string $model, string $format ) {
		if ( $this->useCodeEditor && $title->hasContentModel( CONTENT_MODEL_SCRIBUNTO ) ) {
			$engine = Scribunto::newDefaultEngine();
			if ( $engine->getCodeEditorLanguage() ) {
				$languageCode = $engine->getCodeEditorLanguage();
				return false;
			}
		}

		return true;
	}

}
