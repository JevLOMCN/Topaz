<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2012 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz,
 * and other MediaWiki contributors
 * @license GPL-2.0-or-later
 *
 * @file
 */

namespace MediaWiki\Extension\Math;

use MediaWiki\Extension\Math\InputCheck\BaseChecker;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use Message;
use Parser;
use Psr\Log\LoggerInterface;
use RequestContext;
use StringUtils;
use WANObjectCache;

/**
 * Abstract base class with static methods for rendering the <math> tags using
 * different technologies. These static methods create a new instance of the
 * extending classes and render the math tags based on the mode setting of the user.
 * Furthermore, this class handles the caching of the rendered output.
 *
 * @author Tomasz Wegrzanowski
 * @author Brion Vibber
 * @author Moritz Schubotz
 */
abstract class MathRenderer {

	// REPRESENTATIONS OF THE MATHEMATICAL CONTENT
	/** @var ?string tex representation */
	protected $tex = '';
	/** @var string MathML content and presentation */
	protected $mathml = '';
	/** @var string SVG layout only (no semantics) */
	protected $svg = '';
	/** @var string the original user input string (which was used to calculate the inputhash) */
	protected $userInputTex = '';
	// FURTHER PROPERTIES OF THE MATHEMATICAL CONTENT
	/** @var string ('inlineDisplaystyle'|'display'|'inline'|'linebreak') the rendering style */
	protected $mathStyle = 'inlineDisplaystyle';
	/** @var array with userdefined parameters passed to the extension (not used) */
	protected $params = [];
	/** @var string a userdefined identifier to link to the equation. */
	protected $id = '';

	// STATE OF THE CLASS INSTANCE
	/** @var bool has variable tex been security-checked */
	protected $texSecure = false;
	/** @var bool has the mathematical content changed */
	protected $changed = false;
	/** @var bool is there a database entry for the mathematical content */
	protected $storedInCache = null;
	/** @var bool is there a request to purge the existing mathematical content */
	protected $purge = false;
	/** @var string with last occurred error */
	protected $lastError = '';
	/** @var string binary packed inputhash */
	protected $inputHash = '';
	/** @var string rendering mode */
	protected $mode = MathConfig::MODE_MATHML;
	/** @var string input type */
	protected $inputType = 'tex';
	/** @var MathRestbaseInterface used for checking */
	protected $rbi;
	/** @var array with rendering warnings */
	protected $warnings;
	/** @var LoggerInterface */
	private $logger;

	private WANObjectCache $cache;

	/**
	 * Constructs a base MathRenderer
	 *
	 * @param string $tex (optional) LaTeX markup
	 * @param array $params (optional) HTML attributes
	 * @param WANObjectCache|null $cache (optional)
	 */
	public function __construct( string $tex = '', $params = [], $cache = null ) {
		$this->cache = $cache ?? MediaWikiServices::getInstance()->getMainWANObjectCache();
		$this->params = $params;
		if ( isset( $params['id'] ) ) {
			$this->id = $params['id'];
		}
		if ( isset( $params['display'] ) ) {
			$layoutMode = $params['display'];
			if ( $layoutMode == 'block' ) {
				$this->mathStyle = 'display';
				$tex = '{\displaystyle ' . $tex . '}';
				$this->inputType = 'tex';
			} elseif ( $layoutMode == 'inline' ) {
				$this->mathStyle = 'inline';
				$this->inputType = 'inline-tex';
				$tex = '{\textstyle ' . $tex . '}';
			} elseif ( $layoutMode == 'linebreak' ) {
				$this->mathStyle = 'linebreak';
				$tex = '\[ ' . $tex . ' \]';
			}
		}
		// TODO: Implement caching for attributes of the math tag
		// Currently the key for the database entry relating to an equation
		// is md5($tex) the new option to determine if the tex input
		// is rendered in displaystyle or textstyle would require a database
		// layout change to use a composite key e.g. (md5($tex),$mathStyle).
		// As a workaround we use the prefix \displaystyle so that the key becomes
		// md5((\{\\displaystyle|\{\\textstyle)?\s?$tex\}?)
		// The new value of $tex string describes now how the rendering should look like.
		// The variable MathRenderer::mathStyle determines if the rendered equation should
		// be centered in a new line, or just in be displayed in the current line.
		$this->userInputTex = $tex;
		$this->tex = $tex;
		$this->logger = LoggerFactory::getInstance( 'Math' );
	}

