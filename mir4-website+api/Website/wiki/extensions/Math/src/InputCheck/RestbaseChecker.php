<?php

namespace MediaWiki\Extension\Math\InputCheck;

use Exception;
use MediaWiki\Extension\Math\MathRestbaseInterface;
use Message;

/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz,
 * and other MediaWiki contributors
 *
 * @note The restbase checker does not support purging the cache as it just represents
 * the state of the restbase interface.
 *
 * @license GPL-2.0-or-later
 * @author Moritz Schubotz
 */
class RestbaseChecker extends BaseChecker {
	/** @var MathRestbaseInterface */
	private $restbaseInterface;

	/**
	 * @param string $tex the TeX input string to be checked
	 * @param string $type
	 * @param MathRestbaseInterface|null &$ref
	 */
	public function __construct( $tex = '', $type = 'tex', &$ref = null ) {
		parent::__construct( $tex );
		if ( $ref ) {
			$this->restbaseInterface = $ref;
		} else {
			$this->restbaseInterface = new MathRestbaseInterface( $tex, $type );
			$ref = $this->restbaseInterface;
		}
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return $this->restbaseInterface->getSuccess();
	}

	/**
	 * Some TeX checking programs may return
	 * a modified tex string after having checked it.
	 * You can get the altered tex string with this method
	 * @return ?string A valid Tex string
	 */
	public function getValidTex(): ?string {
		return $this->restbaseInterface->getCheckedTex();
	}

	/**
	 * Returns the string of the last error.
	 * @return ?Message
	 */
	public function getError(): ?Message {
		$err = $this->restbaseInterface->getError();
		if ( $err === null ) {
			return null;
		}
		try {
			$host = $this->restbaseInterface->getUrl( '' );
		} catch ( Exception $ignore ) {
			$host = 'invalid';
		}
		return $this->errorObjectToMessage( $err, $host );
	}

	public function getRbi() {
		return $this->restbaseInterface;
	}

}
