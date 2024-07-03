<?php

namespace Wikimedia\Zest;

use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMNode;
use InvalidArgumentException;
use Throwable;

/**
 * Zest.php (https://github.com/wikimedia/zest.php)
 * Copyright (c) 2019, C. Scott Ananian. (MIT licensed)
 * PHP port based on:
 *
 * Zest (https://github.com/chjj/zest)
 * A css selector engine.
 * Copyright (c) 2011-2012, Christopher Jeffrey. (MIT Licensed)
 * Domino version based on Zest v0.1.3 with bugfixes applied.
 */

class ZestInst {

	/** @var ZestFunc[] */
	private $compileCache = [];

	/**
	 * Helpers
	 */

	/**
	 * Sort query results in document order.
	 * @param array &$results
	 * @param bool $isStandardsMode
	 */
	private static function sort( &$results, bool $isStandardsMode ): void {
		if ( count( $results ) < 2 ) {
			return;
		}
		if ( $isStandardsMode ) {
			// DOM spec-compliant version:
			usort( $results, static function ( $a, $b ) {
				return ( $a->compareDocumentPosition( $b ) & 2 ) ? 1 : -1;
			} );
		}
		// PHP's dom extension returns true for method_exists on
		// compareDocumentPosition, but when called it throws a
		// "Not yet implemented" exception

		// If compareDocumentPosition isn't implemented, skip the sort.
		// Our results are generally added as a result of an in-order
		// traversal of the tree, so absent any funny business with complex
		// selectors, our natural order should be more-or-less sorted.
	}

	/**
	 * @param DOMNode $el
	 * @return ?DOMNode
	 */
	private static function next( $el ) {
		while ( ( $el = $el->nextSibling ) && $el->nodeType !== 1 ) {
			// no op
		}
		return $el;
	}

	/**
	 * @param DOMNode $el
	 * @return ?DOMNode
	 */
	private static function prev( $el ) {
		while ( ( $el = $el->previousSibling ) && $el->nodeType !== 1 ) {
			// no op
		}
		return $el;
	}

	/**
	 * @param DOMNode $el
	 * @return ?DOMNode
	 */
	private static function child( $el ) {
		if ( $el = $el->firstChild ) {
			while ( $el->nodeType !== 1 && ( $el = $el->nextSibling ) ) {
				// no op
			}
		}
		return $el;
	}

	/**
	 * @param DOMNode $el
	 * @return ?DOMNode
	 */
	private static function lastChild( $el ) {
		if ( $el = $el->lastChild ) {
			while ( $el->nodeType !== 1 && ( $el = $el->previousSibling ) ) {
				// no op
			}
		}
		return $el;
	}

	/**
	 * @param DOMNode $n
	 * @return bool
	 */
	private static function parentIsElement( $n ): bool {
		$parent = $n->parentNode;
		if ( !$parent ) {
			return false;
		}
		// The root `html` element (node type 9) can be a first- or
		// last-child, too, which means that the document (or document
		// fragment) counts as an "element".
		return $parent->nodeType === 1 /* Element */ ||
			self::nodeIsDocument( $parent ) /* Document */ ||
			$parent->nodeType === 11; /* DocumentFragment */
	}

	/**
	 * @param DOMNode $n
	 * @return bool
	 */
	private static function nodeIsDocument( $n ): bool {
		$nodeType = $n->nodeType;
		return $nodeType === 9 /* Document */ ||
			// In PHP, if you load a document with
			// DOMDocument::loadHTML, your root DOMDocument will have node
			// type 13 (!) which is PHP's bespoke "XML_HTML_DOCUMENT_NODE"
			// and Not A Real Thing.  But we'll recognize it anyway...
			$nodeType === 13; /* HTMLDocument */
	}

	private static function unichr( int $codepoint ): string {
		if ( extension_loaded( 'intl' ) ) {
			return \IntlChar::chr( $codepoint );
		} else {
			return mb_chr( $codepoint, "utf-8" );
		}
	}

	private static function unquote( string $str ): string {
		if ( !$str ) {
			return $str;
		}
		self::initRules();
		$ch = $str[ 0 ];
		if ( $ch === '"' || $ch === "'" ) {
			if ( substr( $str, -1 ) === $ch ) {
				$str = substr( $str, 1, -1 );
			} else {
				// bad string.
				$str = substr( $str, 1 );
			}
			return preg_replace_callback( self::$rules->str_escape, function ( array $matches ) {
				$s = $matches[0];
				if ( !preg_match( '/^\\\(?:([0-9A-Fa-f]+)|([\r\n\f]+))/', $s, $m ) ) {
					return substr( $s, 1 );
				}
				if ( $m[ 2 ] ) {
					return ''; /* escaped newlines are ignored in strings. */
				}
				$cp = intval( $m[ 1 ], 16 );
				return self::unichr( $cp );
			}, $str );
		} elseif ( preg_match( self::$rules->ident, $str ) ) {
			return self::decodeid( $str );
		} else {
			// NUMBER, PERCENTAGE, DIMENSION, etc
			return $str;
		}
	}

	private static function decodeid( string $str ): string {
		return preg_replace_callback( self::$rules->escape, function ( array $matches ) {
			$s = $matches[0];
			if ( !preg_match( '/^\\\([0-9A-Fa-f]+)/', $s, $m ) ) {
				return $s[ 1 ];
			}
			$cp = intval( $m[ 1 ], 16 );
			return self::unichr( $cp );
		}, $str );
	}

	/**
	 * Escape an identifier for CSS.
	 * This is equivalent to CSS.escape
	 * (https://drafts.csswg.org/cssom/#the-css.escape()-method)
	 * and is the opposite of self::decodeid().
	 */
	private static function encodeid( string $str ): string {
		return preg_replace_callback( '/(\\x00)|([\\x01-\\x1F\\x7F])|(^[0-9])|(^-[0-9])|(^-$)|([^-A-Za-z0-9_\\x{80}-\\x{10FFFF}])/u', static function ( array $matches ) {
			if ( isset( $matches[1] ) ) {
				return "\u{FFFD}";
			} elseif ( isset( $matches[2] ) || isset( $matches[3] ) ) {
				$cp = mb_ord( $matches[0], "UTF-8" );
				return '\\' . dechex( $cp ) . ' ';
			} elseif ( isset( $matches[4] ) ) {
				$cp = mb_ord( $matches[0][1], "UTF-8" );
				return '-\\' . dechex( $cp ) . ' ';
			} else {
				return '\\' . $matches[0];
			}
		}, $str, -1, $ignore, PREG_UNMATCHED_AS_NULL );
	}

	private static function makeInside( string $start, string $end ): string {
		$regex = preg_replace(
			'/>/', $end, preg_replace(
				'/</', $start, self::reSource( self::$rules->inside )
			)
		);
		return '/' . $regex . '/Su';
	}

	private static function reSource( string $regex ): string {
		// strip delimiter and flags from regular expression
		return preg_replace( '/(^\/)|(\/[a-z]*$)/Diu', '', $regex );
	}

	private static function replace( string $regex, string $name, string $val ): string {
		$regex = self::reSource( $regex );
		$regex = str_replace( $name, self::reSource( $val ), $regex );
		return '/' . $regex . '/Su';
	}

