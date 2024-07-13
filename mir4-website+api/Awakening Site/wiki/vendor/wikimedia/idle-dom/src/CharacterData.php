<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CharacterData
 *
 * @see https://dom.spec.whatwg.org/#interface-characterdata
 *
 * @property int $nodeType
 * @property string $nodeName
 * @property string $baseURI
 * @property bool $isConnected
 * @property Document|null $ownerDocument
 * @property Node|null $parentNode
 * @property Element|null $parentElement
 * @property NodeList $childNodes
 * @property Node|null $firstChild
 * @property Node|null $lastChild
 * @property Node|null $previousSibling
 * @property Node|null $nextSibling
 * @property ?string $nodeValue
 * @property ?string $textContent
 * @property Element|null $previousElementSibling
 * @property Element|null $nextElementSibling
 * @property string $data
 * @property int $length
 * @phan-forbid-undeclared-magic-properties
 */
interface CharacterData extends Node, ChildNode, NonDocumentTypeChildNode {
	// Direct parent: Node

	/**
	 * @return string
	 */
	public function getData(): string;

	/**
	 * @param ?string $val
	 */
	public function setData( ?string $val ): void;

	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @param int $offset
	 * @param int $count
	 * @return string
	 */
	public function substringData( int $offset, int $count ): string;

	/**
	 * @param string $data
	 * @return void
	 */
	public function appendData( string $data ): void;

	/**
	 * @param int $offset
	 * @param string $data
	 * @return void
	 */
	public function insertData( int $offset, string $data ): void;

	/**
	 * @param int $offset
	 * @param int $count
	 * @return void
	 */
	public function deleteData( int $offset, int $count ): void;

	/**
	 * @param int $offset
	 * @param int $count
	 * @param string $data
	 * @return void
	 */
	public function replaceData( int $offset, int $count, string $data ): void;

}
