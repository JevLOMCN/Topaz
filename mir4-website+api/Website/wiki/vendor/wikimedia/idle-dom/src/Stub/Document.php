<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Attr;
use Wikimedia\IDLeDOM\CDATASection;
use Wikimedia\IDLeDOM\Comment;
use Wikimedia\IDLeDOM\DocumentFragment;
use Wikimedia\IDLeDOM\DocumentType;
use Wikimedia\IDLeDOM\DOMImplementation;
use Wikimedia\IDLeDOM\Element;
use Wikimedia\IDLeDOM\ElementCreationOptions;
use Wikimedia\IDLeDOM\Event;
use Wikimedia\IDLeDOM\EventHandlerNonNull;
use Wikimedia\IDLeDOM\HTMLCollection;
use Wikimedia\IDLeDOM\HTMLElement;
use Wikimedia\IDLeDOM\HTMLHeadElement;
use Wikimedia\IDLeDOM\HTMLScriptElement;
use Wikimedia\IDLeDOM\Location;
use Wikimedia\IDLeDOM\Node;
use Wikimedia\IDLeDOM\NodeFilter;
use Wikimedia\IDLeDOM\NodeIterator;
use Wikimedia\IDLeDOM\NodeList;
use Wikimedia\IDLeDOM\ProcessingInstruction;
use Wikimedia\IDLeDOM\Range;
use Wikimedia\IDLeDOM\Text;
use Wikimedia\IDLeDOM\TreeWalker;

trait Document {
	// use \Wikimedia\IDLeDOM\Stub\DocumentAndElementEventHandlers;
	// use \Wikimedia\IDLeDOM\Stub\DocumentOrShadowRoot;
	// use \Wikimedia\IDLeDOM\Stub\GlobalEventHandlers;
	// use \Wikimedia\IDLeDOM\Stub\NonElementParentNode;
	// use \Wikimedia\IDLeDOM\Stub\ParentNode;
	// use \Wikimedia\IDLeDOM\Stub\XPathEvaluatorBase;

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return DOMImplementation
	 */
	public function getImplementation() {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getURL(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getDocumentURI(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getCompatMode(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getCharacterSet(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getCharset(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getInputEncoding(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getContentType(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return DocumentType|null
	 */
	public function getDoctype() {
		throw self::_unimplemented();
	}

	/**
	 * @return Element|null
	 */
	public function getDocumentElement() {
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
	 * @param string $localName
	 * @param string|ElementCreationOptions|associative-array|null $options
	 * @return Element
	 */
	public function createElement( string $localName, /* ?mixed */ $options = null ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $qualifiedName
	 * @param string|ElementCreationOptions|associative-array|null $options
	 * @return Element
	 */
	public function createElementNS( ?string $namespace, string $qualifiedName, /* ?mixed */ $options = null ) {
		throw self::_unimplemented();
	}

	/**
	 * @return DocumentFragment
	 */
	public function createDocumentFragment() {
		throw self::_unimplemented();
	}

	/**
	 * @param string $data
	 * @return Text
	 */
	public function createTextNode( string $data ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $data
	 * @return CDATASection
	 */
	public function createCDATASection( string $data ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $data
	 * @return Comment
	 */
	public function createComment( string $data ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $target
	 * @param string $data
	 * @return ProcessingInstruction
	 */
	public function createProcessingInstruction( string $target, string $data ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @param bool $deep
	 * @return Node
	 */
	public function importNode( /* Node */ $node, bool $deep = false ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return Node
	 */
	public function adoptNode( /* Node */ $node ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $localName
	 * @return Attr
	 */
	public function createAttribute( string $localName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $qualifiedName
	 * @return Attr
	 */
	public function createAttributeNS( ?string $namespace, string $qualifiedName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $interface
	 * @return Event
	 */
	public function createEvent( string $interface ) {
		throw self::_unimplemented();
	}

	/**
	 * @return Range
	 */
	public function createRange() {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $root
	 * @param int $whatToShow
	 * @param NodeFilter|callable|null $filter
	 * @return NodeIterator
	 */
	public function createNodeIterator( /* Node */ $root, int $whatToShow = -1, /* ?mixed */ $filter = null ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $root
	 * @param int $whatToShow
	 * @param NodeFilter|callable|null $filter
	 * @return TreeWalker
	 */
	public function createTreeWalker( /* Node */ $root, int $whatToShow = -1, /* ?mixed */ $filter = null ) {
		throw self::_unimplemented();
	}

	/**
	 * @return Location|null
	 */
	public function getLocation() {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getReferrer(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getCookie(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setCookie( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getLastModified(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setTitle( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getDir(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setDir( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLElement|null
	 */
	public function getBody() {
		throw self::_unimplemented();
	}

	/**
	 * @param HTMLElement|null $val
	 */
	public function setBody( /* ?HTMLElement */ $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLHeadElement|null
	 */
	public function getHead() {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getImages() {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getEmbeds() {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getPlugins() {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getLinks() {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getForms() {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getScripts() {
		throw self::_unimplemented();
	}

	/**
	 * @param string $elementName
	 * @return NodeList
	 */
	public function getElementsByName( string $elementName ) {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLScriptElement|null
	 */
	public function getCurrentScript() {
		throw self::_unimplemented();
	}

	/**
	 * @param string $type
	 * @param string $replace
	 * @return \Wikimedia\IDLeDOM\Document
	 */
	public function open( string $type = 'text/html', string $replace = '' ) {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function close(): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string ...$text
	 * @return void
	 */
	public function write( string ...$text ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string ...$text
	 * @return void
	 */
	public function writeln( string ...$text ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function hasFocus(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnreadystatechange() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnreadystatechange( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getAnchors() {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getApplets() {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function clear(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function captureEvents(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function releaseEvents(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getHidden(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getVisibilityState(): /* VisibilityState */ string {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnvisibilitychange() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnvisibilitychange( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getEncoding(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setEncoding( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getPreserveWhiteSpace(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setPreserveWhiteSpace( bool $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getFormatOutput(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setFormatOutput( bool $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getValidateOnParse(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setValidateOnParse( bool $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getStrictErrorChecking(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setStrictErrorChecking( bool $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $source
	 * @param int $options
	 * @return bool
	 */
	public function loadHTML( string $source, int $options = 0 ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $source
	 * @param int $options
	 * @return bool
	 */
	public function loadXML( string $source, int $options = 0 ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param Node|null $node
	 * @return string|bool
	 */
	public function saveHTML( /* ?Node */ $node = null ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Node|null $node
	 * @param int $options
	 * @return string|bool
	 */
	public function saveXML( /* ?Node */ $node = null, int $options = 0 ) {
		throw self::_unimplemented();
	}

}
