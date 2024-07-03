<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait HTMLLinkElement {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * Handle an attempt to get a non-existing property on this
	 * object.  The default implementation raises an exception
	 * but the implementor can choose a different behavior:
	 * return null (like JavaScript), dynamically create the
	 * property, etc.
	 * @param string $prop the name of the property requested
	 * @return mixed
	 */
	abstract protected function _getMissingProp( string $prop );

	/**
	 * Handle an attempt to set a non-existing property on this
	 * object.  The default implementation raises an exception
	 * but the implementor can choose a different behavior:
	 * ignore the operation (like JavaScript), dynamically create
	 * the property, etc.
	 * @param string $prop the name of the property requested
	 * @param mixed $value the value to set
	 */
	abstract protected function _setMissingProp( string $prop, $value ): void;

	// phpcs:enable

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get( string $name ) {
		'@phan-var \Wikimedia\IDLeDOM\HTMLLinkElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLLinkElement $this
		switch ( $name ) {
			case "nodeType":
				return $this->getNodeType();
			case "nodeName":
				return $this->getNodeName();
			case "baseURI":
				return $this->getBaseURI();
			case "isConnected":
				return $this->getIsConnected();
			case "ownerDocument":
				return $this->getOwnerDocument();
			case "parentNode":
				return $this->getParentNode();
			case "parentElement":
				return $this->getParentElement();
			case "childNodes":
				return $this->getChildNodes();
			case "firstChild":
				return $this->getFirstChild();
			case "lastChild":
				return $this->getLastChild();
			case "previousSibling":
				return $this->getPreviousSibling();
			case "nextSibling":
				return $this->getNextSibling();
			case "nodeValue":
				return $this->getNodeValue();
			case "textContent":
				return $this->getTextContent();
			case "innerHTML":
				return $this->getInnerHTML();
			case "previousElementSibling":
				return $this->getPreviousElementSibling();
			case "nextElementSibling":
				return $this->getNextElementSibling();
			case "children":
				return $this->getChildren();
			case "firstElementChild":
				return $this->getFirstElementChild();
			case "lastElementChild":
				return $this->getLastElementChild();
			case "childElementCount":
				return $this->getChildElementCount();
			case "assignedSlot":
				return $this->getAssignedSlot();
			case "namespaceURI":
				return $this->getNamespaceURI();
			case "prefix":
				return $this->getPrefix();
			case "localName":
				return $this->getLocalName();
			case "tagName":
				return $this->getTagName();
			case "id":
				return $this->getId();
			case "className":
				return $this->getClassName();
			case "classList":
				return $this->getClassList();
			case "slot":
				return $this->getSlot();
			case "attributes":
				return $this->getAttributes();
			case "shadowRoot":
				return $this->getShadowRoot();
			case "outerHTML":
				return $this->getOuterHTML();
			case "style":
				return $this->getStyle();
			case "contentEditable":
				return $this->getContentEditable();
			case "enterKeyHint":
				return $this->getEnterKeyHint();
			case "isContentEditable":
				return $this->getIsContentEditable();
			case "inputMode":
				return $this->getInputMode();
			case "onload":
				return $this->getOnload();
			case "dataset":
				return $this->getDataset();
			case "nonce":
				return $this->getNonce();
			case "tabIndex":
				return $this->getTabIndex();
			case "title":
				return $this->getTitle();
			case "lang":
				return $this->getLang();
			case "translate":
				return $this->getTranslate();
			case "dir":
				return $this->getDir();
			case "hidden":
				return $this->getHidden();
			case "accessKey":
				return $this->getAccessKey();
			case "accessKeyLabel":
				return $this->getAccessKeyLabel();
			case "draggable":
				return $this->getDraggable();
			case "spellcheck":
				return $this->getSpellcheck();
			case "autocapitalize":
				return $this->getAutocapitalize();
			case "innerText":
				return $this->getInnerText();
			case "offsetParent":
				return $this->getOffsetParent();
			case "offsetTop":
				return $this->getOffsetTop();
			case "offsetLeft":
				return $this->getOffsetLeft();
			case "offsetWidth":
				return $this->getOffsetWidth();
			case "offsetHeight":
				return $this->getOffsetHeight();
			case "crossOrigin":
				return $this->getCrossOrigin();
			case "sheet":
				return $this->getSheet();
			case "referrerPolicy":
				return $this->getReferrerPolicy();
			case "href":
				return $this->getHref();
			case "rel":
				return $this->getRel();
			case "as":
				return $this->getAs();
			case "relList":
				return $this->getRelList();
			case "media":
				return $this->getMedia();
			case "hreflang":
				return $this->getHreflang();
			case "type":
				return $this->getType();
			case "sizes":
				return $this->getSizes();
			case "charset":
				return $this->getCharset();
			case "rev":
				return $this->getRev();
			case "target":
				return $this->getTarget();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\HTMLLinkElement $this';
		// @var \Wikimedia\IDLeDOM\Helper\HTMLLinkElement $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\HTMLLinkElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLLinkElement $this
		switch ( $name ) {
			case "nodeType":
				return true;
			case "nodeName":
				return true;
			case "baseURI":
				return true;
			case "isConnected":
				return true;
			case "ownerDocument":
				return $this->getOwnerDocument() !== null;
			case "parentNode":
				return $this->getParentNode() !== null;
			case "parentElement":
				return $this->getParentElement() !== null;
			case "childNodes":
				return true;
			case "firstChild":
				return $this->getFirstChild() !== null;
			case "lastChild":
				return $this->getLastChild() !== null;
			case "previousSibling":
				return $this->getPreviousSibling() !== null;
			case "nextSibling":
				return $this->getNextSibling() !== null;
			case "nodeValue":
				return $this->getNodeValue() !== null;
			case "textContent":
				return $this->getTextContent() !== null;
			case "innerHTML":
				return true;
			case "previousElementSibling":
				return $this->getPreviousElementSibling() !== null;
			case "nextElementSibling":
				return $this->getNextElementSibling() !== null;
			case "children":
				return true;
			case "firstElementChild":
				return $this->getFirstElementChild() !== null;
			case "lastElementChild":
				return $this->getLastElementChild() !== null;
			case "childElementCount":
				return true;
			case "assignedSlot":
				return $this->getAssignedSlot() !== null;
			case "namespaceURI":
				return $this->getNamespaceURI() !== null;
			case "prefix":
				return $this->getPrefix() !== null;
			case "localName":
				return true;
			case "tagName":
				return true;
			case "id":
				return true;
			case "className":
				return true;
			case "classList":
				return true;
			case "slot":
				return true;
			case "attributes":
				return true;
			case "shadowRoot":
				return $this->getShadowRoot() !== null;
			case "outerHTML":
				return true;
			case "style":
				return true;
			case "contentEditable":
				return true;
			case "enterKeyHint":
				return true;
			case "isContentEditable":
				return true;
			case "inputMode":
				return true;
			case "onload":
				return true;
			case "dataset":
				return true;
			case "nonce":
				return true;
			case "tabIndex":
				return true;
			case "title":
				return true;
			case "lang":
				return true;
			case "translate":
				return true;
			case "dir":
				return true;
			case "hidden":
				return true;
			case "accessKey":
				return true;
			case "accessKeyLabel":
				return true;
			case "draggable":
				return true;
			case "spellcheck":
				return true;
			case "autocapitalize":
				return true;
			case "innerText":
				return true;
			case "offsetParent":
				return $this->getOffsetParent() !== null;
			case "offsetTop":
				return true;
			case "offsetLeft":
				return true;
			case "offsetWidth":
				return true;
			case "offsetHeight":
				return true;
			case "crossOrigin":
				return $this->getCrossOrigin() !== null;
			case "sheet":
				return $this->getSheet() !== null;
			case "referrerPolicy":
				return true;
			case "href":
				return true;
			case "rel":
				return true;
			case "as":
				return true;
			case "relList":
				return true;
			case "media":
				return true;
			case "hreflang":
				return true;
			case "type":
				return true;
			case "sizes":
				return true;
			case "charset":
				return true;
			case "rev":
				return true;
			case "target":
				return true;
			default:
				break;
		}
		return false;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set( string $name, $value ): void {
		'@phan-var \Wikimedia\IDLeDOM\HTMLLinkElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLLinkElement $this
		switch ( $name ) {
			case "nodeValue":
				$this->setNodeValue( $value );
				return;
			case "textContent":
				$this->setTextContent( $value );
				return;
			case "innerHTML":
				$this->setInnerHTML( $value );
				return;
			case "id":
				$this->setId( $value );
				return;
			case "className":
				$this->setClassName( $value );
				return;
			case "classList":
				$this->setClassList( $value );
				return;
			case "slot":
				$this->setSlot( $value );
				return;
			case "outerHTML":
				$this->setOuterHTML( $value );
				return;
			case "style":
				$this->setStyle( $value );
				return;
			case "contentEditable":
				$this->setContentEditable( $value );
				return;
			case "enterKeyHint":
				$this->setEnterKeyHint( $value );
				return;
			case "inputMode":
				$this->setInputMode( $value );
				return;
			case "onload":
				$this->setOnload( $value );
				return;
			case "nonce":
				$this->setNonce( $value );
				return;
			case "tabIndex":
				$this->setTabIndex( $value );
				return;
			case "title":
				$this->setTitle( $value );
				return;
			case "lang":
				$this->setLang( $value );
				return;
			case "translate":
				$this->setTranslate( $value );
				return;
			case "dir":
				$this->setDir( $value );
				return;
			case "hidden":
				$this->setHidden( $value );
				return;
			case "accessKey":
				$this->setAccessKey( $value );
				return;
			case "draggable":
				$this->setDraggable( $value );
				return;
			case "spellcheck":
				$this->setSpellcheck( $value );
				return;
			case "autocapitalize":
				$this->setAutocapitalize( $value );
				return;
			case "innerText":
				$this->setInnerText( $value );
				return;
			case "crossOrigin":
				$this->setCrossOrigin( $value );
				return;
			case "referrerPolicy":
				$this->setReferrerPolicy( $value );
				return;
			case "href":
				$this->setHref( $value );
				return;
			case "rel":
				$this->setRel( $value );
				return;
			case "as":
				$this->setAs( $value );
				return;
			case "relList":
				$this->setRelList( $value );
				return;
			case "media":
				$this->setMedia( $value );
				return;
			case "hreflang":
				$this->setHreflang( $value );
				return;
			case "type":
				$this->setType( $value );
				return;
			case "sizes":
				$this->setSizes( $value );
				return;
			case "charset":
				$this->setCharset( $value );
				return;
			case "rev":
				$this->setRev( $value );
				return;
			case "target":
				$this->setTarget( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\HTMLLinkElement $this';
		// @var \Wikimedia\IDLeDOM\Helper\HTMLLinkElement $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\HTMLLinkElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLLinkElement $this
		switch ( $name ) {
			case "nodeType":
				break;
			case "nodeName":
				break;
			case "baseURI":
				break;
			case "isConnected":
				break;
			case "ownerDocument":
				break;
			case "parentNode":
				break;
			case "parentElement":
				break;
			case "childNodes":
				break;
			case "firstChild":
				break;
			case "lastChild":
				break;
			case "previousSibling":
				break;
			case "nextSibling":
				break;
			case "nodeValue":
				$this->setNodeValue( null );
				return;
			case "textContent":
				$this->setTextContent( null );
				return;
			case "innerHTML":
				break;
			case "previousElementSibling":
				break;
			case "nextElementSibling":
				break;
			case "children":
				break;
			case "firstElementChild":
				break;
			case "lastElementChild":
				break;
			case "childElementCount":
				break;
			case "assignedSlot":
				break;
			case "namespaceURI":
				break;
			case "prefix":
				break;
			case "localName":
				break;
			case "tagName":
				break;
			case "id":
				break;
			case "className":
				break;
			case "classList":
				break;
			case "slot":
				break;
			case "attributes":
				break;
			case "shadowRoot":
				break;
			case "outerHTML":
				break;
			case "style":
				break;
			case "contentEditable":
				break;
			case "enterKeyHint":
				break;
			case "isContentEditable":
				break;
			case "inputMode":
				break;
			case "onload":
				break;
			case "dataset":
				break;
			case "nonce":
				break;
			case "tabIndex":
				break;
			case "title":
				break;
			case "lang":
				break;
			case "translate":
				break;
			case "dir":
				break;
			case "hidden":
				break;
			case "accessKey":
				break;
			case "accessKeyLabel":
				break;
			case "draggable":
				break;
			case "spellcheck":
				break;
			case "autocapitalize":
				break;
			case "innerText":
				break;
			case "offsetParent":
				break;
			case "offsetTop":
				break;
			case "offsetLeft":
				break;
			case "offsetWidth":
				break;
			case "offsetHeight":
				break;
			case "crossOrigin":
				$this->setCrossOrigin( null );
				return;
			case "sheet":
				break;
			case "referrerPolicy":
				break;
			case "href":
				break;
			case "rel":
				break;
			case "as":
				break;
			case "relList":
				break;
			case "media":
				break;
			case "hreflang":
				break;
			case "type":
				break;
			case "sizes":
				break;
			case "charset":
				break;
			case "rev":
				break;
			case "target":
				break;
			default:
				return;
		}
		$trace = debug_backtrace();
		while (
			count( $trace ) > 0 &&
			$trace[0]['function'] !== "__unset"
		) {
			array_shift( $trace );
		}
		trigger_error(
			'Undefined property' .
			' via ' . ( $trace[0]['function'] ?? '' ) . '(): ' . $name .
			' in ' . ( $trace[0]['file'] ?? '' ) .
			' on line ' . ( $trace[0]['line'] ?? '' ),
			E_USER_NOTICE
		);
	}

	/**
	 * @param string $val
	 */
	public function setRelList( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\HTMLLinkElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLLinkElement $this
		$this->getRelList()->setValue( $val );
	}

	/**
	 * @param string $val
	 */
	public function setSizes( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\HTMLLinkElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLLinkElement $this
		$this->getSizes()->setValue( $val );
	}

	/**
	 * @return ?string
	 */
	public function getCrossOrigin(): ?string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$val = $this->getAttribute( 'crossorigin' );
		if ( $val !== null ) {
			$val = strtr( $val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz' );
			switch ( $val ) {
				case 'anonymous':
				case 'use-credentials':
					return $val;
				default:
					return 'anonymous';
			}
		}
		return null;
	}

	/**
	 * @param ?string $val
	 */
	public function setCrossOrigin( ?string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		if ( $val !== null ) {
			$this->setAttribute( 'crossorigin', $val );
		} else {
			$this->removeAttribute( 'crossorigin' );
		}
	}

	/**
	 * @return string
	 */
	public function getReferrerPolicy(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$val = $this->getAttribute( 'referrerpolicy' );
		if ( $val !== null ) {
			$val = strtr( $val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz' );
			switch ( $val ) {
				case '':
				case 'no-referrer':
				case 'no-referrer-when-downgrade':
				case 'same-origin':
				case 'origin':
				case 'strict-origin':
				case 'origin-when-cross-origin':
				case 'strict-origin-when-cross-origin':
				case 'unsafe-url':
					return $val;
				default:
					return '';
			}
		}
		return '';
	}

	/**
	 * @param string $val
	 */
	public function setReferrerPolicy( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'referrerpolicy', $val );
	}

	/**
	 * @return string
	 */
	public function getRel(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'rel' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setRel( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'rel', $val );
	}

	/**
	 * @return string
	 */
	public function getAs(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'as' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setAs( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'as', $val );
	}

	/**
	 * @return string
	 */
	public function getMedia(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'media' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setMedia( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'media', $val );
	}

	/**
	 * @return string
	 */
	public function getHreflang(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'hreflang' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setHreflang( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'hreflang', $val );
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'type' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setType( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'type', $val );
	}

	/**
	 * @return string
	 */
	public function getCharset(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'charset' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setCharset( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'charset', $val );
	}

	/**
	 * @return string
	 */
	public function getRev(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'rev' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setRev( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'rev', $val );
	}

	/**
	 * @return string
	 */
	public function getTarget(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'target' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setTarget( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'target', $val );
	}

}