	private static function truncateUrl( string $url, int $num ): string {
		$url = preg_replace( '/^(?:\w+:\/\/|\/+)/', '', $url );
		$url = preg_replace( '/(?:\/+|\/*#.*?)$/', '', $url );
		return implode( '/', explode( '/', $url, $num ) );
	}

	private static function xpathQuote( string $s ): string {
		// Ugly-but-functional escape mechanism for xpath query
		$parts = explode( "'", $s );
		$parts = array_map( static function ( string $ss ) {
			return "'$ss'";
		}, $parts );
		if ( count( $parts ) === 1 ) {
			return $parts[0];
		} else {
			return 'concat(' . implode( ',"\'",', $parts ) . ')';
		}
	}

	/**
	 * Get descendants by ID.
	 *
	 * The PHP DOM doesn't provide this method for DOMElement, and the
	 * implementation in DOMDocument is broken.
	 *
	 * Further, the web spec only provides for returning a single element
	 * here.  This function can support returning *all* of the matches for
	 * a given ID, if the underlying DOM implementation supports this.
	 *
	 * This is an *exclusive* query; that is, $context should never be included
	 * among the results.
	 *
	 * Although a `getElementsById` key can be passed in the options array
	 * to override the default implementation, for efficiency it is recommended
	 * that clients subclass ZestInst and override this entire method if
	 * they can provide an efficient id index.
	 *
	 * @param DOMDocument|DOMDocumentFragment|DOMElement $context
	 *   The scoping root for the search
	 * @param string $id
	 * @param array $opts Additional match-context options (optional)
	 * @return array<DOMElement> A list of the elements with the given ID. When there are more
	 *   than one, this method might return all of them or only the first one.
	 */
	public function getElementsById( $context, string $id, array $opts = [] ): array {
		if ( is_callable( $opts['getElementsById'] ?? null ) ) {
			// Third-party DOM implementation might provide a way to
			// get multiple results for a given ID.
			// Note that this must work for DocumentFragment and Element
			// as well!
			$func = $opts['getElementsById'];
			return $func( $context, $id );
		}
		// Neither PHP nor the web standards provide an DOMElement-scoped
		// version of getElementById, so we can't call this directly on
		// $context -- but that's okay because (1) IDs should be unique, and
		// (2) we verify the scope of the returned element below
		// anyway (to work around bugs with deleted-but-not-gc'ed
		// nodes).
		$doc = self::nodeIsDocument( $context ) ?
			$context : $context->ownerDocument;
		$r = $doc->getElementById( $id );
		// Note that $r could be null here because the
		// DOMDocument hasn't had an "id attribute" set, even if the id
		// exists in the document. See:
		// http://php.net/manual/en/domdocument.getelementbyid.php
		if ( $r !== null ) {
			// Verify that this node is actually connected to the
			// document (or to the context), since the element
			// isn't removed from the index immediately when it
			// is deleted. (Also PHP's call is not scoped.)
			// (Note that scoped getElementsById is *exclusive* of $context,
			// so we start this search at r's parent node.)
			for ( $parent = $r->parentNode; $parent; $parent = $parent->parentNode ) {
				if ( $parent === $context ) {
					return [ $r ];
				}
			}
			// It's possible a deleted-but-still-indexed element was
			// shadowing a later-added element, so we can't return
			// null here directly; fallback to a full search.
		}
		if ( $this->isStandardsMode( $context, $opts ) ) {
			// The workaround below only works (and is only necessary!)
			// when this is a PHP-provided \DOMDocument.  For 3rd-party
			// DOM implementations, we assume that getElementById() was
			// reliable.
			// @phan-suppress-next-line PhanUndeclaredProperty
			if ( $context->isConnected || $id === '' ) {
				return [];
			}
			// For disconnected Elements and DocumentFragments, we need
			// to do this the hard/slow way
			$r = [];
			foreach ( $this->getElementsByTagName( $context, '*', $opts ) as $el ) {
				// Work around Phan 8.1 / 7.4 issues by telling Phan what
				// the expected type is. Zest could be used with newer DOM
				// implementations that could return null for missing attributes.
				// See https://developer.mozilla.org/en-US/docs/Web/API/Element/getAttribute#non-existing_attributes
				// But, Phan doesn't know that and only sees the native PHP
				// implementation that returns '' for missing attributes.
				$elId = $el->getAttribute( 'id' );
				'@phan-var ?string $elId';
				if ( $id === ( $elId ?? '' ) ) {
					$r[] = $el;
				}
			}
			return $r;
		}
		// Do an xpath search, which is still a full traversal of the tree
		// (sigh) but 25% faster than traversing it wholly in PHP.
		$xpath = new \DOMXPath( $doc );
		$query = './/*[@id=' . self::xpathQuote( $id ) . ']';
		if ( $context->nodeType === 11 ) {
			// ugh, PHP dom extension workaround: nodes which are direct
			// children of the DocumentFragment are not matched unless we
			// use a ./ query in addition to the .// query.
			$query = "./" . substr( $query, 3 ) . "|$query";
		}
		return iterator_to_array( $xpath->query( $query, $context ) );
	}

	private function docFragHelper( $docFrag, string $sel, array $opts, callable $collectFunc ) {
		$result = [];
		for ( $n = $docFrag->firstChild; $n; $n = $n->nextSibling ) {
			if ( $n->nodeType !== 1 ) {
				continue; // Not an element
			}
			// See if $n itself should be included
			if ( $this->matches( $n, $sel, $opts ) ) {
				$result[] = $n;
			}
			// Now include all of $n's children
			array_splice( $result, count( $result ), 0, $collectFunc( $n ) );
		}
		return $result;
	}

	/**
	 * Get descendants by tag name.
	 * The PHP DOM doesn't provide this method for DOMElement, and the
	 * implementation in DOMDocument has performance issues.
	 *
	 * This is an *exclusive* query; that is, $context should never be included
	 * among the results.
	 *
	 * Clients can subclass and override this to provide a more efficient
	 * implementation if one is available.
	 *
	 * @param DOMDocument|DOMDocumentFragment|DOMElement $context
	 * @param string $tagName
	 * @param array $opts Additional match-context options (optional)
	 * @return array<DOMElement>
	 */
	public function getElementsByTagName( $context, string $tagName, array $opts = [] ) {
		if ( $context->nodeType === 11 /* DocumentFragment */ ) {
			// DOM standards don't define getElementsByTagName on
			// DocumentFragment, and XPath supports it but has a bug which
			// omits root elements.  So emulate in both these cases.
			$selector = $tagName === '*' ? '*' : self::encodeid( $tagName );
			return $this->docFragHelper(
				$context, $selector, $opts,
				function ( $el ) use ( $tagName, $opts ): array {
					return $this->getElementsByTagName( $el, $tagName, $opts );
				}
			);
		}
		if ( $this->isStandardsMode( $context, $opts ) ) {
			// For third-party DOM implementations, just use native func.
			return iterator_to_array(
				$context->getElementsByTagName( $tagName )
			);
		}
		// This *should* just be a call to PHP's `getElementByTagName`
		// function *BUT* PHP's implementation is 100x slower than using
		// XPath to get the same results (!)

		// XXX this assumes default PHP DOM implementation, which
		// reports lowercase tag names in DOMNode->tagName (even though
		// the DOM spec says it should report uppercase)
		$tagName = strtolower( $tagName );

		$doc = self::nodeIsDocument( $context ) ?
			$context : $context->ownerDocument;
		$xpath = new \DOMXPath( $doc );
		$ns = $doc->documentElement === null ? 'force use of local-name' :
			$doc->documentElement->namespaceURI;
		if ( $tagName === '*' ) {
			$query = ".//*";
		} elseif ( $ns || !preg_match( '/^[_a-z][-.0-9_a-z]*$/S', $tagName ) ) {
			$query = './/*[local-name()=' . self::xpathQuote( $tagName ) . ']';
		} else {
			$query = ".//$tagName";
		}
		return iterator_to_array( $xpath->query( $query, $context ) );
	}

