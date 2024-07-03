<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\CSSStyleDeclaration;
use Wikimedia\IDLeDOM\Document;
use Wikimedia\IDLeDOM\Element;
use Wikimedia\IDLeDOM\Event;
use Wikimedia\IDLeDOM\Location;
use Wikimedia\IDLeDOM\Navigator;

trait Window {
	// use \Wikimedia\IDLeDOM\Stub\GlobalEventHandlers;
	// use \Wikimedia\IDLeDOM\Stub\WindowEventHandlers;

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return Document
	 */
	public function getDocument() {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setName( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return Location
	 */
	public function getLocation() {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getStatus(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setStatus( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function close(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getClosed(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function stop(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function focus(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function blur(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return mixed|null
	 */
	public function getOpener() {
		throw self::_unimplemented();
	}

	/**
	 * @param mixed|null $val
	 */
	public function setOpener( /* any */ $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return Element|null
	 */
	public function getFrameElement() {
		throw self::_unimplemented();
	}

	/**
	 * @return Navigator
	 */
	public function getNavigator() {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getOriginAgentCluster(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function print(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return Event|null
	 */
	public function getEvent() {
		throw self::_unimplemented();
	}

	/**
	 * @param Element $elt
	 * @param ?string $pseudoElt
	 * @return CSSStyleDeclaration
	 */
	public function getComputedStyle( /* Element */ $elt, ?string $pseudoElt = null ) {
		throw self::_unimplemented();
	}

}
