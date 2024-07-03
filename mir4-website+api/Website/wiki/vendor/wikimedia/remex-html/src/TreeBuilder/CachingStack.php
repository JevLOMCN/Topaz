<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\HTMLData;

/**
 * An implementation of the "stack of open elements" which includes a cache of
 * elements currently in the various kinds of scope. It presumably has good
 * worst-case performance at the expense of somewhat slower updates.
 */
class CachingStack extends Stack {
	private const SCOPE_DEFAULT = 0;
	private const SCOPE_LIST = 1;
	private const SCOPE_BUTTON = 2;
	private const SCOPE_TABLE = 3;
	private const SCOPE_SELECT = 4;

	private static $allScopes = [ self::SCOPE_DEFAULT, self::SCOPE_LIST, self::SCOPE_BUTTON,
		self::SCOPE_TABLE, self::SCOPE_SELECT ];
	private static $nonTableScopes = [ self::SCOPE_DEFAULT, self::SCOPE_LIST, self::SCOPE_BUTTON,
		self::SCOPE_SELECT ];
	private static $listScopes = [ self::SCOPE_LIST, self::SCOPE_SELECT ];
	private static $buttonScopes = [ self::SCOPE_BUTTON, self::SCOPE_SELECT ];
	private static $selectOnly = [ self::SCOPE_SELECT ];

	private static $mathBreakers = [
		'mi' => true,
		'mo' => true,
		'mn' => true,
		'ms' => true,
		'mtext' => true,
		'annotation-xml' => true
	];

	private static $svgBreakers = [
		'foreignObject' => true,
		'desc' => true,
		'title' => true
	];

	/**
	 * If you compile every predicate of the form "an X element in Y scope" in
	 * the HTML 5 spec, you discover that every element name X corresponds to
	 * at most one such scope Y. This is useful because it means when we see
	 * a new element, we need to add it to at most one scope cache.
	 *
	 * This is a list of such statements in the spec, and the scope they relate
	 * to. All formatting elements are included as SCOPE_DEFAULT since the AAA
	 * involves pulling an item out of the AFE list and checking if it is in
	 * scope.
	 *
	 * @var array
	 */
	private static $predicateMap = [
		'a' => self::SCOPE_DEFAULT,
		'address' => self::SCOPE_DEFAULT,
		'applet' => self::SCOPE_DEFAULT,
		'article' => self::SCOPE_DEFAULT,
		'aside' => self::SCOPE_DEFAULT,
		'b' => self::SCOPE_DEFAULT,
		'big' => self::SCOPE_DEFAULT,
		'blockquote' => self::SCOPE_DEFAULT,
		'body' => self::SCOPE_DEFAULT,
		'button' => self::SCOPE_DEFAULT,
		'caption' => self::SCOPE_TABLE,
		'center' => self::SCOPE_DEFAULT,
		'code' => self::SCOPE_DEFAULT,
		'dd' => self::SCOPE_DEFAULT,
		'details' => self::SCOPE_DEFAULT,
		'dialog' => self::SCOPE_DEFAULT,
		'dir' => self::SCOPE_DEFAULT,
		'div' => self::SCOPE_DEFAULT,
		'dl' => self::SCOPE_DEFAULT,
		'dt' => self::SCOPE_DEFAULT,
		'em' => self::SCOPE_DEFAULT,
		'fieldset' => self::SCOPE_DEFAULT,
		'figcaption' => self::SCOPE_DEFAULT,
		'figure' => self::SCOPE_DEFAULT,
		'font' => self::SCOPE_DEFAULT,
		'footer' => self::SCOPE_DEFAULT,
		'form' => self::SCOPE_DEFAULT,
		'h1' => self::SCOPE_DEFAULT,
		'h2' => self::SCOPE_DEFAULT,
		'h3' => self::SCOPE_DEFAULT,
		'h4' => self::SCOPE_DEFAULT,
		'h5' => self::SCOPE_DEFAULT,
		'h6' => self::SCOPE_DEFAULT,
		'header' => self::SCOPE_DEFAULT,
		'hgroup' => self::SCOPE_DEFAULT,
		'i' => self::SCOPE_DEFAULT,
		'li' => self::SCOPE_LIST,
		'listing' => self::SCOPE_DEFAULT,
		'main' => self::SCOPE_DEFAULT,
		'marquee' => self::SCOPE_DEFAULT,
		'menu' => self::SCOPE_DEFAULT,
		'nav' => self::SCOPE_DEFAULT,
		'nobr' => self::SCOPE_DEFAULT,
		'object' => self::SCOPE_DEFAULT,
		'ol' => self::SCOPE_DEFAULT,
		'p' => self::SCOPE_BUTTON,
		'pre' => self::SCOPE_DEFAULT,
		'ruby' => self::SCOPE_DEFAULT,
		's' => self::SCOPE_DEFAULT,
		'section' => self::SCOPE_DEFAULT,
		'select' => self::SCOPE_SELECT,
		'small' => self::SCOPE_DEFAULT,
		'strike' => self::SCOPE_DEFAULT,
		'strong' => self::SCOPE_DEFAULT,
		'summary' => self::SCOPE_DEFAULT,
		'table' => self::SCOPE_TABLE,
		'tbody' => self::SCOPE_TABLE,
		'td' => self::SCOPE_TABLE,
		'tfoot' => self::SCOPE_TABLE,
		'th' => self::SCOPE_TABLE,
		'thead' => self::SCOPE_TABLE,
		'tr' => self::SCOPE_TABLE,
		'tt' => self::SCOPE_DEFAULT,
		'u' => self::SCOPE_DEFAULT,
		'ul' => self::SCOPE_DEFAULT,
	];

