<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSStyleSheetInit
 *
 * @see https://dom.spec.whatwg.org/#dictdef-cssstylesheetinit
 *
 * @property string $baseURL
 * @property MediaList|string $media
 * @property bool $disabled
 * @phan-forbid-undeclared-magic-properties
 */
abstract class CSSStyleSheetInit implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\CSSStyleSheetInit;

	/**
	 * @return string
	 */
	abstract public function getBaseURL(): string;

	/**
	 * @return MediaList|string
	 */
	abstract public function getMedia();

	/**
	 * @return bool
	 */
	abstract public function getDisabled(): bool;

}
