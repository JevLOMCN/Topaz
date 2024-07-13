<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ProcessingInstruction
 *
 * @see https://dom.spec.whatwg.org/#interface-processinginstruction
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
 * @property CSSStyleSheet|null $sheet
 * @property string $target
 * @phan-forbid-undeclared-magic-properties
 */
interface ProcessingInstruction extends CharacterData, LinkStyle {
	// Direct parent: CharacterData

	/**
	 * @return string
	 */
	public function getTarget(): string;

}