	/**
	 * The stack of open elements
	 *
	 * @var array
	 */
	private $elements = [];

	/**
	 * A cache of the elements which are currently in a given scope.
	 * The first key is the scope ID, the second key is the element name, and the
	 * value is the first Element in a singly-linked list of Element objects,
	 * linked by $element->nextEltInScope.
	 *
	 * @todo Benchmark time and memory compared to an array stack instead of an
	 * SLL. The SLL here is maybe not quite so well justified as some other
	 * SLLs in RemexHtml.
	 *
	 * @var array<int,array<string,Element>>
	 */
	private $scopes = [
		self::SCOPE_DEFAULT => [],
		self::SCOPE_LIST => [],
		self::SCOPE_BUTTON => [],
		self::SCOPE_TABLE => [],
		self::SCOPE_SELECT => []
	];

	/**
	 * This is the part of the scope cache which stores scope lists for objects
	 * which are not currently in scope. The first key is the scope ID, the
	 * second key is the stack index, the third key is the element name,
	 * and the value is the Element object.
	 *
	 * @var array<int,array<int,array<string,Element>>>
	 */
	private $scopeStacks = [
		self::SCOPE_DEFAULT => [],
		self::SCOPE_LIST => [],
		self::SCOPE_BUTTON => [],
		self::SCOPE_TABLE => [],
		self::SCOPE_SELECT => []
	];

	/**
	 * The number of <template> elements in the stack of open elements. This
	 * speeds up the hot function hasTemplate().
	 *
	 * @var int
	 */
	private $templateCount;

	/**
	 * For a given namespace and element name, get the list of scopes
	 * for which a new scope should be created and the old one needs to
	 * be pushed onto the scope stack.
	 *
	 * @param string $ns
	 * @param string $name
	 * @return int[]
	 */
	private function getScopeTypesToStack( $ns, $name ) {
		if ( $ns === HTMLData::NS_HTML ) {
			switch ( $name ) {
				case 'html':
				case 'table':
				case 'template':
					return self::$allScopes;

				case 'applet':
				case 'caption':
				case 'td':
				case 'th':
				case 'marquee':
				case 'object':
					return self::$nonTableScopes;

				case 'ol':
				case 'ul':
					return self::$listScopes;

				case 'button':
					return self::$buttonScopes;

				case 'option':
				case 'optgroup':
					return [];

				default:
					return self::$selectOnly;
			}
		} elseif ( $ns === HTMLData::NS_MATHML ) {
			if ( isset( self::$mathBreakers[$name] ) ) {
				return self::$nonTableScopes;
			} else {
				return self::$selectOnly;
			}
		} elseif ( $ns === HTMLData::NS_SVG ) {
			if ( isset( self::$svgBreakers[$name] ) ) {
				return self::$nonTableScopes;
			} else {
				return self::$selectOnly;
			}
		} else {
			return self::$selectOnly;
		}
	}

