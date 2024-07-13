<?php

namespace MediaWiki\Extension\Math\InputCheck;

use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\MediaWikiServices;
use Message;
use stdClass;

/**
 * MediaWiki math extension
 *
 * @copyright 2002-2014 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz,
 * and other MediaWiki contributors
 * @license GPL-2.0-or-later
 * @author Moritz Schubotz
 */
abstract class BaseChecker {
	protected string $inputTeX;
	protected ?string $validTeX = null;
	/** @var bool */
	protected $isValid = false;
	protected bool $purge = false;

	/**
	 * @param string $tex the TeX InputString to be checked
	 * @param bool $purge if true, the cache will be purged
	 */
	public function __construct( $tex = '', bool $purge = false ) {
		$this->inputTeX = $tex;
		$this->purge = $purge;
		$this->isValid = false;
	}

	/**
	 * Returns true if the TeX input String is valid
	 * @return bool
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * Returns the string of the last error.
	 * @return ?Message
	 */
	abstract public function getError(): ?Message;

	/**
	 * Some TeX checking programs may return
	 * a modified tex string after having checked it.
	 * You can get the altered tex string with this method
	 * @return ?string A valid Tex string
	 */
	public function getValidTex(): ?string {
		return $this->validTeX;
	}

	public function setPurge( bool $purge ) {
		$this->purge = $purge;
	}

	/**
	 * @see https://phabricator.wikimedia.org/T119300
	 * @param stdClass $e
	 * @param string $host
	 * @return Message
	 */
	protected function errorObjectToMessage( stdClass $e, $host = 'invalid' ): Message {
		if ( isset( $e->error->message ) ) {
			if ( $e->error->message === 'Illegal TeX function' ) {
				return Message::newFromKey( 'math_unknown_function', $e->error->found );
			} elseif ( preg_match( '/Math extension/', $e->error->message ) ) {
				// TODO: inject once checker is refactored more
				$mode = MediaWikiServices::getInstance()
					->get( 'Math.Config' )
					->getRenderingModeName( MathConfig::MODE_MATHML );
				$msg = $e->error->message;
				return Message::newFromKey( 'math_invalidresponse', $mode, $host, $msg );
			}

			return Message::newFromKey( 'math_syntax_error' );
		}

		return Message::newFromKey( 'math_unknown_error' );
	}
}
