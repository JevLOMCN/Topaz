<?php

namespace MediaWiki\Extension\AbuseFilter\Variables;

use LogicException;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\AFPData;

/**
 * Service that allows manipulating a VariableHolder
 */
class VariablesManager {
	public const SERVICE_NAME = 'AbuseFilterVariablesManager';
	/**
	 * Used in self::getVar() to determine what to do if the requested variable is missing. See
	 * the docs of that method for an explanation.
	 */
	public const GET_LAX = 0;
	public const GET_STRICT = 1;
	public const GET_BC = 2;

	/** @var KeywordsManager */
	private $keywordsManager;
	/** @var LazyVariableComputer */
	private $lazyComputer;

	/**
	 * @param KeywordsManager $keywordsManager
	 * @param LazyVariableComputer $lazyComputer
	 */
	public function __construct(
		KeywordsManager $keywordsManager,
		LazyVariableComputer $lazyComputer
	) {
		$this->keywordsManager = $keywordsManager;
		$this->lazyComputer = $lazyComputer;
	}

	/**
	 * Checks whether any deprecated variable is stored with the old name, and replaces it with
	 * the new name. This should normally only happen when a DB dump is retrieved from the DB.
	 *
	 * @param VariableHolder $holder
	 */
	public function translateDeprecatedVars( VariableHolder $holder ): void {
		$deprecatedVars = $this->keywordsManager->getDeprecatedVariables();
		foreach ( $holder->getVars() as $name => $value ) {
			if ( array_key_exists( $name, $deprecatedVars ) ) {
				$holder->setVar( $deprecatedVars[$name], $value );
				$holder->removeVar( $name );
			}
		}
	}

	/**
	 * Get a variable from the current object
	 *
	 * @param VariableHolder $holder
	 * @param string $varName The variable name
	 * @param int $mode One of the self::GET_* constants, determines how to behave when the variable is unset:
	 *  - GET_STRICT -> In the future, this will throw an exception. For now it returns a DUNDEFINED and logs a warning
	 *  - GET_LAX -> Return a DUNDEFINED AFPData
	 *  - GET_BC -> Return a DNULL AFPData (this should only be used for BC, see T230256)
	 * @return AFPData
	 */
	public function getVar(
		VariableHolder $holder,
		string $varName,
		$mode = self::GET_STRICT
	): AFPData {
		$varName = strtolower( $varName );
		if ( $holder->varIsSet( $varName ) ) {
			/** @var $variable LazyLoadedVariable|AFPData */
			$variable = $holder->getVarThrow( $varName );
			if ( $variable instanceof LazyLoadedVariable ) {
				$getVarCB = function ( string $varName ) use ( $holder ): AFPData {
					return $this->getVar( $holder, $varName );
				};
				$value = $this->lazyComputer->compute( $variable, $holder, $getVarCB );
				$holder->setVar( $varName, $value );
				return $value;
			} elseif ( $variable instanceof AFPData ) {
				return $variable;
			} else {
				// @codeCoverageIgnoreStart
				throw new \UnexpectedValueException(
					"Variable $varName has unexpected type " . gettype( $variable )
				);
				// @codeCoverageIgnoreEnd
			}
		}

		// The variable is not set.
		switch ( $mode ) {
			case self::GET_STRICT:
				throw new UnsetVariableException( $varName );
			case self::GET_LAX:
				return new AFPData( AFPData::DUNDEFINED );
			case self::GET_BC:
				// Old behaviour, which can sometimes lead to unexpected results (e.g.
				// `edit_delta < -5000` will match any non-edit action).
				return new AFPData( AFPData::DNULL );
			default:
				// @codeCoverageIgnoreStart
				throw new LogicException( "Mode '$mode' not recognized." );
				// @codeCoverageIgnoreEnd
		}
	}

	/**
	 * Dump all variables stored in the holder in their native types.
	 * If you want a not yet set variable to be included in the results you can
	 * either set $compute to an array with the name of the variable or set
	 * $compute to true to compute all not yet set variables.
	 *
	 * @param VariableHolder $holder
	 * @param array|bool $compute Variables we should compute if not yet set
	 * @param bool $includeUserVars Include user set variables
	 * @return array
	 */
	public function dumpAllVars(
		VariableHolder $holder,
		$compute = [],
		bool $includeUserVars = false
	): array {
		$coreVariables = [];

		if ( !$includeUserVars ) {
			// Compile a list of all variables set by the extension to be able
			// to filter user set ones by name
			$activeVariables = array_keys( $this->keywordsManager->getVarsMappings() );
			$deprecatedVariables = array_keys( $this->keywordsManager->getDeprecatedVariables() );
			$disabledVariables = array_keys( $this->keywordsManager->getDisabledVariables() );
			$coreVariables = array_merge( $activeVariables, $deprecatedVariables, $disabledVariables );
			$coreVariables = array_map( 'strtolower', $coreVariables );
		}

		$exported = [];
		foreach ( array_keys( $holder->getVars() ) as $varName ) {
			$computeThis = ( is_array( $compute ) && in_array( $varName, $compute ) ) || $compute === true;
			if (
				( $includeUserVars || in_array( strtolower( $varName ), $coreVariables ) ) &&
				// Only include variables set in the extension in case $includeUserVars is false
				( $computeThis || $holder->getVarThrow( $varName ) instanceof AFPData )
			) {
				$exported[$varName] = $this->getVar( $holder, $varName )->toNative();
			}
		}

		return $exported;
	}

	/**
	 * Compute all vars which need DB access. Useful for vars which are going to be saved
	 * cross-wiki or used for offline analysis.
	 *
	 * @param VariableHolder $holder
	 */
	public function computeDBVars( VariableHolder $holder ): void {
		static $dbTypes = [
			'links-from-database',
			'links-from-update',
			'links-from-wikitext-or-database',
			'load-recent-authors',
			'page-age',
			'get-page-restrictions',
			'user-editcount',
			'user-emailconfirm',
			'user-groups',
			'user-rights',
			'user-age',
			'user-block',
			'revision-text-by-id',
			'content-model-by-id',
		];

		/** @var LazyLoadedVariable[] $missingVars */
		$missingVars = array_filter( $holder->getVars(), static function ( $el ) {
			return ( $el instanceof LazyLoadedVariable );
		} );
		foreach ( $missingVars as $name => $var ) {
			if ( in_array( $var->getMethod(), $dbTypes ) ) {
				$holder->setVar( $name, $this->getVar( $holder, $name ) );
			}
		}
	}

	/**
	 * Export all variables stored in this object with their native (PHP) types.
	 *
	 * @param VariableHolder $holder
	 * @return array
	 */
	public function exportAllVars( VariableHolder $holder ): array {
		$exported = [];
		foreach ( array_keys( $holder->getVars() ) as $varName ) {
			$exported[ $varName ] = $this->getVar( $holder, $varName )->toNative();
		}

		return $exported;
	}

	/**
	 * Export all non-lazy variables stored in this object as string
	 *
	 * @param VariableHolder $holder
	 * @return string[]
	 */
	public function exportNonLazyVars( VariableHolder $holder ): array {
		$exported = [];
		foreach ( $holder->getVars() as $varName => $data ) {
			if ( !( $data instanceof LazyLoadedVariable ) ) {
				$exported[$varName] = $holder->getComputedVariable( $varName )->toString();
			}
		}

		return $exported;
	}
}