	public function push( Element $elt ) {
		// Update the stack store
		$n = count( $this->elements );
		$this->elements[$n] = $elt;
		// Update the current node and index cache
		$this->current = $elt;
		$elt->stackIndex = $n;
		// Update the scope cache
		$ns = $elt->namespace;
		$name = $elt->name;
		foreach ( $this->getScopeTypesToStack( $ns, $name ) as $type ) {
			$this->scopeStacks[$type][] = $this->scopes[$type];
			$this->scopes[$type] = [];
		}
		if ( $ns === HTMLData::NS_HTML && isset( self::$predicateMap[$name] ) ) {
			$type = self::$predicateMap[$name];
			$scope =& $this->scopes[$type];
			$elt->nextEltInScope = $scope[$name] ?? null;
			$scope[$name] = $elt;
			unset( $scope );
		}
		// Update the template count
		if ( $ns === HTMLData::NS_HTML && $name === 'template' ) {
			$this->templateCount++;
		}
	}

	public function pop() {
		$n = count( $this->elements );
		if ( !$n ) {
			throw new TreeBuilderError( __METHOD__ . ': stack empty' );
		}
		// Update the stack store, index cache and current node
		$elt = array_pop( $this->elements );
		$n--;
		$elt->stackIndex = null;
		$ns = $elt->namespace;
		$name = $elt->name;
		$this->current = $n ? $this->elements[$n - 1] : null;
		// Update the scope cache
		if ( $ns === HTMLData::NS_HTML && isset( self::$predicateMap[$name] ) ) {
			$scope = self::$predicateMap[$name];
			$this->scopes[$scope][$name] = $elt->nextEltInScope;
			$elt->nextEltInScope = null;
		}
		foreach ( $this->getScopeTypesToStack( $ns, $name ) as $scope ) {
			$this->scopes[$scope] = array_pop( $this->scopeStacks[$scope] );
		}
		// Update the template count
		if ( $ns === HTMLData::NS_HTML && $name === 'template' ) {
			$this->templateCount--;
		}
		return $elt;
	}

	public function replace( Element $oldElt, Element $elt ) {
		$idx = $oldElt->stackIndex;
		// AAA calls this function only for elements with the same name, which
		// simplifies the scope cache update, and eliminates the template count
		// update
		if ( $oldElt->name !== $elt->name || $oldElt->namespace !== $elt->namespace ) {
			throw new TreeBuilderError( __METHOD__ . ' can only be called for elements of the same name' );
		}
		$ns = $elt->namespace;
		$name = $elt->name;
		// Find the old element in its scope list and replace it
		if ( $ns === HTMLData::NS_HTML && isset( self::$predicateMap[$name] ) ) {
			$type = self::$predicateMap[$name];
			$scopeElt = $this->scopes[$type][$name];
			if ( $scopeElt === $oldElt ) {
				$this->scopes[$type][$name] = $elt;
				$elt->nextEltInScope = $scopeElt->nextEltInScope;
				$scopeElt->nextEltInScope = null;
			} else {
				$nextElt = $scopeElt->nextEltInScope;
				while ( $nextElt ) {
					if ( $nextElt === $oldElt ) {
						$scopeElt->nextEltInScope = $elt;
						$elt->nextEltInScope = $nextElt->nextEltInScope;
						$nextElt->nextEltInScope = null;
						break;
					}
					$scopeElt = $scopeElt->nextEltInScope;
					$nextElt = $scopeElt->nextEltInScope;
				}
				if ( !$nextElt ) {
					throw new TreeBuilderError( __METHOD__ . ': cannot find old element in scope cache' );
				}
			}
		}
		// Replace the stack element
		$this->elements[$idx] = $elt;
		if ( $idx === count( $this->elements ) - 1 ) {
			$this->current = $elt;
		}
		$oldElt->stackIndex = null;
		$elt->stackIndex = $idx;
	}