	/**
	 * Static factory method for getting a renderer based on mode
	 *
	 * @deprecated since 3.0.0. Use Math.RendererFactory service instead.
	 * @param string $tex LaTeX markup
	 * @param array $params HTML attributes
	 * @param string $mode indicating rendering mode
	 * @return self appropriate renderer for mode
	 */
	public static function getRenderer( $tex, $params = [], $mode = MathConfig::MODE_MATHML ) {
		return MediaWikiServices::getInstance()
			->get( 'Math.RendererFactory' )
			->getRenderer( $tex, $params, $mode );
	}

	/**
	 * Performs the rendering
	 *
	 * @return bool if rendering was successful.
	 */
	abstract public function render();

	/**
	 * @return string Html output that is embedded in the page
	 */
	abstract public function getHtmlOutput();

	/**
	 * texvc error messages
	 * TODO: update to MathML
	 * Returns an internationalized HTML error string
	 *
	 * @param string $msg message key for specific error
	 * @param string ...$parameters zero or more message
	 *  parameters for specific error
	 *
	 * @return string HTML error string
	 */
	public function getError( $msg, ...$parameters ) {
		$mf = wfMessage( 'math_failure' )->inContentLanguage()->escaped();
		$errmsg = wfMessage( $msg, $parameters )->inContentLanguage()->escaped();
		$source = htmlspecialchars( str_replace( "\n", ' ', $this->tex ) );
		return "<strong class=\"error texerror\">$mf ($errmsg): $source</strong>\n";
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getInputHash(): string {
		if ( !$this->inputHash ) {
			$this->inputHash = hash( 'md5', // xxh128 might be better when dropping php 7 support
				$this->mode .
				$this->userInputTex .
				implode( $this->params )
			);
		}
		return $this->inputHash;
	}

	/**
	 * Reads rendering data from database
	 *
	 * @return bool true if read successfully, false otherwise
	 */
	public function readFromCache(): bool {
		$rpage = $this->cache->get( $this->getCacheKey() );
		if ( $rpage !== false ) {
			$this->initializeFromCache( $rpage );
			$this->storedInCache = true;
			return true;
		} else {
			# Missing from the database and/or the render cache
			$this->storedInCache = false;
			return false;
		}
	}

	/**
	 * @return array with the database column names
	 */
	protected function dbInArray() {
		$in = [ 'math_inputhash',
			'math_mathml',
			'math_inputtex',
			'math_tex',
			'math_svg'
		];
		return $in;
	}

	/**
	 * Reads the values from the database but does not overwrite set values with empty values
	 * @param array $rpage (a database row)
	 */
	public function initializeFromCache( $rpage ) {
		$this->inputHash = $rpage['math_inputhash']; // MUST NOT BE NULL
		if ( isset( $rpage['math_mathml'] ) ) {
			$this->mathml = $rpage['math_mathml'];
		}
		if ( isset( $rpage['math_inputtex'] ) ) {
			$this->userInputTex = $rpage['math_inputtex'];
		}
		if ( isset( $rpage['math_tex'] ) ) {
			$this->tex = $rpage['math_tex'];
		}
		if ( isset( $rpage['math_svg'] ) ) {
			$this->svg = $rpage['math_svg'];
		}
		$this->changed = false;
	}

	/**
	 * Writes rendering entry to cache.
	 *
	 * WARNING: Use writeCache() instead of this method to be sure that all
	 * renderer specific (such as squid caching) are taken into account.
	 * This function stores the values that are currently present in the class
	 * to the cache even if they are empty.
	 *
	 * This function can be seen as protected function.
	 */
	public function writeToCache() {
		$outArray = $this->dbOutArray();
		$this->cache->set( $this->getCacheKey(), $outArray );
	}

	/**
	 * Gets an array that matches the variables of the class to the database columns
	 * @return array
	 */
	protected function dbOutArray() {
		$out = [
			'math_inputhash' => $this->getInputHash(),
			'math_mathml' => $this->mathml,
			'math_inputtex' => $this->userInputTex,
			'math_tex' => $this->tex,
			'math_svg' => $this->svg,
			'math_mode' => $this->mode
		];
		return $out;
	}

	/**
	 * @param MathRestbaseInterface $param
	 */
	public function setRestbaseInterface( $param ) {
		$this->rbi = $param;
		$this->rbi->setPurge( $this->isPurge() );
	}

	public function hasWarnings() {
		if ( is_array( $this->warnings ) && count( $this->warnings ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Adds tracking categories to the parser
	 *
	 * @param Parser $parser
	 */
	public function addTrackingCategories( $parser ) {
		if ( !$this->checkTeX() ) {
			$parser->addTrackingCategory( 'math-tracking-category-error' );
		}
		if ( $this->lastError ) {
			// Add a tracking category specialized on render errors.
			$parser->addTrackingCategory( 'math-tracking-category-render-error' );
		}
	}

	protected function getChecker(): BaseChecker {
		return Math::getCheckerFactory()
			->newDefaultChecker( $this->tex, $this->getInputType(), $this->rbi, $this->isPurge() );
	}

	/**
	 * Returns sanitized attributes
	 *
	 * @param string $tag element name
	 * @param array $defaults default attributes
	 * @param array $overrides attributes to override defaults
	 * @return array HTML attributes
	 */
	protected function getAttributes( $tag, $defaults = [], $overrides = [] ) {
		$attribs = Sanitizer::validateTagAttributes( $this->params, $tag );
		$attribs = Sanitizer::mergeAttributes( $defaults, $attribs );
		return Sanitizer::mergeAttributes( $attribs, $overrides );
	}

	/**
	 * Writes cache. Writes the database entry if values were changed
	 * @return bool
	 */
	public function writeCache() {
		$this->debug( 'Writing of cache requested' );
		if ( $this->isChanged() ) {
			$this->debug( 'Change detected. Perform writing' );
			$this->writeToCache();
			return true;
		} else {
			$this->debug( "Nothing was changed. Don't write to database" );
			return false;
		}
	}

	/**
	 * Gets TeX markup
	 *
	 * @return string TeX markup
	 */
	public function getTex() {
		return $this->tex;
	}

	/**
	 * Gets the rendering mode
	 *
	 * @return string
	 */
	public function getMode() {
		return $this->mode;
	}

	/**
	 * Sets the rendering mode
	 * @param string $newMode element of the array $wgMathValidModes
	 * @return bool
	 */
	public function setMode( $newMode ) {
		if ( Math::getMathConfig()->isValidRenderingMode( $newMode ) ) {
			$this->mode = $newMode;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Sets the TeX code
	 *
	 * @param ?string $tex
	 */
	public function setTex( ?string $tex ) {
		if ( $this->tex != $tex ) {
			$this->changed = true;
			$this->tex = $tex;
		}
	}

	/**
	 * Gets the MathML XML element
	 * @return string in UTF-8 encoding
	 */
	public function getMathml() {
		if ( !StringUtils::isUtf8( $this->mathml ) ) {
			$this->setMathml( '' );
		}
		return $this->mathml;
	}

	/**
	 * @param string $mathml use UTF-8 encoding
	 */
	public function setMathml( $mathml ) {
		$this->changed = true;
		$this->mathml = $mathml;
	}

	/**
	 * Get the attributes of the math tag
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * @param array $params
	 */
	public function setParams( $params ) {
		// $changed is not set to true here, because the attributes do not affect
		// the rendering in the current implementation.
		// If this behavior will change in the future $this->tex is no longer a
		// primary key and the input hash cannot be calculate form $this->tex
		// only. See the discussion 'Tag extensions in Block mode' on wikitech-l.
		$this->params = $params;
	}

	/**
	 * Checks if the instance was modified i.e., because math was rendered
	 *
	 * @return bool true if something was changed false otherwise
	 */
	public function isChanged() {
		return $this->changed;
	}

	/**
	 * Checks if there is an explicit user request to rerender the math-tag.
	 * @return bool purge state
	 */
	public function isPurge() {
		if ( $this->purge ) {
			return true;
		}
		$refererHeader = RequestContext::getMain()->getRequest()->getHeader( 'REFERER' );
		if ( $refererHeader ) {
			$url = parse_url( $refererHeader, PHP_URL_QUERY );
			if ( !is_string( $url ) ) {
				return false;
			}
			parse_str( $url, $refererParam );
			if ( isset( $refererParam['action'] ) && $refererParam['action'] === 'purge' ) {
				$this->logger->debug( 'Re-Rendering on user request' );
				return true;
			}
		}
		return false;
	}

	/**
	 * Sets purge. If set to true the render is forced to rerender and must not
	 * use a cached version.
	 * @param bool $purge
	 */
	public function setPurge( $purge = true ) {
		$this->changed = true;
		$this->purge = $purge;
	}

	public function getLastError() {
		return $this->lastError;
	}

	/**
	 * @param string $mathStyle ('inlineDisplaystyle'|'display'|'inline')
	 */
	public function setMathStyle( $mathStyle = 'display' ) {
		if ( $this->mathStyle !== $mathStyle ) {
			$this->mathStyle = $mathStyle;
			$this->changed = true;
			$this->inputType = $mathStyle === 'inline' ? 'inline-tex' : 'tex';
		}
	}

	/**
	 * Returns the value of the DisplayStyle attribute
	 * @return string ('inlineDisplaystyle'|'display'|'inline'|'linebreak') the DisplayStyle
	 */
	public function getMathStyle() {
		return $this->mathStyle;
	}

	/**
	 * Get if the input tex was marked as secure
	 * @return bool
	 */
	public function isTexSecure() {
		return $this->texSecure;
	}

	/**
	 * @return bool
	 */
	public function checkTeX() {
		if ( $this->texSecure ) {
			// equation was already checked
			return true;
		}
		$texCheckDisabled = MediaWikiServices::getInstance()
			->get( 'Math.Config' )
			->texCheckDisabled();
		if ( $texCheckDisabled === MathConfig::ALWAYS ) {
			// checking is disabled
			$this->debug( 'Skip TeX check ' );
			return true;
		} else {
			if ( $texCheckDisabled === MathConfig::NEW && $this->mode != MathConfig::MODE_SOURCE ) {
				if ( $this->readFromCache() ) {
					$this->debug( 'Skip TeX check' );
					$this->texSecure = true;
					return true;
				}
			}
			$this->debug( 'Perform TeX check' );
			return $this->doCheck();
		}
	}

	public function isInDatabase() {
		if ( $this->storedInCache === null ) {
			$this->readFromCache();
		}
		return $this->storedInCache;
	}

	/**
	 * @return string TeX the original tex string specified by the user
	 */
	public function getUserInputTex() {
		return $this->userInputTex;
	}

	/**
	 * @return string user defined ID
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * @param string $id user defined ID
	 */
	public function setID( $id ) {
		// Changes in the ID affect the container for the math element on the current page
		// only. Therefore an id change does not affect the $this->changed variable, which
		// indicates if database relevant fields have been changed.
		$this->id = $id;
	}

	/**
	 * @param string $svg
	 */
	public function setSvg( $svg ) {
		$this->changed = true;
		$this->svg = trim( $svg );
	}

	/**
	 * Gets the SVG image
	 *
	 * @param string $render if set to 'render' (default) and no SVG image exists, the function
	 *                       tries to generate it on the fly.
	 *                       Otherwise, if set to 'cached', and there is no SVG in the database
	 *                       cache, an empty string is returned.
	 *
	 * @return string XML-Document of the rendered SVG
	 */
	public function getSvg( $render = 'render' ) {
		// Spaces will prevent the image from being displayed correctly in the browser
		if ( !$this->svg && $this->rbi ) {
			$this->svg = $this->rbi->getSvg();
		}
		return trim( $this->svg );
	}

	/**
	 * @return string
	 */
	abstract protected function getMathTableName();

	protected function getModeName(): Message {
		return MediaWikiServices::getInstance()
			->get( 'Math.Config' )
			->getRenderingModeName( $this->getMode() );
	}

	public function setInputType( string $inputType ) {
		$this->inputType = $inputType;
	}

	public function getInputType(): string {
		return $this->inputType;
	}

	protected function doCheck(): bool {
		$checker = $this->getChecker();

		if ( $checker->isValid() ) {
			$this->setTex( $checker->getValidTex() );
			$this->texSecure = true;
			return true;
		}

		$checkerError = $checker->getError();
		$this->lastError = $checkerError === null ?
			$this->getError( 'math_unknown_error' ) :
			$this->getError( $checkerError->getKey(), ...$checkerError->getParams() );
		return false;
	}

	protected function debug( $msg ) {
		$this->logger->debug( "$msg for \"{tex}\".", [ 'tex' => $this->userInputTex ] );
	}

	private function getCacheKey() {
			return $this->cache->makeGlobalKey(
				self::class,
				$this->getInputHash()
			);
	}
}
