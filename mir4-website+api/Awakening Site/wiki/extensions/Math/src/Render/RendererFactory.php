<?php

namespace MediaWiki\Extension\Math\Render;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\MathLaTeXML;
use MediaWiki\Extension\Math\MathMathML;
use MediaWiki\Extension\Math\MathMathMLCli;
use MediaWiki\Extension\Math\MathNativeMML;
use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Extension\Math\MathSource;
use MediaWiki\User\Options\UserOptionsLookup;
use Psr\Log\LoggerInterface;
use WANObjectCache;

class RendererFactory {

	/** @var string[] */
	public const CONSTRUCTOR_OPTIONS = [
		'MathoidCli',
		'MathEnableExperimentalInputFormats',
		'MathValidModes',
	];

	/** @var ServiceOptions */
	private $options;

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	/** @var MathConfig */
	private $mathConfig;

	/** @var LoggerInterface */
	private $logger;

	private WANObjectCache $cache;

	/**
	 * @param ServiceOptions $serviceOptions
	 * @param MathConfig $mathConfig
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param LoggerInterface $logger
	 * @param WANObjectCache $cache
	 */
	public function __construct(
		ServiceOptions $serviceOptions,
		MathConfig $mathConfig,
		UserOptionsLookup $userOptionsLookup,
		LoggerInterface $logger,
		WANObjectCache $cache
	) {
		$serviceOptions->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $serviceOptions;
		$this->mathConfig = $mathConfig;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->logger = $logger;
		$this->cache = $cache;
	}

	/**
	 * Factory method for getting a renderer based on mode
	 *
	 * @param string $tex LaTeX markup
	 * @param array $params HTML attributes
	 * @param string $mode indicating rendering mode, one of MathConfig::MODE_*
	 * @return MathRenderer appropriate renderer for mode
	 */
	public function getRenderer(
		string $tex,
		array $params = [],
		string $mode = MathConfig::MODE_MATHML
	): MathRenderer {
		if ( isset( $params['forcemathmode'] ) ) {
			$mode = $params['forcemathmode'];
		}
		if ( !in_array( $mode, $this->mathConfig->getValidRenderingModes(), true ) ) {
			$mode = $this->userOptionsLookup->getDefaultOption( 'math' );
		}
		if ( $this->options->get( 'MathEnableExperimentalInputFormats' ) === true &&
			$mode == MathConfig::MODE_MATHML &&
			isset( $params['type'] )
		) {
			// Support of MathML input (experimental)
			// Currently support for mode 'mathml' only
			if ( !in_array( $params['type'], [ 'pmml', 'ascii' ], true ) ) {
				unset( $params['type'] );
			}
		}
		if ( isset( $params['chem'] ) ) {
			$mode = ( $mode == MathConfig::MODE_NATIVE_MML ) ? MathConfig::MODE_NATIVE_MML : MathConfig::MODE_MATHML;
			$params['type'] = 'chem';
		}
		switch ( $mode ) {
			case MathConfig::MODE_SOURCE:
				$renderer = new MathSource( $tex, $params );
				break;
			case MathConfig::MODE_NATIVE_MML:
				$renderer = new MathNativeMML( $tex, $params, $this->cache );
				break;
			case MathConfig::MODE_LATEXML:
				$renderer = new MathLaTeXML( $tex, $params, $this->cache );
				break;
			case MathConfig::MODE_MATHML:
			default:
				if ( $this->options->get( 'MathoidCli' ) ) {
					$renderer = new MathMathMLCli( $tex, $params, $this->cache );
				} else {
					$renderer = new MathMathML( $tex, $params, $this->cache );
				}
		}
		$this->logger->debug(
			'Start rendering "{tex}" in mode {mode}',
			[
				'tex' => $tex,
				'mode' => $mode
			]
		);
		return $renderer;
	}

	public function getFromHash( $hash ) {
		$rpage = $this->cache->get( $hash );
		if ( $rpage === false ) {
			throw new InvalidArgumentException( 'Cache key is invalid' );
		}
		$mode = $rpage['math_mode'];
		$renderer = $this->getRenderer( '', [], $mode );
		$renderer->initializeFromCache( $rpage );
		return $renderer;
	}
}