	/**
	 * Clients can subclass and override this to provide a more efficient
	 * implementation if one is available.
	 *
	 * This is an *exclusive* query; that is, $context should never be included
	 * among the results.
	 *
	 * @param DOMDocument|DOMDocumentFragment|DOMElement $context
	 * @param string $className
	 * @param array $opts
	 * @return array<DOMElement>
	 */
	protected function getElementsByClassName( $context, string $className, $opts ) {
		if ( $context->nodeType === 11 /* DocumentFragment */ ) {
			// DOM standards don't define getElementsByClassName on
			// DocumentFragment, and XPath supports it but has a bug which
			// omits root elements.  So emulate in both these cases.
			return $this->docFragHelper(
				$context,
				// NOTE this only works when $className is a single class,
				// but that's the only way we invoke it.
				"." . self::encodeid( $className ),
				$opts,
				function ( $el ) use ( $className, $opts ): array {
					return $this->getElementsByClassName( $el, $className, $opts );
				}
			);
		}
		if ( $this->isStandardsMode( $context, $opts ) ) {
			// For third-party DOM implementations, just use native func.
			return iterator_to_array(
				// @phan-suppress-next-line PhanUndeclaredMethod
				$context->getElementsByClassName( $className )
			);
		}

		// PHP doesn't have an implementation of this method; use XPath
		// to quickly get results.  (It would be faster still if there was an
		// actual index, but this will be about 25% faster than doing the
		// tree traversal all in PHP.)
		$doc = self::nodeIsDocument( $context ) ?
			$context : $context->ownerDocument;
		$xpath = new \DOMXPath( $doc );
		$quotedClassName = self::xpathQuote( " $className " );
		$query = ".//*[contains(concat(' ', normalize-space(@class), ' '), $quotedClassName)]";
		return iterator_to_array( $xpath->query( $query, $context ) );
	}

	/**
	 * Handle `nth` Selectors
	 */
	private static function parseNth( string $param ): object {
		$param = preg_replace( '/\s+/', '', $param );

		if ( $param === 'even' ) {
			$param = '2n+0';
		} elseif ( $param === 'odd' ) {
			$param = '2n+1';
		} elseif ( strpos( $param, 'n' ) === false ) {
			$param = '0n' . $param;
		}

		preg_match( '/^([+-])?(\d+)?n([+-])?(\d+)?$/', $param, $cap, PREG_UNMATCHED_AS_NULL );

		$group = intval( ( $cap[1] ?? '' ) . ( $cap[2] ?? '1' ), 10 );
		$offset = intval( ( $cap[3] ?? '' ) . ( $cap[4] ?? '0' ), 10 );
		return (object)[
			'group' => $group,
			'offset' => $offset,
		];
	}

	/**
	 * @param string $param
	 * @param callable(DOMNode,DOMNode,array):bool $test
	 * @param bool $last
	 * @return callable(DOMNode,array):bool
	 */
	private static function nth( string $param, callable $test, bool $last ): callable {
		$param = self::parseNth( $param );
		$group = $param->group;
		$offset = $param->offset;
		$find = ( !$last ) ? [ self::class, 'child' ] : [ self::class, 'lastChild' ];
		$advance = ( !$last ) ? [ self::class, 'next' ] : [ self::class, 'prev' ];
		return function ( $el, array $opts ) use ( $find, $test, $offset, $group, $advance ): bool {
			if ( !self::parentIsElement( $el ) ) {
				return false;
			}

			$rel = call_user_func( $find, $el->parentNode );
			$pos = 0;

			while ( $rel ) {
				if ( call_user_func( $test, $rel, $el, $opts ) ) {
					$pos++;
				}
				if ( $rel === $el ) {
					$pos -= $offset;
					return ( $group && $pos )
						? ( $pos % $group ) === 0 && ( ( $pos < 0 ) === ( $group < 0 ) )
						: !$pos;
				}
				$rel = call_user_func( $advance, $rel );
			}
			return false;
		};
	}

	/**
	 * Simple Selectors which take no arguments.
	 * @var array<string,(callable(DOMNode,array):bool)>
	 */
	private $selectors0;

	/**
	 * Simple Selectors which take one argument.
	 * @var array<string,(callable(string,ZestInst):(callable(DOMNode,array):bool))>
	 */
	private $selectors1;

	/**
	 * Add a custom selector that takes no parameters.
	 * @param string $key Name of the selector
	 * @param callable(DOMNode,array):bool $func
	 *   The selector match function
	 */
	public function addSelector0( string $key, callable $func ) {
		$this->selectors0[$key] = $func;
	}

	/**
	 * Add a custom selector that takes 1 parameter, which is passed as a
	 * string.
	 * @param string $key Name of the selector
	 * @param callable(string,ZestInst):(callable(DOMNode,array):bool)|callable(string):(callable(DOMNode,array):bool) $func
	 *   The selector match function
	 */
	public function addSelector1( string $key, callable $func ) {
		$this->selectors1[$key] = $func;
	}

