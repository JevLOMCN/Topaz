<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\PropGuard;
use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * Storage for all the state that TreeBuilder needs to associate with each
 * element. These objects should be freed once they fall out of TreeBuilder's
 * data structures (the stack etc.).
 *
 * These objects are also used to communicate information about elements with
 * downstream clients.
 */
class Element implements FormattingElement {
	use PropGuard;

	/**
	 * The namespace. This will be the HTML namespace for elements that are not
	 * in foreign content, even if there is a prefix.
	 * @var string
	 */
	public $namespace;

	/**
	 * The tag name, usually exactly as it appeared in the source document.
	 * This is not strictly a local name, since it still contains a colon for
	 * prefixed elements. In foreign content, it is effectively a local name.
	 * It is suitable for use as a serialized tag name.
	 * @var string
	 */
	public $name;

	/**
	 * This is an internal designation of the type of the element, which is
	 * equal to the tag name when the element is in the HTML namespace, and is
	 * some other string when the element is not in the HTML namespace.
	 * @var string
	 */
	public $htmlName;

	/**
	 * @var Attributes
	 */
	public $attrs;

	/**
	 * This is true if the element was created by the TreeBuilder either as a
	 * fragment context node, or as a synthetic <html> element to be used as
	 * the top-level element in fragment parsing.
	 * @var bool
	 */
	public $isVirtual;

	/**
	 * Internal to CachingStack. A link in the scope list.
	 */
	public $nextEltInScope;

	/**
	 * Internal to CachingStack and SimpleStack. The current stack index, or
	 * null if the element is not in the stack.
	 */
	public $stackIndex;

	/**
	 * Internal to ActiveFormattingElements.
	 */
	public $prevAFE;

	/**
	 * Internal to ActiveFormattingElements.
	 */
	public $nextAFE;

	/**
	 * Internal to ActiveFormattingElements.
	 */
	public $nextNoah;

	/**
	 * The cache for getNoahKey()
	 */
	private $noahKey;

	/**
	 * This member variable can be written to by the TreeHandler, to store any
	 * state associated with the element (such as a DOM node). It is not used
	 * by TreeBuilder.
	 */
	public $userData;

	/**
	 * A unique ID which identifies the element
	 * @var int
	 */
	public $uid;

	/**
	 * The next unique ID to be used
	 *
	 * @var int
	 */
	private static $nextUid = 1;

	/**
	 * The element types in the MathML namespace which are MathML text
	 * integration points.
	 * @var array<string,bool>
	 */
	private static $mathmlIntegration = [
		'mi' => true,
		'mo' => true,
		'mn' => true,
		'ms' => true,
		'mtext' => true
	];

	/**
	 * The element types in the SVG namespace which are SVG text integration
	 * points.
	 * @var array<string,bool>
	 */
	private static $svgHtmlIntegration = [
		'foreignObject' => true,
		'desc' => true,
		'title' => true
	];

	/**
	 * Constructor.
	 *
	 * @param string $namespace
	 * @param string $name
	 * @param Attributes $attrs
	 */
	public function __construct( $namespace, $name, Attributes $attrs ) {
		$this->namespace = $namespace;
		$this->name = $name;
		if ( $namespace === HTMLData::NS_HTML ) {
			$this->htmlName = $name;
		} elseif ( $namespace === HTMLData::NS_MATHML ) {
			$this->htmlName = "mathml $name";
		} elseif ( $namespace === HTMLData::NS_SVG ) {
			$this->htmlName = "svg $name";
		} else {
			$this->htmlName = "$namespace $name";
		}
		$this->attrs = $attrs;
		$this->uid = self::$nextUid++;
	}

	/**
	 * Is the element a MathML text integration point?
	 *
	 * @return bool
	 */
	public function isMathmlTextIntegration() {
		return $this->namespace === HTMLData::NS_MATHML
			&& isset( self::$mathmlIntegration[$this->name] );
	}

	/**
	 * Is the element an HTML integration point?
	 * @return bool
	 */
	public function isHtmlIntegration() {
		if ( $this->namespace === HTMLData::NS_MATHML ) {
			if ( isset( $this->attrs['encoding'] ) ) {
				$encoding = strtolower( $this->attrs['encoding'] );
				return $encoding === 'text/html' || $encoding === 'application/xhtml+xml';
			} else {
				return false;
			}
		} elseif ( $this->namespace === HTMLData::NS_SVG ) {
			return isset( self::$svgHtmlIntegration[$this->name] );
		} else {
			return false;
		}
	}

	/**
	 * Get a string key for the Noah's Ark algorithm
	 *
	 * @return string
	 */
	public function getNoahKey() {
		if ( $this->noahKey === null ) {
			$attrs = $this->attrs->getValues();
			ksort( $attrs );
			$this->noahKey = serialize( [ $this->htmlName, $attrs ] );
		}
		return $this->noahKey;
	}

	/**
	 * Get a string identifying the element, for use in debugging.
	 * @return string
	 */
	public function getDebugTag() {
		return $this->htmlName . '#' . $this->uid;
	}
}
