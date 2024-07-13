<?php

namespace MediaWiki\Extension\AbuseFilter\Variables;

use MediaWiki\Extension\AbuseFilter\Parser\AFPData;

/**
 * Mutable value object that holds a list of variables
 */
class VariableHolder {
	/**
	 * @var (AFPData|LazyLoadedVariable)[]
	 */
	private $mVars = [];

	/**
	 * Utility function to translate an array with shape [ varname => value ] into a self instance
	 *
	 * @param array $vars
	 * @return VariableHolder
	 */
	public static function newFromArray( array $vars ): VariableHolder {
		$ret = new self();
		foreach ( $vars as $var => $value ) {
			$ret->setVar( $var, $value );
		}
		return $ret;
	}

	/**
	 * @param string $variable
	 * @param mixed $datum
	 */
	public function setVar( string $variable, $datum ): void {
		$variable = strtolower( $variable );
		if ( !( $datum instanceof AFPData || $datum instanceof LazyLoadedVariable ) ) {
			$datum = AFPData::newFromPHPVar( $datum );
		}

		$this->mVars[$variable] = $datum;
	}

	/**
	 * Get all variables stored in this object
	 *
	 * @return (AFPData|LazyLoadedVariable)[]
	 */
	public function getVars(): array {
		return $this->mVars;
	}

	/**
	 * @param string $variable
	 * @param string $method
	 * @param array $parameters
	 */
	public function setLazyLoadVar( string $variable, string $method, array $parameters ): void {
		$placeholder = new LazyLoadedVariable( $method, $parameters );
		$this->setVar( $variable, $placeholder );
	}

	/**
	 * Get a variable from the current object, or throw if not set
	 *
	 * @param string $varName The variable name
	 * @return AFPData|LazyLoadedVariable
	 */
	public function getVarThrow( string $varName ) {
		$varName = strtolower( $varName );
		if ( !$this->varIsSet( $varName ) ) {
			throw new UnsetVariableException( $varName );
		}
		return $this->mVars[$varName];
	}

	/**
	 * A stronger version of self::getVarThrow that also asserts that the variable was computed
	 * @param string $varName
	 * @return AFPData
	 * @codeCoverageIgnore
	 */
	public function getComputedVariable( string $varName ): AFPData {
		return $this->getVarThrow( $varName );
	}

	/**
	 * Merge any number of holders given as arguments into this holder.
	 *
	 * @param VariableHolder ...$holders
	 */
	public function addHolders( VariableHolder ...$holders ): void {
		foreach ( $holders as $addHolder ) {
			$this->mVars = array_merge( $this->mVars, $addHolder->mVars );
		}
	}

	/**
	 * @param string $var
	 * @return bool
	 */
	public function varIsSet( string $var ): bool {
		return array_key_exists( $var, $this->mVars );
	}

	/**
	 * @param string $varName
	 */
	public function removeVar( string $varName ): void {
		unset( $this->mVars[$varName] );
	}
}

// @deprecated Since 1.36. Kept for BC with the UpdateVarDumps script, see T331861. The alias can be removed
// once we no longer support updating from a MW version where that script may run.
class_alias( VariableHolder::class, 'AbuseFilterVariableHolder' );
