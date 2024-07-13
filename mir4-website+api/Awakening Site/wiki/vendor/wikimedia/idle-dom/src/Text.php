<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * Text
 *
 * @see https://dom.spec.whatwg.org/#interface-text
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
 * @property HTMLSlotElement|null $assignedSlot
 * @property string $wholeText
 * @phan-forbid-undeclared-magic-properties
 */
interface Text extends CharacterData, Slottable {
	// Direct parent: CharacterData

	/**
	 * @param int $offset
	 * @return \Wikimedia\IDLeDOM\Text
	 */
	public function splitText( int $offset );

	/**
	 * @return string
	 */
	public function getWholeText(): string;

}
