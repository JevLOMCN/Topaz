<?php

namespace MediaWiki\Extension\Math;

use MediaWiki\Extension\Math\Hooks\HookRunner;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use StatusValue;

/**
 * Contains the driver function for the LaTeXML daemon
 *
 * @copyright 2012 Moritz Schubotz
 * @license GPL-2.0-or-later
 */
class MathLaTeXML extends MathMathML {

	/** @var string[] */
	protected $defaultAllowedRootElements = [ 'math', 'div', 'table', 'query' ];

	/** @var string settings for LaTeXML daemon */
	private $LaTeXMLSettings = '';

	public function __construct( $tex = '', $params = [], $cache = null ) {
		global $wgMathLaTeXMLUrl;
		parent::__construct( $tex, $params, $cache );
		$this->host = $wgMathLaTeXMLUrl;
		$this->setMode( MathConfig::MODE_LATEXML );
	}

	/**
	 * Converts an array with LaTeXML settings to a URL encoded String.
	 * If the argument is a string the input will be returned.
	 * Thus the function has projector properties and can be applied a second time safely.
	 * @param string|array $array
	 * @return string
	 */
	public function serializeSettings( $array ) {
		if ( !is_array( $array ) ) {
			return $array;
		}

		// removes the [1] [2]... for the unnamed subarrays since LaTeXML
		// assigns multiple values to one key e.g.
		// preload=amsmath.sty&preload=amsthm.sty&preload=amstext.sty
		$cgi_string = wfArrayToCgi( $array );
		$cgi_string = preg_replace( '|\%5B\d+\%5D|', '', $cgi_string );
		$cgi_string = preg_replace( '|&\d+=|', '&', $cgi_string );

		return $cgi_string;
	}

	/**
	 * Gets the settings for the LaTeXML daemon.
	 * @return string
	 */
	public function getLaTeXMLSettings() {
		global $wgMathDefaultLaTeXMLSetting;
		return $this->LaTeXMLSettings ?: $wgMathDefaultLaTeXMLSetting;
	}

	/**
	 * Sets the settings for the LaTeXML daemon.
	 * The settings affect only the current instance of the class.
	 * For a list of possible settings see:
	 * http://dlmf.nist.gov/LaTeXML/manual/commands/latexmlpost.xhtml
	 * An empty value indicates to use the default settings.
	 * @param string|array $settings
	 */
	public function setLaTeXMLSettings( $settings ) {
		$this->LaTeXMLSettings = $settings;
	}

	/**
	 * Calculates the HTTP POST Data for the request. Depends on the settings
	 * and the input string only.
	 * @return string HTTP POST data
	 */
	public function getPostData() {
		$tex = $this->getTex();
		if ( $this->getMathStyle() == 'inlineDisplaystyle' ) {
			// In 'inlineDisplaystyle' the old
			// texvc behavior is reproduced:
			// The equation is rendered in displaystyle
			// (texvc used $$ $tex $$ to render)
			// but the equation is not centered.
			$tex = '{\displaystyle ' . $tex . '}';
		}
		$texcmd = rawurlencode( $tex );
		$settings = $this->serializeSettings( $this->getLaTeXMLSettings() );
		$postData = $settings . '&tex=' . $texcmd;

		// There is an API-inconsistency between different versions of the LaTeXML daemon
		// some versions require the literal prefix other don't allow it.
		if ( !strpos( $this->host, '/convert' ) ) {
			$postData = preg_replace( '/&tex=/', '&tex=literal:', $postData, 1 );
		}

		LoggerFactory::getInstance( 'Math' )->debug( 'Get post data: ' . $postData );
		return $postData;
	}

	/**
	 * Does the actual web request to convert TeX to MathML.
	 * @return StatusValue
	 */
	protected function doRender(): StatusValue {
		if ( trim( $this->getTex() ) === '' ) {
			LoggerFactory::getInstance( 'Math' )->warning(
				'Rendering was requested, but no TeX string is specified.' );
			return StatusValue::newFatal( 'math_empty_tex' );
		}
		$requestStatus = $this->makeRequest();
		if ( $requestStatus->isGood() ) {
			$jsonResult = json_decode( $requestStatus->getValue() );
			if ( $jsonResult && json_last_error() === JSON_ERROR_NONE ) {
				if ( $this->isValidMathML( $jsonResult->result ) ) {
					$this->setMathml( $jsonResult->result );
					// Avoid PHP 7.1 warning from passing $this by reference
					$renderer = $this;
					( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) )
						->onMathRenderingResultRetrieved(
							$renderer, $jsonResult
						); // Enables debugging of server results
					return StatusValue::newGood();
				}

				// Do not print bad mathml. It's probably too verbose and might
				// mess up the browser output.
				LoggerFactory::getInstance( 'Math' )
					->warning( 'LaTeXML invalid MathML', [
						'post' => $this->getPostData(),
						'host' => $this->host,
						'result' => $requestStatus->getValue()
					] );
				return StatusValue::newFatal( 'math_invalidxml', $this->getModeName(), $this->host );
			}
			LoggerFactory::getInstance( 'Math' )
				->warning( 'LaTeXML invalid JSON', [
					'post' => $this->getPostData(),
					'host' => $this->host,
					'res' => $requestStatus->getValue()
				] );

			return StatusValue::newFatal( $this->getError( 'math_invalidjson', $this->getModeName(), $this->host ) );
		} else {
			return $requestStatus;
		}
	}

	/**
	 * Calculates the SVG image based on the MathML input
	 * No cache is used.
	 * @return bool
	 */
	public function calculateSvg() {
		$renderer = new MathMathML( $this->getTex() );
		$renderer->setMathml( $this->getMathml() );
		$renderer->setMode( MathConfig::MODE_LATEXML );
		$renderer->setPurge();
		$res = $renderer->render();
		if ( $res == true ) {
			$this->setSvg( $renderer->getSvg() );
		} else {
			$lastError = $renderer->getLastError();
			LoggerFactory::getInstance( 'Math' )->error(
				'Failed to convert LaTeXML-MathML to SVG:' . $lastError );
		}
		return $res;
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
		if ( $render == 'render' && ( $this->isPurge() || $this->svg == '' ) ) {
			$this->calculateSvg();
		}
		return parent::getSvg( $render );
	}

	/**
	 * @return string
	 */
	protected function getMathTableName() {
		return 'mathlatexml';
	}
}

class_alias( MathLaTeXML::class, 'MathLaTeXML' );
