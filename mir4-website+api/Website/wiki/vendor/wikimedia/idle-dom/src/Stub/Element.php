<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Attr;
use Wikimedia\IDLeDOM\DOMTokenList;
use Wikimedia\IDLeDOM\HTMLCollection;
use Wikimedia\IDLeDOM\NamedNodeMap;
use Wikimedia\IDLeDOM\ShadowRoot;
use Wikimedia\IDLeDOM\ShadowRootInit;

trait Element {
	// use \Wikimedia\IDLeDOM\Stub\ChildNode;
	// use \Wikimedia\IDLeDOM\Stub\InnerHTML;
	// use \Wikimedia\IDLeDOM\Stub\NonDocumentTypeChildNode;
	// use \Wikimedia\IDLeDOM\Stub\ParentNode;
	// use \Wikimedia\IDLeDOM\Stub\Slottable;

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return ?string
	 */
	public function getNamespaceURI(): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @return ?string
	 */
	public function getPrefix(): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getLocalName(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getTagName(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return DOMTokenList
	 */
	public function getClassList() {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function hasAttributes(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return NamedNodeMap
	 */
	public function getAttributes() {
		throw self::_unimplemented();
	}

	/**
	 * @return list<string>
	 */
	public function getAttributeNames(): array {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @return ?string
	 */
	public function getAttribute( string $qualifiedName ): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return ?string
	 */
	public function getAttributeNS( ?string $namespace, string $localName ): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @param string $value
	 * @return void
	 */
	public function setAttribute( string $qualifiedName, string $value ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $qualifiedName
	 * @param string $value
	 * @return void
	 */
	public function setAttributeNS( ?string $namespace, string $qualifiedName, string $value ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @return void
	 */
	public function removeAttribute( string $qualifiedName ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return void
	 */
	public function removeAttributeNS( ?string $namespace, string $localName ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @param ?bool $force
	 * @return bool
	 */
	public function toggleAttribute( string $qualifiedName, ?bool $force = null ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @return bool
	 */
	public function hasAttribute( string $qualifiedName ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return bool
	 */
	public function hasAttributeNS( ?string $namespace, string $localName ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @return Attr|null
	 */
	public function getAttributeNode( string $qualifiedName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return Attr|null
	 */
	public function getAttributeNodeNS( ?string $namespace, string $localName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Attr $attr
	 * @return Attr|null
	 */
	public function setAttributeNode( /* Attr */ $attr ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Attr $attr
	 * @return Attr|null
	 */
	public function setAttributeNodeNS( /* Attr */ $attr ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Attr $attr
	 * @return Attr
	 */
	public function removeAttributeNode( /* Attr */ $attr ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ShadowRootInit|associative-array $init
	 * @return ShadowRoot
	 */
	public function attachShadow( /* mixed */ $init ) {
		throw self::_unimplemented();
	}

	/**
	 * @return ShadowRoot|null
	 */
	public function getShadowRoot() {
		throw self::_unimplemented();
	}

	/**
	 * @param string $selectors
	 * @return \Wikimedia\IDLeDOM\Element|null
	 */
	public function closest( string $selectors ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $selectors
	 * @return bool
	 */
	public function matches( string $selectors ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $selectors
	 * @return bool
	 */
	public function webkitMatchesSelector( string $selectors ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @return HTMLCollection
	 */
	public function getElementsByTagName( string $qualifiedName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return HTMLCollection
	 */
	public function getElementsByTagNameNS( ?string $namespace, string $localName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $classNames
	 * @return HTMLCollection
	 */
	public function getElementsByClassName( string $classNames ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $where
	 * @param \Wikimedia\IDLeDOM\Element $element
	 * @return \Wikimedia\IDLeDOM\Element|null
	 */
	public function insertAdjacentElement( string $where, /* \Wikimedia\IDLeDOM\Element */ $element ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $where
	 * @param string $data
	 * @return void
	 */
	public function insertAdjacentText( string $where, string $data ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getOuterHTML(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $val
	 */
	public function setOuterHTML( ?string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $position
	 * @param string $text
	 * @return void
	 */
	public function insertAdjacentHTML( string $position, string $text ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @param bool $isId
	 * @return void
	 */
	public function setIdAttribute( string $qualifiedName, bool $isId ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Attr $attr
	 * @param bool $isId
	 * @return void
	 */
	public function setIdAttributeNode( /* Attr */ $attr, bool $isId ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $namespace
	 * @param string $qualifiedName
	 * @param bool $isId
	 * @return void
	 */
	public function setIdAttributeNS( string $namespace, string $qualifiedName, bool $isId ): void {
		throw self::_unimplemented();
	}

}
