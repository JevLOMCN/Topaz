<?php

namespace MediaWiki\Extension\Math\InputCheck;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\MathRestbaseInterface;
use MediaWiki\Http\HttpRequestFactory;
use Psr\Log\LoggerInterface;
use WANObjectCache;

class InputCheckFactory {

	public const CONSTRUCTOR_OPTIONS = [
		'MathMathMLUrl',
		'MathLaTeXMLTimeout',
		'MathTexVCService'
	];
	/** @var string */
	private $url;
	/** @var int */
	private $timeout;
	/** @var WANObjectCache */
	private $cache;
	/** @var HttpRequestFactory */
	private $httpFactory;
	/** @var LoggerInterface */
	private $logger;
	/** @var string */
	private $texVCmode;

	/**
	 * @param ServiceOptions $options
	 * @param WANObjectCache $cache
	 * @param HttpRequestFactory $httpFactory
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		ServiceOptions $options,
		WANObjectCache $cache,
		HttpRequestFactory $httpFactory,
		LoggerInterface $logger
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->url = $options->get( 'MathMathMLUrl' );
		$this->timeout = $options->get( 'MathLaTeXMLTimeout' );
		$this->texVCmode = $options->get( 'MathTexVCService' );
		$this->cache = $cache;
		$this->httpFactory = $httpFactory;
		$this->logger = $logger;
	}

	/**
	 * @param string $input input string to be checked
	 * @param string $type type of input
	 * @param bool $purge whether to purge the cache
	 * @return MathoidChecker checker based on mathoid
	 */
	public function newMathoidChecker( string $input, string $type, bool $purge ): MathoidChecker {
		return new MathoidChecker(
			$this->cache,
			$this->httpFactory,
			$this->logger,
			$this->url,
			$this->timeout,
			$input,
			$type,
			$purge
		);
	}

	/**
	 * @param string $input input string to be checked
	 * @param string $type type of input
	 * @param MathRestbaseInterface|null &$restbaseInterface restbase interface which is used for remote communication
	 * @return RestbaseChecker checker based on communication with restbase interface
	 */
	public function newRestbaseChecker( string $input, string $type,
										MathRestbaseInterface &$restbaseInterface = null ): RestbaseChecker {
		return new RestbaseChecker(
			$input,
			$type,
			$restbaseInterface
		);
	}

	/**
	 * @param string $input input string to be checked
	 * @param string $type type of input (only 'tex')
	 * @param bool $purge whether to purge the cache
	 * @return LocalChecker checker based on php implementation of WikiTexVC within Math-extension
	 */
	public function newLocalChecker( string $input, string $type, bool $purge = false ): LocalChecker {
		return new LocalChecker(
			$this->cache,
			$input,
			$type,
			$purge
		);
	}

	/**
	 * Creates an instance of BaseChecker based on the configuration parameter for the texVC Service.
	 * By default, this sets the checker to the local PHP variant of WikiTexVC.
	 *
	 * @param string $input input string which is checked
	 * @param string $type input type, for some configurations this has to be 'tex'
	 * @param MathRestbaseInterface|null &$restbaseInterface restbase interface,
	 *         only necessary when using 'restbase' configuration
	 * @param bool $purge whether to purge the cache
	 * @return BaseChecker a checker object which has the results of the check.
	 */
	public function newDefaultChecker( string $input,
									   string $type,
									   MathRestbaseInterface &$restbaseInterface = null,
									   bool $purge = false ): BaseChecker {
		switch ( $this->texVCmode ) {
			case "mathoid":
				return $this->newMathoidChecker( $input, $type, $purge );
			case "local":
				return $this->newLocalChecker( $input, $type, $purge );
			case "restbase":
			default:
				return $this->newRestbaseChecker( $input, $type, $restbaseInterface );
		}
	}
}
