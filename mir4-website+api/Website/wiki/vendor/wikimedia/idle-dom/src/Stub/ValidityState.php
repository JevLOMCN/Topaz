<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait ValidityState {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return bool
	 */
	public function getValueMissing(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getTypeMismatch(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getPatternMismatch(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getTooLong(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getTooShort(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getRangeUnderflow(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getRangeOverflow(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getStepMismatch(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getBadInput(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getCustomError(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getValid(): bool {
		throw self::_unimplemented();
	}

}
