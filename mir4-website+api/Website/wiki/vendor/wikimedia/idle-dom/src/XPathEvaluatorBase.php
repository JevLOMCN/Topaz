<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * XPathEvaluatorBase
 *
 * @see https://dom.spec.whatwg.org/#interface-xpathevaluatorbase
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface XPathEvaluatorBase {
	/**
	 * @param string $expression
	 * @param XPathNSResolver|callable|null $resolver
	 * @return XPathExpression
	 */
	public function createExpression( string $expression, /* ?mixed */ $resolver = null );

	/**
	 * @param Node $nodeResolver
	 * @return XPathNSResolver|callable
	 */
	public function createNSResolver( /* Node */ $nodeResolver );

	/**
	 * @param string $expression
	 * @param Node $contextNode
	 * @param XPathNSResolver|callable|null $resolver
	 * @param int $type
	 * @param XPathResult|null $result
	 * @return XPathResult
	 */
	public function evaluate( string $expression, /* Node */ $contextNode, /* ?mixed */ $resolver = null, int $type = 0, /* ?XPathResult */ $result = null );

}
