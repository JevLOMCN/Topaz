<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use MediaWiki\Extension\Math\Hooks\HookRunner;
use MediaWiki\Html\Html;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use StatusValue;
use stdClass;
use Throwable;
use Xml;
use XmlTypeCheck;

/**
 * Converts LaTeX to MathML using the mathoid-server
 */
class MathMathML extends MathRenderer {

	/** @var string[] */
	protected $defaultAllowedRootElements = [ 'math' ];
	/** @var string[] */
	protected $restbaseInputTypes = [ 'tex', 'inline-tex', 'chem' ];
	/** @var string[] */
	protected $restbaseRenderingModes = [ MathConfig::MODE_MATHML ];
	/** @var string[] */
	protected $allowedRootElements = [];
	/** @var string */
	protected $host;

	/** @var LoggerInterface */
	protected $logger;

	/** @var bool if false MathML output is not validated */
	private $XMLValidation = true;

	/**
	 * @var string|bool
	 */
	private $svgPath = false;

	/** @var string|null */
	private $mathoidStyle;

	public function __construct( string $tex = '', array $params = [], $cache = null ) {
		global $wgMathMathMLUrl;
		parent::__construct( $tex, $params, $cache );
		$this->setMode( MathConfig::MODE_MATHML );
		$this->host = $wgMathMathMLUrl;
		if ( isset( $params['type'] ) ) {
			$allowedTypes = [ 'pmml', 'ascii', 'chem' ];
			if ( in_array( $params['type'], $allowedTypes, true ) ) {
				$this->inputType = $params['type'];
			}
			if ( $params['type'] == 'pmml' ) {
				$this->setMathml( '<math>' . $tex . '</math>' );
			}
		}
		if ( !isset( $params['display'] ) && $this->getMathStyle() == 'inlineDisplaystyle' ) {
			// default preserve the (broken) layout as it was
			$this->tex = '{\\displaystyle ' . $tex . '}';
		}
		$this->logger = LoggerFactory::getInstance( 'Math' );
	}

	/**
	 * @inheritDoc
	 */
	public function addTrackingCategories( $parser ) {
		parent::addTrackingCategories( $parser );
		if ( $this->hasWarnings() ) {
			foreach ( $this->warnings as $warning ) {
				if ( isset( $warning->type ) ) {
					switch ( $warning->type ) {
						case 'mhchem-deprecation':
							$parser->addTrackingCategory( 'math-tracking-category-mhchem-deprecation' );
							break;
						case 'texvc-deprecation':
							$parser->addTrackingCategory( 'math-tracking-category-texvc-deprecation' );
					}
				}
			}
		}
	}

	/**
	 * @param MathRenderer[] $renderers
	 */
	public static function batchEvaluate( array $renderers ) {
		$rbis = [];
		foreach ( $renderers as $renderer ) {
			$rbi = new MathRestbaseInterface( $renderer->getTex(), $renderer->getInputType() );
			$renderer->setRestbaseInterface( $rbi );
			$rbis[] = $rbi;
		}
		MathRestbaseInterface::batchEvaluate( $rbis );
	}

	/**
	 * Gets the allowed root elements the rendered math tag might have.
	 *
	 * @return string[]
	 */
	public function getAllowedRootElements() {
		if ( $this->allowedRootElements ) {
			return $this->allowedRootElements;
		} else {
			return $this->defaultAllowedRootElements;
		}
	}

	/**
	 * Sets the XML validation.
	 * If set to false the output of MathML is not validated.
	 * @param bool $validation
	 */
	public function setXMLValidation( $validation = true ) {
		$this->XMLValidation = $validation;
	}

	/**
	 * Sets the allowed root elements the rendered math tag might have.
	 * An empty value indicates to use the default settings.
	 * @param string[] $settings
	 */
	public function setAllowedRootElements( $settings ) {
		$this->allowedRootElements = $settings;
	}

	public function render() {
		global $wgMathFullRestbaseURL;
		try {
			if ( in_array( $this->inputType, $this->restbaseInputTypes, true ) &&
				 in_array( $this->mode, $this->restbaseRenderingModes, true )
			) {
				if ( !$this->rbi ) {
					$this->rbi =
						new MathRestbaseInterface( $this->getTex(), $this->getInputType() );
					$this->rbi->setPurge( $this->isPurge() );
				}
				$rbi = $this->rbi;
				if ( $rbi->getSuccess() ) {
					$this->mathml = $rbi->getMathML();
					$this->mathoidStyle = $rbi->getMathoidStyle();
					$this->svgPath = $rbi->getFullSvgUrl();
					$this->warnings = $rbi->getWarnings();
				} elseif ( $this->lastError === '' ) {
					$this->doCheck();
				}
				$this->changed = false;
				return $rbi->getSuccess();
			}
			if ( $this->renderingRequired() ) {
				$renderResult = $this->doRender();
				if ( !$renderResult->isGood() ) {
					// TODO: this is a hacky hack, lastError will not exist soon.
					$renderError = $renderResult->getErrors()[0];
					$this->lastError = $this->getError( $renderError['message'], ...$renderError['params'] );
				}
				return $renderResult->isGood();
			}
			return true;
		} catch ( Throwable $e ) {
			$this->lastError = $this->getError( 'math_mathoid_error',
				$wgMathFullRestbaseURL, $e->getMessage() );
			$this->logger->error( $e->getMessage(), [ $e, $this ] );
			return false;
		}
	}

