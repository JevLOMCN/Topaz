<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

class HashLibrary extends LibraryBase {

	public function register() {
		$lib = [
			'listAlgorithms' => [ $this, 'listAlgorithms' ],
			'hashValue' => [ $this, 'hashValue' ],
		];

		return $this->getEngine()->registerInterface( 'mw.hash.lua', $lib );
	}

	/**
	 * Returns a list of known/ supported hash algorithms
	 *
	 * @internal
	 * @return string[][]
	 */
	public function listAlgorithms() {
		$algos = hash_algos();
		$algos = array_combine( range( 1, count( $algos ) ), $algos );

		return [ $algos ];
	}

	/**
	 * Hash a given value.
	 *
	 * @internal
	 * @param string $algo
	 * @param string $value
	 * @return string[]
	 */
	public function hashValue( $algo, $value ) {
		if ( !in_array( $algo, hash_algos() ) ) {
			throw new LuaError( "Unknown hashing algorithm: $algo" );
		}

		$hash = hash( $algo, $value );

		return [ $hash ];
	}

}
