<?php

namespace MediaWiki\Extension\Math\Hooks;

use MediaWiki\Extension\Math\MathRenderer;
use Parser;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "MathFormulaPostRender" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface MathFormulaPostRenderHook {
	/**
	 * @param Parser $parser
	 * @param MathRenderer $renderer
	 * @param string &$renderedMath
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onMathFormulaPostRender( Parser $parser, MathRenderer $renderer, string &$renderedMath );
}
