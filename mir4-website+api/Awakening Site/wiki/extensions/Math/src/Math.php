<?php

namespace MediaWiki\Extension\Math;

use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;

/**
 * Top level factory for the Math extension.
 *
 * @license GPL-2.0-or-later
 */
final class Math {

	/**
	 * @codeCoverageIgnore
	 */
	private function __construct() {
		// should not be instantiated
	}

	public static function getMathConfig( ContainerInterface $services = null ): MathConfig {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'Math.Config' );
	}

	public static function getCheckerFactory( ContainerInterface $services = null ): InputCheckFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'Math.CheckerFactory' );
	}
}