	private function initSelectors() {
		// Careful: this method is only called once, on the singleton
		// ZestInst, which all child ZestInst instances inherit their
		// default selector lists from.  But as a result $this is
		// always the singleton; be sure to use the $self argument (for
		// selector1) or $opts['this'] (for selector0) to access the
		// dynamically-bound $this.

		$this->addSelector0( '*', static function ( $el, $opts ): bool {
			return true;
		} );
		$this->addSelector1( 'type', static function ( string $type ): callable {
			$type = strtolower( $type );
			return static function ( $el, $opts ) use ( $type ): bool {
				return strtolower( $el->nodeName ) === $type;
			};
		} );
		$this->addSelector1( 'typeNoNS', static function ( string $type ): callable {
			$type = strtolower( $type );
			return static function ( $el, $opts ) use ( $type ): bool {
				return ( $el->namespaceURI ?? '' ) === '' &&
					strtolower( $el->nodeName ) === $type;
			};
		} );
		$this->addSelector0( ':first-child', function ( $el, $opts ): bool {
			return !self::prev( $el ) && self::parentIsElement( $el );
		} );
		$this->addSelector0( ':last-child', function ( $el, $opts ): bool {
			return !self::next( $el ) && self::parentIsElement( $el );
		} );
		$this->addSelector0( ':only-child', function ( $el, $opts ): bool {
			return !self::prev( $el ) && !self::next( $el )
				&& self::parentIsElement( $el );
		} );
		$this->addSelector1( ':nth-child', function ( string $param ): callable {
			return self::nth( $param, static function ( $rel, $el, $opts ): bool {
				return true;
			}, false /* last */ );
		} );
		$this->addSelector1( ':nth-last-child', function ( string $param ): callable {
			return self::nth( $param, static function ( $rel, $el, $opts ): bool {
				return true;
			}, true /* last */ );
		} );
		$this->addSelector0( ':root', static function ( $el, $opts ): bool {
			return $el->ownerDocument->documentElement === $el;
		} );
		$this->addSelector0( ':empty', static function ( $el, $opts ): bool {
			return !$el->firstChild;
		} );
		$this->addSelector1( ':not', static function ( string $sel, ZestInst $self ) {
			$test = $self->compileGroup( $sel );
			return static function ( $el, $opts ) use ( $test ): bool {
				return !call_user_func( $test, $el, $opts );
			};
		} );
		$this->addSelector0( ':first-of-type', function ( $el, $opts ): bool {
			if ( !self::parentIsElement( $el ) ) {
				return false;
			}
			$type = $el->nodeName;
			while ( $el = self::prev( $el ) ) {
				if ( $el->nodeName === $type ) {
					return false;
				}
			}
			return true;
		} );
		$this->addSelector0( ':last-of-type', function ( $el, $opts ): bool {
			if ( !self::parentIsElement( $el ) ) {
				return false;
			}
			$type = $el->nodeName;
			while ( $el = self::next( $el ) ) {
				if ( $el->nodeName === $type ) {
					return false;
				}
			}
			return true;
		} );
		$this->addSelector0( ':only-of-type', static function ( $el, $opts ): bool {
			$self = $opts['this'];
			return $self->selectors0[ ':first-of-type' ]( $el, $opts ) &&
				$self->selectors0[ ':last-of-type' ]( $el, $opts );
		} );
		$makeNthOfType = static function ( bool $last ) {
			return function ( string $param ) use ( $last ): callable  {
				return self::nth( $param, static function ( $rel, $el, $opts ): bool {
					return $rel->nodeName === $el->nodeName;
				}, $last );
			};
		};
		$this->addSelector1( ':nth-of-type', $makeNthOfType( false ) );
		$this->addSelector1( ':nth-last-of-type', $makeNthOfType( true ) );
		/** @suppress PhanUndeclaredProperty not defined in PHP DOM */
		$this->addSelector0( ':checked', static function ( $el, $opts ): bool {
			'@phan-var DOMElement $el';
			$self = $opts['this'];
			if ( $self->isStandardsMode( $el, $opts ) ) {
				// These properties don't exist in the PHP DOM, and in fact
				// they are supposed to reflect the *dynamic* state of the
				// widget, not the 'default' state (which is given by the
				// attribute value)
				if ( isset( $el->checked ) || isset( $el->selected ) ) {
					return ( isset( $el->checked ) && $el->checked ) ||
						( isset( $el->selected ) && $el->selected );
				}
			}
			return $el->hasAttribute( 'checked' ) || $el->hasAttribute( 'selected' );
		} );
		$this->addSelector0( ':indeterminate', static function ( $el, $opts ): bool {
			$self = $opts['this'];
			return !$self->selectors0[ ':checked' ]( $el, $opts );
		} );
		/** @suppress PhanUndeclaredProperty not defined in PHP DOM */
		$this->addSelector0( ':enabled', static function ( $el, $opts ): bool {
			'@phan-var DOMElement $el';
			$self = $opts['this'];
			if ( $self->isStandardsMode( $el, $opts ) && isset( $el->type ) ) {
				$type = $el->type; // this does case normalization in spec
			} else {
				$type = $el->getAttribute( 'type' );
			}
			return !$el->hasAttribute( 'disabled' ) && $type !== 'hidden';
		} );
		$this->addSelector0( ':disabled', static function ( $el, $opts ): bool {
			'@phan-var DOMElement $el';
			return $el->hasAttribute( 'disabled' );
		} );
		/*
		$this->addSelector0( ':target', function ( $el ) use ( &$window ) {
			return $el->id === $window->location->hash->substring( 1 );
		});
		$this->addSelector0( ':focus', function ( $el ) {
			return $el === $el->ownerDocument->activeElement;
		});
		*/
		$this->addSelector1( ':is', static function ( string $sel, ZestInst $self ): callable {
			return $self->compileGroup( $sel );
		} );
		// :matches is an older name for :is; see
		// https://github.com/w3c/csswg-drafts/issues/3258
		$this->addSelector1( ':matches', static function ( string $sel, ZestInst $self ): callable {
			return $self->selectors1[ ':is' ]( $sel, $self );
		} );
		$makeNthMatch = static function ( bool $last ) {
			return function ( string $param, ZestInst $self ) use ( $last ): callable {
				$args = preg_split( '/\s*,\s*/', $param );
				$arg = array_shift( $args );
				$test = $self->compileGroup( implode( ',', $args ) );

				return self::nth( $arg, static function ( $rel, $el, $opts ) use ( $test ): bool {
					return call_user_func( $test, $el, $opts );
				}, $last );
			};
		};
		$this->addSelector1( ':nth-match', $makeNthMatch( false ) );
		$this->addSelector1( ':nth-last-match', $makeNthMatch( true ) );
		/*
		$this->addSelector0( ':links-here', function ( $el ) use ( &$window ) {
			return $el . '' === $window->location . '';
		});
		*/
		$this->addSelector1( ':lang', static function ( string $param ): callable {
			return static function ( $el, $opts ) use ( $param ): bool {
				while ( $el ) {
					if ( $el->nodeType === 1 /* Element */ ) {
						'@phan-var DOMElement $el';
						// PHP DOM doesn't have 'lang' property
						$lang = $el->getAttribute( 'lang' );
						if ( $lang ) {
							return strpos( $lang, $param ) === 0;
						}
					}
					$el = $el->parentNode;
				}
				return false;
			};
		} );
		$this->addSelector1( ':dir', static function ( string $param ): callable {
			return static function ( $el, $opts ) use ( $param ): bool {
				while ( $el ) {
					if ( $el->nodeType === 1 /* Element */ ) {
						'@phan-var DOMElement $el';
						$dir = $el->getAttribute( 'dir' );
						if ( $dir ) {
							return $dir === $param;
						}
					}
					$el = $el->parentNode;
				}
				return false;
			};
		} );
		$this->addSelector0( ':scope', static function ( $el, $opts ): bool {
			$self = $opts['this'];
			$scope = $opts['scope'] ?? null;
			if ( $scope !== null && $scope->nodeType === 1 ) {
				return $el === $scope;
			}
			// If the scoping root is missing or not an element, then :scope
			// should be a synonym for :root
			return $self->selectors0[ ':root' ]( $el, $opts );
		} );
		/*
		$this->addSelector0( ':any-link', function ( $el ):bool {
			return gettype( $el->href ) === 'string';
		});
		$this->addSelector( ':local-link', function ( $el ) use ( &$window ) {
			if ( $el->nodeName ) {
				return $el->href && $el->host === $window->location->host;
			}
			// XXX this is really selector1 not selector0
			$param = +$el + 1;
			return function ( $el ) use ( &$window, $param ) {
				if ( !$el->href ) { return;  }

				$url = $window->location . '';
				$href = $el . '';

				return self::truncateUrl( $url, $param ) === self::truncateUrl( $href, $param );
			};
		});
		$this->addSelector0( ':default', function ( $el ):bool {
			return !!$el->defaultSelected;
		});
		$this->addSelector0( ':valid', function ( $el ):bool {
			return $el->willValidate || ( $el->validity && $el->validity->valid );
		});
		*/
		$this->addSelector0( ':invalid', static function ( $el, $opts ): bool {
			$self = $opts['this'];
			return !$self->selectors0[ ':valid' ]( $el, $opts );
		} );
		/*
		$this->addSelector0( ':in-range', function ( $el ):bool {
			return $el->value > $el->min && $el->value <= $el->max;
		});
		*/
		$this->addSelector0( ':out-of-range', static function ( $el, $opts ): bool {
			$self = $opts['this'];
			return !$self->selectors0[ ':in-range' ]( $el, $opts );
		} );
		$this->addSelector0( ':required', static function ( $el, $opts ): bool {
			'@phan-var DOMElement $el';
			return $el->hasAttribute( 'required' );
		} );
		$this->addSelector0( ':optional', static function ( $el, $opts ): bool {
			$self = $opts['this'];
			return !$self->selectors0[ ':required' ]( $el, $opts );
		} );
		$this->addSelector0( ':read-only', static function ( $el, $opts ): bool {
			'@phan-var DOMElement $el';
			if ( $el->hasAttribute( 'readOnly' ) ) {
				return true;
			}

			$attr = $el->getAttribute( 'contenteditable' );
			$name = strtolower( $el->nodeName );

			$name = $name !== 'input' && $name !== 'textarea';

			return ( $name || $el->hasAttribute( 'disabled' ) ) && $attr == null;
		} );
		$this->addSelector0( ':read-write', static function ( $el, $opts ): bool {
			$self = $opts['this'];
			return !$self->selectors0[ ':read-only' ]( $el, $opts );
		} );
		foreach ( [
			':hover',
			':active',
			':link',
			':visited',
			':column',
			':nth-column',
			':nth-last-column',
			':current',
			':past',
			':future',
		] as $selector ) {
			$this->addSelector0(
				$selector,
				/**
				 * @param DOMNode $el
				 * @param array $opts
				 * @return never
				 */
				static function ( $el, $opts ) use ( $selector ): bool {
					$self = $opts['this'];
					throw $self->newBadSelectorException( $selector . ' is not supported.' );
				}
			);
		}
		// Non-standard, for compatibility purposes.
		$this->addSelector1( ':contains', static function ( string $param ): callable {
			return static function ( $el ) use ( $param ): bool {
				$text = $el->textContent;
				return strpos( $text, $param ) !== false;
			};
		} );
		$this->addSelector1( ':has', static function ( string $param ): callable {
			return static function ( $el, array $opts ) use ( $param ): bool {
				'@phan-var DOMElement $el';
				$self = $opts['this'];
				return count( $self->find( $param, $el, $opts ) ) > 0;
			};
		} );
		// Potentially add more pseudo selectors for
		// compatibility with sizzle and most other
		// selector engines (?).
	}