	public function remove( Element $elt ) {
		$tempStack = [];
		$eltIndex = $elt->stackIndex;
		if ( $eltIndex === null ) {
			throw new TreeBuilderError( __METHOD__ . ': element not in stack' );
		}
		$n = count( $this->elements );
		for ( $i = $n - 1; $i > $eltIndex; $i-- ) {
			$tempStack[] = $this->pop();
		}
		$this->pop();
		foreach ( array_reverse( $tempStack ) as $temp ) {
			$this->push( $temp );
		}
	}

	public function isInScope( $name ) {
		if ( self::$predicateMap[$name] !== self::SCOPE_DEFAULT ) {
			throw new TreeBuilderError( "Unexpected predicate: \"$name is in scope\"" );
		}
		return !empty( $this->scopes[self::SCOPE_DEFAULT][$name] );
	}

	public function isElementInScope( Element $elt ) {
		$name = $elt->name;
		if ( self::$predicateMap[$name] !== self::SCOPE_DEFAULT ) {
			throw new TreeBuilderError( "Unexpected predicate: \"$name is in scope\"" );
		}
		if ( !empty( $this->scopes[self::SCOPE_DEFAULT][$name] ) ) {
			$scopeMember = $this->scopes[self::SCOPE_DEFAULT][$name];
			while ( $scopeMember ) {
				if ( $scopeMember === $elt ) {
					return true;
				}
				$scopeMember = $scopeMember->nextEltInScope;
			}
		}
		return false;
	}

	public function isOneOfSetInScope( $names ) {
		foreach ( $names as $name => $unused ) {
			if ( $this->isInScope( $name ) ) {
				return true;
			}
		}
		return false;
	}

	public function isInListScope( $name ) {
		if ( self::$predicateMap[$name] !== self::SCOPE_LIST ) {
			throw new TreeBuilderError( "Unexpected predicate: \"$name is in list scope\"" );
		}
		return !empty( $this->scopes[self::SCOPE_LIST][$name] );
	}

	public function isInButtonScope( $name ) {
		if ( self::$predicateMap[$name] !== self::SCOPE_BUTTON ) {
			throw new TreeBuilderError( "Unexpected predicate: \"$name is in button scope\"" );
		}
		return !empty( $this->scopes[self::SCOPE_BUTTON][$name] );
	}

	public function isInTableScope( $name ) {
		if ( self::$predicateMap[$name] !== self::SCOPE_TABLE ) {
			throw new TreeBuilderError( "Unexpected predicate: \"$name is in table scope\"" );
		}
		return !empty( $this->scopes[self::SCOPE_TABLE][$name] );
	}

	public function isInSelectScope( $name ) {
		if ( self::$predicateMap[$name] !== self::SCOPE_SELECT ) {
			throw new TreeBuilderError( "Unexpected predicate: \"$name is in select scope\"" );
		}
		return !empty( $this->scopes[self::SCOPE_SELECT][$name] );
	}

	public function item( $idx ) {
		return $this->elements[$idx];
	}

	public function length() {
		return count( $this->elements );
	}

	public function hasTemplate() {
		return (bool)$this->templateCount;
	}

	public function dump() {
		return parent::dump() .
			$this->scopeDump( self::SCOPE_DEFAULT, 'In scope' ) .
			$this->scopeDump( self::SCOPE_LIST, 'In list scope' ) .
			$this->scopeDump( self::SCOPE_BUTTON, 'In button scope' ) .
			$this->scopeDump( self::SCOPE_TABLE, 'In table scope' ) .
			$this->scopeDump( self::SCOPE_SELECT, 'In select scope' ) . "\n";
	}

	private function scopeDump( $type, $scopeName ) {
		if ( count( $this->scopes[$type] ) ) {
			return "$scopeName: " . implode( ', ', array_keys( $this->scopes[$type] ) ) . "\n";
		} else {
			return '';
		}
	}
}
