<?php

namespace OOUI;

/**
 * DOM element abstraction.
 *
 * @abstract
 */
class Element extends Tag {

	/* Static Properties */

	/**
	 * HTML tag name.
	 *
	 * This may be ignored if getTagName() is overridden.
	 *
	 * @var string
	 */
	public static $tagName = 'div';

	/**
	 * Default text direction, used for some layout calculations. Use setDefaultDir() to change.
	 *
	 * Currently only per-document directionality is supported.
	 *
	 * @var string
	 */
	public static $defaultDir = 'ltr';

	/* Properties */

	/**
	 * Element data.
	 *
	 * @var mixed
	 */
	protected $data = null;

	/**
	 * @var bool
	 */
	protected $visible = true;

	/**
	 * Strings of the CSS classes explicitly configured for this element (as opposed to #$classes,
	 * which contains all classes for this element).
	 *
	 * @var array
	 */
	protected $ownClasses = [];

	/**
	 * @var callable[]
	 */
	protected $configCallbacks = [];

	/* Static methods */

	/**
	 * Emits a deprecation warning with provided message.
	 *
	 * @param string $message Message about the deprecation
	 */
	public static function warnDeprecation( $message = '' ) {
		trigger_error( $message, E_USER_DEPRECATED );
	}

	/* Methods */

	/**
	 * @param array $config Configuration options
	 *      - string[] $config['classes'] CSS class names to add
	 *      - string $config['id'] HTML id attribute
	 *      - string $config['text'] Text to insert
	 *      - string[]|HtmlSnippet[]|Element[] $config['content'] Content to append (after text).
	 *          Strings will be HTML-escaped for output, use an HtmlSnippet instance to prevent that.
	 *      - mixed $config['data'] Element data
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $this->getTagName() );

		// Initialization
		if ( isset( $config['infusable'] ) && is_bool( $config['infusable'] ) ) {
			$this->setInfusable( $config['infusable'] );
		}
		if ( isset( $config['data'] ) ) {
			$this->setData( $config['data'] );
		}
		if ( isset( $config['classes'] ) && is_array( $config['classes'] ) ) {
			$this->ownClasses = $config['classes'];
			$this->addClasses( $this->ownClasses );
		}
		if ( isset( $config['id'] ) ) {
			$this->setAttributes( [ 'id' => $config['id'] ] );
		}
		if ( isset( $config['text'] ) ) {
			// JS compatibility
			$this->appendContent( $config['text'] );
		}
		if ( isset( $config['content'] ) ) {
			$this->appendContent( $config['content'] );
		}
	}

	/**
	 * Get the HTML tag name.
	 *
	 * Override this method to base the result on instance information.
	 *
	 * @return string HTML tag name
	 */
	public function getTagName() {
		return $this::$tagName;
	}

	/**
	 * Toggle visibility of an element.
	 *
	 * @param bool|null $show Make element visible, omit to toggle visibility
	 * @return $this
	 */
	public function toggle( $show = null ) {
		$show = $show === null ? !$this->visible : $show;

		$this->visible = $show;
		$this->toggleClasses( [ 'oo-ui-element-hidden' ], !$this->visible );

		return $this;
	}

	/**
	 * Get element data.
	 *
	 * @return mixed Element data
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Set element data.
	 *
	 * @param mixed $data Element data
	 * @return $this
	 */
	public function setData( $data ) {
		$this->data = $data;
		return $this;
	}