	/** @return callable(DOMNode,array):bool */
	private function selectorsAttr( string $key, string $op, string $val, bool $i ): callable {
		$op = $this->operators[ $op ];
		return static function ( $el, $opts ) use ( $key, $i, $op, $val ): bool {
			/* XXX: the below all assumes a more complete PHP DOM than we have
			switch ( $key ) {
			#case 'for':
			#	$attr = $el->htmlFor; // Not supported in PHP DOM
			#	break;
			case 'class':
				// PHP DOM doesn't support $el->className
				// className is '' when non-existent
				// getAttribute('class') is null
				if ($el->hasAttributes() && $el->hasAttribute( 'class' ) ) {
					$attr = $el->getAttribute( 'class' );
				} else {
					$attr = null;
				}
				break;
			case 'href':
			case 'src':
				$attr = $el->getAttribute( $key, 2 );
				break;
			case 'title':
				// getAttribute('title') can be '' when non-existent sometimes?
				if ($el->hasAttribute('title')) {
					$attr = $el->getAttribute( 'title' );
				} else {
					$attr = null;
				}
				break;
				// careful with attributes with special getter functions
			case 'id':
			case 'lang':
			case 'dir':
			case 'accessKey':
			case 'hidden':
			case 'tabIndex':
			case 'style':
				if ( $el->getAttribute ) {
					$attr = $el->getAttribute( $key );
					break;
				}
				// falls through
			default:
				if ( $el->hasAttribute && !$el->hasAttribute( $key ) ) {
					break;
				}
				$attr = ( $el[ $key ] != null ) ?
					$el[ $key ] :
					$el->getAttribute && $el->getAttribute( $key );
				break;
			}
			*/
			// This is our simple PHP DOM version
			'@phan-var DOMElement $el';
			if ( $el->hasAttributes() && $el->hasAttribute( $key ) ) {
				$attr = $el->getAttribute( $key );
			} else {
				return false;
			}
			// End simple PHP DOM version
			if ( $i ) {
				$attr = strtolower( $attr );
				$val = strtolower( $val );
			}
			return call_user_func( $op, $attr, $val );
		};
	}

	/**
	 * Attribute Operators
	 * @var array<string,(callable(string,string):bool)>
	 */
	private $operators;

	/**
	 * Add a custom operator
	 * @param string $key Name of the operator
	 * @param callable(string,string):bool $func
	 *   The operator match function
	 */
	public function addOperator( string $key, callable $func ) {
		$this->operators[$key] = $func;
	}

	private function initOperators() {
		// Be careful: $this always points to the singleton ZestInst
		$this->addOperator( '-', static function ( string $attr, string $val ): bool {
			return true;
		} );
		$this->addOperator( '=', static function ( string $attr, string $val ): bool {
			return $attr === $val;
		} );
		$this->addOperator( '*=', static function ( string $attr, string $val ): bool {
			return strpos( $attr, $val ) !== false;
		} );
		$this->addOperator( '~=', static function ( string $attr, string $val ): bool {
			// https://drafts.csswg.org/selectors-4/#attribute-representation
			// 	If "val" contains whitespace, it will never represent
			// 	anything (since the words are separated by spaces)
			if ( strcspn( $val, " \t\r\n\f" ) !== strlen( $val ) ) {
				return false;
			}
			// Also if "val" is the empty string, it will never
			// 	represent anything.
			if ( strlen( $val ) === 0 ) {
				return false;
			}
			$attrLen = strlen( $attr );
			$valLen = strlen( $val );
			for ( $s = 0;  $s < $attrLen;  $s = $i + 1 ) {
				$i = strpos( $attr, $val, $s );
				if ( $i === false ) {
					return false;
				}
				$j = $i + $valLen;
				$f = ( $i === 0 ) ? ' ' : $attr[ $i - 1 ];
				$l = ( $j >= $attrLen ) ? ' ' : $attr[ $j ];
				$f = strtr( $f, "\t\r\n\f", "    " );
				$l = strtr( $l, "\t\r\n\f", "    " );
				if ( $f === ' ' && $l === ' ' ) {
					return true;
				}
			}
			return false;
		} );
		$this->addOperator( '|=', static function ( string $attr, string $val ): bool {
			$i = strpos( $attr, $val );
			if ( $i !== 0 ) {
				return false;
			}
			$j = $i + strlen( $val );
			if ( $j >= strlen( $attr ) ) {
				return true;
			}
			$l = $attr[ $j ];
			return $l === '-';
		} );
		$this->addOperator( '^=', static function ( string $attr, string $val ): bool {
			return strpos( $attr, $val ) === 0;
		} );
		$this->addOperator( '$=', static function ( string $attr, string $val ): bool {
			$i = strrpos( $attr, $val );
			return $i !== false && $i + strlen( $val ) === strlen( $attr );
		} );
		// non-standard
		$this->addOperator( '!=', static function ( string $attr, string $val ): bool {
			return $attr !== $val;
		} );
	}

