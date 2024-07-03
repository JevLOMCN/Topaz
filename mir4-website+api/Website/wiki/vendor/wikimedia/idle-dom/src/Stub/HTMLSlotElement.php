<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\AssignedNodesOptions;
use Wikimedia\IDLeDOM\Element;
use Wikimedia\IDLeDOM\Node;

trait HTMLSlotElement {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param AssignedNodesOptions|associative-array|null $options
	 * @return list<Node>
	 */
	public function assignedNodes( /* ?mixed */ $options = null ): array {
		throw self::_unimplemented();
	}

	/**
	 * @param AssignedNodesOptions|associative-array|null $options
	 * @return list<Element>
	 */
	public function assignedElements( /* ?mixed */ $options = null ): array {
		throw self::_unimplemented();
	}

}