	/**
	 * Helper function to checks if the math tag must be rendered.
	 * @return bool
	 */
	private function renderingRequired() {
		if ( $this->isPurge() ) {
			$this->logger->debug( 'Rerendering was requested.' );
			return true;
		}

		$dbres = $this->isInDatabase();
		if ( $dbres ) {
			if ( $this->isValidMathML( $this->getMathml() ) ) {
				$this->logger->debug( 'Valid MathML entry found in database.' );
				if ( $this->getSvg( 'cached' ) ) {
					$this->logger->debug( 'SVG-fallback found in database.' );
					return false;
				} else {
					$this->logger->debug( 'SVG-fallback missing.' );
					return true;
				}
			} else {
				$this->logger->debug( 'Malformatted entry found in database' );
				return true;
			}
		} else {
			$this->logger->debug( 'No entry found in database.' );
			return true;
		}
	}

	/**
	 * Performs a HTTP Post request to the given host.
	 * Uses $wgMathLaTeXMLTimeout as timeout.
	 *
	 * @return StatusValue result with response body as a value
	 */
	public function makeRequest() {
		// TODO: Change the timeout mechanism.
		global $wgMathLaTeXMLTimeout;
		$post = $this->getPostData();
		$options = [ 'method' => 'POST', 'postData' => $post, 'timeout' => $wgMathLaTeXMLTimeout ];
		$req = MediaWikiServices::getInstance()->getHttpRequestFactory()->create( $this->host, $options );
		$status = $req->execute();
		if ( $status->isGood() ) {
			return StatusValue::newGood( $req->getContent() );
		} else {
			if ( $status->hasMessage( 'http-timed-out' ) ) {
				$this->logger->warning( 'Math service request timeout', [
					'post' => $post,
					'host' => $this->host,
					'timeout' => $wgMathLaTeXMLTimeout
				] );
				return StatusValue::newFatal( 'math_timeout', $this->getModeName(), $this->host );
			} else {
				$errormsg = $req->getContent();
				$this->logger->warning( 'Math service request failed', [
					'post' => $post,
					'host' => $this->host,
					'errormsg' => $errormsg
				] );
				return StatusValue::newFatal(
					'math_invalidresponse',
					$this->getModeName(),
					$this->host,
					$errormsg,
					$this->getModeName()
				);
			}
		}
	}

	/**
	 * Calculates the HTTP POST Data for the request. Depends on the settings
	 * and the input string only.
	 * @return string HTTP POST data
	 */
	public function getPostData() {
		$input = $this->getTex();
		if ( $this->inputType == 'pmml' ||
			( $this->getMode() == MathConfig::MODE_LATEXML && $this->getMathml() )
		) {
			$out = 'type=mml&q=' . rawurlencode( $this->getMathml() );
		} elseif ( $this->inputType == 'ascii' ) {
			$out = 'type=asciimath&q=' . rawurlencode( $input );
		} else {
			if ( $this->getMathStyle() === 'inlineDisplaystyle' ) {
				// default preserve the (broken) layout as it was
				$out = 'type=inline-TeX&q=' . rawurlencode( '{\\displaystyle ' . $input . '}' );
			} elseif ( $this->getMathStyle() === 'inline' ) {
				$out = 'type=inline-TeX&q=' . rawurlencode( $input );
			} else {
				$out = 'type=tex&q=' . rawurlencode( $input );
			}
		}
		$this->logger->debug( 'Get post data: ' . $out );
		return $out;
	}