	/**
	 * Combinator Logic
	 * @var array<string,(callable(callable(DOMNode,array):bool):(callable(DOMNode,array):(?DOMNode)))>
	 */
	private $combinators;

	/**
	 * Add a custom combinator
	 * @param string $key Name of the combinator
	 * @param callable(callable(DOMNode,array):bool):(callable(DOMNode,array):(?DOMNode)) $func
	 *   The combinator match function
	 */
	public function addCombinator( string $key, callable $func ) {
		$this->combinators[$key] = $func;
	}

	private function initCombinators() {
		// Be careful: $this always points to the singleton ZestInst
		$this->addCombinator( ' ', static function ( callable $test ): callable {
			return static function ( $el, $opts ) use ( $test ) {
				while ( $el = $el->parentNode ) {
					if ( $el->nodeType === 1 && call_user_func( $test, $el, $opts ) ) {
						return $el;
					}
				}
				return null;
			};
		} );
		$this->addCombinator( '>', static function ( callable $test ): callable {
			return static function ( $el, $opts ) use ( $test ) {
				if ( $el = $el->parentNode ) {
					if ( $el->nodeType === 1 && call_user_func( $test, $el, $opts ) ) {
						return $el;
					}
				}
				return null;
			};
		} );
		$this->addCombinator( '+', function ( callable $test ): callable {
			return function ( $el, $opts ) use ( $test ) {
				if ( $el = self::prev( $el ) ) {
					if ( call_user_func( $test, $el, $opts ) ) {
						return $el;
					}
				}
				return null;
			};
		} );
		$this->addCombinator( '~', function ( callable $test ): callable {
			return function ( $el, $opts ) use ( $test ) {
				while ( $el = self::prev( $el ) ) {
					if ( call_user_func( $test, $el, $opts ) ) {
						return $el;
					}
				}
				return null;
			};
		} );
		$this->addCombinator( 'noop', static function ( callable $test ): callable {
			return static function ( $el, $opts ) use ( $test ) {
				if ( call_user_func( $test, $el, $opts ) ) {
					return $el;
				}
				return null;
			};
		} );
	}

	/**
	 * @param callable(DOMNode,array):bool $test
	 * @param string $name
	 * @return ZestFunc
	 */
	private function makeRef( callable $test, string $name ): ZestFunc {
		$node = null;
		$ref = new ZestFunc( function ( $el, $opts ) use ( &$node, &$ref ): bool {
			$doc = $el->ownerDocument;
			$nodes = $this->getElementsByTagName( $doc, '*', $opts );
			$i = count( $nodes );

			while ( $i-- ) {
				$node = $nodes[$i];
				if ( call_user_func( $ref->test->func, $el, $opts ) ) {
					$node = null;
					return true;
				}
			}

			$node = null;
			return false;
		} );

		$ref->combinator = static function ( $el, $opts ) use ( &$node, $name, $test ) {
			if ( !$node || $node->nodeType !== 1 /* Element */ ) {
				return null;
			}

			$attr = $node->getAttribute( $name ) ?? '';
			if ( $attr !== '' && $attr[ 0 ] === '#' ) {
				$attr = substr( $attr, 1 );
			}

			$id = $node->getAttribute( 'id' ) ?? '';
			if ( $attr === $id && call_user_func( $test, $node, $opts ) ) {
				return $node;
			}
			return null;
		};

		return $ref;
	}

	/**
	 * Grammar
	 */

	/** @var \stdClass */
	private static $rules;

	public static function initRules() {
		self::$rules = (object)[
		'escape' => '/\\\(?:[^0-9A-Fa-f\r\n]|[0-9A-Fa-f]{1,6}[\r\n\t ]?)/',
		'str_escape' => '/(escape)|\\\(\n|\r\n?|\f)/',
		'nonascii' => '/[\x{00A0}-\x{FFFF}]/',
		'cssid' => '/(?:(?!-?[0-9])(?:escape|nonascii|[-_a-zA-Z0-9])+)/',
		'qname' => '/^ *((?:\*?\|)?cssid|\*)/',
		'simple' => '/^(?:([.#]cssid)|pseudo|attr)/',
		'ref' => '/^ *\/(cssid)\/ */',
		'combinator' => '/^(?: +([^ \w*.#\\\]) +|( )+|([^ \w*.#\\\]))(?! *$)/',
		'attr' => '/^\[(cssid)(?:([^\w]?=)(inside))?\]/',
		'pseudo' => '/^(:cssid)(?:\((inside)\))?/',
		'inside' => "/(?:\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*'|<[^\"'>]*>|\\\\[\"'>]|[^\"'>])*/",
		'ident' => '/^(cssid)$/',
		];
		self::$rules->cssid = self::replace( self::$rules->cssid, 'nonascii', self::$rules->nonascii );
		self::$rules->cssid = self::replace( self::$rules->cssid, 'escape', self::$rules->escape );
		self::$rules->qname = self::replace( self::$rules->qname, 'cssid', self::$rules->cssid );
		self::$rules->simple = self::replace( self::$rules->simple, 'cssid', self::$rules->cssid );
		self::$rules->ref = self::replace( self::$rules->ref, 'cssid', self::$rules->cssid );
		self::$rules->attr = self::replace( self::$rules->attr, 'cssid', self::$rules->cssid );
		self::$rules->pseudo = self::replace( self::$rules->pseudo, 'cssid', self::$rules->cssid );
		self::$rules->inside = self::replace( self::$rules->inside, "[^\"'>]*", self::$rules->inside );
		self::$rules->attr = self::replace( self::$rules->attr, 'inside', self::makeInside( '\[', '\]' ) );
		self::$rules->pseudo = self::replace( self::$rules->pseudo, 'inside', self::makeInside( '\(', '\)' ) );
		self::$rules->simple = self::replace( self::$rules->simple, 'pseudo', self::$rules->pseudo );
		self::$rules->simple = self::replace( self::$rules->simple, 'attr', self::$rules->attr );
		self::$rules->ident = self::replace( self::$rules->ident, 'cssid', self::$rules->cssid );
		self::$rules->str_escape = self::replace( self::$rules->str_escape, 'escape', self::$rules->escape );
	}

	/**
	 * Compiling
	 */