	/**
	 * Check if element supports one or more methods.
	 *
	 * @param string|string[] $methods Method or list of methods to check
	 * @return bool All methods are supported
	 */
	public function supports( $methods ) {
		foreach ( (array)$methods as $method ) {
			if ( !method_exists( $this, $method ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Register an additional function to call when building the config. See ::getConfig().
	 *
	 * @param callable $func The function. Parameters and return value are the same as ::getConfig().
	 */
	public function registerConfigCallback( callable $func ) {
		$this->configCallbacks[] = $func;
	}

	/**
	 * Add the necessary properties to the given `$config` array to allow
	 * reconstruction of this widget via its constructor.
	 * @param array &$config An array which will be mutated to add the necessary configuration
	 *   properties.  Unless you are implementing a subclass, you should
	 *   always pass a new empty array `[]`.
	 * @return array A configuration array which can be passed to this object's
	 *   constructor to recreate it.  This is a return value to allow
	 *   the safe use of copy-by-value functions like `array_merge` in
	 *   the implementation.
	 */
	public function getConfig( &$config ) {
		// If there are traits, add their config
		foreach ( $this->configCallbacks as $func ) {
			$func( $config );
		}

		if ( $this->data !== null ) {
			$config['data'] = $this->data;
		}
		if ( $this->ownClasses !== [] ) {
			$config['classes'] = $this->ownClasses;
		}
		return $config;
	}

	/**
	 * Create a modified version of the configuration array suitable for
	 * JSON serialization by replacing `Tag` references and
	 * `HtmlSnippet`s.
	 *
	 * @return array A serialized configuration array.
	 */
	private function getSerializedConfig() {
		// Ensure that '_' comes first in the output.
		$config = [ '_' => true ];
		$config = $this->getConfig( $config );
		// Post-process config array to turn Tag references into ID references
		// and HtmlSnippet references into a { html: 'string' } JSON form.
		$replaceElements = static function ( &$item ) {
			if ( $item instanceof Tag ) {
				$item->ensureInfusableId();
				$item = [ 'tag' => $item->getAttribute( 'id' ) ];
			} elseif ( $item instanceof HtmlSnippet ) {
				$item = [ 'html' => (string)$item ];
			}
		};
		array_walk_recursive( $config, $replaceElements );
		// Set '_' last to ensure that subclasses can't accidentally step on it.
		$config['_'] = $this->getJavaScriptClassName();
		return $config;
	}

	/**
	 * The class name of the JavaScript version of this widget
	 * @return string
	 */
	protected function getJavaScriptClassName() {
		return str_replace( 'OOUI\\', 'OO.ui.', get_class( $this ) );
	}

	/**
	 * @return string[]
	 */
	protected function getGeneratedAttributes() {
		$attributesArray = parent::getGeneratedAttributes();
		// Add `data-ooui` attribute from serialized config array.
		if ( $this->infusable ) {
			$serialized = $this->getSerializedConfig();
			$attributesArray['data-ooui'] = json_encode( $serialized );
		}
		return $attributesArray;
	}

	/**
	 * Render element into HTML.
	 *
	 * @return string HTML serialization
	 */
	public function toString() {
		Theme::singleton()->updateElementClasses( $this );
		if ( $this->isInfusable() ) {
			$this->ensureInfusableId();
		}
		return parent::toString();
	}

	/**
	 * Get the direction of the user interface for a given element.
	 *
	 * Currently only per-document directionality is supported.
	 *
	 * @param Tag $element Element to check
	 * @return string Text direction, either 'ltr' or 'rtl'
	 */
	public static function getDir( Tag $element ) {
		return self::$defaultDir;
	}

	/**
	 * Set the default direction of the user interface.
	 *
	 * @param string $dir Text direction, either 'ltr' or 'rtl'
	 */
	public static function setDefaultDir( $dir ) {
		self::$defaultDir = $dir === 'rtl' ? 'rtl' : 'ltr';
	}

	/**
	 * A helper method to massage an array of HTML attributes into a format that is more likely to
	 * work with an OOUI PHP element, camel-casing attribute names and setting values of boolean
	 * ones to true. Intended as a convenience to be used when refactoring legacy systems using HTML
	 * to use OOUI.
	 *
	 * @param array $attrs HTML attributes, e.g. `[ 'disabled' => '', 'accesskey' => 'k' ]`
	 * @return array OOUI PHP element config, e.g. `[ 'disabled' => true, 'accessKey' => 'k' ]`
	 */
	public static function configFromHtmlAttributes( array $attrs ) {
		$booleanAttrs = [
			'disabled' => true,
			'required' => true,
			'autofocus' => true,
			'multiple' => true,
			'readonly' => true,
		];
		$attributeToConfig = [
			'maxlength' => 'maxLength',
			'minlength' => 'minLength',
			'readonly' => 'readOnly',
			'tabindex' => 'tabIndex',
			'accesskey' => 'accessKey',
		];
		$config = [];
		foreach ( $attrs as $key => $value ) {
			if ( isset( $booleanAttrs[$key] ) && $value !== false && $value !== null ) {
				$value = true;
			}
			if ( isset( $attributeToConfig[$key] ) ) {
				$key = $attributeToConfig[$key];
			}
			$config[$key] = $value;
		}
		return $config;
	}
}
