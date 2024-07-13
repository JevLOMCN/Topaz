<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\CSSStyleSheet;
use Wikimedia\IDLeDOM\Element;
use Wikimedia\IDLeDOM\MediaList;
use Wikimedia\IDLeDOM\ProcessingInstruction;

trait StyleSheet {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return string
	 */
	public function getType(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return ?string
	 */
	public function getHref(): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @return Element|ProcessingInstruction|null
	 */
	public function getOwnerNode() {
		throw self::_unimplemented();
	}

	/**
	 * @return CSSStyleSheet|null
	 */
	public function getParentStyleSheet() {
		throw self::_unimplemented();
	}

	/**
	 * @return ?string
	 */
	public function getTitle(): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @return MediaList
	 */
	public function getMedia() {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getDisabled(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setDisabled( bool $val ): void {
		throw self::_unimplemented();
	}

}