	private function compile( string $sel ): ZestFunc {
		if ( !isset( $this->compileCache[$sel] ) ) {
			$this->compileCache[$sel] = $this->doCompile( $sel );
		}
		return $this->compileCache[$sel];
	}

	private function doCompile( string $sel ): ZestFunc {
		$sel = preg_replace( '/^\s+|\s+$/', '', $sel );
		$test = null;
		$filter = [];
		$buff = [];
		$subject = null;
		$qname = null;
		$cap = null;
		$op = null;
		$ref = null;

		while ( $sel ) {
			if ( preg_match( self::$rules->qname, $sel, $cap ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$qname = self::decodeid( $cap[ 1 ] );
				$buff[] = $this->tokQname( $qname );
				// strip off *| or | prefix
				if ( substr( $qname, 0, 1 ) === '|' ) {
					$qname = substr( $qname, 1 );
				} elseif ( substr( $qname, 0, 2 ) === '*|' ) {
					$qname = substr( $qname, 2 );
				}
			} elseif ( preg_match( self::$rules->simple, $sel, $cap, PREG_UNMATCHED_AS_NULL ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$qname = '*';
				$buff[] = $this->tokQname( $qname );
				$buff[] = $this->tok( $cap );
			} else {
				throw $this->newBadSelectorException( 'Invalid selector.' );
			}

			while ( preg_match( self::$rules->simple, $sel, $cap, PREG_UNMATCHED_AS_NULL ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$buff[] = $this->tok( $cap );
			}

			if ( $sel && $sel[ 0 ] === '!' ) {
				$sel = substr( $sel, 1 );
				$subject = $this->makeSubject();
				$subject->qname = $qname;
				$buff[] = $subject->simple;
			}

			if ( preg_match( self::$rules->ref, $sel, $cap ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$ref = $this->makeRef( self::makeSimple( $buff ), self::decodeid( $cap[ 1 ] ) );
				$filter[] = $ref->combinator;
				$buff = [];
				continue;
			}

			if ( preg_match( self::$rules->combinator, $sel, $cap, PREG_UNMATCHED_AS_NULL ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$op = $cap[ 1 ] ?? $cap[ 2 ] ?? $cap[ 3 ];
				if ( $op === ',' ) {
					$filter[] = $this->combinators['noop']( self::makeSimple( $buff ) );
					break;
				}
			} else {
				$op = 'noop';
			}

			if ( !isset( $this->combinators[ $op ] ) ) {
				throw $this->newBadSelectorException( 'Bad combinator: ' . $op );
			}
			$filter[] = $this->combinators[ $op ]( self::makeSimple( $buff ) );
			$buff = [];
		}

		$test = self::makeTest( $filter );
		$test->qname = $qname;
		$test->sel = $sel;

		if ( $subject ) {
			$subject->lname = $test->qname;

			$subject->test = $test;
			// @phan-suppress-next-line PhanPluginDuplicateExpressionAssignment
			$subject->qname = $subject->qname;
			$subject->sel = $test->sel;
			$test = $subject;
		}

		if ( $ref ) {
			$ref->test = $test;
			$ref->qname = $test->qname;
			$ref->sel = $test->sel;
			$test = $ref;
		}

		return $test;
	}

	/** @return callable(DOMNode,array):bool */
	private function tokQname( string $cap ): callable {
		// qname
		if ( $cap === '*' ) {
			return $this->selectors0['*'];
		} elseif ( substr( $cap, 0, 1 ) === '|' ) {
			// no namespace
			return $this->selectors1['typeNoNS']( substr( $cap, 1 ), $this );
		} elseif ( substr( $cap, 0, 2 ) === '*|' ) {
			// any namespace including no namespace
			return $this->selectors1['type']( substr( $cap, 2 ), $this );
		} else {
			return $this->selectors1['type']( $cap, $this );
		}
	}

	/** @return callable(DOMNode,array):bool */
	private function tok( array $cap ): callable {
		// class/id
		if ( $cap[ 1 ] ) {
			return $cap[ 1 ][ 0 ] === '.'
			// XXX unescape here?  or in attr?
				? $this->selectorsAttr( 'class', '~=', self::decodeid( substr( $cap[ 1 ], 1 ) ), false ) :
				$this->selectorsAttr( 'id', '=', self::decodeid( substr( $cap[ 1 ], 1 ) ), false );
		}

		// pseudo-name
		// inside-pseudo
		if ( $cap[ 2 ] ) {
			$id = self::decodeid( $cap[ 2 ] );
			if ( isset( $cap[3] ) && $cap[ 3 ] ) {
				if ( !isset( $this->selectors1[ $id ] ) ) {
					throw $this->newBadSelectorException( "Unknown Selector: $id" );
				}
				return $this->selectors1[ $id ]( self::unquote( $cap[ 3 ] ), $this );
			} else {
				if ( !isset( $this->selectors0[ $id ] ) ) {
					throw $this->newBadSelectorException( "Unknown Selector: $id" );
				}
				return $this->selectors0[ $id ];
			}
		}

		// attr name
		// attr op
		// attr value
		if ( $cap[ 4 ] ) {
			$value = $cap[ 6 ] ?? '';
			$i = preg_match( "/[\"'\\s]\\s*I\$/i", $value );
			if ( $i ) {
				$value = preg_replace( '/\s*I$/i', '', $value, 1 );
			}
			return $this->selectorsAttr( self::decodeid( $cap[ 4 ] ), $cap[ 5 ] ?? '-', self::unquote( $value ), (bool)$i );
		}

		throw $this->newBadSelectorException( 'Unknown Selector.' );
	}

	/**
	 * Returns true if all $func return true
	 * @param array<callable(DOMNode,array):bool> $func
	 * @return callable(DOMNode,array):bool
	 */
	private static function makeSimple( array $func ): callable {
		$l = count( $func );

		// Potentially make sure
		// `el` is truthy.
		if ( $l < 2 ) {
			return $func[ 0 ];
		}

		return static function ( $el, $opts ) use ( $l, $func ): bool {
			for ( $i = 0;  $i < $l;  $i++ ) {
				if ( !call_user_func( $func[ $i ], $el, $opts ) ) {
					return false;
				}
			}
			return true;
		};
	}

	/**
	 * Returns the element that all $func return
	 * @param array<callable(DOMNode,array):(?DOMNode)> $func
	 * @return ZestFunc
	 */
	private static function makeTest( array $func ): ZestFunc {
		if ( count( $func ) < 2 ) {
			return new ZestFunc( static function ( $el, $opts ) use ( $func ): bool {
				return (bool)call_user_func( $func[ 0 ], $el, $opts );
			} );
		}
		return new ZestFunc( static function ( $el, $opts ) use ( $func ): bool {
			$i = count( $func );
			while ( $i-- ) {
				if ( !( $el = call_user_func( $func[ $i ], $el, $opts ) ) ) {
					return false;
				}
			}
			return true;
		} );
	}

	/**
	 * Return a skeleton ZestFunc for the caller to fill in.
	 * @return ZestFunc
	 */
	private function makeSubject(): ZestFunc {
		$target = null;

		$subject = new ZestFunc( function ( $el, $opts ) use ( &$subject, &$target ): bool {
			$node = $el->ownerDocument;
			$scope = $this->getElementsByTagName( $node, $subject->lname, $opts );
			$i = count( $scope );

			while ( $i-- ) {
				if ( call_user_func( $subject->test->func, $scope[$i], $opts ) && $target === $el ) {
					$target = null;
					return true;
				}
			}

			$target = null;
			return false;
		} );

		$subject->simple = static function ( $el, $opts ) use ( &$target ): bool {
			$target = $el;
			return true;
		};

		return $subject;
	}

	/**
	 * @return callable(DOMNode,array):bool
	 */
	private function compileGroup( string $sel ): callable {
		$test = $this->compile( $sel );
		$tests = [ $test ];

		while ( $test->sel ) {
			$test = $this->compile( $test->sel );
			$tests[] = $test;
		}

		if ( count( $tests ) < 2 ) {
			return $test->func;
		}

		return static function ( $el, $opts ) use ( $tests ): bool {
			for ( $i = 0, $l = count( $tests );  $i < $l;  $i++ ) {
				if ( call_user_func( $tests[ $i ]->func, $el, $opts ) ) {
					return true;
				}
			}
			return false;
		};
	}

	/**
	 * Selection
	 */

	// $node should be a DOMDocument, DOMDocumentFragment, or a DOMElement
	// These are "ParentNode" in the DOM spec.

	/**
	 * @param string $sel
	 * @param DOMDocument|DOMDocumentFragment|DOMElement $node
	 * @param array $opts
	 * @return DOMElement[]
	 */
	private function findInternal( string $sel, $node, $opts ): array {
		$results = [];
		$test = $this->compile( $sel );
		$scope = $this->getElementsByTagName( $node, $test->qname, $opts );
		$i = 0;
		$el = null;
		$needsSort = false;

		foreach ( $scope as $el ) {
			if ( call_user_func( $test->func, $el, $opts ) ) {
				$results[spl_object_id( $el )] = $el;
			}
		}

		if ( $test->sel ) {
			$needsSort = true;
			while ( $test->sel ) {
				$test = $this->compile( $test->sel );
				$scope = $this->getElementsByTagName( $node, $test->qname, $opts );
				foreach ( $scope as $el ) {
					if ( call_user_func( $test->func, $el, $opts ) ) {
						$results[spl_object_id( $el )] = $el;
					}
				}
			}
		}

		$results = array_values( $results );
		if ( $needsSort ) {
			 self::sort( $results, $this->isStandardsMode( $node, $opts ) );
		}
		return $results;
	}

	/**
	 * Find elements matching a CSS selector underneath $context.
	 *
	 * This search is exclusive; that is, `find(':scope', ...)` returns
	 * no matches, although `:scope *` would return matches.
	 *
	 * @param string $sel The CSS selector string
	 * @param DOMDocument|DOMDocumentFragment|DOMElement $context
	 *   The scoping root for the search
	 * @param array $opts Additional match-context options (optional)
	 * @return DOMElement[] Elements matching the CSS selector
	 */
	public function find( string $sel, $context, array $opts = [] ): array {
		$opts['this'] = $this;
		$opts['scope'] = $context;

		/* when context isn't a DocumentFragment and the selector is simple: */
		if ( $context->nodeType !== 11 && strpos( $sel, ' ' ) === false ) {
			// https://www.w3.org/TR/CSS21/syndata.html#value-def-identifier
			// Valid identifiers starting with a hyphen or with escape
			// sequences will be handled correctly by the fall-through case.
			if ( $sel[ 0 ] === '#' && preg_match( '/^#[A-Za-z_](?:[-A-Za-z0-9_]|[^\0-\237])*$/Su', $sel ) ) {
				// Setting 'getElementsById' to `true` disables this
				// optimization and forces the hard/slow search in
				// order to guarantee that multiple elements will be
				// returned if there are multiple elements in the
				// $context with the given id.
				if ( ( $opts['getElementsById'] ?? null ) !== true ) {
					$id = substr( $sel, 1 );
					return $this->getElementsById( $context, $id, $opts );
				}
			}
			if ( $sel[ 0 ] === '.' && preg_match( '/^\.\w+$/', $sel ) ) {
				return $this->getElementsByClassName( $context, substr( $sel, 1 ), $opts );
			}
			if ( preg_match( '/^\w+$/', $sel ) ) {
				return $this->getElementsByTagName( $context, $sel, $opts );
			}
		}
		/* do things the hard/slow way */
		return $this->findInternal( $sel, $context, $opts );
	}

	/**
	 * Determine whether an element matches the given selector.
	 *
	 * This test is inclusive; that is, `matches($el, ':scope')`
	 * returns true.
	 *
	 * @param DOMNode $el The element to be tested
	 * @param string $sel The CSS selector string
	 * @param array $opts Additional match-context options (optional)
	 * @return bool True iff the element matches the selector
	 */
	public function matches( $el, string $sel, array $opts = [] ): bool {
		$opts['this'] = $this;
		$opts['scope'] = $el;

		$test = new ZestFunc( static function ( $el, $opts ): bool {
			return true;
		} );
		$test->sel = $sel;
		do {
			$test = $this->compile( $test->sel );
			if ( call_user_func( $test->func, $el, $opts ) ) {
				return true;
			}
		} while ( $test->sel );
		return false;
	}

	/**
	 * Allow customization of the exception thrown for a bad selector.
	 * @param string $msg Description of the failure
	 * @return Throwable
	 */
	protected function newBadSelectorException( string $msg ): Throwable {
		return new InvalidArgumentException( $msg );
	}

	/**
	 * Allow subclasses to force Zest into "standards mode" (or not).
	 * The default implementation looks for a 'standardsMode' key in the
	 * option and if that is not present switches to standards mode if
	 * the ownerDocument of the given node is not a \DOMDocument.
	 * @param DOMNode $context a context node
	 * @param array $opts The zest options array pased to ::find, ::matches, etc
	 * @return bool True for standards mode, otherwise false.
	 */
	protected function isStandardsMode( $context, array $opts ): bool {
		// The $opts array can force a specific mode, if key is present
		if ( array_key_exists( 'standardsMode', $opts ) ) {
			return (bool)$opts['standardsMode'];
		}
		// Otherwise guess "not standard mode" if the node document is a
		// \DOMDocument, otherwise use standards mode.
		$doc = self::nodeIsDocument( $context ) ?
			 $context : $context->ownerDocument;
		return !( $doc instanceof DOMDocument );
	}

	/** @var ?ZestInst */
	private static $singleton = null;

	/**
	 * Create a new instance of Zest.  Custom combinators and selectors
	 * registered for each instance of Zest do not bleed
	 * over into other instances.
	 */
	public function __construct() {
		$z = self::$singleton;
		$this->selectors0 = $z ? $z->selectors0 : [];
		$this->selectors1 = $z ? $z->selectors1 : [];
		$this->operators = $z ? $z->operators : [];
		$this->combinators = $z ? $z->combinators : [];
		if ( !$z ) {
			$this->initRules();
			$this->initSelectors();
			$this->initOperators();
			$this->initCombinators();
			self::$singleton = $this;
			// Now create another instance so that backing arrays are cloned
			// @phan-suppress-next-line PhanPossiblyInfiniteRecursionSameParams
			self::$singleton = new ZestInst;
		}
	}
}
