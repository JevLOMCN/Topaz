<?php

namespace MediaWiki\Extension\Math\Hooks;

use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\HookContainer\HookContainer;
use Parser;
use stdClass;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	MathFormulaPostRenderHook,
	MathRenderingResultRetrievedHook
{
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onMathFormulaPostRender( Parser $parser, MathRenderer $renderer, string &$renderedMath ) {
		return $this->hookContainer->run(
			'MathFormulaPostRender',
			[ $parser, $renderer, &$renderedMath ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onMathRenderingResultRetrieved( MathRenderer &$renderer, stdClass &$jsonResult ) {
		return $this->hookContainer->run(
			'MathRenderingResultRetrieved',
			[ &$renderer, &$jsonResult ]
		);
	}
}
