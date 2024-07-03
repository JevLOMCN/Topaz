<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * Window
 *
 * @see https://dom.spec.whatwg.org/#interface-window
 *
 * @property EventHandlerNonNull|callable|null $onload
 * @property Document $document
 * @property string $name
 * @property Location $location
 * @property string $status
 * @property bool $closed
 * @property int $length
 * @property mixed|null $opener
 * @property Element|null $frameElement
 * @property Navigator $navigator
 * @property bool $originAgentCluster
 * @property Event|null $event
 * @phan-forbid-undeclared-magic-properties
 */
interface Window extends EventTarget, GlobalEventHandlers, WindowEventHandlers {
	// Direct parent: EventTarget

	/**
	 * @return Document
	 */
	public function getDocument();

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @param string $val
	 */
	public function setName( string $val ): void;

	/**
	 * @return Location
	 */
	public function getLocation();

	/**
	 * @param string $val
	 */
	public function setLocation( string $val ): void;

	/**
	 * @return string
	 */
	public function getStatus(): string;

	/**
	 * @param string $val
	 */
	public function setStatus( string $val ): void;

	/**
	 * @return void
	 */
	public function close(): void;

	/**
	 * @return bool
	 */
	public function getClosed(): bool;

	/**
	 * @return void
	 */
	public function stop(): void;

	/**
	 * @return void
	 */
	public function focus(): void;

	/**
	 * @return void
	 */
	public function blur(): void;

	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @return mixed|null
	 */
	public function getOpener();

	/**
	 * @param mixed|null $val
	 */
	public function setOpener( /* any */ $val ): void;

	/**
	 * @return Element|null
	 */
	public function getFrameElement();

	/**
	 * @return Navigator
	 */
	public function getNavigator();

	/**
	 * @return bool
	 */
	public function getOriginAgentCluster(): bool;

	/**
	 * @return void
	 */
	public function print(): void;

	/**
	 * @return Event|null
	 */
	public function getEvent();

	/**
	 * @param Element $elt
	 * @param ?string $pseudoElt
	 * @return CSSStyleDeclaration
	 */
	public function getComputedStyle( /* Element */ $elt, ?string $pseudoElt = null );

}