	/**
	 * Does the actual web request to convert TeX to MathML.
	 *
	 * @return StatusValue
	 */
	protected function doRender(): StatusValue {
		if ( $this->isEmpty() ) {
			$this->logger->debug( 'Rendering was requested, but no TeX string is specified.' );
			return StatusValue::newFatal( 'math_empty_tex' );
		}
		$requestStatus = $this->makeRequest();
		if ( $requestStatus->isGood() ) {
			$jsonResult = json_decode( $requestStatus->getValue() );
			if ( $jsonResult && json_last_error() === JSON_ERROR_NONE ) {
				if ( $jsonResult->success ) {
					return $this->processJsonResult( $jsonResult, $this->host );
				} else {
					$serviceLog = $jsonResult->log ?? wfMessage( 'math_unknown_error' )
							->inContentLanguage()
							->escaped();
					$this->logger->warning( 'Mathoid conversion error', [
						'post' => $this->getPostData(),
						'host' => $this->host,
						'result' => $requestStatus->getValue(),
						'service_log' => $serviceLog
					] );
					return StatusValue::newFatal( 'math_mathoid_error', $this->host, $serviceLog );
				}
			} else {
				$this->logger->error( 'MathML invalid JSON', [
					'post' => $this->getPostData(),
					'host' => $this->host,
					'res' => $requestStatus->getValue(),
				] );
				return StatusValue::newFatal( 'math_invalidjson', $this->host );
			}
		} else {
			return $requestStatus;
		}
	}

	/**
	 * Checks if the input is valid MathML,
	 * and if the root element has the name math
	 * @param string $XML
	 * @return bool
	 */
	public function isValidMathML( $XML ) {
		$out = false;
		if ( !$this->XMLValidation ) {
			return true;
		}

		$xmlObject = new XmlTypeCheck( $XML, null, false );
		if ( !$xmlObject->wellFormed ) {
			$this->logger->error(
				'XML validation error: ' . var_export( $XML, true ) );
		} else {
			$name = $xmlObject->getRootElement();
			$elementSplit = explode( ':', $name );
			$localName = end( $elementSplit );
			if ( in_array( $localName, $this->getAllowedRootElements(), true ) ) {
				$out = true;
			} else {
				$this->logger->error( "Got wrong root element: $name" );
			}
		}
		return $out;
	}

	/**
	 * @param bool $noRender
	 * @return Title|string
	 */
	private function getFallbackImageUrl( $noRender = false ) {
		if ( $this->svgPath ) {
			return $this->svgPath;
		}
		return SpecialPage::getTitleFor( 'MathShowImage' )->getLocalURL( [
				'hash' => $this->getInputHash(),
				'mode' => $this->getMode(),
				'noRender' => $noRender
			]
		);
	}

	/**
	 * Helper function to correct the style information for a
	 * linked SVG image.
	 * @param string &$style current style information to be updated
	 */
	public function correctSvgStyle( &$style ) {
		if ( preg_match( '/style="([^"]*)"/', $this->getSvg(), $styles ) ) {
			$style .= ' ' . $styles[1]; // merge styles
			if ( $this->getMathStyle() === 'display' ) {
				// TODO: Improve style cleaning
				$style = preg_replace(
					'/margin\-(left|right)\:\s*\d+(\%|in|cm|mm|em|ex|pt|pc|px)\;/', '', $style
				);
			}
			$style = trim( preg_replace( '/position:\s*absolute;\s*left:\s*0px;/', '', $style ),
				"; \t\n\r\0\x0B" ) . '; ';

		}
		// TODO: Figure out if there is a way to construct
		// a SVGReader from a string that represents the SVG
		// content
		if ( preg_match( "/height=\"(.*?)\"/", $this->getSvg(), $matches ) ) {
			$style .= 'height: ' . $matches[1] . '; ';
		}
		if ( preg_match( "/width=\"(.*?)\"/", $this->getSvg(), $matches ) ) {
			$style .= 'width: ' . $matches[1] . ';';
		}
	}

	/**
	 * Gets img tag for math image
	 * @param bool $noRender if true no rendering will be performed
	 * if the image is not stored in the database
	 * @param false|string $classOverride if classOverride
	 * is false the class name will be calculated by getClassName
	 * @return string XML the image html tag
	 */
	protected function getFallbackImage( $noRender = false, $classOverride = false ) {
		$attribs = [
			'src' => $this->getFallbackImageUrl( $noRender ),
			'class' => $classOverride === false ? $this->getClassName( true ) : $classOverride,
		];
		if ( !$this->mathoidStyle ) {
			$this->correctSvgStyle( $this->mathoidStyle );
		}

		return Html::element( 'img', $this->getAttributes( 'span', $attribs, [
			'aria-hidden' => 'true',
			'style' => $this->mathoidStyle,
			'alt' => $this->tex
		] ) );
	}

	/**
	 * @return string
	 */
	protected function getMathTableName() {
		return 'mathoid';
	}

