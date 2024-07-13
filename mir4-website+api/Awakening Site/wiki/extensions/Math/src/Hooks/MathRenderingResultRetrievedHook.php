<?php

namespace MediaWiki\Extension\Math\Hooks;

use MediaWiki\Extension\Math\MathRenderer;
use stdClass;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "MathRenderingResultRetrieved" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface MathRenderingResultRetrievedHook {
	/**
	 * @param MathRenderer &$renderer
	 * @param stdClass &$jsonResult
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onMathRenderingResultRetrieved( MathRenderer &$renderer, stdClass &$jsonResult );
}
