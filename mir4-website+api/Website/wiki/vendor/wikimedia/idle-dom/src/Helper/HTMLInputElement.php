<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait HTMLInputElement {

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
		'@phan-var \Wikimedia\IDLeDOM\HTMLInputElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLInputElement $this
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
			case "accept":
				return $this->getAccept();
			case "alt":
				return $this->getAlt();
			case "autocomplete":
				return $this->getAutocomplete();
			case "autofocus":
				return $this->getAutofocus();
			case "defaultChecked":
				return $this->getDefaultChecked();
			case "checked":
				return $this->getChecked();
			case "dirName":
				return $this->getDirName();
			case "disabled":
				return $this->getDisabled();
			case "form":
				return $this->getForm();
			case "formEnctype":
				return $this->getFormEnctype();
			case "formMethod":
				return $this->getFormMethod();
			case "formNoValidate":
				return $this->getFormNoValidate();
			case "formTarget":
				return $this->getFormTarget();
			case "indeterminate":
				return $this->getIndeterminate();
			case "list":
				return $this->getList();
			case "max":
				return $this->getMax();
			case "maxLength":
				return $this->getMaxLength();
			case "min":
				return $this->getMin();
			case "minLength":
				return $this->getMinLength();
			case "multiple":
				return $this->getMultiple();
			case "name":
				return $this->getName();
			case "pattern":
				return $this->getPattern();
			case "placeholder":
				return $this->getPlaceholder();
			case "readOnly":
				return $this->getReadOnly();
			case "required":
				return $this->getRequired();
			case "size":
				return $this->getSize();
			case "src":
				return $this->getSrc();
			case "step":
				return $this->getStep();
			case "type":
				return $this->getType();
			case "defaultValue":
				return $this->getDefaultValue();
			case "value":
				return $this->getValue();
			case "valueAsNumber":
				return $this->getValueAsNumber();
			case "willValidate":
				return $this->getWillValidate();
			case "validity":
				return $this->getValidity();
			case "validationMessage":
				return $this->getValidationMessage();
			case "labels":
				return $this->getLabels();
			case "selectionStart":
				return $this->getSelectionStart();
			case "selectionEnd":
				return $this->getSelectionEnd();
			case "selectionDirection":
				return $this->getSelectionDirection();
			case "align":
				return $this->getAlign();
			case "useMap":
				return $this->getUseMap();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\HTMLInputElement $this';
		// @var \Wikimedia\IDLeDOM\Helper\HTMLInputElement $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\HTMLInputElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLInputElement $this
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
			case "accept":
				return true;
			case "alt":
				return true;
			case "autocomplete":
				return true;
			case "autofocus":
				return true;
			case "defaultChecked":
				return true;
			case "checked":
				return true;
			case "dirName":
				return true;
			case "disabled":
				return true;
			case "form":
				return $this->getForm() !== null;
			case "formEnctype":
				return true;
			case "formMethod":
				return true;
			case "formNoValidate":
				return true;
			case "formTarget":
				return true;
			case "indeterminate":
				return true;
			case "list":
				return $this->getList() !== null;
			case "max":
				return true;
			case "maxLength":
				return true;
			case "min":
				return true;
			case "minLength":
				return true;
			case "multiple":
				return true;
			case "name":
				return true;
			case "pattern":
				return true;
			case "placeholder":
				return true;
			case "readOnly":
				return true;
			case "required":
				return true;
			case "size":
				return true;
			case "src":
				return true;
			case "step":
				return true;
			case "type":
				return true;
			case "defaultValue":
				return true;
			case "value":
				return true;
			case "valueAsNumber":
				return true;
			case "willValidate":
				return true;
			case "validity":
				return true;
			case "validationMessage":
				return true;
			case "labels":
				return $this->getLabels() !== null;
			case "selectionStart":
				return $this->getSelectionStart() !== null;
			case "selectionEnd":
				return $this->getSelectionEnd() !== null;
			case "selectionDirection":
				return $this->getSelectionDirection() !== null;
			case "align":
				return true;
			case "useMap":
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
		'@phan-var \Wikimedia\IDLeDOM\HTMLInputElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLInputElement $this
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
			case "accept":
				$this->setAccept( $value );
				return;
			case "alt":
				$this->setAlt( $value );
				return;
			case "autocomplete":
				$this->setAutocomplete( $value );
				return;
			case "autofocus":
				$this->setAutofocus( $value );
				return;
			case "defaultChecked":
				$this->setDefaultChecked( $value );
				return;
			case "checked":
				$this->setChecked( $value );
				return;
			case "dirName":
				$this->setDirName( $value );
				return;
			case "disabled":
				$this->setDisabled( $value );
				return;
			case "formEnctype":
				$this->setFormEnctype( $value );
				return;
			case "formMethod":
				$this->setFormMethod( $value );
				return;
			case "formNoValidate":
				$this->setFormNoValidate( $value );
				return;
			case "formTarget":
				$this->setFormTarget( $value );
				return;
			case "indeterminate":
				$this->setIndeterminate( $value );
				return;
			case "max":
				$this->setMax( $value );
				return;
			case "maxLength":
				$this->setMaxLength( $value );
				return;
			case "min":
				$this->setMin( $value );
				return;
			case "minLength":
				$this->setMinLength( $value );
				return;
			case "multiple":
				$this->setMultiple( $value );
				return;
			case "name":
				$this->setName( $value );
				return;
			case "pattern":
				$this->setPattern( $value );
				return;
			case "placeholder":
				$this->setPlaceholder( $value );
				return;
			case "readOnly":
				$this->setReadOnly( $value );
				return;
			case "required":
				$this->setRequired( $value );
				return;
			case "size":
				$this->setSize( $value );
				return;
			case "src":
				$this->setSrc( $value );
				return;
			case "step":
				$this->setStep( $value );
				return;
			case "type":
				$this->setType( $value );
				return;
			case "defaultValue":
				$this->setDefaultValue( $value );
				return;
			case "value":
				$this->setValue( $value );
				return;
			case "valueAsNumber":
				$this->setValueAsNumber( $value );
				return;
			case "selectionStart":
				$this->setSelectionStart( $value );
				return;
			case "selectionEnd":
				$this->setSelectionEnd( $value );
				return;
			case "selectionDirection":
				$this->setSelectionDirection( $value );
				return;
			case "align":
				$this->setAlign( $value );
				return;
			case "useMap":
				$this->setUseMap( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\HTMLInputElement $this';
		// @var \Wikimedia\IDLeDOM\Helper\HTMLInputElement $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\HTMLInputElement $this';
		// @var \Wikimedia\IDLeDOM\HTMLInputElement $this
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
			case "accept":
				break;
			case "alt":
				break;
			case "autocomplete":
				break;
			case "autofocus":
				break;
			case "defaultChecked":
				break;
			case "checked":
				break;
			case "dirName":
				break;
			case "disabled":
				break;
			case "form":
				break;
			case "formEnctype":
				break;
			case "formMethod":
				break;
			case "formNoValidate":
				break;
			case "formTarget":
				break;
			case "indeterminate":
				break;
			case "list":
				break;
			case "max":
				break;
			case "maxLength":
				break;
			case "min":
				break;
			case "minLength":
				break;
			case "multiple":
				break;
			case "name":
				break;
			case "pattern":
				break;
			case "placeholder":
				break;
			case "readOnly":
				break;
			case "required":
				break;
			case "size":
				break;
			case "src":
				break;
			case "step":
				break;
			case "type":
				break;
			case "defaultValue":
				break;
			case "value":
				break;
			case "valueAsNumber":
				break;
			case "willValidate":
				break;
			case "validity":
				break;
			case "validationMessage":
				break;
			case "labels":
				break;
			case "selectionStart":
				$this->setSelectionStart( null );
				return;
			case "selectionEnd":
				$this->setSelectionEnd( null );
				return;
			case "selectionDirection":
				$this->setSelectionDirection( null );
				return;
			case "align":
				break;
			case "useMap":
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
	 * @return string
	 */
	public function getAccept(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'accept' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setAccept( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'accept', $val );
	}

	/**
	 * @return string
	 */
	public function getAlt(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'alt' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setAlt( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'alt', $val );
	}

	/**
	 * @return string
	 */
	public function getAutocomplete(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$val = $this->getAttribute( 'autocomplete' );
		if ( $val !== null ) {
			$val = strtr( $val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz' );
			switch ( $val ) {
				case 'on':
				case 'off':
					return $val;
				default:
					return 'on';
			}
		}
		return 'on';
	}

	/**
	 * @param string $val
	 */
	public function setAutocomplete( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'autocomplete', $val );
	}

	/**
	 * @return bool
	 */
	public function getAutofocus(): bool {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->hasAttribute( 'autofocus' );
	}

	/**
	 * @param bool $val
	 */
	public function setAutofocus( bool $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		if ( $val ) {
			$this->setAttribute( 'autofocus', '' );
		} else {
			$this->removeAttribute( 'autofocus' );
		}
	}

	/**
	 * @return bool
	 */
	public function getDefaultChecked(): bool {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->hasAttribute( 'checked' );
	}

	/**
	 * @param bool $val
	 */
	public function setDefaultChecked( bool $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		if ( $val ) {
			$this->setAttribute( 'checked', '' );
		} else {
			$this->removeAttribute( 'checked' );
		}
	}

	/**
	 * @return string
	 */
	public function getDirName(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'dirname' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setDirName( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'dirname', $val );
	}

	/**
	 * @return bool
	 */
	public function getDisabled(): bool {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->hasAttribute( 'disabled' );
	}

	/**
	 * @param bool $val
	 */
	public function setDisabled( bool $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		if ( $val ) {
			$this->setAttribute( 'disabled', '' );
		} else {
			$this->removeAttribute( 'disabled' );
		}
	}

	/**
	 * @return string
	 */
	public function getFormEnctype(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$val = $this->getAttribute( 'formenctype' );
		if ( $val !== null ) {
			$val = strtr( $val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz' );
			switch ( $val ) {
				case 'application/x-www-form-urlencoded':
				case 'multipart/form-data':
				case 'text/plain':
					return $val;
				default:
					return 'application/x-www-form-urlencoded';
			}
		}
		return '';
	}

	/**
	 * @param string $val
	 */
	public function setFormEnctype( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'formenctype', $val );
	}

	/**
	 * @return string
	 */
	public function getFormMethod(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$val = $this->getAttribute( 'formmethod' );
		if ( $val !== null ) {
			$val = strtr( $val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz' );
			switch ( $val ) {
				case 'get':
				case 'post':
				case 'dialog':
					return $val;
				default:
					return 'get';
			}
		}
		return '';
	}

	/**
	 * @param string $val
	 */
	public function setFormMethod( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'formmethod', $val );
	}

	/**
	 * @return bool
	 */
	public function getFormNoValidate(): bool {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->hasAttribute( 'formnovalidate' );
	}

	/**
	 * @param bool $val
	 */
	public function setFormNoValidate( bool $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		if ( $val ) {
			$this->setAttribute( 'formnovalidate', '' );
		} else {
			$this->removeAttribute( 'formnovalidate' );
		}
	}

	/**
	 * @return string
	 */
	public function getFormTarget(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'formtarget' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setFormTarget( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'formtarget', $val );
	}

	/**
	 * @return string
	 */
	public function getMax(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'max' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setMax( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'max', $val );
	}

	/**
	 * @return string
	 */
	public function getMin(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'min' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setMin( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'min', $val );
	}

	/**
	 * @return bool
	 */
	public function getMultiple(): bool {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->hasAttribute( 'multiple' );
	}

	/**
	 * @param bool $val
	 */
	public function setMultiple( bool $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		if ( $val ) {
			$this->setAttribute( 'multiple', '' );
		} else {
			$this->removeAttribute( 'multiple' );
		}
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'name' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setName( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'name', $val );
	}

	/**
	 * @return string
	 */
	public function getPattern(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'pattern' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setPattern( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'pattern', $val );
	}

	/**
	 * @return string
	 */
	public function getPlaceholder(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'placeholder' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setPlaceholder( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'placeholder', $val );
	}

	/**
	 * @return bool
	 */
	public function getReadOnly(): bool {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->hasAttribute( 'readonly' );
	}

	/**
	 * @param bool $val
	 */
	public function setReadOnly( bool $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		if ( $val ) {
			$this->setAttribute( 'readonly', '' );
		} else {
			$this->removeAttribute( 'readonly' );
		}
	}

	/**
	 * @return bool
	 */
	public function getRequired(): bool {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->hasAttribute( 'required' );
	}

	/**
	 * @param bool $val
	 */
	public function setRequired( bool $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		if ( $val ) {
			$this->setAttribute( 'required', '' );
		} else {
			$this->removeAttribute( 'required' );
		}
	}

	/**
	 * @return string
	 */
	public function getStep(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'step' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setStep( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'step', $val );
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$val = $this->getAttribute( 'type' );
		if ( $val !== null ) {
			$val = strtr( $val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz' );
			switch ( $val ) {
				case 'hidden':
				case 'text':
				case 'search':
				case 'tel':
				case 'url':
				case 'email':
				case 'password':
				case 'date':
				case 'month':
				case 'week':
				case 'time':
				case 'datetime-local':
				case 'number':
				case 'range':
				case 'color':
				case 'checkbox':
				case 'radio':
				case 'file':
				case 'submit':
				case 'image':
				case 'reset':
				case 'button':
					return $val;
				default:
					return 'text';
			}
		}
		return 'text';
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
	public function getDefaultValue(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'value' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setDefaultValue( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'value', $val );
	}

	/**
	 * @return string
	 */
	public function getAlign(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'align' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setAlign( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'align', $val );
	}

	/**
	 * @return string
	 */
	public function getUseMap(): string {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		return $this->getAttribute( 'usemap' ) ?? '';
	}

	/**
	 * @param string $val
	 */
	public function setUseMap( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Element $this';
		// @var \Wikimedia\IDLeDOM\Element $this
		$this->setAttribute( 'usemap', $val );
	}

}
