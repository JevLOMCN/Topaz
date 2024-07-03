<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\EventTarget;

trait Event {

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
	 * @return EventTarget|null
	 */
	public function getTarget() {
		throw self::_unimplemented();
	}

	/**
	 * @return EventTarget|null
	 */
	public function getSrcElement() {
		throw self::_unimplemented();
	}

	/**
	 * @return EventTarget|null
	 */
	public function getCurrentTarget() {
		throw self::_unimplemented();
	}

	/**
	 * @return list<EventTarget>
	 */
	public function composedPath(): array {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getEventPhase(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function stopPropagation(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getCancelBubble(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setCancelBubble( bool $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function stopImmediatePropagation(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getBubbles(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getCancelable(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getReturnValue(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setReturnValue( bool $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function preventDefault(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getDefaultPrevented(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getComposed(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getIsTrusted(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getTimeStamp(): float {
		throw self::_unimplemented();
	}

	/**
	 * @param string $type
	 * @param bool $bubbles
	 * @param bool $cancelable
	 * @return void
	 */
	public function initEvent( string $type, bool $bubbles = false, bool $cancelable = false ): void {
		throw self::_unimplemented();
	}

}
