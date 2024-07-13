<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Node;
use Wikimedia\IDLeDOM\XPathExpression;
use Wikimedia\IDLeDOM\XPathNSResolver;
use Wikimedia\IDLeDOM\XPathResult;

trait XPathEvaluatorBase {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param string $expression
	 * @param XPathNSResolver|callable|null $resolver
	 * @return XPathExpression
	 */
	public function createExpression( string $expression, /* ?mixed */ $resolver = null ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $nodeResolver
	 * @return XPathNSResolver|callable
	 */
	public function createNSResolver( /* Node */ $nodeResolver ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $expression
	 * @param Node $contextNode
	 * @param XPathNSResolver|callable|null $resolver
	 * @param int $type
	 * @param XPathResult|null $result
	 * @return XPathResult
	 */
	public function evaluate( string $expression, /* Node */ $contextNode, /* ?mixed */ $resolver = null, int $type = 0, /* ?XPathResult */ $result = null ) {
		throw self::_unimplemented();
	}

}
