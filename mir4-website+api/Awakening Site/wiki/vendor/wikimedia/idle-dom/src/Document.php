<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * Document
 *
 * @see https://dom.spec.whatwg.org/#interface-document
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
 * @property StyleSheetList $styleSheets
 * @property EventHandlerNonNull|callable|null $onload
 * @property HTMLCollection $children
 * @property Element|null $firstElementChild
 * @property Element|null $lastElementChild
 * @property int $childElementCount
 * @property DOMImplementation $implementation
 * @property string $URL
 * @property string $documentURI
 * @property string $compatMode
 * @property string $characterSet
 * @property string $charset
 * @property string $inputEncoding
 * @property string $contentType
 * @property DocumentType|null $doctype
 * @property Element|null $documentElement
 * @property Location|null $location
 * @property string $referrer
 * @property string $cookie
 * @property string $lastModified
 * @property string $title
 * @property string $dir
 * @property HTMLElement|null $body
 * @property HTMLHeadElement|null $head
 * @property HTMLCollection $images
 * @property HTMLCollection $embeds
 * @property HTMLCollection $plugins
 * @property HTMLCollection $links
 * @property HTMLCollection $forms
 * @property HTMLCollection $scripts
 * @property HTMLScriptElement|null $currentScript
 * @property EventHandlerNonNull|callable|null $onreadystatechange
 * @property HTMLCollection $anchors
 * @property HTMLCollection $applets
 * @property bool $hidden
 * @property string $visibilityState
 * @property EventHandlerNonNull|callable|null $onvisibilitychange
 * @property string $encoding
 * @property bool $preserveWhiteSpace
 * @property bool $formatOutput
 * @property bool $validateOnParse
 * @property bool $strictErrorChecking
 * @phan-forbid-undeclared-magic-properties
 */
interface Document extends Node, DocumentAndElementEventHandlers, DocumentOrShadowRoot, GlobalEventHandlers, NonElementParentNode, ParentNode, XPathEvaluatorBase {
	// Direct parent: Node

	/**
	 * @return DOMImplementation
	 */
	public function getImplementation();

	/**
	 * @return string
	 */
	public function getURL(): string;

	/**
	 * @return string
	 */
	public function getDocumentURI(): string;

	/**
	 * @return string
	 */
	public function getCompatMode(): string;

	/**
	 * @return string
	 */
	public function getCharacterSet(): string;

	/**
	 * @return string
	 */
	public function getCharset(): string;

	/**
	 * @return string
	 */
	public function getInputEncoding(): string;

	/**
	 * @return string
	 */
	public function getContentType(): string;

	/**
	 * @return DocumentType|null
	 */
	public function getDoctype();

	/**
	 * @return Element|null
	 */
	public function getDocumentElement();

	/**
	 * @param string $qualifiedName
	 * @return HTMLCollection
	 */
	public function getElementsByTagName( string $qualifiedName );

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return HTMLCollection
	 */
	public function getElementsByTagNameNS( ?string $namespace, string $localName );

	/**
	 * @param string $classNames
	 * @return HTMLCollection
	 */
	public function getElementsByClassName( string $classNames );

	/**
	 * @param string $localName
	 * @param string|ElementCreationOptions|associative-array|null $options
	 * @return Element
	 */
	public function createElement( string $localName, /* ?mixed */ $options = null );

	/**
	 * @param ?string $namespace
	 * @param string $qualifiedName
	 * @param string|ElementCreationOptions|associative-array|null $options
	 * @return Element
	 */
	public function createElementNS( ?string $namespace, string $qualifiedName, /* ?mixed */ $options = null );

	/**
	 * @return DocumentFragment
	 */
	public function createDocumentFragment();

	/**
	 * @param string $data
	 * @return Text
	 */
	public function createTextNode( string $data );

	/**
	 * @param string $data
	 * @return CDATASection
	 */
	public function createCDATASection( string $data );

	/**
	 * @param string $data
	 * @return Comment
	 */
	public function createComment( string $data );

	/**
	 * @param string $target
	 * @param string $data
	 * @return ProcessingInstruction
	 */
	public function createProcessingInstruction( string $target, string $data );

	/**
	 * @param Node $node
	 * @param bool $deep
	 * @return Node
	 */
	public function importNode( /* Node */ $node, bool $deep = false );

	/**
	 * @param Node $node
	 * @return Node
	 */
	public function adoptNode( /* Node */ $node );

	/**
	 * @param string $localName
	 * @return Attr
	 */
	public function createAttribute( string $localName );

	/**
	 * @param ?string $namespace
	 * @param string $qualifiedName
	 * @return Attr
	 */
	public function createAttributeNS( ?string $namespace, string $qualifiedName );

	/**
	 * @param string $interface
	 * @return Event
	 */
	public function createEvent( string $interface );

	/**
	 * @return Range
	 */
	public function createRange();

