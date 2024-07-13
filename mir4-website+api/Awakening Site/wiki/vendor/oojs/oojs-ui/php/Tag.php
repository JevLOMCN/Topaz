<?php

namespace OOUI;

class Tag {

	/* Properties */

	/**
	 * Tag name for this instance.
	 *
	 * @var string HTML tag name
	 */
	protected $tag = '';

	/**
	 * Attributes.
	 *
	 * @var array HTML attributes
	 */
	protected $attributes = [];

	/**
	 * Classes.
	 *
	 * @var array CSS classes
	 */
	protected $classes = [];

	/**
	 * Content.
	 *
	 * @var array Content text and elements
	 */
	protected $content = [];

	/**
	 * Group.
	 *
	 * @var GroupElement|null Group element is in
	 */
	protected $elementGroup = null;

	/**
	 * Infusion support.
	 *
	 * @var bool Whether to serialize tag/element/widget state for client-side use.
	 */
	protected $infusable = false;

	/* Methods */

	/**
	 * Create element.
	 *
	 * @param string $tag HTML tag name
	 */
	public function __construct( $tag = 'div' ) {
		$this->tag = $tag;
	}

	/**
	 * Check for CSS class.
	 *
	 * @param string $class CSS class name
	 * @return bool
	 */
	public function hasClass( $class ) {
		return in_array( $class, $this->classes );
	}

	/**
	 * Add CSS classes.
	 *
	 * @param array $classes List of classes to add
	 * @return $this
	 */
	public function addClasses( array $classes ) {
		$this->classes = array_unique( array_merge( $this->classes, $classes ) );
		return $this;
	}

	/**
	 * Remove CSS classes.
	 *
	 * @param array $classes List of classes to remove
	 * @return $this
	 */
	public function removeClasses( array $classes ) {
		$this->classes = array_diff( $this->classes, $classes );
		return $this;
	}