	/**
	 * Calculates the default class name for a math element
	 * @param bool $fallback
	 * @return string the class name
	 */
	private function getClassName( $fallback = false ) {
		$class = 'mwe-math-';
		if ( $fallback ) {
			$class .= 'fallback-image-';
		} else {
			$class .= 'mathml-';
		}
		if ( $this->getMathStyle() == 'display' ) {
			$class .= 'display';
		} else {
			$class .= 'inline';
		}
		if ( $fallback ) {
			$class .= ' mw-invert';
		} else {
			$class .= ' mwe-math-mathml-a11y';
		}
		return $class;
	}

	/**
	 * @return string Html output that is embedded in the page
	 */
	public function getHtmlOutput() {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$enableLinks = $config->get( "MathEnableFormulaLinks" );
		if ( $this->getMathStyle() == 'display' ) {
			$element = 'div';
		} else {
			$element = 'span';
		}
		$attribs = [ 'class' => 'mwe-math-element' ];
		if ( $this->getID() !== '' ) {
			$attribs['id'] = $this->getID();
		}
		$hyperlink = null;
		if ( isset( $this->params['qid'] ) && preg_match( '/Q\d+/', $this->params['qid'] ) ) {
			$attribs['data-qid'] = $this->params['qid'];
			$titleObj = SpecialPage::getTitleFor( 'MathWikibase' );
			$hyperlink = $titleObj->getLocalURL( [ 'qid' => $this->params['qid'] ] );
		}
		$output = Html::openElement( $element, $attribs );
		if ( $hyperlink && $enableLinks ) {
			$output .= Html::openElement( 'a', [ 'href' => $hyperlink, 'style' => 'color:inherit;' ] );
		}
		// MathML has to be wrapped into a div or span in order to be able to hide it.
		// Remove displayStyle attributes set by the MathML converter
		// (Beginning from Mathoid 0.2.5 block is the default layout.)
		$mml = preg_replace(
			'/(<math[^>]*)(display|mode)=["\'](inline|block)["\']/', '$1', $this->getMathml()
		);
		if ( $this->getMathStyle() == 'display' ) {
			$mml = preg_replace( '/<math/', '<math display="block"', $mml );
		}
		$output .= Xml::tags( $element, [
			'class' => $this->getClassName(), 'style' => 'display: none;'
		], $mml );
		$output .= $this->getFallbackImage();
		if ( $hyperlink && $enableLinks ) {
			$output .= Html::closeElement( 'a' );
		}
		$output .= Html::closeElement( $element );
		return $output;
	}

	protected function dbOutArray() {
		$out = parent::dbOutArray();
		if ( $this->getMathTableName() === 'mathoid' ) {
			$out['math_input'] = $out['math_inputtex'];
			unset( $out['math_inputtex'] );
		}
		return $out;
	}

	protected function dbInArray() {
		$out = parent::dbInArray();
		if ( $this->getMathTableName() === 'mathoid' ) {
			$out = array_diff( $out, [ 'math_inputtex' ] );
			$out[] = 'math_input';
		}
		return $out;
	}

	public function initializeFromCache( $rpage ) {
		// mathoid allows different input formats
		// therefore the column name math_inputtex was changed to math_input
		if ( $this->getMathTableName() === 'mathoid' && isset( $rpage['math_input'] ) ) {
			$this->userInputTex = $rpage['math_input'];
		}
		parent::initializeFromCache( $rpage );
	}

	/**
	 * @param stdClass $jsonResult
	 * @param string $host name
	 *
	 * @return StatusValue
	 */
	protected function processJsonResult( $jsonResult, $host ): StatusValue {
		if ( $this->getMode() == MathConfig::MODE_LATEXML || $this->inputType == 'pmml' ||
			 $this->isValidMathML( $jsonResult->mml )
		) {
			if ( isset( $jsonResult->svg ) ) {
				$xmlObject = new XmlTypeCheck( $jsonResult->svg, null, false );
				if ( !$xmlObject->wellFormed ) {
					return StatusValue::newFatal( 'math_invalidxml', $host );
				} else {
					$this->setSvg( $jsonResult->svg );
				}
			} else {
				$this->logger->error( 'Missing SVG property in JSON result.' );
			}
			if ( $this->getMode() != MathConfig::MODE_LATEXML && $this->inputType != 'pmml' ) {
				$this->setMathml( $jsonResult->mml );
			}
			// Avoid PHP 7.1 warning from passing $this by reference
			$renderer = $this;
			( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) )->onMathRenderingResultRetrieved(
				$renderer, $jsonResult
			); // Enables debugging of server results
			return StatusValue::newGood(); // FIXME: empty?
		} else {
			return StatusValue::newFatal( 'math_unknown_error', $host );
		}
	}

	/**
	 * @return bool
	 */
	protected function isEmpty() {
		return $this->userInputTex === '';
	}
}