	/**
	 * @param Node $root
	 * @param int $whatToShow
	 * @param NodeFilter|callable|null $filter
	 * @return NodeIterator
	 */
	public function createNodeIterator( /* Node */ $root, int $whatToShow = -1, /* ?mixed */ $filter = null );

	/**
	 * @param Node $root
	 * @param int $whatToShow
	 * @param NodeFilter|callable|null $filter
	 * @return TreeWalker
	 */
	public function createTreeWalker( /* Node */ $root, int $whatToShow = -1, /* ?mixed */ $filter = null );

	/**
	 * @return Location|null
	 */
	public function getLocation();

	/**
	 * @param string $val
	 */
	public function setLocation( string $val ): void;

	/**
	 * @return string
	 */
	public function getReferrer(): string;

	/**
	 * @return string
	 */
	public function getCookie(): string;

	/**
	 * @param string $val
	 */
	public function setCookie( string $val ): void;

	/**
	 * @return string
	 */
	public function getLastModified(): string;

	/**
	 * @return string
	 */
	public function getTitle(): string;

	/**
	 * @param string $val
	 */
	public function setTitle( string $val ): void;

	/**
	 * @return string
	 */
	public function getDir(): string;

	/**
	 * @param string $val
	 */
	public function setDir( string $val ): void;

	/**
	 * @return HTMLElement|null
	 */
	public function getBody();

	/**
	 * @param HTMLElement|null $val
	 */
	public function setBody( /* ?HTMLElement */ $val ): void;

	/**
	 * @return HTMLHeadElement|null
	 */
	public function getHead();

	/**
	 * @return HTMLCollection
	 */
	public function getImages();

	/**
	 * @return HTMLCollection
	 */
	public function getEmbeds();

	/**
	 * @return HTMLCollection
	 */
	public function getPlugins();

	/**
	 * @return HTMLCollection
	 */
	public function getLinks();

	/**
	 * @return HTMLCollection
	 */
	public function getForms();

	/**
	 * @return HTMLCollection
	 */
	public function getScripts();

	/**
	 * @param string $elementName
	 * @return NodeList
	 */
	public function getElementsByName( string $elementName );

	/**
	 * @return HTMLScriptElement|null
	 */
	public function getCurrentScript();

	/**
	 * @param string $type
	 * @param string $replace
	 * @return \Wikimedia\IDLeDOM\Document
	 */
	public function open( string $type = 'text/html', string $replace = '' );

	/**
	 * @return void
	 */
	public function close(): void;

	/**
	 * @param string ...$text
	 * @return void
	 */
	public function write( string ...$text ): void;

	/**
	 * @param string ...$text
	 * @return void
	 */
	public function writeln( string ...$text ): void;

	/**
	 * @return bool
	 */
	public function hasFocus(): bool;

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnreadystatechange();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnreadystatechange( /* ?mixed */ $val ): void;

	/**
	 * @return HTMLCollection
	 */
	public function getAnchors();

	/**
	 * @return HTMLCollection
	 */
	public function getApplets();

	/**
	 * @return void
	 */
	public function clear(): void;

	/**
	 * @return void
	 */
	public function captureEvents(): void;

	/**
	 * @return void
	 */
	public function releaseEvents(): void;

	/**
	 * @return bool
	 */
	public function getHidden(): bool;

	/**
	 * @return string
	 */
	public function getVisibilityState(): /* VisibilityState */ string;

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnvisibilitychange();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnvisibilitychange( /* ?mixed */ $val ): void;

	/**
	 * @return string
	 */
	public function getEncoding(): string;

	/**
	 * @param string $val
	 */
	public function setEncoding( string $val ): void;

	/**
	 * @return bool
	 */
	public function getPreserveWhiteSpace(): bool;

	/**
	 * @param bool $val
	 */
	public function setPreserveWhiteSpace( bool $val ): void;

	/**
	 * @return bool
	 */
	public function getFormatOutput(): bool;

	/**
	 * @param bool $val
	 */
	public function setFormatOutput( bool $val ): void;

	/**
	 * @return bool
	 */
	public function getValidateOnParse(): bool;

	/**
	 * @param bool $val
	 */
	public function setValidateOnParse( bool $val ): void;

	/**
	 * @return bool
	 */
	public function getStrictErrorChecking(): bool;

	/**
	 * @param bool $val
	 */
	public function setStrictErrorChecking( bool $val ): void;

	/**
	 * @param string $source
	 * @param int $options
	 * @return bool
	 */
	public function loadHTML( string $source, int $options = 0 ): bool;

	/**
	 * @param string $source
	 * @param int $options
	 * @return bool
	 */
	public function loadXML( string $source, int $options = 0 ): bool;

	/**
	 * @param Node|null $node
	 * @return string|bool
	 */
	public function saveHTML( /* ?Node */ $node = null );

	/**
	 * @param Node|null $node
	 * @param int $options
	 * @return string|bool
	 */
	public function saveXML( /* ?Node */ $node = null, int $options = 0 );

}