	/**
	 * Toggle CSS classes.
	 *
	 * @param array $classes List of classes to add
	 * @param bool|null $toggle Add classes
	 * @return $this
	 */
	public function toggleClasses( array $classes, $toggle = null ) {
		if ( $toggle === null ) {
			$this->classes = array_diff(
				array_merge( $this->classes, $classes ),
				array_intersect( $this->classes, $classes )
			);
		} elseif ( $toggle ) {
			$this->classes = array_merge( $this->classes, $classes );
		} else {
			$this->classes = array_diff( $this->classes, $classes );
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTag() {
		return $this->tag;
	}

	/**
	 * Get HTML attribute value.
	 *
	 * @param string $key HTML attribute name
	 * @return string|null
	 */
	public function getAttribute( $key ) {
		return $this->attributes[$key] ?? null;
	}

	/**
	 * Add HTML attributes.
	 *
	 * @param array $attributes List of attribute key/value pairs to add
	 * @return $this
	 */
	public function setAttributes( array $attributes ) {
		foreach ( $attributes as $key => $value ) {
			$this->attributes[$key] = $value;
		}
		return $this;
	}

	/**
	 * Set value of input element ('value' attribute for most, element content for textarea).
	 *
	 * @param mixed $value New value should usually be a string
	 * @return $this
	 */
	public function setValue( $value ) {
		if ( strtolower( $this->tag ) === 'textarea' ) {
			$this->clearContent();
			$this->appendContent( $value );
		} else {
			$this->setAttributes( [ 'value' => $value ] );
		}
		return $this;
	}

	/**
	 * Remove HTML attributes.
	 *
	 * @param array $keys List of attribute keys to remove
	 * @return $this
	 */
	public function removeAttributes( array $keys ) {
		foreach ( $keys as $key ) {
			unset( $this->attributes[$key] );
		}
		return $this;
	}

	/**
	 * Remove any items that match by reference
	 *
	 * String items should never match by reference
	 * so will not be removed.
	 *
	 * @param string|Tag|HtmlSnippet|array ...$content Content to remove.
	 * @return $this
	 */
	public function removeContent( ...$content ) {
		if ( isset( $content[0] ) && is_array( $content[0] ) ) {
			return $this->removeContent( ...$content[0] );
		}
		foreach ( $content as $i => $item ) {
			if ( !is_int( $i ) ) {
				throw new \AssertionError( '$content cannot have string keys' );
			}
			if ( !is_string( $item ) ) {
				// Use strict type comparions so we don't
				// compare objects with existing strings
				$index = array_search( $item, $this->content, true );
				if ( $index !== false ) {
					array_splice( $this->content, $index, 1 );
				}
			}
		}
		return $this;
	}

	/**
	 * Add content to the end. Strings will be HTML-escaped, use HtmlSnippet to prevent that.
	 *
	 * Accepts either variadic arguments (the $content argument can be repeated any number of times)
	 * or an array of arguments.
	 *
	 * For example, these uses are valid:
	 * * $tag->appendContent( [ $element1, $element2 ] );
	 * * $tag->appendContent( $element1, $element2 );
	 * This, however, is not acceptable
	 * * $tag->appendContent( [ $element1, $element2 ], $element3 );
	 *
	 * Objects that are already in $this->content will be moved
	 * to the end of the list, not duplicated.
	 *
	 * @param string|Tag|HtmlSnippet|array ...$content Content to append
	 * @return $this
	 */
	public function appendContent( ...$content ) {
		if ( isset( $content[0] ) && is_array( $content[0] ) ) {
			return $this->appendContent( ...$content[0] );
		}
		$this->removeContent( ...$content );
		$this->content = array_merge( $this->content, $content );
		return $this;
	}

	/**
	 * Add content to the beginning.
	 *
	 * Accepts either variadic arguments (the $content argument can be repeated any number of times)
	 * or an array of arguments.
	 *
	 * Objects that are already in $this->content will be moved
	 * to the end of the list, not duplicated.
	 *
	 * For example, these uses are valid:
	 * * $tag->prependContent( [ $element1, $element2 ] );
	 * * $tag->prependContent( $element1, $element2 );
	 * This, however, is not acceptable
	 * * $tag->prependContent( [ $element1, $element2 ], $element3 );
	 *
	 * @param string|Tag|HtmlSnippet|array ...$content Content to prepend. Strings will be HTML-escaped
	 *   for output, use a HtmlSnippet instance to prevent that.
	 * @return $this
	 */
	public function prependContent( ...$content ) {
		if ( isset( $content[0] ) && is_array( $content[0] ) ) {
			return $this->prependContent( ...$content[0] );
		}
		$this->removeContent( ...$content );
		array_splice( $this->content, 0, 0, $content );
		return $this;
	}

	/**
	 * Remove all content.
	 *
	 * @return $this
	 */
	public function clearContent() {
		$this->content = [];
		return $this;
	}

	/**
	 * Get group element is in.
	 *
	 * @return GroupElement|null Group element, null if none
	 */
	public function getElementGroup() {
		return $this->elementGroup;
	}

	/**
	 * Set group element is in.
	 *
	 * @param GroupElement|null $group Group element, null if none
	 * @return $this
	 */
	public function setElementGroup( $group ) {
		$this->elementGroup = $group;
		return $this;
	}

	/**
	 * Enable widget for client-side infusion.
	 *
	 * @param bool $infusable True to allow tag/element/widget to be referenced client-side.
	 * @return $this
	 */
	public function setInfusable( $infusable ) {
		$this->infusable = $infusable;
		return $this;
	}

	/**
	 * Get client-side infusability.
	 *
	 * @return bool If this tag/element/widget can be referenced client-side.
	 */
	public function isInfusable() {
		return $this->infusable;
	}

	/** @var int */
	private static $elementId = 0;

	/**
	 * Generate a unique ID for element
	 *
	 * @return string ID
	 */
	public static function generateElementId() {
		self::$elementId++;
		return 'ooui-php-' . self::$elementId;
	}

	/**
	 * Reset the unique ID, for consistent test output.
	 * Warning: Do not use this function outside of tests!
	 *
	 * @see https://phabricator.wikimedia.org/T345515
	 */
	public static function resetElementId() {
		self::$elementId = 0;
	}

	/**
	 * Ensure that this given Tag is infusable and has a unique `id`
	 * attribute.
	 * @return $this
	 */
	public function ensureInfusableId() {
		$this->setInfusable( true );
		if ( $this->getAttribute( 'id' ) === null ) {
			$this->setAttributes( [ 'id' => self::generateElementId() ] );
		}
		return $this;
	}

	/**
	 * Return an augmented `attributes` array, including synthetic attributes
	 * which are created from other properties (like the `classes` array)
	 * but which shouldn't be retained in the user-visible `attributes`.
	 * @return string[]
	 */
	protected function getGeneratedAttributes() {
		// Copy attributes, add `class` attribute from `$this->classes` array.
		$attributesArray = $this->attributes;
		if ( $this->classes ) {
			$attributesArray['class'] = implode( ' ', array_unique( $this->classes ) );
		}
		if ( $this->infusable ) {
			// Indicate that this is "just" a tag (not a widget)
			$attributesArray['data-ooui'] = json_encode( [ '_' => 'Tag' ] );
		}
		return $attributesArray;
	}

	/**
	 * Check whether the string $haystack begins with the string $needle.
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return bool True if $haystack begins with $needle, false otherwise.
	 */
	private static function stringStartsWith( $haystack, $needle ) {
		return strncmp( $haystack, $needle, strlen( $needle ) ) === 0;
	}

	/**
	 * Check whether user-supplied URL is safe, that is, whether outputting it will not result in XSS
	 * vulnerability. (Note that URLs must be HTML-escaped regardless of this check.)
	 *
	 * The point is to disallow 'javascript:' URLs (there are no good reasons to ever use them
	 * anyway), but there's no good way to prohibit them because of very lax parsing in browsers.
	 *
	 * An URL is safe if:
	 *
	 *  - it is empty, or
	 *  - it starts with an allowed protocol, followed by a colon (absolute URL), or
	 *  - it starts with two slashes `//` (protocol-relative URL), or
	 *  - it starts with a single slash `/`, or dot and slash `./` (relative URL), or
	 *  - it starts with a question mark `?` (replace query part in current URL), or
	 *  - it starts with a pound sign `#` (replace fragment part in current URL)
	 *
	 * Plain relative URLs (like `index.php`) are not allowed, since it's pretty much impossible to
	 * distinguish them from malformed absolute ones (again, very lax rules for parsing protocols).
	 *
	 * @param string $url URL
	 * @return bool [description]
	 */
	public static function isSafeUrl( $url ) {
		// Keep this function in sync with OO.ui.isSafeUrl
		$protocolAllowList = [
			// Sourced from MediaWiki's $wgUrlProtocols
			'bitcoin', 'ftp', 'ftps', 'geo', 'git', 'gopher', 'http', 'https', 'irc', 'ircs',
			'magnet', 'mailto', 'mms', 'news', 'nntp', 'redis', 'sftp', 'sip', 'sips', 'sms', 'ssh',
			'svn', 'tel', 'telnet', 'urn', 'worldwind', 'xmpp',
		];

		if ( $url === '' ) {
			return true;
		}

		foreach ( $protocolAllowList as $protocol ) {
			if ( self::stringStartsWith( $url, $protocol . ':' ) ) {
				return true;
			}
		}

		// This matches '//' too
		if ( self::stringStartsWith( $url, '/' ) || self::stringStartsWith( $url, './' ) ) {
			return true;
		}
		if ( self::stringStartsWith( $url, '?' ) || self::stringStartsWith( $url, '#' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Render element into HTML.
	 * @return string HTML serialization
	 */
	public function toString() {
		// List of void elements from HTML5, section 8.1.2 as of 2016-09-19
		static $voidElements = [
			'area',
			'base',
			'br',
			'col',
			'embed',
			'hr',
			'img',
			'input',
			'keygen',
			'link',
			'meta',
			'param',
			'source',
			'track',
			'wbr',
		];

		$attributes = '';
		foreach ( $this->getGeneratedAttributes() as $key => $value ) {
			if ( !preg_match( '/^[0-9a-zA-Z-]+$/', $key ) ) {
				throw new Exception( 'Attribute name must consist of only ASCII letters, numbers and dash' );
			}

			// Note that this is not a complete list of HTML attributes that need this validation.
			// We only check for the ones that are generated by built-in OOUI PHP elements.
			if ( $key === 'href' || $key === 'action' ) {
				if ( !self::isSafeUrl( $value ) ) {
					// We can't tell for sure whether this URL is safe (although it might be). The only safe
					// URLs that we can't check for is relative ones, so just prefix with './'. This should
					// change nothing for relative URLs, and it will neutralize sneaky 'javascript:' URLs.
					$value = './' . $value;
				}
			}

			// Use single-quotes around the attribute value in HTML, because
			// some of the values might be JSON strings
			// 1. Encode both single and double quotes (and other special chars)
			$value = htmlspecialchars( $value ?? '', ENT_QUOTES );
			// 2. Decode double quotes, for readability.
			$value = str_replace( '&quot;', '"', $value );
			// 3. Wrap attribute value in single quotes in the HTML.
			$attributes .= ' ' . $key . "='" . $value . "'";
		}

		// Content
		$content = '';
		foreach ( $this->content as $part ) {
			if ( $part instanceof Tag || $part instanceof HtmlSnippet ) {
				$content .= (string)$part;
			} else {
				$content .= htmlspecialchars( (string)$part );
			}
		}

		if ( !preg_match( '/^[0-9a-zA-Z]+$/', $this->tag ) ) {
			throw new Exception( 'Tag name must consist of only ASCII letters and numbers' );
		}

		// Tag
		if ( !$content && in_array( $this->tag, $voidElements ) ) {
			return '<' . $this->tag . $attributes . ' />';
		} else {
			return '<' . $this->tag . $attributes . '>' . $content . '</' . $this->tag . '>';
		}
	}

	/**
	 * Magic method implementation.
	 *
	 * It was not possible to throw an exception from within a __toString() method prior to PHP 7.4.0.
	 * Doing so will result in a fatal error.
	 * This is a wrapper around the real toString() to convert them to errors instead.
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->toString();
		} catch ( Exception $ex ) {
			trigger_error( (string)$ex, E_USER_ERROR );
		}
	}
}
